<?php
/**
 * controllers/CompteController.php — Module 1 (Houssem) : comptes sous-admins STEG et SONEDE
 *
 * CRUD réservé au super admin (requireRole(['admin']) dans chaque route) :
 *   create()   ajout d'un sous-admin (rôle choisi ici entre steg et sonede, mot de passe haché)
 *   getAll()   liste            getById()  fiche
 *   update()   nom, prénom, e-mail, téléphone, gouvernorat, organisation (jamais le rôle)
 *   setStatut() suspendre / réactiver
 *   delete()   suppression sûre : uniquement un compte sans aucune donnée liée
 *
 * Toutes les requêtes filtrent « AND role IN ('steg','sonede') » : avec l'id d'un citoyen,
 * d'une entreprise ou de l'admin, la réponse est « introuvable » et rien ne change.
 * Les comptes entreprise (Module 3), fournisseur (Module 4) et citoyen (Module 2) ne sont pas gérés ici.
 *
 * Profil du personnel du Back Office (admin, steg, sonede) : updateProfil() et changePassword().
 * L'id vient TOUJOURS de la session ; le rôle et le statut ne viennent jamais du formulaire.
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/FormMessages.php';
require_once __DIR__ . '/../models/UserModel.php';

class CompteController extends Controller
{
    use FormMessages;

    /** Les seuls rôles gérés par ce contrôleur. */
    public const ROLES = ['steg', 'sonede'];

    /** Statuts possibles (ENUM de la base commune). */
    public const STATUTS = ['actif', 'suspendu'];

    /** Messages des champs : les mêmes dans la vue (data-message-*) et ici (withMessages). */
    public const MESSAGES = [
        'prenom'               => ['required' => 'Indiquez le prénom.'],
        'nom'                  => ['required' => 'Indiquez le nom.'],
        'email'                => ['required' => "Indiquez l'adresse e-mail."],
        'id_zone'              => ['required' => 'Sélectionnez le gouvernorat.'],
        'role'                 => ['required' => 'Choisissez STEG ou SONEDE.', 'in' => 'Choisissez STEG ou SONEDE.'],
        'mot_de_passe'         => ['required' => 'Choisissez un mot de passe.'],
        'confirmation'         => ['required' => 'Confirmez le mot de passe.', 'match' => 'Les deux mots de passe ne correspondent pas.'],
        'mot_de_passe_actuel'  => ['required' => 'Indiquez votre mot de passe actuel.'],
        'nouveau_mot_de_passe' => ['required' => 'Choisissez le nouveau mot de passe.'],
    ];

    /**
     * Les 16 clés étrangères qui pointent vers utilisateur (table => colonne).
     * Un compte qui apparaît dans l'une d'elles n'est jamais supprimé : on le suspend.
     */
    private const LIENS = [
        ['notification',         'id_utilisateur', 'notification(s)'],
        ['interruption',         'id_auteur',      'interruption(s) publiée(s)'],
        ['signalement',          'id_utilisateur', 'signalement(s)'],
        ['confirmation',         'id_utilisateur', 'confirmation(s)'],
        ['alerte',               'id_auteur',      'alerte(s) publiée(s)'],
        ['lieu_suivi',           'id_utilisateur', 'lieu(x) suivi(s)'],
        ['preference_alerte',    'id_utilisateur', "préférence(s) d'alerte"],
        ['facture',              'id_utilisateur', 'facture(s)'],
        ['consommation',         'id_utilisateur', 'consommation(s)'],
        ['refuge_zone',          'id_entreprise',  'refuge(s)'],
        ['reservation_refuge',   'id_utilisateur', 'réservation(s)'],
        ['installation_solaire', 'id_utilisateur', 'installation(s) solaire(s)'],
        ['demande_assistance',   'id_utilisateur', "demande(s) d'assistance"],
        ['demande_assistance',   'id_fournisseur', 'intervention(s) de fournisseur'],
        ['reponse',              'id_utilisateur', 'réponse(s)'],
        ['personne_prioritaire', 'id_utilisateur', 'personne(s) prioritaire(s)'],
    ];

    private const SELECT = "SELECT u.*, z.nom_zone FROM utilisateur u LEFT JOIN zone z ON z.id_zone = u.id_zone";

    // ==============================================================
    // READ
    // ==============================================================

    /** @return UserModel[] tous les sous-admins, les plus récents d'abord */
    public function getAll(): array
    {
        $stmt = $this->db->query(self::SELECT . " WHERE u.role IN ('steg','sonede')
                                               ORDER BY u.date_inscription DESC, u.id_utilisateur DESC");
        return array_map(fn (array $row) => UserModel::fromArray($row), $stmt->fetchAll());
    }

    /** Un sous-admin, ou null (id inconnu OU compte d'un autre rôle). */
    public function getById(int $id): ?UserModel
    {
        $stmt = $this->db->prepare(self::SELECT . " WHERE u.id_utilisateur = :id AND u.role IN ('steg','sonede')");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? UserModel::fromArray($row) : null;
    }

    /** @return array<string, int> nombre de sous-admins par statut */
    public function countByStatut(): array
    {
        $counts = ['actif' => 0, 'suspendu' => 0];
        $stmt = $this->db->query("SELECT statut_compte, COUNT(*) AS n FROM utilisateur
                                   WHERE role IN ('steg','sonede') GROUP BY statut_compte");
        foreach ($stmt as $row) {
            $counts[$row['statut_compte']] = (int) $row['n'];
        }
        return $counts;
    }

    /**
     * Données liées à un compte (vide = le compte peut être supprimé).
     *
     * @return string[] ex. ['2 interruption(s) publiée(s)']
     */
    public function donneesLiees(int $id): array
    {
        $found = [];
        foreach (self::LIENS as [$table, $column, $label]) {
            // Noms de table et de colonne pris dans la constante ci-dessus (jamais dans la saisie)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = :id");
            $stmt->execute(['id' => $id]);
            if ($n = (int) $stmt->fetchColumn()) {
                $found[] = $n . ' ' . $label;
            }
        }
        return $found;
    }

    // ==============================================================
    // CREATE
    // ==============================================================

    /**
     * Ajoute un sous-admin. Rôle limité à steg / sonede, statut « actif », mot de passe haché.
     *
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = enregistré)
     */
    public function create(array $post): array
    {
        $errors = $this->checkIdentite($post, [
            'role'         => 'required|in:' . implode(',', self::ROLES),
            'mot_de_passe' => 'required|password',
            'confirmation' => 'required|match:mot_de_passe',
        ]);
        if ($errors) {
            return $errors;
        }

        $data = $this->identite($post);
        $data['role'] = (string) $post['role'];
        $data['mdp']  = password_hash((string) $post['mot_de_passe'], PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO utilisateur (nom, prenom, email, telephone, mot_de_passe, role, statut_compte, id_zone, nom_organisation)
                 VALUES (:nom, :prenom, :email, :telephone, :mdp, :role, 'actif', :id_zone, :organisation)"
            );
            $stmt->execute($data);
        } catch (PDOException $e) {
            return $this->duplicate($e);
        }
        return [];
    }

    // ==============================================================
    // UPDATE
    // ==============================================================

    /**
     * Modifie l'identité d'un sous-admin. Le rôle, le statut et le mot de passe ne sont pas modifiables ici.
     *
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    public function update(UserModel $compte, array $post): array
    {
        $errors = $this->checkIdentite($post, [], $compte->getIdUtilisateur());
        if ($errors) {
            return $errors;
        }
        $data = $this->identite($post);
        $data['id'] = $compte->getIdUtilisateur();

        try {
            $stmt = $this->db->prepare(
                "UPDATE utilisateur SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone,
                        id_zone = :id_zone, nom_organisation = :organisation
                  WHERE id_utilisateur = :id AND role IN ('steg','sonede')"
            );
            $stmt->execute($data);
        } catch (PDOException $e) {
            return $this->duplicate($e);
        }
        return [];
    }

    /** Suspend ou réactive un sous-admin. Retourne un message d'erreur ou ''. */
    public function setStatut(UserModel $compte, string $statut): string
    {
        if (!in_array($statut, self::STATUTS, true)) {
            return 'Statut non autorisé.';
        }
        $stmt = $this->db->prepare(
            "UPDATE utilisateur SET statut_compte = :statut WHERE id_utilisateur = :id AND role IN ('steg','sonede')"
        );
        $stmt->execute(['statut' => $statut, 'id' => $compte->getIdUtilisateur()]);
        return '';
    }

    // ==============================================================
    // DELETE (suppression sûre)
    // ==============================================================

    /**
     * Supprime un sous-admin SEULEMENT s'il n'a aucune donnée liée (sinon : le suspendre).
     * Verrou FOR UPDATE dans une transaction : personne ne peut lier une donnée au compte pendant le contrôle.
     *
     * @return string|null message d'erreur, null = supprimé
     */
    public function delete(int $id): ?string
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id_utilisateur FROM utilisateur
                WHERE id_utilisateur = :id AND role IN ('steg','sonede') FOR UPDATE");
            $stmt->execute(['id' => $id]);
            if ($stmt->fetchAll() === []) {
                $erreur = 'Compte introuvable.';
            } elseif (($liens = $this->donneesLiees($id)) !== []) {
                $erreur = 'Ce compte a des données (' . implode(', ', $liens) . ') : suspendez-le plutôt.';
            } else {
                $del = $this->db->prepare("DELETE FROM utilisateur
                    WHERE id_utilisateur = :id AND role IN ('steg','sonede')");
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

    // ==============================================================
    // PROFIL DU PERSONNEL (admin, steg, sonede) — id pris dans la session
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    public function updateProfil(UserModel $moi, array $post): array
    {
        $errors = $this->checkIdentite($post, [], $moi->getIdUtilisateur(), false);
        if ($errors) {
            return $errors;
        }
        $data = $this->identite($post);
        unset($data['organisation']);
        $data['id'] = $moi->getIdUtilisateur();

        try {
            $stmt = $this->db->prepare(
                "UPDATE utilisateur SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, id_zone = :id_zone
                  WHERE id_utilisateur = :id"
            );
            $stmt->execute($data);
        } catch (PDOException $e) {
            return $this->duplicate($e);
        }
        return [];
    }

    /**
     * Change son propre mot de passe : l'actuel est vérifié, le nouveau est haché,
     * puis l'identifiant de session est renouvelé.
     *
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    public function changePassword(UserModel $moi, array $post): array
    {
        $errors = $this->withMessages($this->validate($post, [
            'mot_de_passe_actuel'  => 'required',
            'nouveau_mot_de_passe' => 'required|password',
            'confirmation'         => 'required|match:nouveau_mot_de_passe',
        ]));
        if ($errors) {
            return $errors;
        }
        if (!password_verify((string) $post['mot_de_passe_actuel'], $moi->getMotDePasse())) {
            return ['mot_de_passe_actuel' => 'Mot de passe actuel incorrect.'];
        }
        if (password_verify((string) $post['nouveau_mot_de_passe'], $moi->getMotDePasse())) {
            return ['nouveau_mot_de_passe' => "Le nouveau mot de passe doit être différent de l'actuel."];
        }

        $hache = password_hash((string) $post['nouveau_mot_de_passe'], PASSWORD_DEFAULT);
        $stmt  = $this->db->prepare('UPDATE utilisateur SET mot_de_passe = :mdp WHERE id_utilisateur = :id');
        $stmt->execute(['mdp' => $hache, 'id' => $moi->getIdUtilisateur()]);
        session_regenerate_id(true);
        // Empreinte utilisée par la branche commune pour fermer les AUTRES sessions après un changement de mot de passe :
        // on la met à jour pour que la session en cours reste connectée.
        $_SESSION['mdp_empreinte'] = hash('sha256', $hache);
        return [];
    }

    // ==============================================================
    // CONTRÔLE DE SAISIE (mêmes règles que les data-rules des vues)
    // ==============================================================

    /**
     * Identité commune (ajout, modification, profil) + règles supplémentaires.
     *
     * @param array<string, mixed>  $post
     * @param array<string, string> $extra
     * @return array<string, string>
     */
    private function checkIdentite(array $post, array $extra, ?int $exceptId = null, bool $withOrganisation = true): array
    {
        $rules = [
            'prenom'    => 'required|name',
            'nom'       => 'required|name',
            'email'     => 'required|email',
            'telephone' => 'phone',
            'id_zone'   => 'required|int',
        ];
        if ($withOrganisation) {
            $rules['nom_organisation'] = 'max:100';
        }
        $errors = $this->withMessages($this->validate($post, $rules + $extra));

        // Seul un gouvernorat est accepté (pas une délégation), comme à l'inscription commune
        $gouvernorats = array_map(fn (ZoneModel $z) => $z->getIdZone(), (new ZoneController())->getGouvernorats());
        if (!isset($errors['id_zone']) && !in_array((int) $post['id_zone'], $gouvernorats, true)) {
            $errors['id_zone'] = 'Gouvernorat inconnu.';
        }
        if (!isset($errors['email']) && $this->emailPris(trim((string) $post['email']), $exceptId)) {
            $errors['email'] = 'Cette adresse e-mail est déjà utilisée.';
        }
        return $errors;
    }

    /** @param array<string, mixed> $post  @return array<string, mixed> paramètres SQL de l'identité */
    private function identite(array $post): array
    {
        // Une valeur reçue sous forme de liste devient '' (déjà refusée par validate() si le champ est requis)
        $s   = static fn (string $k): string => is_string($post[$k] ?? null) ? trim($post[$k]) : '';
        $tel = preg_replace('/[\s.-]/u', '', $s('telephone')) ?? '';
        $org = $s('nom_organisation');
        return [
            'nom'          => $s('nom'),
            'prenom'       => $s('prenom'),
            'email'        => mb_strtolower($s('email')),
            'telephone'    => $tel !== '' ? $tel : null,
            'id_zone'      => (int) $s('id_zone'),
            'organisation' => $org !== '' ? $org : null,
        ];
    }

    private function emailPris(string $email, ?int $exceptId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM utilisateur WHERE email = :email AND id_utilisateur <> :id');
        $stmt->execute(['email' => mb_strtolower($email), 'id' => $exceptId ?? 0]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Deux envois simultanés avec le même e-mail : MySQL refuse le second (erreur 1062).
     * On affiche le message sous le champ au lieu d'une erreur fatale.
     *
     * @return array<string, string>
     */
    private function duplicate(PDOException $e): array
    {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['email' => 'Cette adresse e-mail est déjà utilisée.'];
        }
        throw $e;
    }
}
