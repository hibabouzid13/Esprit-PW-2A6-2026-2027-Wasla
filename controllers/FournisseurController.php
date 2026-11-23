<?php
/**
 * controllers/FournisseurController.php — Module 4 (Bechir)
 *
 * Comptes FOURNISSEUR (électricien, plombier…). Ce n'est pas une table : c'est une ligne de
 * « utilisateur » avec role = 'fournisseur'. Nom commercial, spécialité, description et vérification
 * sont des colonnes de utilisateur.
 *
 * Règles communes appliquées (document « Gestion des utilisateurs », partie 2) :
 *  - chaque requête filtre le rôle : AND role = 'fournisseur' ;
 *  - rôle, statut et vérification écrits en dur, jamais lus dans un formulaire ;
 *  - mot de passe : password_hash() à la création, password_verify() de l'actuel pour le changer ;
 *  - e-mail en double : message sous le champ, y compris en cas d'envoi simultané (erreur MySQL 1062) ;
 *  - suppression : verrou FOR UPDATE puis contrôle des 16 clés étrangères vers utilisateur.
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/ZoneController.php';
require_once __DIR__ . '/../models/UserModel.php';

class FournisseurController extends Controller
{
    private const SELECT = 'SELECT u.*, z.nom_zone FROM utilisateur u LEFT JOIN zone z ON z.id_zone = u.id_zone';

    /** Les 16 clés étrangères qui pointent vers utilisateur : table, colonne, libellé. */
    private const LIENS = [
        ['notification',         'id_utilisateur', 'notifications'],
        ['interruption',         'id_auteur',      'interruptions publiées'],
        ['signalement',          'id_utilisateur', 'signalements'],
        ['confirmation',         'id_utilisateur', 'confirmations de signalement'],
        ['alerte',               'id_auteur',      'alertes publiées'],
        ['lieu_suivi',           'id_utilisateur', 'lieux suivis'],
        ['preference_alerte',    'id_utilisateur', "préférences d'alerte"],
        ['facture',              'id_utilisateur', 'factures'],
        ['consommation',         'id_utilisateur', 'consommations'],
        ['refuge_zone',          'id_entreprise',  'refuges'],
        ['reservation_refuge',   'id_utilisateur', 'réservations'],
        ['installation_solaire', 'id_utilisateur', 'installations solaires'],
        ['demande_assistance',   'id_utilisateur', "demandes d'assistance envoyées"],
        ['demande_assistance',   'id_fournisseur', "demandes d'intervention reçues"],
        ['reponse',              'id_utilisateur', 'réponses à des demandes'],
        ['personne_prioritaire', 'id_utilisateur', 'personnes prioritaires déclarées'],
    ];

    // ==============================================================
    // RÈGLES DE SAISIE (reprises telles quelles dans les vues : data-rules)
    // ==============================================================

    /** @param string $context inscription | profil | admin-ajout | admin-modif */
    public static function rules(string $context): array
    {
        $rules = [
            'prenom'           => 'required|name',
            'nom'              => 'required|name',
            'email'            => 'required|email',
            'telephone'        => 'required|phone',
            'id_zone'          => 'required|int',
            'adresse'          => 'max:255',
            'nom_organisation' => 'required|min:2|max:100',
            'specialite'       => 'required|in:' . implode(',', UserModel::SPECIALITES),
            'description'      => 'max:500',
        ];
        if ($context === 'inscription' || $context === 'admin-ajout') {
            $rules['password'] = 'required|password';
            $rules['password_confirm'] = 'required|match:password';
        }
        if ($context === 'inscription') {
            $rules['terms'] = 'accepted';
        }
        return $rules;
    }

    public static function passwordRules(): array
    {
        return [
            'current_password' => 'required',
            'password'         => 'required|password',
            'password_confirm' => 'required|match:password',
        ];
    }

    public static function deleteRules(): array
    {
        return ['current_password' => 'required', 'confirmation' => 'accepted'];
    }

    // ==============================================================
    // LECTURE
    // ==============================================================

    public function find(int $id): ?UserModel
    {
        $stmt = $this->db->prepare(self::SELECT . " WHERE u.id_utilisateur = :id AND u.role = 'fournisseur' LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    /**
     * Back Office : recherche et filtres.
     *
     * @param array<string, string> $filters q, specialite, statut (actif|suspendu), verifie (0|1)
     * @return UserModel[]
     */
    public function search(array $filters = []): array
    {
        $where = ["u.role = 'fournisseur'"];
        $params = [];

        if (in_array($filters['specialite'] ?? '', UserModel::SPECIALITES, true)) {
            $where[] = 'u.specialite = :specialite';
            $params['specialite'] = $filters['specialite'];
        }
        if (in_array($filters['statut'] ?? '', ['actif', 'suspendu'], true)) {
            $where[] = 'u.statut_compte = :statut';
            $params['statut'] = $filters['statut'];
        }
        if (in_array($filters['verifie'] ?? '', ['0', '1'], true)) {
            $where[] = 'u.compte_verifie = :verifie';
            $params['verifie'] = (int) $filters['verifie'];
        }
        $q = $filters['q'] ?? '';
        if ($q !== '') {
            $like = '%' . addcslashes(mb_substr($q, 0, 60), '%_\\') . '%';
            $where[] = '(u.nom LIKE :q1 OR u.prenom LIKE :q2 OR u.email LIKE :q3 OR u.nom_organisation LIKE :q4 OR u.adresse LIKE :q5)';
            foreach (['q1', 'q2', 'q3', 'q4', 'q5'] as $key) {
                $params[$key] = $like;
            }
        }

        $stmt = $this->db->prepare(self::SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY u.date_inscription DESC, u.id_utilisateur DESC');
        $stmt->execute($params);
        return array_map(fn (array $row) => UserModel::fromArray($row), $stmt->fetchAll());
    }

    /**
     * Annuaire public : uniquement les fournisseurs actifs ET vérifiés.
     *
     * @param array<string, string> $filters specialite, id_zone, q, tri (note|nom)
     * @return UserModel[]
     */
    public function getPublics(array $filters = []): array
    {
        $where = ["u.role = 'fournisseur'", "u.statut_compte = 'actif'", 'u.compte_verifie = 1'];
        $params = [];

        if (in_array($filters['specialite'] ?? '', UserModel::SPECIALITES, true)) {
            $where[] = 'u.specialite = :specialite';
            $params['specialite'] = $filters['specialite'];
        }
        if (ctype_digit($filters['id_zone'] ?? '')) {
            $where[] = 'u.id_zone = :zone';
            $params['zone'] = (int) $filters['id_zone'];
        }
        $q = $filters['q'] ?? '';
        if ($q !== '') {
            $like = '%' . addcslashes(mb_substr($q, 0, 60), '%_\\') . '%';
            $where[] = '(u.nom_organisation LIKE :q1 OR u.nom LIKE :q2 OR u.adresse LIKE :q3 OR u.description LIKE :q4)';
            foreach (['q1', 'q2', 'q3', 'q4'] as $key) {
                $params[$key] = $like;
            }
        }
        $order = ($filters['tri'] ?? '') === 'nom'
            ? 'u.nom_organisation'
            : 'u.note_moyenne IS NULL, u.note_moyenne DESC, u.nom_organisation';

        $stmt = $this->db->prepare(self::SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order);
        $stmt->execute($params);
        return array_map(fn (array $row) => UserModel::fromArray($row), $stmt->fetchAll());
    }

    /** @return array<string, int> spécialité => nombre de fournisseurs publics */
    public function countPublicsParSpecialite(): array
    {
        $counts = array_fill_keys(UserModel::SPECIALITES, 0);
        $stmt = $this->db->query(
            "SELECT specialite, COUNT(*) AS n FROM utilisateur
              WHERE role = 'fournisseur' AND statut_compte = 'actif' AND compte_verifie = 1 AND specialite IS NOT NULL
              GROUP BY specialite"
        );
        foreach ($stmt as $row) {
            $counts[$row['specialite']] = (int) $row['n'];
        }
        return $counts;
    }

    /** @return array<string, int> libellé => nombre de lignes liées (seulement celles > 0) */
    public function donneesLiees(int $id): array
    {
        $found = [];
        foreach (self::LIENS as [$table, $column, $label]) {
            // Noms de table et de colonne écrits en dur ci-dessus (jamais saisis) : pas d'injection possible
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = :id");
            $stmt->execute(['id' => $id]);
            $n = (int) $stmt->fetchColumn();
            if ($n > 0) {
                $found[$label] = $n;
            }
        }
        return $found;
    }

    // ==============================================================
    // CRÉATION
    // ==============================================================

    /**
     * Inscription publique : compte actif, NON vérifié, rôle écrit en dur.
     *
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = compte créé)
     */
    public function register(array $post): array
    {
        return $this->insert($post, 'inscription');
    }

    /** Ajout par le super admin (même règle : non vérifié tant qu'il n'a pas cliqué « Vérifier »). */
    public function adminCreate(array $post): array
    {
        return $this->insert($post, 'admin-ajout');
    }

    /** @return array<string, string> */
    private function insert(array $post, string $context): array
    {
        $errors = $this->checkFields($post, self::rules($context), null);
        if ($errors !== []) {
            return $errors;
        }
        $f = $this->fields($post);
        $stmt = $this->db->prepare(
            "INSERT INTO utilisateur (nom, prenom, email, telephone, mot_de_passe, id_zone, adresse,
                                      nom_organisation, specialite, description,
                                      role, statut_compte, compte_verifie)
             VALUES (:nom, :prenom, :email, :tel, :mdp, :zone, :adresse,
                     :organisation, :specialite, :description, 'fournisseur', 'actif', 0)"
        );
        try {
            $stmt->execute($f + ['mdp' => password_hash(self::str($post, 'password'), PASSWORD_DEFAULT)]);
        } catch (PDOException $e) {
            return $this->duplicateError($e);
        }
        return [];
    }

    // ==============================================================
    // MODIFICATION
    // ==============================================================

    /** Modification par l'admin : ni mot de passe, ni rôle, ni statut, ni vérification. */
    public function adminUpdate(UserModel $fournisseur, array $post): array
    {
        return $this->updateFields($fournisseur->getIdUtilisateur(), $post, 'admin-modif');
    }

    /** Profil du fournisseur connecté (l'id vient de la session, jamais du formulaire). */
    public function updateProfil(UserModel $current, array $post): array
    {
        if ($current->getRole() !== 'fournisseur') {
            return ['form' => 'Cette page est réservée aux fournisseurs.'];
        }
        return $this->updateFields($current->getIdUtilisateur(), $post, 'profil');
    }

    /** @return array<string, string> */
    private function updateFields(int $id, array $post, string $context): array
    {
        $errors = $this->checkFields($post, self::rules($context), $id);
        if ($errors !== []) {
            return $errors;
        }
        $stmt = $this->db->prepare(
            "UPDATE utilisateur
                SET nom = :nom, prenom = :prenom, email = :email, telephone = :tel, id_zone = :zone,
                    adresse = :adresse, nom_organisation = :organisation, specialite = :specialite, description = :description
              WHERE id_utilisateur = :id AND role = 'fournisseur'"
        );
        try {
            $stmt->execute($this->fields($post) + ['id' => $id]);
        } catch (PDOException $e) {
            return $this->duplicateError($e);
        }
        return [];
    }

    /** Changement de son propre mot de passe : l'actuel est exigé. @return array<string, string> */
    public function changePassword(UserModel $current, array $post): array
    {
        if ($current->getRole() !== 'fournisseur') {
            return ['form' => 'Cette page est réservée aux fournisseurs.'];
        }
        $errors = $this->validate($post, self::passwordRules());
        if (!isset($errors['current_password']) && !password_verify(self::str($post, 'current_password'), $current->getMotDePasse())) {
            $errors['current_password'] = 'Mot de passe actuel incorrect.';
        }
        if ($errors !== []) {
            return $errors;
        }
        $stmt = $this->db->prepare("UPDATE utilisateur SET mot_de_passe = :mdp WHERE id_utilisateur = :id AND role = 'fournisseur'");
        $stmt->execute(['mdp' => password_hash(self::str($post, 'password'), PASSWORD_DEFAULT), 'id' => $current->getIdUtilisateur()]);
        session_regenerate_id(true);
        return [];
    }

    // ==============================================================
    // ACTIONS ADMIN : vérification, statut
    // ==============================================================

    /** @return bool true si un fournisseur a été trouvé */
    public function setVerifie(int $id, bool $verifie): bool
    {
        if ($this->find($id) === null) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE utilisateur SET compte_verifie = :v WHERE id_utilisateur = :id AND role = 'fournisseur'");
        $stmt->execute(['v' => $verifie ? 1 : 0, 'id' => $id]);
        return true;
    }

    public function setStatut(int $id, string $statut): bool
    {
        if (!in_array($statut, ['actif', 'suspendu'], true) || $this->find($id) === null) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE utilisateur SET statut_compte = :s WHERE id_utilisateur = :id AND role = 'fournisseur'");
        $stmt->execute(['s' => $statut, 'id' => $id]);
        return true;
    }

    // ==============================================================
    // SUPPRESSION
    // ==============================================================

    /**
     * Suppression par l'admin : uniquement un compte sans aucune donnée liée.
     *
     * @return string|null message d'erreur, null si supprimé
     */
    public function delete(int $id): ?string
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = :id AND role = 'fournisseur' FOR UPDATE");
            $stmt->execute(['id' => $id]);
            if ($stmt->fetchAll() === []) {
                $erreur = 'Fournisseur introuvable.';
            } elseif ($this->donneesLiees($id) !== []) {
                $erreur = 'Ce compte a des données : suspendez-le plutôt.';
            } else {
                $del = $this->db->prepare("DELETE FROM utilisateur WHERE id_utilisateur = :id AND role = 'fournisseur'");
                $del->execute(['id' => $id]);
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
     * Le fournisseur supprime son propre compte (mot de passe exigé) :
     * supprimé s'il n'a aucune donnée, sinon désactivé (statut « suspendu », données conservées).
     *
     * @return array{errors: array<string, string>, result: ?string} result = 'supprime' | 'desactive'
     */
    public function deleteOwn(UserModel $current, array $post): array
    {
        if ($current->getRole() !== 'fournisseur') {
            return ['errors' => ['form' => 'Cette page est réservée aux fournisseurs.'], 'result' => null];
        }
        $errors = $this->validate($post, self::deleteRules());
        if (!isset($errors['current_password']) && !password_verify(self::str($post, 'current_password'), $current->getMotDePasse())) {
            $errors['current_password'] = 'Mot de passe incorrect.';
        }
        if ($errors !== []) {
            return ['errors' => $errors, 'result' => null];
        }

        $id = $current->getIdUtilisateur();
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = :id AND role = 'fournisseur' FOR UPDATE");
            $stmt->execute(['id' => $id]);
            if ($stmt->fetchAll() === []) {
                $result = null;
            } elseif ($this->donneesLiees($id) !== []) {
                $upd = $this->db->prepare("UPDATE utilisateur SET statut_compte = 'suspendu' WHERE id_utilisateur = :id AND role = 'fournisseur'");
                $upd->execute(['id' => $id]);
                $result = 'desactive';
            } else {
                $del = $this->db->prepare("DELETE FROM utilisateur WHERE id_utilisateur = :id AND role = 'fournisseur'");
                $del->execute(['id' => $id]);
                $result = 'supprime';
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
        return $result === null
            ? ['errors' => ['form' => 'Compte introuvable.'], 'result' => null]
            : ['errors' => [], 'result' => $result];
    }

    // ==============================================================
    // OUTILS
    // ==============================================================

    /**
     * Validation commune + gouvernorat existant + e-mail unique.
     *
     * @return array<string, string>
     */
    private function checkFields(array $post, array $rules, ?int $exceptId): array
    {
        $errors = $this->validate($post, $rules);
        if (!isset($errors['id_zone']) && !(new ZoneController())->exists((int) self::str($post, 'id_zone'))) {
            $errors['id_zone'] = 'Veuillez choisir un gouvernorat.';
        }
        if (!isset($errors['email'])) {
            $stmt = $this->db->prepare('SELECT 1 FROM utilisateur WHERE email = :email AND id_utilisateur <> :id LIMIT 1');
            $stmt->execute(['email' => mb_strtolower(self::str($post, 'email')), 'id' => $exceptId ?? 0]);
            if ($stmt->fetchColumn()) {
                $errors['email'] = 'Un compte existe déjà avec cette adresse e-mail.';
            }
        }
        return $errors;
    }

    /** Valeurs nettoyées pour les requêtes (uniquement des textes, jamais une liste). */
    private function fields(array $post): array
    {
        return [
            'nom'          => self::str($post, 'nom'),
            'prenom'       => self::str($post, 'prenom'),
            'email'        => mb_strtolower(self::str($post, 'email')),
            'tel'          => preg_replace('/[\s.-]/', '', self::str($post, 'telephone')),
            'zone'         => (int) self::str($post, 'id_zone'),
            'adresse'      => self::str($post, 'adresse') ?: null,
            'organisation' => self::str($post, 'nom_organisation'),
            'specialite'   => self::str($post, 'specialite'),
            'description'  => self::str($post, 'description') ?: null,
        ];
    }

    /** Erreur MySQL 1062 (deux envois simultanés avec le même e-mail) → message sous le champ. */
    private function duplicateError(PDOException $e): array
    {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['email' => 'Un compte existe déjà avec cette adresse e-mail.'];
        }
        throw $e;
    }

    /** Valeur texte d'un tableau reçu ; une liste ou une valeur absente donne "". */
    public static function str(array $data, string $key): string
    {
        $value = $data[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }
}
