<?php
/**
 * controllers/DemandeAssistanceController.php — Module 4 (Bechir)
 *
 * CRUD des demandes d'assistance (table demande_assistance) et de leurs réponses (table reponse).
 *
 *  - Relais de voisinage : publication sans id_fournisseur, sens « demande » ou « offre ».
 *  - Fournisseurs        : demande d'intervention adressée à un utilisateur de rôle « fournisseur »
 *                          (id_fournisseur rempli ; le rôle est vérifié ici, la base ne peut pas le faire).
 *  - Réponses            : un bénévole, un fournisseur ou un propriétaire solaire répond à une demande ;
 *                          l'auteur de la demande accepte ou refuse la réponse.
 *
 * Tout le SQL est ici (requêtes préparées) ; les modèles ne contiennent que des données.
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/ZoneController.php';
require_once __DIR__ . '/../models/DemandeAssistanceModel.php';
require_once __DIR__ . '/../models/ReponseModel.php';
require_once __DIR__ . '/../models/UserModel.php';

class DemandeAssistanceController extends Controller
{
    /** Rôles qui peuvent répondre à une DEMANDE d'aide du Relais. */
    public const HELPER_ROLES = ['benevole', 'fournisseur', 'proprietaire'];

    /** Rôles du Front Office qui peuvent publier sur le Relais ou contacter un fournisseur. */
    public const FRONT_ROLES = ['citoyen', 'benevole', 'fournisseur', 'proprietaire', 'entreprise'];

    /** Statuts encore « actifs » (une réponse est encore possible). */
    private const OPEN_STATUTS = ['ouverte', 'prise_en_charge'];

    /** Requête de base : la demande + sa zone + son auteur + le fournisseur visé + le nombre de réponses. */
    private const SELECT =
        'SELECT d.*, z.nom_zone, a.prenom AS auteur_prenom, a.nom AS auteur_nom,
                f.nom_organisation AS fournisseur_nom,
                (SELECT COUNT(*) FROM reponse r WHERE r.id_demande = d.id_demande) AS nb_reponses
           FROM demande_assistance d
           JOIN zone z        ON z.id_zone = d.id_zone
           JOIN utilisateur a ON a.id_utilisateur = d.id_utilisateur
      LEFT JOIN utilisateur f ON f.id_utilisateur = d.id_fournisseur';

    // ==============================================================
    // LECTURE
    // ==============================================================

    public function findById(int $id): ?DemandeAssistanceModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE d.id_demande = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? DemandeAssistanceModel::fromArray($row) : null;
    }

    /**
     * Fil du Relais de voisinage (publications sans fournisseur).
     *
     * @param array<string, mixed> $filters tab (all|demande|offre|mine), categorie, id_zone, q, resolues, tri (recent|urgent)
     * @return DemandeAssistanceModel[]
     */
    public function getRelais(array $filters, ?int $idUser): array
    {
        $where = ['d.id_fournisseur IS NULL'];
        $params = [];

        $tab = (string) ($filters['tab'] ?? 'all');
        if ($tab === 'mine' && $idUser !== null) {
            // « Mes publications » inclut aussi mes signalements Priorité santé (visibles de moi seul)
            $where[] = 'd.id_utilisateur = :me';
            $params['me'] = $idUser;
        } else {
            // Les signalements Priorité santé ne sont jamais publics : ils ne sont traités qu'au Back Office
            $where[] = "d.categorie <> 'sante'";
            if (in_array($tab, ['demande', 'offre'], true)) {
                $where[] = 'd.sens = :sens';
                $params['sens'] = $tab;
            }
            // Les demandes résolues ne sont affichées que sur demande ; les annulées jamais (sauf « Mes publications »)
            $where[] = !empty($filters['resolues'])
                ? "d.statut <> 'annulee'"
                : "d.statut IN ('ouverte', 'prise_en_charge')";
        }

        $this->applyCommonFilters($filters, $where, $params);

        $order = ($filters['tri'] ?? '') === 'urgent'
            ? "FIELD(d.statut, 'ouverte', 'prise_en_charge', 'resolue', 'annulee'), FIELD(d.urgence, 'haute', 'moyenne', 'faible'), d.date_creation DESC"
            : 'd.date_creation DESC, d.id_demande DESC';

        $stmt = $this->db->prepare(self::SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order);
        $stmt->execute($params);
        return array_map(fn (array $row) => DemandeAssistanceModel::fromArray($row), $stmt->fetchAll());
    }

    /** @return array{all: int, demande: int, offre: int, mine: int} compteurs des onglets du Relais */
    public function countRelais(?int $idUser): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                SUM(categorie <> 'sante' AND statut IN ('ouverte', 'prise_en_charge'))                      AS nb_all,
                SUM(categorie <> 'sante' AND statut IN ('ouverte', 'prise_en_charge') AND sens = 'demande') AS nb_demande,
                SUM(categorie <> 'sante' AND statut IN ('ouverte', 'prise_en_charge') AND sens = 'offre')   AS nb_offre,
                SUM(id_utilisateur = :me)                                                                   AS nb_mine
               FROM demande_assistance WHERE id_fournisseur IS NULL"
        );
        $stmt->execute(['me' => $idUser ?? 0]);
        $row = $stmt->fetch() ?: [];
        return [
            'all'     => (int) ($row['nb_all'] ?? 0),
            'demande' => (int) ($row['nb_demande'] ?? 0),
            'offre'   => (int) ($row['nb_offre'] ?? 0),
            'mine'    => (int) ($row['nb_mine'] ?? 0),
        ];
    }

    /** @return DemandeAssistanceModel[] demandes d'intervention envoyées PAR cet utilisateur à des fournisseurs */
    public function getEnvoyeesAuxFournisseurs(int $idUser): array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE d.id_fournisseur IS NOT NULL AND d.id_utilisateur = :me ORDER BY d.date_creation DESC');
        $stmt->execute(['me' => $idUser]);
        return array_map(fn (array $row) => DemandeAssistanceModel::fromArray($row), $stmt->fetchAll());
    }

    /** @return DemandeAssistanceModel[] demandes d'intervention REÇUES par ce fournisseur */
    public function getRecuesParFournisseur(int $idFournisseur): array
    {
        $stmt = $this->db->prepare(
            self::SELECT . " WHERE d.id_fournisseur = :f
             ORDER BY FIELD(d.statut, 'ouverte', 'prise_en_charge', 'resolue', 'annulee'), d.date_creation DESC"
        );
        $stmt->execute(['f' => $idFournisseur]);
        return array_map(fn (array $row) => DemandeAssistanceModel::fromArray($row), $stmt->fetchAll());
    }

    /**
     * Toutes les demandes (Back Office).
     *
     * @param array<string, mixed> $filters type (relais|fournisseur|sante), statut, categorie, id_zone, q
     * @return DemandeAssistanceModel[]
     */
    public function getAll(array $filters = []): array
    {
        $where = ['1 = 1'];
        $params = [];

        $type = (string) ($filters['type'] ?? '');
        if ($type === 'relais') {
            $where[] = "d.id_fournisseur IS NULL AND d.categorie <> 'sante'";
        } elseif ($type === 'sante') {
            $where[] = "d.categorie = 'sante'";
        } elseif ($type === 'fournisseur') {
            $where[] = 'd.id_fournisseur IS NOT NULL';
        }
        $statut = (string) ($filters['statut'] ?? '');
        if (isset(DemandeAssistanceModel::STATUTS[$statut])) {
            $where[] = 'd.statut = :statut';
            $params['statut'] = $statut;
        }
        $this->applyCommonFilters($filters, $where, $params);

        $stmt = $this->db->prepare(self::SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY d.date_creation DESC, d.id_demande DESC');
        $stmt->execute($params);
        return array_map(fn (array $row) => DemandeAssistanceModel::fromArray($row), $stmt->fetchAll());
    }

    /** @return array<string, int> statut => nombre (tous les statuts, même à 0) */
    public function countByStatut(): array
    {
        $counts = array_fill_keys(array_keys(DemandeAssistanceModel::STATUTS), 0);
        foreach ($this->db->query('SELECT statut, COUNT(*) AS n FROM demande_assistance GROUP BY statut') as $row) {
            $counts[$row['statut']] = (int) $row['n'];
        }
        return $counts;
    }

    /**
     * Filtres communs : catégorie, gouvernorat, recherche texte.
     *
     * @param array<string, mixed> $filters
     * @param string[]             $where
     * @param array<string, mixed> $params
     */
    private function applyCommonFilters(array $filters, array &$where, array &$params): void
    {
        $categorie = (string) ($filters['categorie'] ?? '');
        if (isset(DemandeAssistanceModel::TOUTES_CATEGORIES[$categorie])) {
            $where[] = 'd.categorie = :categorie';
            $params['categorie'] = $categorie;
        }
        $idZone = (int) ($filters['id_zone'] ?? 0);
        if ($idZone > 0) {
            $where[] = 'd.id_zone = :zone';
            $params['zone'] = $idZone;
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // Vraies requêtes préparées : un même marqueur ne peut pas être utilisé deux fois
            $where[] = '(d.titre LIKE :q1 OR d.description LIKE :q2 OR d.quartier LIKE :q3)';
            $like = '%' . addcslashes(mb_substr($q, 0, 60), '%_\\') . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }
    }

    // ==============================================================
    // CRÉATION / MODIFICATION / SUPPRESSION
    // ==============================================================

    /**
     * Nouvelle publication du Relais de voisinage.
     *
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = enregistrée)
     */
    public function createRelais(array $post, UserModel $user): array
    {
        if (!in_array($user->getRole(), self::FRONT_ROLES, true)) {
            return ['form' => "Votre compte ne permet pas de publier sur le Relais de voisinage."];
        }
        $demande = new DemandeAssistanceModel();
        $errors = $this->fillRelais($demande, $post, true);
        if ($errors !== []) {
            return $errors;
        }
        $demande->setIdUtilisateur($user->getIdUtilisateur());
        $demande->setStatut('ouverte');
        $this->insert($demande);
        return [];
    }

    /**
     * Demande d'intervention adressée à un fournisseur.
     *
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = enregistrée)
     */
    public function createDemandeFournisseur(array $post, UserModel $user): array
    {
        if (!in_array($user->getRole(), self::FRONT_ROLES, true)) {
            return ['form' => "Votre compte ne permet pas d'envoyer une demande d'intervention."];
        }
        $demande = new DemandeAssistanceModel();
        $errors = $this->fillFournisseur($demande, $post, true);
        if (!isset($errors['id_fournisseur']) && $demande->getIdFournisseur() === $user->getIdUtilisateur()) {
            $errors['id_fournisseur'] = 'Vous ne pouvez pas vous adresser une demande à vous-même.';
        }
        if ($errors !== []) {
            return $errors;
        }
        $demande->setIdUtilisateur($user->getIdUtilisateur());
        $demande->setSens('demande');
        $demande->setStatut('ouverte');
        $this->insert($demande);

        $this->notify(
            (int) $demande->getIdFournisseur(),
            "Nouvelle demande d'intervention",
            $user->getPrenom() . ' vous a envoyé une demande : « ' . $demande->getTitre() . ' ».',
            url('fournisseurs', [], 'demandes-recues')
        );
        return [];
    }

    // --------------------------------------------------------------
    // PRIORITÉ SANTÉ : chaque enregistrement devient une demande d'assistance
    // (catégorie « sante ») traitée par l'équipe du Back Office.
    // --------------------------------------------------------------

    private const SANTE_BESOINS = [
        'dialyse'     => 'Dialyse à domicile',
        'oxygene'     => 'Oxygénothérapie / concentrateur',
        'respirateur' => 'Respirateur ou appareil vital électrique',
        'froid'       => 'Médicaments à conserver au froid',
        'mobilite'    => 'Mobilité réduite (étage, ascenseur)',
        'autre'       => 'Autre besoin vital',
    ];
    private const SANTE_RESSOURCES = ['eau' => 'Eau', 'electricite' => 'Électricité', 'both' => 'Eau et électricité'];
    private const SANTE_PRIORITES = ['critique' => 'Critique', 'elevee' => 'Élevée', 'moderee' => 'Modérée'];
    /** Priorité santé → urgence de la demande d'assistance. */
    private const SANTE_URGENCES = ['critique' => 'haute', 'elevee' => 'moyenne', 'moderee' => 'faible'];
    /** Noms du référentiel JS qui diffèrent de la table zone. */
    private const GOUVERNORAT_ALIAS = ['Manouba' => 'La Manouba', 'El Kef' => 'Le Kef'];

    public static function santeRules(): array
    {
        return [
            'forWhom'        => 'required|in:moi,proche',
            'firstName'      => 'required|name',
            'lastName'       => 'required|name',
            'birthYear'      => 'required|int',
            'relation'       => 'max:50',
            'phone'          => 'required|phone',
            'email'          => 'email',
            'gov'            => 'required|max:60',
            'area'           => 'required|min:2|max:60',
            'address'        => 'required|min:6|max:160',
            'need'           => 'required|in:' . implode(',', array_keys(self::SANTE_BESOINS)),
            'sessions'       => 'int',
            'center'         => 'max:80',
            'autonomy'       => 'decimal',
            'device'         => 'max:80',
            'medication'     => 'max:80',
            'coldHours'      => 'int',
            'floor'          => 'int',
            'lift'           => 'in:oui,non',
            'otherNeed'      => 'max:100',
            'resource'       => 'required|in:' . implode(',', array_keys(self::SANTE_RESSOURCES)),
            'description'    => 'required|min:20|max:600',
            'priority'       => 'required|in:' . implode(',', array_keys(self::SANTE_PRIORITES)),
            'caregiverName'  => 'required|max:80',
            'caregiverPhone' => 'required|phone',
            'consent'        => 'accepted',
        ];
    }

    /**
     * Enregistre (ou met à jour, si $existing est fourni) un signalement Priorité santé.
     *
     * @param array<string, mixed> $post
     * @return array{errors: array<string, string>, id: int}
     */
    public function saveSante(array $post, UserModel $user, ?DemandeAssistanceModel $existing = null): array
    {
        if (!in_array($user->getRole(), self::FRONT_ROLES, true)) {
            return ['errors' => ['form' => "Votre compte ne permet pas d'enregistrer une personne prioritaire."], 'id' => 0];
        }
        if ($existing !== null && (!$this->isOwner($existing, $user)
                || $existing->getCategorie() !== DemandeAssistanceModel::CATEGORIE_SANTE)) {
            return ['errors' => ['form' => 'Demande introuvable.'], 'id' => 0];
        }
        if ($existing !== null && $existing->getStatut() !== 'ouverte') {
            return ['errors' => ['form' => "Cette demande est déjà traitée par l'équipe : elle ne peut plus être modifiée."], 'id' => 0];
        }

        $errors = $this->validate($post, self::santeRules());
        $v = fn (string $k): string => trim((string) ($post[$k] ?? ''));
        $year = (int) $v('birthYear');
        if (!isset($errors['birthYear']) && ($year < 1900 || $year > (int) date('Y'))) {
            $errors['birthYear'] = 'Année de naissance invalide.';
        }
        if ($v('forWhom') === 'proche' && $v('relation') === '') {
            $errors['relation'] = 'Précisez votre lien.';
        }
        $required = ['dialyse' => 'sessions', 'oxygene' => 'autonomy', 'respirateur' => 'autonomy',
                     'froid' => 'medication', 'mobilite' => 'floor', 'autre' => 'otherNeed'];
        $field = $required[$v('need')] ?? null;
        if ($field !== null && !isset($errors[$field]) && $v($field) === '') {
            $errors[$field] = 'Ce champ est obligatoire.';
        }
        $gov = self::GOUVERNORAT_ALIAS[$v('gov')] ?? $v('gov');
        $idZone = 0;
        if (!isset($errors['gov'])) {
            $stmt = $this->db->prepare("SELECT id_zone FROM zone WHERE type_zone = 'gouvernorat' AND nom_zone = :n LIMIT 1");
            $stmt->execute(['n' => $gov]);
            $idZone = (int) $stmt->fetchColumn();
            if ($idZone === 0) {
                $errors['gov'] = 'Veuillez choisir un gouvernorat.';
            }
        }
        if ($errors !== []) {
            return ['errors' => $errors, 'id' => 0];
        }

        $need = $v('need');
        $details = match ($need) {
            'dialyse'                => $v('sessions') . ' séance(s) par semaine' . ($v('center') !== '' ? ' — centre : ' . $v('center') : ''),
            'oxygene', 'respirateur' => 'Autonomie batterie : ' . $v('autonomy') . ' h' . ($v('device') !== '' ? ' — appareil : ' . $v('device') : ''),
            'froid'                  => 'Traitement : ' . $v('medication') . ($v('coldHours') !== '' ? ' — tient ' . $v('coldHours') . ' h sans froid' : ''),
            'mobilite'               => 'Étage ' . $v('floor') . ' — ascenseur : ' . ($v('lift') === 'oui' ? 'oui' : 'non'),
            default                  => $v('otherNeed'),
        };
        $lines = [
            'Signalement Priorité santé (personne prioritaire)',
            '',
            'Personne : ' . $v('firstName') . ' ' . $v('lastName') . ', née en ' . $year
                . ($v('forWhom') === 'proche' ? ' — déclarée par un proche (' . $v('relation') . ')' : ' — déclarée par elle-même'),
            'Téléphone : ' . $v('phone') . ($v('email') !== '' ? ' — e-mail : ' . $v('email') : ''),
            'Adresse : ' . $v('address') . ', ' . $v('area') . ', ' . $gov,
            'Besoin : ' . self::SANTE_BESOINS[$need] . ' — ' . $details,
            'Ressource indispensable : ' . self::SANTE_RESSOURCES[$v('resource')],
            'Priorité déclarée : ' . self::SANTE_PRIORITES[$v('priority')],
            'Aidant à prévenir : ' . $v('caregiverName') . ' — ' . $v('caregiverPhone'),
            '',
            'Situation :',
            $v('description'),
        ];

        $d = $existing ?? new DemandeAssistanceModel();
        $d->setSens('demande');
        $d->setCategorie(DemandeAssistanceModel::CATEGORIE_SANTE);
        $d->setTitre(mb_substr('Priorité santé — ' . self::SANTE_BESOINS[$need] . ' — '
            . $v('firstName') . ' ' . mb_substr($v('lastName'), 0, 1) . '.', 0, 120));
        $d->setDescription(implode("\n", $lines));
        $d->setIdZone($idZone);
        $d->setQuartier(mb_substr($v('area'), 0, 100));
        $d->setUrgence(self::SANTE_URGENCES[$v('priority')]);
        $d->setIdFournisseur(null);
        $d->setDateSouhaitee(null);
        $d->setCreneau(null);
        $d->setTelephone(self::normalizePhone($v('phone')));

        if ($existing === null) {
            $d->setIdUtilisateur($user->getIdUtilisateur());
            $d->setStatut('ouverte');
            return ['errors' => [], 'id' => $this->insert($d)];
        }
        $stmt = $this->db->prepare(
            'UPDATE demande_assistance
                SET titre = :titre, description = :description, id_zone = :id_zone, quartier = :quartier,
                    urgence = :urgence, telephone = :telephone
              WHERE id_demande = :id AND id_utilisateur = :me'
        );
        $stmt->execute([
            'titre'       => $d->getTitre(),
            'description' => $d->getDescription(),
            'id_zone'     => $d->getIdZone(),
            'quartier'    => $d->getQuartier(),
            'urgence'     => $d->getUrgence(),
            'telephone'   => $d->getTelephone(),
            'id'          => $d->getIdDemande(),
            'me'          => $user->getIdUtilisateur(),
        ]);
        return ['errors' => [], 'id' => $d->getIdDemande()];
    }

    /**
     * Modification par son auteur (uniquement tant que la demande est ouverte).
     *
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    public function update(DemandeAssistanceModel $demande, array $post, UserModel $user): array
    {
        if (!$this->canEdit($demande, $user)) {
            return ['form' => 'Seules vos demandes encore ouvertes peuvent être modifiées.'];
        }
        $errors = $demande->getIdFournisseur() === null
            ? $this->fillRelais($demande, $post, false)
            : $this->fillFournisseur($demande, $post, false);
        if ($errors !== []) {
            return $errors;
        }

        $stmt = $this->db->prepare(
            'UPDATE demande_assistance
                SET sens = :sens, categorie = :categorie, titre = :titre, description = :description,
                    id_zone = :id_zone, quartier = :quartier, urgence = :urgence,
                    date_souhaitee = :date_souhaitee, creneau = :creneau, telephone = :telephone
              WHERE id_demande = :id AND id_utilisateur = :me'
        );
        $stmt->execute([
            'sens'           => $demande->getSens(),
            'categorie'      => $demande->getCategorie(),
            'titre'          => $demande->getTitre(),
            'description'    => $demande->getDescription(),
            'id_zone'        => $demande->getIdZone(),
            'quartier'       => $demande->getQuartier(),
            'urgence'        => $demande->getUrgence(),
            'date_souhaitee' => $demande->getDateSouhaitee(),
            'creneau'        => $demande->getCreneau(),
            'telephone'      => $demande->getTelephone(),
            'id'             => $demande->getIdDemande(),
            'me'             => $user->getIdUtilisateur(),
        ]);
        return [];
    }

    /** Suppression par son auteur ou par l'administrateur (les réponses partent avec : ON DELETE CASCADE). */
    public function delete(DemandeAssistanceModel $demande, UserModel $user): bool
    {
        if (!$this->canDelete($demande, $user)) {
            return false;
        }
        $stmt = $this->db->prepare('DELETE FROM demande_assistance WHERE id_demande = :id');
        $stmt->execute(['id' => $demande->getIdDemande()]);
        return true;
    }

    private function insert(DemandeAssistanceModel $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO demande_assistance
                (id_utilisateur, sens, categorie, titre, description, id_zone, quartier, urgence,
                 id_fournisseur, date_souhaitee, creneau, telephone, statut)
             VALUES
                (:id_utilisateur, :sens, :categorie, :titre, :description, :id_zone, :quartier, :urgence,
                 :id_fournisseur, :date_souhaitee, :creneau, :telephone, :statut)'
        );
        $stmt->execute([
            'id_utilisateur' => $d->getIdUtilisateur(),
            'sens'           => $d->getSens(),
            'categorie'      => $d->getCategorie(),
            'titre'          => $d->getTitre(),
            'description'    => $d->getDescription(),
            'id_zone'        => $d->getIdZone(),
            'quartier'       => $d->getQuartier(),
            'urgence'        => $d->getUrgence(),
            'id_fournisseur' => $d->getIdFournisseur(),
            'date_souhaitee' => $d->getDateSouhaitee(),
            'creneau'        => $d->getCreneau(),
            'telephone'      => $d->getTelephone(),
            'statut'         => $d->getStatut(),
        ]);
        return (int) $this->db->lastInsertId();
    }

    // ==============================================================
    // VALIDATION (mêmes règles que les data-rules des formulaires)
    // ==============================================================

    /** Règles du formulaire du Relais (reprises telles quelles dans la vue : data-rules). */
    public static function relaisRules(bool $isNew): array
    {
        $rules = [
            'sens'        => 'required|in:' . implode(',', array_keys(DemandeAssistanceModel::SENS)),
            'categorie'   => 'required|in:' . implode(',', array_keys(DemandeAssistanceModel::CATEGORIES)),
            'titre'       => 'required|min:8|max:120',
            'description' => 'required|min:20|max:1000',
            'id_zone'     => 'required|int',
            'quartier'    => 'required|min:2|max:100',
            'urgence'     => 'in:' . implode(',', array_keys(DemandeAssistanceModel::URGENCES)),
            'creneau'     => 'in:' . implode(',', array_keys(DemandeAssistanceModel::CRENEAUX)),
        ];
        if ($isNew) {
            $rules['regles'] = 'accepted';
        }
        return $rules;
    }

    /** Règles du formulaire « Demande d'intervention » (fournisseur). */
    public static function fournisseurRules(bool $isNew): array
    {
        $rules = [
            'id_fournisseur' => 'required|int',
            'categorie'      => 'required|in:' . implode(',', array_keys(DemandeAssistanceModel::CATEGORIES)),
            'titre'          => 'required|min:5|max:120',
            'urgence'        => 'required|in:' . implode(',', array_keys(DemandeAssistanceModel::URGENCES)),
            'description'    => 'required|min:20|max:1000',
            'id_zone'        => 'required|int',
            'quartier'       => 'required|min:2|max:100',
            'date_souhaitee' => 'required|date',
            'creneau'        => 'in:' . implode(',', array_keys(DemandeAssistanceModel::CRENEAUX)),
            'telephone'      => 'required|phone',
        ];
        if ($isNew) {
            $rules['consentement'] = 'accepted';
        }
        return $rules;
    }

    /**
     * Valide $post puis remplit $d (publication du Relais).
     *
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    private function fillRelais(DemandeAssistanceModel $d, array $post, bool $isNew): array
    {
        $errors = $this->validate($post, self::relaisRules($isNew));
        $sens = trim((string) ($post['sens'] ?? ''));

        // Une demande d'aide doit préciser son urgence (une offre n'en a pas besoin)
        if ($sens === 'demande' && !isset($errors['urgence']) && trim((string) ($post['urgence'] ?? '')) === '') {
            $errors['urgence'] = "Indiquez le niveau d'urgence.";
        }
        $this->checkZone($post, $errors);
        $this->checkNoPhone($post, ['titre', 'description', 'quartier'], $errors);

        if ($errors !== []) {
            return $errors;
        }

        $d->setSens($sens);
        $d->setCategorie(trim((string) $post['categorie']));
        $d->setTitre(trim((string) $post['titre']));
        $d->setDescription(trim((string) $post['description']));
        $d->setIdZone((int) $post['id_zone']);
        $d->setQuartier(trim((string) $post['quartier']));
        $d->setUrgence($sens === 'demande' ? trim((string) $post['urgence']) : 'faible');
        $d->setCreneau($sens === 'offre' ? (trim((string) ($post['creneau'] ?? '')) ?: null) : null);
        $d->setIdFournisseur(null);
        $d->setDateSouhaitee(null);
        $d->setTelephone(null);   // jamais de numéro sur le Relais (confidentialité)
        return [];
    }

    /**
     * Valide $post puis remplit $d (demande d'intervention à un fournisseur).
     *
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    private function fillFournisseur(DemandeAssistanceModel $d, array $post, bool $isNew): array
    {
        $rules = self::fournisseurRules($isNew);
        if (!$isNew) {
            unset($rules['id_fournisseur']);   // le fournisseur choisi ne change plus après l'envoi
        }
        $errors = $this->validate($post, $rules);

        if ($isNew && !isset($errors['id_fournisseur'])) {
            $idF = (int) $post['id_fournisseur'];
            if (!$this->isFournisseurDisponible($idF)) {
                $errors['id_fournisseur'] = 'Ce professionnel n\'existe pas ou n\'est pas vérifié.';
            } else {
                $d->setIdFournisseur($idF);
            }
        }
        $date = trim((string) ($post['date_souhaitee'] ?? ''));
        if (!isset($errors['date_souhaitee']) && $date < date('Y-m-d')) {
            $errors['date_souhaitee'] = 'La date souhaitée ne peut pas être passée.';
        }
        $this->checkZone($post, $errors);

        if ($errors !== []) {
            return $errors;
        }

        $d->setCategorie(trim((string) $post['categorie']));
        $d->setTitre(trim((string) $post['titre']));
        $d->setUrgence(trim((string) $post['urgence']));
        $d->setDescription(trim((string) $post['description']));
        $d->setIdZone((int) $post['id_zone']);
        $d->setQuartier(trim((string) $post['quartier']));
        $d->setDateSouhaitee($date);
        $d->setCreneau(trim((string) ($post['creneau'] ?? '')) ?: null);
        $d->setTelephone(self::normalizePhone((string) $post['telephone']));
        return [];
    }

    /** Le fournisseur visé existe, a bien le rôle « fournisseur », est actif et vérifié par l'admin. */
    private function isFournisseurDisponible(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM utilisateur
              WHERE id_utilisateur = :id AND role = 'fournisseur' AND statut_compte = 'actif' AND compte_verifie = 1
              LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    /** @param array<string, string> $errors */
    private function checkZone(array $post, array &$errors): void
    {
        if (!isset($errors['id_zone']) && !(new ZoneController())->exists((int) ($post['id_zone'] ?? 0))) {
            $errors['id_zone'] = 'Veuillez choisir un gouvernorat.';
        }
    }

    /**
     * Relais : aucun numéro de téléphone dans le texte publié (il serait visible de tous).
     *
     * @param string[]              $fields
     * @param array<string, string> $errors
     */
    private function checkNoPhone(array $post, array $fields, array &$errors): void
    {
        foreach ($fields as $field) {
            if (!isset($errors[$field]) && preg_match('/(?:\+216)?(?<!\d)[2-9](?:[\s.-]?\d){7}(?!\d)/', (string) ($post[$field] ?? ''))) {
                $errors[$field] = "N'indiquez pas de numéro de téléphone : il serait visible de tous.";
            }
        }
    }

    public static function normalizePhone(string $phone): ?string
    {
        $clean = preg_replace('/[\s.-]/', '', trim($phone)) ?? '';
        return $clean === '' ? null : $clean;
    }

    // ==============================================================
    // DROITS ET STATUTS
    // ==============================================================

    public function isOwner(DemandeAssistanceModel $d, ?UserModel $u): bool
    {
        return $u !== null && $d->getIdUtilisateur() === $u->getIdUtilisateur();
    }

    public function canEdit(DemandeAssistanceModel $d, ?UserModel $u): bool
    {
        return $this->isOwner($d, $u) && $d->getStatut() === 'ouverte'
            && $d->getCategorie() !== DemandeAssistanceModel::CATEGORIE_SANTE;
    }

    public function canDelete(DemandeAssistanceModel $d, ?UserModel $u): bool
    {
        return $u !== null && ($this->isOwner($d, $u) || $u->getRole() === 'admin');
    }

    /**
     * Statuts vers lesquels $u peut faire passer la demande.
     *   auteur            : ouverte → annulée ; ouverte / prise en charge → résolue ; résolue / annulée → ouverte
     *   fournisseur visé  : ouverte → prise en charge ; prise en charge → résolue ; ouverte → annulée (refus)
     *   administrateur    : tous les statuts
     *
     * @return string[]
     */
    public function allowedStatuts(DemandeAssistanceModel $d, ?UserModel $u): array
    {
        if ($u === null) {
            return [];
        }
        if ($u->getRole() === 'admin') {
            return array_values(array_diff(array_keys(DemandeAssistanceModel::STATUTS), [$d->getStatut()]));
        }
        $current = $d->getStatut();
        $idF = $d->getIdFournisseur();
        if ($this->isOwner($d, $u)) {
            if ($idF === null) {
                return match ($current) {
                    'ouverte'         => ['resolue', 'annulee'],
                    'prise_en_charge' => ['resolue'],
                    default           => ['ouverte'],
                };
            }
            // Demande d'intervention : elle dépend d'un fournisseur encore actif et vérifié
            $dispo = $this->isFournisseurDisponible($idF);
            return match ($current) {
                'ouverte'         => ['resolue', 'annulee'],
                // Fournisseur suspendu / non vérifié en cours d'intervention : l'auteur peut abandonner la demande
                'prise_en_charge' => $dispo ? ['resolue'] : ['resolue', 'annulee'],
                // Rouvrir renvoie la demande au même fournisseur : seulement s'il est encore disponible
                'resolue'         => $dispo ? ['ouverte'] : [],
                // Annulée par l'auteur ou refusée par le fournisseur : définitive (envoyer une nouvelle demande)
                default           => [],
            };
        }
        if ($idF !== null && $idF === $u->getIdUtilisateur()) {
            // Mêmes conditions que pour répondre : compte fournisseur vérifié par l'administrateur
            if ($u->getRole() !== 'fournisseur' || !$u->isCompteVerifie()) {
                return [];
            }
            return match ($current) {
                'ouverte'         => ['prise_en_charge', 'annulee'],
                'prise_en_charge' => ['resolue'],
                default           => [],
            };
        }
        return [];
    }

    /** @return string|null message d'erreur, null si le statut a été changé */
    public function changeStatut(DemandeAssistanceModel $d, string $statut, UserModel $u): ?string
    {
        if (!in_array($statut, $this->allowedStatuts($d, $u), true)) {
            return "Ce changement de statut n'est pas autorisé.";
        }
        $stmt = $this->db->prepare('UPDATE demande_assistance SET statut = :statut WHERE id_demande = :id');
        $stmt->execute(['statut' => $statut, 'id' => $d->getIdDemande()]);

        $isOwner = $this->isOwner($d, $u);

        // Le fournisseur qui accepte, refuse ou termine une intervention prévient le citoyen
        if (!$isOwner && $u->getRole() !== 'admin') {
            $refus = $statut === 'annulee';
            $this->notify(
                $d->getIdUtilisateur(),
                $refus ? 'Votre demande : refusée par le professionnel' : 'Votre demande : ' . DemandeAssistanceModel::STATUTS[$statut],
                $refus
                    ? '« ' . $d->getTitre() . " » a été refusée par le professionnel. Vous pouvez envoyer une nouvelle demande à un autre professionnel."
                    : '« ' . $d->getTitre() . ' » est maintenant : ' . mb_strtolower(DemandeAssistanceModel::STATUTS[$statut]) . '.',
                url('fournisseurs', [], 'mes-demandes')
            );
        }

        // L'auteur qui rouvre ou annule une demande d'intervention prévient le fournisseur visé
        if ($isOwner && $d->getIdFournisseur() !== null && in_array($statut, ['ouverte', 'annulee'], true)) {
            $this->notify(
                $d->getIdFournisseur(),
                $statut === 'ouverte' ? "Demande d'intervention rouverte" : "Demande d'intervention annulée",
                '« ' . $d->getTitre() . ' » a été ' . ($statut === 'ouverte' ? 'rouverte' : 'annulée') . ' par ' . $u->getPrenom() . '.',
                url('fournisseurs', [], 'demandes-recues')
            );
        }
        return null;
    }

    // ==============================================================
    // RÉPONSES
    // ==============================================================

    /**
     * Réponses de plusieurs demandes en une seule requête (fil du Relais).
     *
     * @param int[] $ids
     * @return array<int, ReponseModel[]> id_demande => réponses (plus anciennes d'abord)
     */
    public function getReponsesFor(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT r.*, u.prenom AS auteur_prenom, u.nom AS auteur_nom, u.role AS auteur_role,
                    u.nom_organisation AS auteur_organisation, i.adresse AS installation_adresse
               FROM reponse r
               JOIN utilisateur u ON u.id_utilisateur = r.id_utilisateur
          LEFT JOIN installation_solaire i ON i.id_installation = r.id_installation
              WHERE r.id_demande IN ($placeholders)
              ORDER BY r.date_reponse, r.id_reponse"
        );
        $stmt->execute($ids);
        $grouped = array_fill_keys($ids, []);
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['id_demande']][] = ReponseModel::fromArray($row);
        }
        return $grouped;
    }

    /** @return ReponseModel[] */
    public function getReponses(int $idDemande): array
    {
        return $this->getReponsesFor([$idDemande])[$idDemande] ?? [];
    }

    public function findReponse(int $id): ?ReponseModel
    {
        $stmt = $this->db->prepare(
            'SELECT r.*, d.titre AS demande_titre FROM reponse r
               JOIN demande_assistance d ON d.id_demande = r.id_demande
              WHERE r.id_reponse = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? ReponseModel::fromArray($row) : null;
    }

    /** @return array<int, string> installations solaires disponibles de l'utilisateur : id => adresse */
    public function getInstallations(int $idUser): array
    {
        $stmt = $this->db->prepare(
            'SELECT id_installation, adresse, puissance_kwc FROM installation_solaire
              WHERE id_utilisateur = :me AND disponible = 1 ORDER BY id_installation'
        );
        $stmt->execute(['me' => $idUser]);
        $list = [];
        foreach ($stmt->fetchAll() as $row) {
            $list[(int) $row['id_installation']] = $row['adresse'] . ' (' . rtrim(rtrim((string) $row['puissance_kwc'], '0'), '.') . ' kWc)';
        }
        return $list;
    }

    /** Identifiants des demandes auxquelles l'utilisateur a déjà répondu. @return int[] */
    public function getDemandesRepondues(int $idUser): array
    {
        $stmt = $this->db->prepare('SELECT id_demande FROM reponse WHERE id_utilisateur = :me');
        $stmt->execute(['me' => $idUser]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @return string|null raison du refus, null si l'utilisateur peut répondre */
    public function whyCannotReply(DemandeAssistanceModel $d, ?UserModel $u): ?string
    {
        if ($u === null) {
            return 'Connectez-vous pour répondre.';
        }
        if ($this->isOwner($d, $u)) {
            return 'Vous ne pouvez pas répondre à votre propre publication.';
        }
        if (!in_array($d->getStatut(), self::OPEN_STATUTS, true)) {
            return "Cette demande n'accepte plus de réponse.";
        }
        if ($u->getRole() === 'fournisseur' && !$u->isCompteVerifie()) {
            return "Votre compte fournisseur doit d'abord être vérifié par l'administrateur pour répondre aux demandes.";
        }
        if ($d->getIdFournisseur() !== null) {
            return $d->getIdFournisseur() === $u->getIdUtilisateur() ? null : 'Cette demande est adressée à un autre professionnel.';
        }
        if ($d->getSens() === 'demande' && !in_array($u->getRole(), self::HELPER_ROLES, true)) {
            return "Seuls les bénévoles, fournisseurs et propriétaires solaires répondent aux demandes d'aide.";
        }
        if ($d->getSens() === 'offre' && !in_array($u->getRole(), self::FRONT_ROLES, true)) {
            return 'Votre compte ne permet pas de répondre à cette offre.';
        }
        return null;
    }

    /** Règles du formulaire de réponse. */
    public static function reponseRules(): array
    {
        return ['message' => 'required|min:10|max:1000', 'id_installation' => 'int'];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    public function addReponse(DemandeAssistanceModel $d, array $post, UserModel $u): array
    {
        if (($reason = $this->whyCannotReply($d, $u)) !== null) {
            return ['form' => $reason];
        }
        $errors = $this->validate($post, self::reponseRules());
        if ($d->getIdFournisseur() === null) {
            $this->checkNoPhone($post, ['message'], $errors);
        }

        $idInstallation = trim((string) ($post['id_installation'] ?? '')) === '' ? null : (int) $post['id_installation'];
        if (!isset($errors['id_installation']) && $idInstallation !== null
            && !array_key_exists($idInstallation, $this->getInstallations($u->getIdUtilisateur()))) {
            $errors['id_installation'] = "Cette installation n'est pas à vous ou n'est pas disponible.";
        }
        if ($errors === [] && in_array($d->getIdDemande(), $this->getDemandesRepondues($u->getIdUtilisateur()), true)) {
            $errors['form'] = 'Vous avez déjà répondu à cette demande.';
        }
        if ($errors !== []) {
            return $errors;
        }

        $reponse = new ReponseModel();
        $reponse->setIdDemande($d->getIdDemande());
        $reponse->setIdUtilisateur($u->getIdUtilisateur());
        $reponse->setIdInstallation($idInstallation);
        $reponse->setMessage(trim((string) $post['message']));

        $stmt = $this->db->prepare(
            'INSERT INTO reponse (id_demande, id_utilisateur, id_installation, message, statut)
             VALUES (:id_demande, :id_utilisateur, :id_installation, :message, :statut)'
        );
        $stmt->execute([
            'id_demande'      => $reponse->getIdDemande(),
            'id_utilisateur'  => $reponse->getIdUtilisateur(),
            'id_installation' => $reponse->getIdInstallation(),
            'message'         => $reponse->getMessage(),
            'statut'          => 'proposee',
        ]);

        $this->notify(
            $d->getIdUtilisateur(),
            'Nouvelle réponse à votre publication',
            $u->getPrenom() . ' a répondu à « ' . $d->getTitre() . ' ».',
            $d->getIdFournisseur() === null ? url('relais', ['tab' => 'mine']) : url('fournisseurs', [], 'mes-demandes')
        );
        return [];
    }

    /** L'auteur de la demande accepte ou refuse une réponse. @return string|null erreur */
    public function changeReponseStatut(ReponseModel $r, string $statut, UserModel $u): ?string
    {
        $demande = $this->findById($r->getIdDemande());
        if ($demande === null || !$this->isOwner($demande, $u)) {
            return 'Seul l\'auteur de la demande peut accepter ou refuser une réponse.';
        }
        if (!in_array($statut, ['acceptee', 'refusee'], true) || $r->getStatut() === $statut) {
            return 'Statut de réponse non autorisé.';
        }
        if (!in_array($demande->getStatut(), self::OPEN_STATUTS, true)) {
            return 'Cette demande est clôturée : ses réponses ne peuvent plus être modifiées.';
        }
        if ($r->getStatut() === 'acceptee') {
            return 'Une réponse acceptée ne peut plus être refusée.';
        }

        $this->db->beginTransaction();
        try {
            if ($statut === 'acceptee') {
                // Une seule réponse acceptée par demande (verrou pour éviter deux acceptations simultanées)
                $stmt = $this->db->prepare('SELECT id_demande FROM demande_assistance WHERE id_demande = :id FOR UPDATE');
                $stmt->execute(['id' => $demande->getIdDemande()]);
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM reponse WHERE id_demande = :id AND statut = 'acceptee'");
                $stmt->execute(['id' => $demande->getIdDemande()]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $this->db->rollBack();
                    return 'Une autre réponse est déjà acceptée pour cette demande.';
                }
            }
            $stmt = $this->db->prepare("UPDATE reponse SET statut = :statut WHERE id_reponse = :id AND statut <> 'acceptee'");
            $stmt->execute(['statut' => $statut, 'id' => $r->getIdReponse()]);
            // Une réponse acceptée = la demande est prise en charge
            if ($statut === 'acceptee' && $demande->getStatut() === 'ouverte') {
                $stmt = $this->db->prepare("UPDATE demande_assistance SET statut = 'prise_en_charge' WHERE id_demande = :id");
                $stmt->execute(['id' => $demande->getIdDemande()]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        $this->notify(
            $r->getIdUtilisateur(),
            $statut === 'acceptee' ? 'Votre proposition est acceptée' : "Votre proposition n'a pas été retenue",
            '« ' . $demande->getTitre() . ' » — ' . $u->getPrenom() . ($statut === 'acceptee' ? ' a accepté votre aide. Merci !' : " n'a pas retenu votre proposition."),
            $demande->getIdFournisseur() === null ? url('relais') : url('fournisseurs', [], 'demandes-recues')
        );
        return null;
    }

    /** Suppression d'une réponse par son auteur (tant qu'elle n'est pas acceptée) ou par l'administrateur. */
    public function deleteReponse(ReponseModel $r, UserModel $u): ?string
    {
        $isAuthor = $r->getIdUtilisateur() === $u->getIdUtilisateur();
        if (!$isAuthor && $u->getRole() !== 'admin') {
            return 'Vous ne pouvez supprimer que vos propres réponses.';
        }
        if ($isAuthor && $u->getRole() !== 'admin' && $r->getStatut() === 'acceptee') {
            return 'Une réponse acceptée ne peut plus être retirée.';
        }
        $stmt = $this->db->prepare('DELETE FROM reponse WHERE id_reponse = :id');
        $stmt->execute(['id' => $r->getIdReponse()]);
        return null;
    }

    // ==============================================================
    // NOTIFICATIONS (table commune)
    // ==============================================================

    private function notify(int $idUser, string $titre, string $message, string $lien): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO notification (id_utilisateur, titre, message, lien) VALUES (:u, :titre, :message, :lien)'
        );
        $stmt->execute([
            'u'       => $idUser,
            'titre'   => mb_substr($titre, 0, 150),
            'message' => $message,
            'lien'    => mb_substr($lien, 0, 255),
        ]);
    }
}
