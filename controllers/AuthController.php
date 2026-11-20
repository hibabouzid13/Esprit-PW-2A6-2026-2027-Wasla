<?php
/**
 * controllers/AuthController.php
 * Authentification, sessions, rôles et CSRF (workshop « Sessions et Sécurité »).
 *
 * Partagé par les 4 modules : chaque module protège ses actions avec
 *   AuthController::requireAuth();                          // être connecté
 *   AuthController::requireRole(['admin']);                 // avoir un des rôles
 *   AuthController::requireRole(AuthController::BACKOFFICE_ROLES);
 *
 * La gestion des comptes propres à chaque module (citoyens, sous-admins,
 * entreprises, fournisseurs…) se fait dans le contrôleur du module, pas ici.
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/ZoneController.php';

class AuthController extends Controller
{
    /** Rôles qui ont accès au Back Office. */
    public const BACKOFFICE_ROLES = ['admin', 'steg', 'sonede'];

    /** Anti force brute : nombre d'essais et durée du blocage (secondes). */
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCK_SECONDS = 60;

    /** Utilisateur connecté, chargé une seule fois par requête. */
    private static ?UserModel $current = null;
    private static bool $loaded = false;

    // ==============================================================
    // ÉTAT DE LA SESSION
    // ==============================================================

    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Utilisateur connecté, relu en base à CHAQUE requête : si l'admin change son rôle,
     * le suspend ou le supprime, c'est pris en compte immédiatement (pas seulement à la connexion).
     */
    public static function currentUser(): ?UserModel
    {
        if (!self::$loaded) {
            self::$loaded = true;
            if (self::isAuthenticated()) {
                $user = (new self())->findById((int) $_SESSION['user_id']);
                if ($user === null || $user->getStatutCompte() !== 'actif') {
                    (new self())->logout();   // compte supprimé ou suspendu entre-temps
                    session_start();
                } else {
                    $_SESSION['role'] = $user->getRole(); // rôle toujours à jour
                    self::$current = $user;
                }
            }
        }
        return self::$current;
    }

    public static function isBackOffice(?UserModel $user = null): bool
    {
        $user ??= self::currentUser();
        return $user !== null && in_array($user->getRole(), self::BACKOFFICE_ROLES, true);
    }

    // ==============================================================
    // CONTRÔLE D'ACCÈS
    // ==============================================================

    /** Arrête la page et renvoie vers la connexion si personne n'est connecté. */
    public static function requireAuth(): void
    {
        if (self::currentUser() === null) {
            self::flash('info', 'Veuillez vous connecter pour accéder à cette page.');
            header('Location: ' . url('connexion'));
            exit;
        }
    }

    /** @param string[] $roles */
    public static function hasRole(array $roles): bool
    {
        $user = self::currentUser();
        return $user !== null && in_array($user->getRole(), $roles, true);
    }

    /**
     * À appeler au début de CHAQUE action sensible (jamais seulement à la connexion).
     * Pas connecté → page de connexion ; connecté sans le bon rôle → erreur 403.
     *
     * @param string[] $roles
     */
    public static function requireRole(array $roles): void
    {
        self::requireAuth();
        if (!self::hasRole($roles)) {
            self::forbidden();
        }
    }

    /** Page « Accès refusé » (HTTP 403). */
    public static function forbidden(string $message = "Vous n'avez pas les droits nécessaires pour accéder à cette page."): void
    {
        http_response_code(403);
        $errorCode = 403;
        $errorTitle = 'Accès refusé';
        $errorMessage = $message;
        require __DIR__ . '/../views/frontoffice/pages/erreurs/erreur.php';
        exit;
    }

    // ==============================================================
    // CSRF
    // ==============================================================

    public static function getCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool
    {
        if (!$token || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // ==============================================================
    // MESSAGES FLASH (affichés une seule fois, après une redirection)
    // ==============================================================

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /** @return array<int, array{type: string, message: string}> */
    public static function takeFlashes(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }

    // ==============================================================
    // ACCÈS AUX DONNÉES (requêtes préparées — protection injection SQL)
    // ==============================================================

    public function findById(int $id): ?UserModel
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, z.nom_zone FROM utilisateur u
             LEFT JOIN zone z ON z.id_zone = u.id_zone
             WHERE u.id_utilisateur = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?UserModel
    {
        $stmt = $this->db->prepare('SELECT * FROM utilisateur WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    private function insertUser(UserModel $user): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, telephone, mot_de_passe, role, statut_compte, id_zone)
             VALUES (:nom, :prenom, :email, :telephone, :mot_de_passe, :role, :statut, :id_zone)'
        );
        $stmt->execute([
            'nom'          => $user->getNom(),
            'prenom'       => $user->getPrenom(),
            'email'        => $user->getEmail(),
            'telephone'    => $user->getTelephone(),
            'mot_de_passe' => $user->getMotDePasse(),
            'role'         => $user->getRole(),
            'statut'       => $user->getStatutCompte(),
            'id_zone'      => $user->getIdZone(),
        ]);
        return (int) $this->db->lastInsertId();
    }

    // ==============================================================
    // INSCRIPTION
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs par champ (vide = succès)
     */
    public function register(array $post): array
    {
        // Workshop 7.4 : le jeton CSRF est vérifié au tout début de register()
        // (index.php le vérifie aussi pour TOUS les formulaires POST : double protection).
        if (!self::verifyCsrfToken($post['csrf_token'] ?? null)) {
            return ['form' => 'Jeton de sécurité invalide ou expiré. Veuillez réessayer.'];
        }

        $errors = $this->validate($post, [
            'prenom'           => 'required|name',
            'nom'              => 'required|name',
            'email'            => 'required|email',
            'telephone'        => 'phone',
            'id_zone'          => 'required|int',
            'password'         => 'required|password',
            'password_confirm' => 'required|match:password',
            'terms'            => 'accepted',
        ]);

        $idZone = (int) ($post['id_zone'] ?? 0);
        if (!isset($errors['id_zone']) && !(new ZoneController())->exists($idZone)) {
            $errors['id_zone'] = 'Veuillez choisir votre gouvernorat.';
        }
        if (isset($errors['password_confirm']) && $errors['password_confirm'] === 'Les deux valeurs ne correspondent pas.') {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }

        $email = mb_strtolower(trim((string) ($post['email'] ?? '')));
        if (!isset($errors['email']) && $this->findByEmail($email) !== null) {
            $errors['email'] = 'Un compte existe déjà avec cette adresse e-mail.';
        }
        if ($errors !== []) {
            return $errors;
        }

        $telephone = preg_replace('/[\s.-]/', '', trim((string) ($post['telephone'] ?? ''))) ?: null;

        $user = new UserModel();
        $user->setNom(trim((string) $post['nom']));
        $user->setPrenom(trim((string) $post['prenom']));
        $user->setEmail($email);
        $user->setTelephone($telephone);
        $user->setMotDePasse(password_hash((string) $post['password'], PASSWORD_DEFAULT)); // bcrypt + sel automatique
        $user->setRole('citoyen');      // rôle FORCÉ : un visiteur ne peut jamais se créer un compte admin
        $user->setStatutCompte('actif');
        $user->setIdZone($idZone);

        $this->insertUser($user);
        return [];
    }

    // ==============================================================
    // CONNEXION / DÉCONNEXION
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = succès)
     */
    public function login(array $post): array
    {
        // Workshop 7.4 : vérification du jeton CSRF au tout début de login()
        if (!self::verifyCsrfToken($post['csrf_token'] ?? null)) {
            return ['form' => 'Jeton de sécurité invalide ou expiré. Veuillez réessayer.'];
        }

        // Anti force brute : blocage temporaire après plusieurs échecs
        $lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            return ['form' => 'Trop de tentatives. Réessayez dans ' . ($lockedUntil - time()) . ' secondes.'];
        }

        $errors = $this->validate($post, ['email' => 'required|email', 'password' => 'required']);
        if ($errors !== []) {
            return $errors;
        }

        $user = $this->findByEmail((string) $post['email']);

        // Message volontairement générique : on ne révèle jamais si l'e-mail existe
        if ($user === null || !password_verify((string) $post['password'], $user->getMotDePasse())) {
            $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= self::MAX_LOGIN_ATTEMPTS) {
                $_SESSION['login_locked_until'] = time() + self::LOCK_SECONDS;
                $_SESSION['login_attempts'] = 0;
            }
            return ['form' => 'Adresse e-mail ou mot de passe incorrect.'];
        }

        if ($user->getStatutCompte() !== 'actif') {
            return ['form' => "Votre compte est suspendu. Contactez l'administration de WASLA."];
        }

        // Nouvel identifiant de session : protection contre la fixation de session
        session_regenerate_id(true);
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
        $_SESSION['user_id'] = $user->getIdUtilisateur();
        $_SESSION['prenom'] = $user->getPrenom();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['login_time'] = time();        // début de la session authentifiée
        $_SESSION['last_activity'] = time();

        $stmt = $this->db->prepare('UPDATE utilisateur SET derniere_connexion = NOW() WHERE id_utilisateur = :id');
        $stmt->execute(['id' => $user->getIdUtilisateur()]);

        self::$loaded = false; // currentUser() relira le compte
        return [];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        self::$current = null;
        self::$loaded = true;
    }

    /** Espace d'arrivée selon le rôle : Back Office pour admin/STEG/SONEDE, espace personnel sinon. */
    public static function homeFor(UserModel $user): string
    {
        return self::isBackOffice($user) ? 'dashboard' : 'mon-compte';
    }

    // ==============================================================
    // DONNÉES DU TABLEAU DE BORD (Back Office)
    // ==============================================================

    public function countAllUsers(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM utilisateur')->fetchColumn();
    }

    /** @return array<string, int> rôle => nombre de comptes (tous les rôles, même à 0) */
    public function countByRole(): array
    {
        $counts = array_fill_keys(UserModel::ROLES, 0);
        foreach ($this->db->query('SELECT role, COUNT(*) AS n FROM utilisateur GROUP BY role') as $row) {
            $counts[$row['role']] = (int) $row['n'];
        }
        return $counts;
    }

    /** @return UserModel[] tous les comptes (tableau du Back Office, comme admin_space du workshop) */
    public function getAllUsers(): array
    {
        $stmt = $this->db->query(
            'SELECT u.*, z.nom_zone FROM utilisateur u
             LEFT JOIN zone z ON z.id_zone = u.id_zone
             ORDER BY u.date_inscription DESC, u.id_utilisateur DESC'
        );
        return array_map(fn (array $row) => UserModel::fromArray($row), $stmt->fetchAll());
    }
}
