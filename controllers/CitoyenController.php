<?php
/**
 * controllers/CitoyenController.php — Module 2 (Hiba)
 * Gestion des comptes CITOYENS (entité commune « utilisateur », rôle « citoyen ») :
 *  - super admin : liste, fiche, ajout, modification, suspension, suppression ;
 *  - citoyen : modification de SON profil et de SON mot de passe.
 *
 * Sécurité appliquée ici :
 *  - chaque requête garde « role = 'citoyen' » : changer l'identifiant dans l'adresse ne permet
 *    jamais de toucher un admin, un sous-admin, une entreprise…
 *  - le rôle n'est jamais lu dans un formulaire ;
 *  - un compte qui a des données n'est jamais supprimé (on le suspend) ;
 *  - les mots de passe sont hachés ; pour en changer, il faut connaître l'actuel.
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';

class CitoyenController extends Controller
{
    /** Règles du profil : les mêmes en PHP et dans les data-rules des formulaires. */
    public const PROFIL = [
        'prenom'    => 'required|name',
        'nom'       => 'required|name',
        'email'     => 'required|email',
        'telephone' => 'phone',
        'id_zone'   => 'required|int',
        'adresse'   => 'max:255',
    ];

    /** Mot de passe choisi (ajout par l'admin, changement par le citoyen). */
    public const MOT_DE_PASSE = [
        'password'         => 'required|password',
        'password_confirm' => 'required|match:password',
    ];

    /** Suppression de son propre compte : le mot de passe est demandé. */
    public const SUPPRESSION = ['suppression_mot_de_passe' => 'required'];

    /** Changement de mot de passe : l'actuel est demandé en plus. */
    public const CHANGEMENT = ['password_actuel' => 'required'] + self::MOT_DE_PASSE;

    /**
     * Format d'e-mail : le même motif en PHP et en JavaScript (data-motif).
     * La règle commune « email » n'est pas identique des deux côtés (« a..b@exemple.tn » passait en JS seulement).
     */
    public const EMAIL_MOTIF = '^(?=[^@]{1,64}@)[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+(\.[A-Za-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@([A-Za-z0-9]([A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}$';

    private const EMAIL_INVALIDE = 'Veuillez saisir une adresse e-mail valide.';
    private const ESPACE_AU_BORD = 'Le mot de passe ne doit pas commencer ni finir par un espace.';

    /** Messages : source unique pour PHP et pour JavaScript (data-message-*). */
    public const MESSAGES = [
        'prenom'           => ['required' => 'Indiquez le prénom.'],
        'nom'              => ['required' => 'Indiquez le nom.'],
        'email'            => ['required' => "Indiquez l'adresse e-mail."],
        'id_zone'          => ['required' => 'Choisissez le gouvernorat.'],
        'password_actuel'  => ['required' => 'Indiquez le mot de passe actuel.'],
        'email_mot_de_passe' => ['required' => "Indiquez votre mot de passe actuel pour changer d'adresse e-mail."],
        'suppression_mot_de_passe' => ['required' => 'Indiquez votre mot de passe pour supprimer le compte.'],
        'password'         => ['required' => 'Choisissez un mot de passe.'],
        'password_confirm' => ['required' => 'Confirmez le mot de passe.', 'match' => 'Les mots de passe ne correspondent pas.'],
    ];

    /** Tri autorisé : clé reçue dans l'URL → ORDER BY (jamais la valeur de l'utilisateur dans le SQL). */
    public const TRIS = [
        'recents' => ['Plus récents', 'u.date_inscription DESC, u.id_utilisateur DESC'],
        'anciens' => ['Plus anciens', 'u.date_inscription ASC, u.id_utilisateur ASC'],
        'nom'     => ['Nom (A → Z)', 'u.nom ASC, u.prenom ASC, u.id_utilisateur ASC'],
    ];

    /**
     * Toutes les clés étrangères vers « utilisateur » (table.colonne => libellé affiché).
     * Supprimer un compte effacerait ou détacherait ces données, y compris celles des autres modules.
     */
    private const LIENS = [
        'facture.id_utilisateur'               => 'Factures',
        'consommation.id_utilisateur'          => 'Relevés de consommation',
        'signalement.id_utilisateur'           => 'Signalements',
        'confirmation.id_utilisateur'          => 'Confirmations de signalement',
        'lieu_suivi.id_utilisateur'            => 'Lieux suivis',
        'preference_alerte.id_utilisateur'     => "Préférences d'alerte",
        'notification.id_utilisateur'          => 'Notifications',
        'alerte.id_auteur'                     => 'Alertes publiées',
        'interruption.id_auteur'               => 'Interruptions publiées',
        'refuge_zone.id_entreprise'            => 'Zones refuge',
        'reservation_refuge.id_utilisateur'    => 'Réservations de refuge',
        'installation_solaire.id_utilisateur'  => 'Installations solaires',
        'demande_assistance.id_utilisateur'    => "Demandes d'assistance",
        'demande_assistance.id_fournisseur'    => "Demandes d'assistance reçues",
        'reponse.id_utilisateur'               => "Réponses d'entraide",
        'personne_prioritaire.id_utilisateur'  => 'Personnes prioritaires',
    ];

    private const EMAIL_EN_DOUBLE = 'Un compte existe déjà avec cette adresse e-mail.';
    private const INTROUVABLE = 'Citoyen introuvable.';

    /** Mot de passe actuel : mêmes limites que la connexion (5 essais, puis 60 secondes). */
    private const ESSAIS_MAX = 5;
    private const BLOCAGE_SECONDES = 60;

    // ==============================================================
    // LECTURE
    // ==============================================================

    /**
     * Filtres reçus de l'URL, nettoyés : seules les valeurs autorisées sont gardées.
     * @return array{q: string, statut: string, zone: int, tri: string}
     */
    public static function cleanFilters(array $get): array
    {
        $statut = self::texte($get['statut'] ?? '') ?? '';
        $zone = self::texte($get['zone'] ?? '') ?? '';
        $tri = self::texte($get['tri'] ?? '') ?? '';
        return [
            'q'      => mb_substr(trim(self::texte($get['q'] ?? '') ?? ''), 0, 50),
            'statut' => in_array($statut, ['actif', 'suspendu'], true) ? $statut : '',
            'zone'   => preg_match('/^[0-9]{1,9}\z/', $zone) ? (int) $zone : 0,
            'tri'    => array_key_exists($tri, self::TRIS) ? $tri : 'recents',
        ];
    }

    /** @return UserModel[] comptes citoyens, avec recherche, filtres et tri */
    public function search(array $filters): array
    {
        $params = [];
        $sql = "SELECT u.*, z.nom_zone FROM utilisateur u
                LEFT JOIN zone z ON z.id_zone = u.id_zone
                WHERE u.role = 'citoyen'";

        if ($filters['q'] !== '') {
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $sql .= " AND (CONCAT(u.prenom, ' ', u.nom) LIKE :q1 OR CONCAT(u.nom, ' ', u.prenom) LIKE :q2 OR u.email LIKE :q3";
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
            // Les téléphones sont enregistrés sans espaces : on les retire aussi de la recherche
            $chiffres = (string) preg_replace('/[\s.-]/u', '', $filters['q']);
            if (preg_match('/^\+?[0-9]+\z/', $chiffres)) {
                $sql .= ' OR u.telephone LIKE :q4';
                $params['q4'] = '%' . $chiffres . '%';
            }
            $sql .= ')';
        }
        if ($filters['statut'] !== '') {
            $sql .= ' AND u.statut_compte = :statut';
            $params['statut'] = $filters['statut'];
        }
        if ($filters['zone'] > 0) {
            $sql .= ' AND u.id_zone = :zone';
            $params['zone'] = $filters['zone'];
        }
        $sql .= ' ORDER BY ' . self::TRIS[$filters['tri']][1];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(fn (array $row) => UserModel::fromArray($row), $stmt->fetchAll());
    }

    /** Un citoyen (null si l'identifiant est celui d'un autre rôle). */
    public function find(int $id): ?UserModel
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, z.nom_zone FROM utilisateur u
             LEFT JOIN zone z ON z.id_zone = u.id_zone
             WHERE u.id_utilisateur = :id AND u.role = 'citoyen' LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    /** @return array{total: int, actifs: int, suspendus: int, avecFactures: int} */
    public function summary(): array
    {
        $r = $this->db->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(u.statut_compte = 'actif'), 0) AS actifs,
                    COALESCE(SUM(u.statut_compte = 'suspendu'), 0) AS suspendus,
                    COALESCE(SUM(EXISTS (SELECT 1 FROM facture f WHERE f.id_utilisateur = u.id_utilisateur)), 0) AS avec_factures
             FROM utilisateur u WHERE u.role = 'citoyen'"
        )->fetch();
        return [
            'total'        => (int) $r['total'],
            'actifs'       => (int) $r['actifs'],
            'suspendus'    => (int) $r['suspendus'],
            'avecFactures' => (int) $r['avec_factures'],
        ];
    }

    /** @return array<int, int> identifiant du citoyen => nombre de factures */
    public function facturesParCitoyen(): array
    {
        $stmt = $this->db->query('SELECT id_utilisateur, COUNT(*) AS n FROM facture GROUP BY id_utilisateur');
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['id_utilisateur']] = (int) $row['n'];
        }
        return $out;
    }

    /** @return array<string, int> libellé => nombre, uniquement ce qui existe (vide = compte supprimable) */
    public function donneesLiees(int $id): array
    {
        $donnees = [];
        foreach (self::LIENS as $lien => $libelle) {
            [$table, $colonne] = explode('.', $lien);   // noms écrits en dur dans LIENS, jamais reçus du navigateur
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM $table WHERE $colonne = :id");
            $stmt->execute(['id' => $id]);
            $n = (int) $stmt->fetchColumn();
            if ($n > 0) {
                $donnees[$libelle] = $n;
            }
        }
        return $donnees;
    }

    /** true si un module a ajouté une clé étrangère vers « utilisateur » absente de LIENS. */
    private function lienInconnu(): bool
    {
        $stmt = $this->db->query(
            "SELECT CONCAT(TABLE_NAME, '.', COLUMN_NAME) FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'utilisateur'"
        );
        return array_diff(array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN)), array_keys(self::LIENS)) !== [];
    }

    // ==============================================================
    // VALIDATION
    // ==============================================================

    /** Texte exploitable, sinon null (liste envoyée à la place d'un texte, UTF-8 invalide, octet nul). */
    private static function texte(mixed $valeur): ?string
    {
        return is_string($valeur) && !str_contains($valeur, "\0") && mb_check_encoding($valeur, 'UTF-8') ? $valeur : null;
    }

    /**
     * Contrôle de base : les mêmes règles et les mêmes messages que les formulaires.
     * @param array<string, string> $regles
     * @return array<string, string>
     */
    private function controler(array $post, array $regles): array
    {
        // Valeur illisible : refusée tout de suite, avant toute conversion en texte
        $illisibles = [];
        foreach (array_keys($regles) as $champ) {
            if (isset($post[$champ]) && self::texte($post[$champ]) === null) {
                $illisibles[$champ] = 'Valeur non autorisée.';
            }
        }
        if ($illisibles !== []) {
            return $illisibles;
        }
        if (isset($post['telephone'])) {
            // Espaces insécables d'un numéro copié-collé : acceptés, comme en JavaScript
            $post['telephone'] = preg_replace('/\s/u', ' ', $post['telephone']);
        }

        $errors = $this->validate($post, $regles);
        foreach ($errors as $champ => $message) {
            if (trim($post[$champ] ?? '') === '') {
                $errors[$champ] = self::MESSAGES[$champ]['required'] ?? $message;
            } elseif ($champ === 'password_confirm') {
                $errors[$champ] = self::MESSAGES[$champ]['match'];
            }
        }

        // Contrôles en plus des règles communes (les mêmes dans citoyens-crud.js)
        if (isset($regles['email']) && !isset($errors['email'])
            && !preg_match('/' . self::EMAIL_MOTIF . '/D', trim($post['email'] ?? ''))) {
            $errors['email'] = self::EMAIL_INVALIDE;
        }
        if (isset($regles['password'])) {
            // validate() compare sans les espaces des bords, mais password_hash() les garde : on les refuse
            if (!isset($errors['password']) && preg_match('/^\s|\s\z/u', $post['password'] ?? '')) {
                $errors['password'] = self::ESPACE_AU_BORD;
            }
            if (!isset($errors['password_confirm']) && ($post['password_confirm'] ?? '') !== ($post['password'] ?? '')) {
                $errors['password_confirm'] = self::MESSAGES['password_confirm']['match'];
            }
        }
        return $errors;
    }

    /**
     * @param int $exclureId compte modifié (ignoré pour l'e-mail en double) ; 0 pour un ajout
     * @return array<string, string>
     */
    private function controlerProfil(array $post, array $regles, int $exclureId): array
    {
        $errors = $this->controler($post, $regles);
        if (!isset($errors['id_zone']) && self::texte($post['id_zone'] ?? '') !== null) {
            $stmt = $this->db->prepare("SELECT 1 FROM zone WHERE id_zone = :id AND type_zone = 'gouvernorat' LIMIT 1");
            $stmt->execute(['id' => (int) ($post['id_zone'] ?? 0)]);
            if (!$stmt->fetchColumn()) {
                $errors['id_zone'] = self::MESSAGES['id_zone']['required'];
            }
        }
        if (!isset($errors['email']) && self::texte($post['email'] ?? '') !== null) {
            $stmt = $this->db->prepare('SELECT 1 FROM utilisateur WHERE email = :email AND id_utilisateur <> :id LIMIT 1');
            $stmt->execute(['email' => mb_strtolower(trim($post['email'] ?? '')), 'id' => $exclureId]);
            if ($stmt->fetchColumn()) {
                $errors['email'] = self::EMAIL_EN_DOUBLE;
            }
        }
        return $errors;
    }

    /** Objet à enregistrer, à partir d'un formulaire déjà validé. Le rôle est toujours « citoyen ». */
    private function fromPost(array $post): UserModel
    {
        $v = fn (string $champ): string => trim($post[$champ] ?? '');
        $u = new UserModel();
        $u->setRole('citoyen');
        $u->setPrenom($v('prenom'));
        $u->setNom($v('nom'));
        $u->setEmail(mb_strtolower($v('email')));
        $u->setTelephone(preg_replace('/[\s.-]/u', '', $v('telephone')) ?: null);
        $u->setIdZone((int) $v('id_zone'));
        $u->setAdresse($v('adresse') !== '' ? $v('adresse') : null);
        return $u;
    }

    /**
     * Exécute l'INSERT ou l'UPDATE : si deux envois utilisent le même e-mail au même moment,
     * la clé uq_utilisateur_email refuse le second.
     * @return array<string, string>
     */
    private function ecrire(PDOStatement $stmt, array $params): array
    {
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062 && str_contains($e->getMessage(), 'uq_utilisateur_email')) {
                return ['email' => self::EMAIL_EN_DOUBLE];
            }
            throw $e;
        }
        return [];
    }

    // ==============================================================
    // CRÉATION / MODIFICATION / STATUT / SUPPRESSION
    // ==============================================================

    /** @return array<string, string> erreurs (vide = compte créé) */
    public function create(array $post): array
    {
        $errors = $this->controlerProfil($post, self::PROFIL + self::MOT_DE_PASSE, 0);
        if ($errors !== []) {
            return $errors;
        }
        $u = $this->fromPost($post);
        $u->setMotDePasse(password_hash($post['password'], PASSWORD_DEFAULT));
        $stmt = $this->db->prepare(
            "INSERT INTO utilisateur (nom, prenom, email, telephone, mot_de_passe, role, statut_compte, id_zone, adresse)
             VALUES (:nom, :prenom, :email, :tel, :mdp, 'citoyen', 'actif', :zone, :adresse)"
        );
        return $this->ecrire($stmt, [
            'nom' => $u->getNom(), 'prenom' => $u->getPrenom(), 'email' => $u->getEmail(), 'tel' => $u->getTelephone(),
            'mdp' => $u->getMotDePasse(), 'zone' => $u->getIdZone(), 'adresse' => $u->getAdresse(),
        ]);
    }

    /**
     * Modifie le profil uniquement : ni le rôle, ni le statut, ni le mot de passe.
     * @return array<string, string> erreurs (vide = compte modifié)
     */
    public function update(int $id, array $post): array
    {
        if ($this->find($id) === null) {
            return ['form' => self::INTROUVABLE];
        }
        $errors = $this->controlerProfil($post, self::PROFIL, $id);
        if ($errors !== []) {
            return $errors;
        }
        $u = $this->fromPost($post);
        $stmt = $this->db->prepare(
            "UPDATE utilisateur
             SET nom = :nom, prenom = :prenom, email = :email, telephone = :tel, id_zone = :zone, adresse = :adresse
             WHERE id_utilisateur = :id AND role = 'citoyen'"
        );
        return $this->ecrire($stmt, [
            'id' => $id, 'nom' => $u->getNom(), 'prenom' => $u->getPrenom(), 'email' => $u->getEmail(),
            'tel' => $u->getTelephone(), 'zone' => $u->getIdZone(), 'adresse' => $u->getAdresse(),
        ]);
    }

    /**
     * Le citoyen modifie SON profil. L'e-mail sert à se connecter : pour le changer, le mot de passe est demandé.
     * @return array<string, string> erreurs (vide = profil modifié)
     */
    public function updateProfil(int $id, array $post): array
    {
        $citoyen = $this->find($id);
        if ($citoyen === null) {
            return ['form' => self::INTROUVABLE];
        }
        $errors = $this->controlerProfil($post, self::PROFIL, $id);
        $email = self::texte($post['email'] ?? '');
        if ($email !== null && mb_strtolower(trim($email)) !== mb_strtolower($citoyen->getEmail())) {
            $mdp = self::texte($post['email_mot_de_passe'] ?? '');
            if ($mdp === null) {
                $errors['email_mot_de_passe'] = 'Valeur non autorisée.';
            } elseif ($mdp === '') {
                $errors['email_mot_de_passe'] = self::MESSAGES['email_mot_de_passe']['required'];
            } elseif ($errors === [] && ($erreur = $this->verifierActuel($citoyen, $mdp)) !== null) {
                $errors['email_mot_de_passe'] = $erreur;
            }
        }
        return $errors !== [] ? $errors : $this->update($id, $post);
    }

    /** Secondes à attendre après trop de mauvais mots de passe (0 = aucun blocage). */
    private function attente(): int
    {
        return max(0, (int) ($_SESSION['mdp_bloque_jusqua'] ?? 0) - time());
    }

    /**
     * Vérifie le mot de passe actuel : un seul compteur d'essais pour e-mail, mot de passe et suppression.
     * @return string|null message d'erreur (null = correct)
     */
    private function verifierActuel(UserModel $citoyen, string $motDePasse): ?string
    {
        if (($reste = $this->attente()) > 0) {
            return "Trop de tentatives. Réessayez dans $reste secondes.";
        }
        if (password_verify($motDePasse, $citoyen->getMotDePasse())) {
            unset($_SESSION['mdp_essais'], $_SESSION['mdp_bloque_jusqua']);
            return null;
        }
        $essais = (int) ($_SESSION['mdp_essais'] ?? 0) + 1;
        if ($essais >= self::ESSAIS_MAX) {
            $_SESSION['mdp_bloque_jusqua'] = time() + self::BLOCAGE_SECONDES;
            $essais = 0;
        }
        $_SESSION['mdp_essais'] = $essais;
        return 'Le mot de passe actuel est incorrect.';
    }

    /** Suspend ou réactive. Un compte suspendu est déconnecté à sa requête suivante (AuthController). */
    public function changerStatut(int $id, string $statut): bool
    {
        if (!in_array($statut, ['actif', 'suspendu'], true) || $this->find($id) === null) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE utilisateur SET statut_compte = :statut WHERE id_utilisateur = :id AND role = 'citoyen'");
        $stmt->execute(['statut' => $statut, 'id' => $id]);
        return true;
    }

    /**
     * Supprime uniquement un citoyen sans aucune donnée. La ligne est verrouillée pendant le contrôle :
     * personne ne peut lui ajouter une donnée entre le comptage et le DELETE.
     * @return string|null message d'erreur (null = supprimé)
     */
    public function delete(int $id): ?string
    {
        if ($this->lienInconnu()) {
            return 'Suppression bloquée : une nouvelle table est liée aux comptes. Suspendez plutôt ce compte.';
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = :id AND role = 'citoyen' FOR UPDATE");
            $stmt->execute(['id' => $id]);
            if ($stmt->fetchAll() === []) {
                $erreur = self::INTROUVABLE;
            } elseif ($this->donneesLiees($id) !== []) {
                $erreur = 'Ce compte a des données : il ne peut pas être supprimé. Suspendez-le plutôt.';
            } else {
                $stmt = $this->db->prepare("DELETE FROM utilisateur WHERE id_utilisateur = :id AND role = 'citoyen'");
                $stmt->execute(['id' => $id]);
                $erreur = null;
            }
            $this->db->commit();
            return $erreur;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Le citoyen supprime SON compte. Sans donnée : le compte est supprimé. Avec des données : il est
     * désactivé (statut « suspendu »), et seul le super admin peut le réactiver.
     * @return array{errors: array<string, string>, supprime: bool} supprime = false : compte désactivé
     */
    public function supprimerSonCompte(int $id, array $post): array
    {
        $citoyen = $this->find($id);
        if ($citoyen === null) {
            return ['errors' => ['form' => self::INTROUVABLE], 'supprime' => false];
        }
        $errors = $this->controler($post, self::SUPPRESSION);
        if ($errors === [] && ($erreur = $this->verifierActuel($citoyen, $post['suppression_mot_de_passe'])) !== null) {
            $errors = ['suppression_mot_de_passe' => $erreur];
        }
        if ($errors !== []) {
            return ['errors' => $errors, 'supprime' => false];
        }

        $lienInconnu = $this->lienInconnu();   // table inconnue : on désactive, on ne supprime pas
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = :id AND role = 'citoyen' FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $stmt->fetchAll();
            $supprime = !$lienInconnu && $this->donneesLiees($id) === [];
            $stmt = $this->db->prepare($supprime
                ? "DELETE FROM utilisateur WHERE id_utilisateur = :id AND role = 'citoyen'"
                : "UPDATE utilisateur SET statut_compte = 'suspendu' WHERE id_utilisateur = :id AND role = 'citoyen'");
            $stmt->execute(['id' => $id]);
            $this->db->commit();
            return ['errors' => [], 'supprime' => $supprime];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Le citoyen change SON mot de passe : il doit connaître l'actuel.
     * @return array<string, string> erreurs (vide = mot de passe changé)
     */
    public function changerMotDePasse(int $id, array $post): array
    {
        $citoyen = $this->find($id);
        if ($citoyen === null) {
            return ['form' => self::INTROUVABLE];
        }
        $reste = $this->attente();
        if ($reste > 0) {
            return ['password_actuel' => "Trop de tentatives. Réessayez dans $reste secondes."];
        }
        $errors = $this->controler($post, self::CHANGEMENT);
        if ($errors !== []) {
            return $errors;
        }
        if (($erreur = $this->verifierActuel($citoyen, $post['password_actuel'])) !== null) {
            return ['password_actuel' => $erreur];
        }
        $stmt = $this->db->prepare("UPDATE utilisateur SET mot_de_passe = :mdp WHERE id_utilisateur = :id AND role = 'citoyen'");
        $hache = password_hash($post['password'], PASSWORD_DEFAULT);
        $stmt->execute(['mdp' => $hache, 'id' => $id]);

        session_regenerate_id(true);   // nouvel identifiant de session, comme à la connexion
        // Cette session reste connectée ; AuthController ferme les autres, dont l'empreinte ne correspond plus
        $_SESSION['mdp_empreinte'] = hash('sha256', $hache);
        return [];
    }

    // ==============================================================
    // FORMULAIRES (affichage)
    // ==============================================================

    /** @return array<string, string> valeurs d'un formulaire vide */
    public static function emptyForm(): array
    {
        return array_fill_keys(array_keys(self::PROFIL), '');
    }

    /** @return array<string, string> valeurs d'un compte existant */
    public static function toForm(UserModel $u): array
    {
        return [
            'prenom'    => $u->getPrenom(),
            'nom'       => $u->getNom(),
            'email'     => $u->getEmail(),
            'telephone' => $u->getTelephone() ?? '',
            'id_zone'   => (string) ($u->getIdZone() ?? ''),
            'adresse'   => $u->getAdresse() ?? '',
        ];
    }

    /** @return array<string, string> valeurs saisies, réaffichées après une erreur (jamais les mots de passe) */
    public static function postedForm(array $post): array
    {
        $out = [];
        foreach (array_keys(self::PROFIL) as $champ) {
            $out[$champ] = trim(self::texte($post[$champ] ?? '') ?? '');
        }
        return $out;
    }

    /** Attributs data-rules et data-message-* d'un champ : le navigateur reçoit les règles de PHP. */
    public static function attributs(string $champ): string
    {
        $regles = self::PROFIL + self::CHANGEMENT + self::SUPPRESSION;
        $html = 'data-rules="' . e($regles[$champ]) . '"';
        if ($champ === 'email') {
            $html .= ' data-motif="' . e(self::EMAIL_MOTIF) . '" data-message-motif="' . e(self::EMAIL_INVALIDE) . '"';
        } elseif ($champ === 'password') {
            $html .= ' data-message-espace="' . e(self::ESPACE_AU_BORD) . '"';
        }
        foreach (self::MESSAGES[$champ] ?? [] as $regle => $message) {
            $html .= ' data-message-' . $regle . '="' . e($message) . '"';
        }
        return $html;
    }

    public static function statutLabel(string $statut): string
    {
        return ['actif' => 'Actif', 'suspendu' => 'Suspendu'][$statut] ?? $statut;
    }
}
