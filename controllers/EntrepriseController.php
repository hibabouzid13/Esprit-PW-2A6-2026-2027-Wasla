<?php
/**
 * controllers/EntrepriseController.php — Module 3 (Louay)
 * Gestion des comptes ENTREPRISE.
 *
 * Entreprise n'est PAS une table : c'est un rôle de « utilisateur ». Ses informations propres
 * (raison sociale, matricule fiscal, secteur, validation) sont des colonnes de utilisateur :
 * nom_organisation, matricule_fiscal, secteur_activite, compte_verifie.
 * Toutes les requêtes ci-dessous filtrent donc sur role = 'entreprise'.
 *
 * États d'un compte (sans changer la base) :
 *  - en attente de vérification : statut_compte = actif, compte_verifie = 0
 *      → se connecte, gère son profil et ses réservations en cours, ne publie pas de refuge ;
 *        ses refuges sont masqués de la carte (aucune nouvelle réservation).
 *  - vérifié : statut_compte = actif, compte_verifie = 1 → toutes les fonctions.
 *  - suspendu : statut_compte = suspendu → ne se connecte plus ; ses refuges sont masqués et
 *    ses réservations en cours sont annulées (le citoyen le voit dans « Mes réservations »).
 *
 * Suppression : uniquement un compte SANS donnée liée (16 clés étrangères vers utilisateur).
 * Sinon on propose « Suspendre » (admin) ou le compte est désactivé (suppression par l'entreprise).
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/Module3Saisie.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/ZoneController.php';

class EntrepriseController extends Controller
{
    use Module3Saisie;

    /** Filtres de la liste du Back Office. */
    public const FILTRES = ['a_valider', 'verifiees', 'suspendues'];

    /** Secteurs proposés dans le formulaire. */
    public const SECTEURS = ['Café', 'Restaurant', 'Hôtel', 'Centre commercial', 'Salle de sport', 'Espace de coworking', 'Banque', 'Pharmacie', 'Autre'];

    /** Matricule fiscal tunisien (après suppression des espaces et passage en majuscules). */
    public const MATRICULE_REGEX = '^\d{7}[A-Z](/[A-Z]/[A-Z]/\d{3})?$';

    /**
     * Messages des formulaires : utilisés par PHP ET écrits dans les vues (data-message-*).
     * Les règles non listées gardent le message commun de validation.js / Controller::validate().
     */
    public const MESSAGES = [
        'nom_organisation'    => ['required' => 'Indiquez la raison sociale.'],
        'matricule_fiscal'    => ['required' => 'Indiquez le matricule fiscal.',
                                  'matricule' => 'Format attendu : 7 chiffres et une lettre (ex. 1234567A ou 1234567A/M/A/000).'],
        'secteur_activite'    => ['required' => 'Veuillez choisir un secteur.'],
        'prenom'              => ['required' => 'Indiquez le prénom du responsable.'],
        'nom'                 => ['required' => 'Indiquez le nom du responsable.'],
        'email'               => ['required' => 'Indiquez une adresse e-mail.'],
        'telephone'           => ['required' => 'Indiquez un numéro de téléphone.'],
        'id_zone'             => ['required' => 'Veuillez choisir un gouvernorat.'],
        'adresse'             => ['required' => "Indiquez l'adresse."],
        'password'            => ['required' => 'Choisissez un mot de passe.'],
        'password_confirm'    => ['required' => 'Confirmez le mot de passe.',
                                  'match'    => 'Les mots de passe ne correspondent pas.'],
        'mot_de_passe_actuel' => ['required' => 'Indiquez votre mot de passe actuel.'],
        'terms'               => ['accepted' => "Veuillez accepter les conditions d'utilisation."],
    ];

    /** Tables (et colonnes) qui pointent vers utilisateur : les 16 clés étrangères de wasla.sql. */
    private const DONNEES_LIEES = [
        'refuges'                    => 'SELECT COUNT(*) FROM refuge_zone WHERE id_entreprise = :id',
        'réservations de refuge'     => 'SELECT COUNT(*) FROM reservation_refuge WHERE id_utilisateur = :id',
        'notifications'              => 'SELECT COUNT(*) FROM notification WHERE id_utilisateur = :id',
        'interruptions publiées'     => 'SELECT COUNT(*) FROM interruption WHERE id_auteur = :id',
        'signalements'               => 'SELECT COUNT(*) FROM signalement WHERE id_utilisateur = :id',
        'confirmations'              => 'SELECT COUNT(*) FROM confirmation WHERE id_utilisateur = :id',
        'alertes publiées'           => 'SELECT COUNT(*) FROM alerte WHERE id_auteur = :id',
        'lieux suivis'               => 'SELECT COUNT(*) FROM lieu_suivi WHERE id_utilisateur = :id',
        "préférences d'alerte"       => 'SELECT COUNT(*) FROM preference_alerte WHERE id_utilisateur = :id',
        'factures'                   => 'SELECT COUNT(*) FROM facture WHERE id_utilisateur = :id',
        'consommations'              => 'SELECT COUNT(*) FROM consommation WHERE id_utilisateur = :id',
        'installations solaires'     => 'SELECT COUNT(*) FROM installation_solaire WHERE id_utilisateur = :id',
        "demandes d'assistance"      => 'SELECT COUNT(*) FROM demande_assistance WHERE id_utilisateur = :id',
        'demandes reçues'            => 'SELECT COUNT(*) FROM demande_assistance WHERE id_fournisseur = :id',
        'réponses'                   => 'SELECT COUNT(*) FROM reponse WHERE id_utilisateur = :id',
        'personnes prioritaires'     => 'SELECT COUNT(*) FROM personne_prioritaire WHERE id_utilisateur = :id',
    ];

    // ==============================================================
    // LECTURE
    // ==============================================================

    /**
     * Liste du Back Office, avec filtre et recherche (raison sociale, responsable, e-mail, matricule).
     *
     * @return UserModel[]
     */
    public function getAll(string $filtre = '', string $recherche = ''): array
    {
        $where = match ($filtre) {
            'a_valider'  => " AND u.compte_verifie = 0 AND u.statut_compte = 'actif'",
            'verifiees'  => ' AND u.compte_verifie = 1',
            'suspendues' => " AND u.statut_compte = 'suspendu'",
            default      => '',
        };
        $params = [];
        if ($recherche !== '') {
            $where .= " AND (u.nom_organisation LIKE :q1 OR u.email LIKE :q2 OR u.matricule_fiscal LIKE :q3
                             OR CONCAT(u.prenom, ' ', u.nom) LIKE :q4)";
            $like = '%' . addcslashes($recherche, '%_\\') . '%';
            // Avec EMULATE_PREPARES = false, un marqueur nommé ne peut pas être répété : un par colonne
            $params = ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        $stmt = $this->db->prepare(
            "SELECT u.*, z.nom_zone FROM utilisateur u
               LEFT JOIN zone z ON z.id_zone = u.id_zone
              WHERE u.role = 'entreprise'$where
              ORDER BY u.compte_verifie ASC, u.date_inscription DESC, u.id_utilisateur DESC"
        );
        $stmt->execute($params);
        return array_map(fn (array $row) => UserModel::fromArray($row), $stmt->fetchAll());
    }

    public function findById(int $id): ?UserModel
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, z.nom_zone FROM utilisateur u
               LEFT JOIN zone z ON z.id_zone = u.id_zone
              WHERE u.id_utilisateur = :id AND u.role = 'entreprise' LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    /** @return array<int, int> id entreprise => nombre de refuges */
    public function countRefuges(): array
    {
        $map = [];
        foreach ($this->db->query('SELECT id_entreprise, COUNT(*) AS n FROM refuge_zone GROUP BY id_entreprise') as $row) {
            $map[(int) $row['id_entreprise']] = (int) $row['n'];
        }
        return $map;
    }

    /** @return array{total: int, a_valider: int, verifiees: int, suspendues: int} */
    public function stats(): array
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(compte_verifie = 0 AND statut_compte = 'actif'), 0) AS a_valider,
                    COALESCE(SUM(compte_verifie = 1), 0) AS verifiees,
                    COALESCE(SUM(statut_compte = 'suspendu'), 0) AS suspendues
               FROM utilisateur WHERE role = 'entreprise'"
        )->fetch();
        return array_map('intval', $row);
    }

    /**
     * Données liées à un compte (libellé => nombre), uniquement les tables non vides.
     *
     * @return array<string, int>
     */
    public function donneesLiees(int $id): array
    {
        $found = [];
        foreach (self::DONNEES_LIEES as $label => $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $n = (int) $stmt->fetchColumn();
            if ($n > 0) {
                $found[$label] = $n;
            }
        }
        return $found;
    }

    // ==============================================================
    // INSCRIPTION PUBLIQUE (compte en attente de validation)
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = succès)
     */
    public function register(array $post): array
    {
        $errors = $this->validateEntreprise($post, null, true);
        if ($errors !== []) {
            return $errors;
        }
        return $this->insert($post, false);
    }

    // ==============================================================
    // ESPACE ENTREPRISE : PROFIL, MOT DE PASSE, SUPPRESSION DU COMPTE
    // (l'id vient TOUJOURS de la session : $user = AuthController::currentUser())
    // ==============================================================

    /**
     * Modification de son profil. Le rôle, le statut et la vérification ne viennent jamais du formulaire.
     * Si le matricule fiscal change, le compte repasse « à vérifier » (compte_verifie = 0).
     *
     * @param array<string, mixed> $post
     * @return array{0: array<string, string>, 1: bool} [erreurs, true si le compte doit être revérifié]
     */
    public function updateProfil(UserModel $user, array $post): array
    {
        if ($user->getRole() !== 'entreprise') {
            return [['form' => 'Compte entreprise introuvable.'], false];
        }
        $errors = $this->validateEntreprise($post, $user, false, false);
        if ($errors !== []) {
            return [$errors, false];
        }

        $params = $this->commonParams($post) + ['id' => $user->getIdUtilisateur()];
        $matriculeChange = $params['matricule_fiscal'] !== (string) $user->getMatriculeFiscal();
        try {
            $stmt = $this->db->prepare(
                "UPDATE utilisateur
                    SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, id_zone = :id_zone,
                        adresse = :adresse, nom_organisation = :nom_organisation, matricule_fiscal = :matricule_fiscal,
                        secteur_activite = :secteur_activite, description = :description,
                        compte_verifie = IF(:change = 1, 0, compte_verifie)
                  WHERE id_utilisateur = :id AND role = 'entreprise'"
            );
            $stmt->execute($params + ['change' => $matriculeChange ? 1 : 0]);
        } catch (PDOException $e) {
            return [$this->duplicateErrors($e), false];
        }
        $reverifier = $matriculeChange && $user->isCompteVerifie();
        return [[], $reverifier];
    }

    /**
     * Changement de son mot de passe : l'actuel est demandé.
     * La route appelle ensuite session_regenerate_id(true).
     *
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    public function changePassword(UserModel $user, array $post): array
    {
        $errors = $this->validateWithMessages($post, [
            'mot_de_passe_actuel' => 'required',
            'password'            => 'required|password',
            'password_confirm'    => 'required|match:password',
        ]);
        if (!isset($errors['mot_de_passe_actuel']) && !password_verify(self::raw($post, 'mot_de_passe_actuel'), $user->getMotDePasse())) {
            $errors['mot_de_passe_actuel'] = 'Mot de passe actuel incorrect.';
        }
        if ($errors !== []) {
            return $errors;
        }
        $stmt = $this->db->prepare("UPDATE utilisateur SET mot_de_passe = :mdp WHERE id_utilisateur = :id AND role = 'entreprise'");
        $hache = password_hash(self::raw($post, 'password'), PASSWORD_DEFAULT);
        $stmt->execute(['mdp' => $hache, 'id' => $user->getIdUtilisateur()]);
        // Empreinte lue par la branche commune pour fermer les AUTRES sessions : on garde la nôtre ouverte
        $_SESSION['mdp_empreinte'] = hash('sha256', $hache);
        return [];
    }

    /**
     * Suppression de son propre compte (mot de passe demandé) :
     * supprimé s'il n'a aucune donnée, sinon désactivé (suspendu, données conservées).
     *
     * @param array<string, mixed> $post
     * @return array{0: array<string, string>, 1: ?string} [erreurs, 'supprime' | 'desactive' | null]
     */
    public function deleteOwnAccount(UserModel $user, array $post): array
    {
        $errors = $this->validateWithMessages($post, ['mot_de_passe_actuel' => 'required']);
        if (!isset($errors['mot_de_passe_actuel']) && !password_verify(self::raw($post, 'mot_de_passe_actuel'), $user->getMotDePasse())) {
            $errors['mot_de_passe_actuel'] = 'Mot de passe incorrect.';
        }
        if ($errors !== []) {
            return [$errors, null];
        }
        $result = $this->safeDelete($user->getIdUtilisateur(), true);
        return $result === null ? [['form' => 'Compte introuvable.'], null] : [[], $result];
    }

    // ==============================================================
    // BACK OFFICE : AJOUT / MODIFICATION / VALIDATION / SUSPENSION / SUPPRESSION
    // ==============================================================

    /**
     * Ajout (id = null) ou modification d'un compte entreprise par l'admin.
     * En modification, le mot de passe n'est JAMAIS changé : seule l'entreprise change le sien.
     *
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = succès)
     */
    public function save(array $post, ?int $id): array
    {
        $existing = null;
        if ($id !== null && ($existing = $this->findById($id)) === null) {
            return ['form' => 'Compte entreprise introuvable.'];
        }
        $errors = $this->validateEntreprise($post, $existing, false);
        if ($errors !== []) {
            return $errors;
        }

        $verifie = self::text($post, 'compte_verifie') !== '';
        if ($existing === null) {
            return $this->insert($post, $verifie);
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE utilisateur
                    SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, id_zone = :id_zone,
                        adresse = :adresse, nom_organisation = :nom_organisation, matricule_fiscal = :matricule_fiscal,
                        secteur_activite = :secteur_activite, description = :description, compte_verifie = :verifie
                  WHERE id_utilisateur = :id AND role = 'entreprise'"
            );
            $stmt->execute($this->commonParams($post) + ['verifie' => $verifie ? 1 : 0, 'id' => $id]);
        } catch (PDOException $e) {
            return $this->duplicateErrors($e);
        }
        return [];
    }

    /** Valide (true) ou retire la validation (false) d'un compte entreprise. */
    public function setVerifie(int $id, bool $verifie): bool
    {
        $stmt = $this->db->prepare("UPDATE utilisateur SET compte_verifie = :v WHERE id_utilisateur = :id AND role = 'entreprise'");
        $stmt->execute(['v' => $verifie ? 1 : 0, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Suspend ou réactive un compte. Suspendu : connexion impossible, refuges masqués,
     * réservations en cours annulées (places libérées).
     */
    public function setStatut(int $id, string $statut): bool
    {
        if (!in_array($statut, ['actif', 'suspendu'], true)) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE utilisateur SET statut_compte = :s
                  WHERE id_utilisateur = :id AND role = 'entreprise' AND statut_compte <> :s2"
            );
            $stmt->execute(['s' => $statut, 's2' => $statut, 'id' => $id]);
            $ok = $stmt->rowCount() > 0;
            if ($ok && $statut === 'suspendu') {
                $this->cancelActiveReservations($id);
            }
            $this->db->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Suppression par l'admin : uniquement un compte sans donnée liée.
     *
     * @return ?string null = supprimé, sinon message d'erreur
     */
    public function delete(int $id): ?string
    {
        return match ($this->safeDelete($id, false)) {
            'supprime' => null,
            'refuse'   => 'Ce compte a des données (refuges, réservations…) : suspendez-le plutôt.',
            default    => 'Compte entreprise introuvable.',
        };
    }

    // ==============================================================
    // OUTILS INTERNES
    // ==============================================================

    /**
     * Suppression sûre (modèle commun de l'équipe) : verrou FOR UPDATE, contrôle des données liées,
     * puis DELETE seulement si le compte est vide.
     *
     * @param bool $desactiverSiDonnees true : désactive (suspend) au lieu de refuser
     * @return ?string 'supprime' | 'desactive' | 'refuse' | null (introuvable)
     */
    private function safeDelete(int $id, bool $desactiverSiDonnees): ?string
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT id_utilisateur FROM utilisateur
                  WHERE id_utilisateur = :id AND role = 'entreprise' FOR UPDATE"
            );
            $stmt->execute(['id' => $id]);
            if ($stmt->fetchAll() === []) {
                $result = null;
            } elseif ($this->donneesLiees($id) === []) {
                $del = $this->db->prepare("DELETE FROM utilisateur WHERE id_utilisateur = :id AND role = 'entreprise'");
                $del->execute(['id' => $id]);
                $result = 'supprime';
            } elseif ($desactiverSiDonnees) {
                $upd = $this->db->prepare("UPDATE utilisateur SET statut_compte = 'suspendu' WHERE id_utilisateur = :id AND role = 'entreprise'");
                $upd->execute(['id' => $id]);
                $this->cancelActiveReservations($id);
                $result = 'desactive';
            } else {
                $result = 'refuse';
            }
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Annule les réservations en cours sur les refuges d'une entreprise et rend leurs places.
     * Appelée dans une transaction déjà ouverte (suspension ou désactivation du compte).
     */
    private function cancelActiveReservations(int $idEntreprise): void
    {
        // Verrou sur les refuges de l'entreprise avant de toucher aux places
        $lock = $this->db->prepare('SELECT id_refuge FROM refuge_zone WHERE id_entreprise = :id FOR UPDATE');
        $lock->execute(['id' => $idEntreprise]);

        $stmt = $this->db->prepare(
            "UPDATE reservation_refuge x JOIN refuge_zone r ON r.id_refuge = x.id_refuge
                SET x.statut = 'annulee'
              WHERE r.id_entreprise = :id AND x.statut IN ('en_attente','confirmee')"
        );
        $stmt->execute(['id' => $idEntreprise]);
        if ($stmt->rowCount() === 0) {
            return;
        }
        // Plus aucune réservation active : toutes les places sont libres
        $stmt = $this->db->prepare(
            "UPDATE refuge_zone
                SET places_disponibles = capacite,
                    statut = IF(statut = 'complet', 'ouvert', statut)
              WHERE id_entreprise = :id"
        );
        $stmt->execute(['id' => $idEntreprise]);
    }

    /**
     * Mêmes règles que les data-rules des formulaires, puis contrôles en base (unicité).
     *
     * @param array<string, mixed> $post
     * @param bool $public     inscription publique (case des conditions)
     * @param bool $motDePasse true : mot de passe demandé (création uniquement)
     * @return array<string, string>
     */
    private function validateEntreprise(array $post, ?UserModel $existing, bool $public, bool $motDePasse = true): array
    {
        $rules = [
            'nom_organisation' => 'required|min:2|max:100',
            'matricule_fiscal' => 'required|max:20',
            'secteur_activite' => 'required|max:60',
            'prenom'           => 'required|name',
            'nom'              => 'required|name',
            'email'            => 'required|email',
            'telephone'        => 'required|phone',
            'id_zone'          => 'required|int',
            'adresse'          => 'required|min:5|max:255',
            'description'      => 'max:500',
        ];
        // Mot de passe uniquement à la création (jamais remplacé par l'admin en modification)
        if ($existing === null && $motDePasse) {
            $rules['password'] = 'required|password';
            $rules['password_confirm'] = 'required|match:password';
        }
        if ($public) {
            $rules['terms'] = 'accepted';
        }
        $errors = $this->validateWithMessages($post, $rules);

        $matricule = $this->normalizeMatricule(self::text($post, 'matricule_fiscal'));
        if (!isset($errors['matricule_fiscal']) && !preg_match('#' . self::MATRICULE_REGEX . '#', $matricule)) {
            $errors['matricule_fiscal'] = self::message('matricule_fiscal', 'matricule');
        }

        if (!isset($errors['secteur_activite']) && !in_array(self::text($post, 'secteur_activite'), self::SECTEURS, true)) {
            $errors['secteur_activite'] = self::message('secteur_activite', 'required');
        }

        if (!isset($errors['id_zone']) && !(new ZoneController())->exists((int) self::text($post, 'id_zone'))) {
            $errors['id_zone'] = self::message('id_zone', 'required');
        }

        $currentId = $existing?->getIdUtilisateur() ?? 0;
        $email = mb_strtolower(self::text($post, 'email'));
        if (!isset($errors['email']) && $this->isTaken('email', $email, $currentId)) {
            $errors['email'] = 'Un compte existe déjà avec cette adresse e-mail.';
        }
        if (!isset($errors['matricule_fiscal']) && $this->isTaken('matricule_fiscal', $matricule, $currentId)) {
            $errors['matricule_fiscal'] = 'Ce matricule fiscal est déjà enregistré.';
        }
        return $errors;
    }

    /** @param 'email'|'matricule_fiscal' $column colonne fixe (jamais saisie par l'utilisateur) */
    private function isTaken(string $column, string $value, int $exceptId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM utilisateur WHERE $column = :v AND id_utilisateur <> :id LIMIT 1");
        $stmt->execute(['v' => $value, 'id' => $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Deux envois simultanés passent tous les deux isTaken(), puis MySQL refuse le second (erreur 1062) :
     * on affiche alors le message sous le bon champ au lieu d'une erreur fatale.
     *
     * @return array<string, string>
     */
    private function duplicateErrors(PDOException $e): array
    {
        if (!self::isDuplicate($e)) {
            throw $e;
        }
        return str_contains($e->getMessage(), 'uq_utilisateur_matricule')
            ? ['matricule_fiscal' => 'Ce matricule fiscal est déjà enregistré.']
            : ['email' => 'Un compte existe déjà avec cette adresse e-mail.'];
    }

    private function normalizeMatricule(string $value): string
    {
        return mb_strtoupper(preg_replace('/\s+/', '', $value) ?? '');
    }

    /** @return array<string, mixed> paramètres communs à l'INSERT et aux UPDATE (valeurs déjà validées) */
    private function commonParams(array $post): array
    {
        $description = self::text($post, 'description');
        return [
            'nom'              => self::text($post, 'nom'),
            'prenom'           => self::text($post, 'prenom'),
            'email'            => mb_strtolower(self::text($post, 'email')),
            'telephone'        => preg_replace('/[\s.-]/u', '', self::text($post, 'telephone')),
            'id_zone'          => (int) self::text($post, 'id_zone'),
            'adresse'          => self::text($post, 'adresse'),
            'nom_organisation' => self::text($post, 'nom_organisation'),
            'matricule_fiscal' => $this->normalizeMatricule(self::text($post, 'matricule_fiscal')),
            'secteur_activite' => self::text($post, 'secteur_activite'),
            'description'      => $description === '' ? null : $description,
        ];
    }

    /** @return array<string, string> erreurs (doublon simultané) ou tableau vide */
    private function insert(array $post, bool $verifie): array
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO utilisateur (nom, prenom, email, telephone, mot_de_passe, role, statut_compte, id_zone, adresse,
                                          nom_organisation, matricule_fiscal, secteur_activite, description, compte_verifie)
                 VALUES (:nom, :prenom, :email, :telephone, :mot_de_passe, 'entreprise', 'actif', :id_zone, :adresse,
                         :nom_organisation, :matricule_fiscal, :secteur_activite, :description, :verifie)"
            );
            $stmt->execute($this->commonParams($post) + [
                'mot_de_passe' => password_hash(self::raw($post, 'password'), PASSWORD_DEFAULT),  // bcrypt
                'verifie'      => $verifie ? 1 : 0,   // rôle FORCÉ à « entreprise » dans la requête
            ]);
        } catch (PDOException $e) {
            return $this->duplicateErrors($e);
        }
        return [];
    }
}
