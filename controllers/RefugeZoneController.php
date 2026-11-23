<?php
/**
 * controllers/RefugeZoneController.php — Module 3 (Louay)
 * CRUD des refuge zones (table refuge_zone).
 *
 *  - Une refuge zone appartient à un utilisateur de rôle « entreprise » (refuge_zone.id_entreprise).
 *    La base ne peut pas vérifier ce rôle : c'est fait ici avant chaque enregistrement.
 *  - Seule une entreprise VALIDÉE par l'admin (compte_verifie = 1) peut publier un refuge.
 *  - Un refuge créé est « en_attente » : il n'apparaît sur la carte publique qu'après validation
 *    par l'admin (Back Office → Refuges à valider).
 *  - « complet » est calculé automatiquement à partir des places disponibles.
 *  - Un refuge qui a des réservations n'est jamais supprimé (elles disparaîtraient en cascade) :
 *    il passe à « ferme » et l'historique des citoyens est conservé.
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/Module3Saisie.php';
require_once __DIR__ . '/../models/RefugeZoneModel.php';
require_once __DIR__ . '/ZoneController.php';

class RefugeZoneController extends Controller
{
    use Module3Saisie;

    /** Capacité maximale acceptée (colonne SMALLINT, valeur raisonnable pour un lieu d'accueil). */
    public const CAPACITE_MAX = 1000;

    /** Messages du formulaire refuge : utilisés par PHP ET écrits dans la vue (data-message-*). */
    public const MESSAGES = [
        'nom'       => ['required' => 'Indiquez le nom du lieu.'],
        'adresse'   => ['required' => "Indiquez l'adresse du lieu."],
        'id_zone'   => ['required' => 'Veuillez choisir un gouvernorat.'],
        'capacite'  => ['required'  => 'Indiquez la capacité d\'accueil.',
                        'int'       => 'Nombre entier de places attendu.',
                        'max-value' => 'Au plus {max} places.',
                        'min-value' => 'Impossible : {n} places sont déjà réservées.'],
        'latitude'  => ['decimal' => 'Nombre avec au plus 3 décimales (ex. 36.800).'],
        'longitude' => ['decimal' => 'Nombre avec au plus 3 décimales (ex. 10.180).'],
        'statut'    => ['required' => 'Choisissez si le lieu est ouvert ou fermé.',
                        'in'       => 'Choisissez si le lieu est ouvert ou fermé.'],
        'services'  => ['required' => 'Cochez au moins un service proposé.'],
    ];

    /** Requête de base : refuge + nom du gouvernorat + entreprise propriétaire. */
    private const SELECT = 'SELECT r.*, z.nom_zone, u.nom_organisation AS nom_entreprise, u.telephone AS telephone_entreprise
                            FROM refuge_zone r
                            JOIN zone z ON z.id_zone = r.id_zone
                            JOIN utilisateur u ON u.id_utilisateur = r.id_entreprise';

    // ==============================================================
    // LECTURE
    // ==============================================================

    /**
     * Refuges visibles par le public : validés par l'admin (statut ≠ en_attente)
     * et appartenant à une entreprise vérifiée et active.
     *
     * @return RefugeZoneModel[]
     */
    public function getPublic(): array
    {
        $stmt = $this->db->query(
            self::SELECT . " WHERE r.statut <> 'en_attente'
                               AND u.role = 'entreprise' AND u.compte_verifie = 1 AND u.statut_compte = 'actif'
                             ORDER BY r.nom"
        );
        return $this->hydrate($stmt->fetchAll());
    }

    /** @return RefugeZoneModel[] refuges d'une entreprise (espace « Mes refuges ») */
    public function getByEntreprise(int $idEntreprise): array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE r.id_entreprise = :id ORDER BY r.date_creation DESC, r.id_refuge DESC');
        $stmt->execute(['id' => $idEntreprise]);
        return $this->hydrate($stmt->fetchAll());
    }

    /**
     * Tous les refuges (Back Office), avec filtre facultatif sur le statut.
     *
     * @return RefugeZoneModel[]
     */
    public function getAll(string $statut = ''): array
    {
        if (in_array($statut, RefugeZoneModel::STATUTS, true)) {
            $stmt = $this->db->prepare(self::SELECT . ' WHERE r.statut = :statut ORDER BY r.date_creation DESC');
            $stmt->execute(['statut' => $statut]);
        } else {
            // Les refuges à valider apparaissent en premier
            $stmt = $this->db->query(self::SELECT . " ORDER BY r.statut = 'en_attente' DESC, r.date_creation DESC");
        }
        return $this->hydrate($stmt->fetchAll());
    }

    /** @return array<string, int> statut => nombre de refuges */
    public function countByStatut(): array
    {
        $counts = array_fill_keys(RefugeZoneModel::STATUTS, 0);
        foreach ($this->db->query('SELECT statut, COUNT(*) AS n FROM refuge_zone GROUP BY statut') as $row) {
            $counts[$row['statut']] = (int) $row['n'];
        }
        return $counts;
    }

    public function findById(int $id): ?RefugeZoneModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE r.id_refuge = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? RefugeZoneModel::fromArray($row) : null;
    }

    /**
     * Refuge seulement s'il appartient à l'entreprise donnée (contrôle de propriété).
     * $lock = true : verrou FOR UPDATE (à appeler dans une transaction).
     */
    public function findOwned(int $id, int $idEntreprise, bool $lock = false): ?RefugeZoneModel
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE r.id_refuge = :id AND r.id_entreprise = :ent LIMIT 1' . ($lock ? ' FOR UPDATE' : ''));
        $stmt->execute(['id' => $id, 'ent' => $idEntreprise]);
        $row = $stmt->fetch();
        return $row ? RefugeZoneModel::fromArray($row) : null;
    }

    /** Refuge visible publiquement (pour la page de réservation). */
    public function findPublic(int $id): ?RefugeZoneModel
    {
        foreach ($this->getPublic() as $refuge) {
            if ($refuge->getIdRefuge() === $id) {
                return $refuge;
            }
        }
        return null;
    }

    // ==============================================================
    // CRÉATION / MODIFICATION / SUPPRESSION (entreprise propriétaire)
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs par champ (vide = succès)
     */
    public function create(array $post, UserModel $entreprise): array
    {
        if ($error = $this->checkEntreprise($entreprise)) {
            return ['form' => $error];
        }
        [$errors, $data] = $this->validateRefuge($post, null);
        if ($errors !== []) {
            return $errors;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO refuge_zone (id_entreprise, nom, adresse, id_zone, latitude, longitude, services,
                                      capacite, places_disponibles, horaires, accessible_pmr, statut)
             VALUES (:id_entreprise, :nom, :adresse, :id_zone, :latitude, :longitude, :services,
                     :capacite, :places, :horaires, :pmr, 'en_attente')"
        );
        $stmt->execute([
            'id_entreprise' => $entreprise->getIdUtilisateur(),
            'nom'           => $data['nom'],
            'adresse'       => $data['adresse'],
            'id_zone'       => $data['id_zone'],
            'latitude'      => $data['latitude'],
            'longitude'     => $data['longitude'],
            'services'      => $data['services'],
            'capacite'      => $data['capacite'],
            'places'        => $data['capacite'],   // aucune réservation au départ
            'horaires'      => $data['horaires'],
            'pmr'           => $data['accessible_pmr'],
        ]);
        return [];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs par champ (vide = succès)
     */
    public function update(int $id, array $post, UserModel $entreprise): array
    {
        if ($error = $this->checkEntreprise($entreprise)) {
            return ['form' => $error];
        }
        // Verrou sur le refuge : une réservation ou une annulation simultanée attend la fin de la modification,
        // sinon les places disponibles seraient calculées sur une valeur périmée.
        $this->db->beginTransaction();
        try {
            $errors = $this->updateLocked($id, $post, $entreprise);
            $errors === [] ? $this->db->commit() : $this->db->rollBack();
            return $errors;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<string, string> */
    private function updateLocked(int $id, array $post, UserModel $entreprise): array
    {
        $refuge = $this->findOwned($id, $entreprise->getIdUtilisateur(), true);
        if ($refuge === null) {
            return ['form' => 'Refuge introuvable.'];
        }
        [$errors, $data] = $this->validateRefuge($post, $refuge);
        if ($errors !== []) {
            return $errors;
        }

        // Les places déjà réservées restent occupées : on recalcule les places disponibles
        $places = $data['capacite'] - $refuge->getPlacesOccupees();

        // Statut : l'entreprise ouvre ou ferme son lieu une fois validé ; « complet » est automatique
        $statut = $refuge->getStatut();
        if ($statut !== 'en_attente') {
            $statut = $data['statut'] === 'ferme' ? 'ferme' : ($places === 0 ? 'complet' : 'ouvert');
        }

        $stmt = $this->db->prepare(
            'UPDATE refuge_zone
                SET nom = :nom, adresse = :adresse, id_zone = :id_zone, latitude = :latitude, longitude = :longitude,
                    services = :services, capacite = :capacite, places_disponibles = :places, horaires = :horaires,
                    accessible_pmr = :pmr, statut = :statut
              WHERE id_refuge = :id AND id_entreprise = :id_entreprise'
        );
        $stmt->execute([
            'nom'           => $data['nom'],
            'adresse'       => $data['adresse'],
            'id_zone'       => $data['id_zone'],
            'latitude'      => $data['latitude'],
            'longitude'     => $data['longitude'],
            'services'      => $data['services'],
            'capacite'      => $data['capacite'],
            'places'        => $places,
            'horaires'      => $data['horaires'],
            'pmr'           => $data['accessible_pmr'],
            'statut'        => $statut,
            'id'            => $id,
            'id_entreprise' => $entreprise->getIdUtilisateur(),
        ]);
        return [];
    }

    /**
     * Suppression par l'entreprise propriétaire.
     *
     * @return ?string 'supprime', 'ferme' (il a des réservations : fermé au lieu d'être supprimé) ou null (introuvable)
     */
    public function delete(int $id, UserModel $entreprise): ?string
    {
        return $this->safeDelete($id, $entreprise->getIdUtilisateur());
    }

    // ==============================================================
    // BACK OFFICE (admin)
    // ==============================================================

    /** Valide un refuge en attente : il devient visible sur la carte publique. */
    public function approve(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE refuge_zone SET statut = IF(places_disponibles = 0, 'complet', 'ouvert')
              WHERE id_refuge = :id AND statut = 'en_attente'"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** Retire un refuge de la carte publique (retour en attente de validation). */
    public function suspend(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE refuge_zone SET statut = 'en_attente' WHERE id_refuge = :id AND statut <> 'en_attente'");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /** @return ?string 'supprime', 'ferme' ou null (introuvable) — voir safeDelete() */
    public function adminDelete(int $id): ?string
    {
        return $this->safeDelete($id, null);
    }

    /**
     * Supprime un refuge SANS réservation. S'il en a (même terminées ou annulées), il passe à « ferme » :
     * ON DELETE CASCADE effacerait sinon les réservations des citoyens.
     */
    private function safeDelete(int $id, ?int $idEntreprise): ?string
    {
        $owner = $idEntreprise === null ? '' : ' AND id_entreprise = :ent';
        $params = ['id' => $id] + ($idEntreprise === null ? [] : ['ent' => $idEntreprise]);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id_refuge FROM refuge_zone WHERE id_refuge = :id' . $owner . ' FOR UPDATE');
            $stmt->execute($params);
            if ($stmt->fetchAll() === []) {
                $this->db->rollBack();
                return null;
            }
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM reservation_refuge WHERE id_refuge = :id');
            $stmt->execute(['id' => $id]);
            if ((int) $stmt->fetchColumn() > 0) {
                $stmt = $this->db->prepare("UPDATE refuge_zone SET statut = 'ferme' WHERE id_refuge = :id" . $owner);
                $result = 'ferme';
            } else {
                $stmt = $this->db->prepare('DELETE FROM refuge_zone WHERE id_refuge = :id' . $owner);
                $result = 'supprime';
            }
            $stmt->execute($params);
            $this->db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // ==============================================================
    // DONNÉES POUR LA CARTE (assets/js/services/refuges.js)
    // ==============================================================

    /**
     * Tableau simple, encodé en JSON dans la vue (attribut data-refuges) pour la carte interactive.
     *
     * @param RefugeZoneModel[] $refuges
     * @return array<int, array<string, mixed>>
     */
    public function toMapData(array $refuges): array
    {
        return array_map(fn (RefugeZoneModel $r) => [
            'id'        => (string) $r->getIdRefuge(),
            'name'      => $r->getNom(),
            'type'      => $r->getNomEntreprise() ?? 'Entreprise partenaire',
            'gov'       => $r->getNomZone() ?? '',
            'address'   => $r->getAdresse(),
            // En texte : json_encode() afficherait 36.79999999… avec la serialize_precision de XAMPP
            'lat'       => $r->getLatitude() !== null ? number_format($r->getLatitude(), 6, '.', '') : null,
            'lon'       => $r->getLongitude() !== null ? number_format($r->getLongitude(), 6, '.', '') : null,
            'hours'     => $r->getHoraires() ?? 'Non précisés',
            'capacity'  => $r->getCapacite(),
            'available' => $r->getPlacesDisponibles(),
            'status'    => ['ouvert' => 'open', 'complet' => 'full'][$r->getStatut()] ?? 'closed',
            'features'  => array_merge($r->getServices(), $r->isAccessiblePmr() ? ['pmr'] : []),
            'phone'     => $r->getTelephoneEntreprise() ?? '',
        ], $refuges);
    }

    // ==============================================================
    // OUTILS INTERNES
    // ==============================================================

    /** Le rôle est vérifié ici car la base ne peut pas le faire (clé étrangère vers utilisateur). */
    private function checkEntreprise(UserModel $user): ?string
    {
        if ($user->getRole() !== 'entreprise') {
            return 'Seul un compte entreprise peut gérer des refuge zones.';
        }
        if (!$user->isCompteVerifie()) {
            return "Votre compte entreprise doit d'abord être validé par l'administrateur.";
        }
        return null;
    }

    /**
     * Contrôle de saisie (mêmes règles que les data-rules du formulaire) + contrôles métier.
     *
     * @param array<string, mixed> $post
     * @return array{0: array<string, string>, 1: array<string, mixed>}
     */
    private function validateRefuge(array $post, ?RefugeZoneModel $existing): array
    {
        $rules = [
            'nom'       => 'required|min:3|max:100',
            'adresse'   => 'required|min:5|max:255',
            'id_zone'   => 'required|int',
            'capacite'  => 'required|int|positive',
            'horaires'  => 'max:100',
            'latitude'  => 'decimal',
            'longitude' => 'decimal',
        ];
        if ($existing !== null && $existing->getStatut() !== 'en_attente') {
            $rules['statut'] = 'required|in:ouvert,ferme';
        }
        $errors = $this->validateWithMessages($post, $rules);
        if (isset($post['accessible_pmr']) && !is_string($post['accessible_pmr'])) {
            $errors['accessible_pmr'] = self::$valeurInvalide;
        }

        $idZone = (int) self::text($post, 'id_zone');
        if (!isset($errors['id_zone']) && !(new ZoneController())->exists($idZone)) {
            $errors['id_zone'] = self::message('id_zone', 'required');
        }

        $capacite = (int) self::text($post, 'capacite');
        if (!isset($errors['capacite'])) {
            if ($capacite > self::CAPACITE_MAX) {
                $errors['capacite'] = self::message('capacite', 'max-value', ['{max}' => self::CAPACITE_MAX]);
            } elseif ($existing !== null && $capacite < $existing->getPlacesOccupees()) {
                $errors['capacite'] = self::message('capacite', 'min-value', ['{n}' => $existing->getPlacesOccupees()]);
            }
        }

        // Coordonnées facultatives, mais toujours les deux ensemble et en Tunisie
        $lat = $this->toFloat($post['latitude'] ?? '');
        $lon = $this->toFloat($post['longitude'] ?? '');
        if (!isset($errors['latitude']) && !isset($errors['longitude'])) {
            if (($lat === null) !== ($lon === null)) {
                $errors[$lat === null ? 'latitude' : 'longitude'] = 'Indiquez la latitude ET la longitude, ou aucune des deux.';
            } elseif ($lat !== null && ($lat < 30 || $lat > 37.6)) {
                $errors['latitude'] = 'Latitude hors de Tunisie (entre 30 et 37,6).';
            } elseif ($lon !== null && ($lon < 7.5 || $lon > 11.7)) {
                $errors['longitude'] = 'Longitude hors de Tunisie (entre 7,5 et 11,7).';
            }
        }

        // Cases à cocher multiples : au moins un service, uniquement des valeurs connues
        $services = $post['services'] ?? [];
        $services = is_array($services) ? array_values(array_unique(array_filter($services, 'is_string'))) : [];
        if ($services === []) {
            $errors['services'] = self::message('services', 'required');
        } elseif (array_diff($services, RefugeZoneModel::SERVICES) !== []) {
            $errors['services'] = 'Service non autorisé.';
        }

        $horaires = self::text($post, 'horaires');
        $data = [
            'nom'            => self::text($post, 'nom'),
            'adresse'        => self::text($post, 'adresse'),
            'id_zone'        => $idZone,
            'latitude'       => $lat,
            'longitude'      => $lon,
            'services'       => implode(',', $services),
            'capacite'       => $capacite,
            'horaires'       => $horaires === '' ? null : $horaires,
            'accessible_pmr' => self::text($post, 'accessible_pmr') === '' ? 0 : 1,
            'statut'         => self::text($post, 'statut'),
        ];
        return [$errors, $data];
    }

    private function toFloat(mixed $value): ?float
    {
        $value = is_string($value) ? trim(str_replace(',', '.', $value)) : '';
        return $value === '' ? null : (float) $value;
    }

    /** @return RefugeZoneModel[] */
    private function hydrate(array $rows): array
    {
        return array_map(fn (array $row) => RefugeZoneModel::fromArray($row), $rows);
    }
}
