<?php
/**
 * controllers/FactureController.php — Module 2 (Hiba)
 * CRUD de l'entité Facture (Front Office : le citoyen gère SES factures ;
 * Back Office : admin = toutes, STEG = électricité, SONEDE = eau).
 *
 * Sécurité appliquée ici (voir docs/SECURITE.md) :
 *  - requêtes préparées uniquement (protection injection SQL) ;
 *  - « périmètre » (scope) ajouté à CHAQUE requête : un citoyen ne voit, ne modifie et ne
 *    supprime que ses propres factures, même s'il change l'identifiant dans l'adresse ;
 *  - validation PHP des mêmes règles que le JavaScript des formulaires ;
 *  - filtres et tri acceptés uniquement depuis des listes autorisées (jamais concaténés tels quels).
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/FactureModel.php';
require_once __DIR__ . '/../models/UserModel.php';

class FactureController extends Controller
{
    /**
     * Règles de base (mêmes noms que data-rules dans les formulaires).
     * Les règles propres aux factures (références, unités, durées…) sont dans check().
     */
    private const RULES = [
        'type_ressource' => 'required|in:electricite,eau',
        'periode_debut'  => 'required|date',
        'periode_fin'    => 'required|date',
        'quantite'       => 'required|max:20',
        'montant'        => 'required|max:20',
        'date_echeance'  => 'date',
        'statut'         => 'required|in:a_payer,payee',
    ];

    /** Tri autorisé : clé reçue dans l'URL → ORDER BY (jamais la valeur de l'utilisateur dans le SQL). */
    public const TRIS = [
        'recentes'       => 'f.periode_debut DESC, f.id_facture DESC',
        'anciennes'      => 'f.periode_debut ASC, f.id_facture ASC',
        'montant_desc'   => 'f.montant DESC',
        'montant_asc'    => 'f.montant ASC',
        'echeance'       => 'f.date_echeance IS NULL, f.date_echeance ASC',
    ];

    private const MONTANT_MAX = 99999.999;
    private const QUANTITE_MAX = 999999.99;

    /**
     * Les deux références d'une facture, selon le fournisseur (chiffres uniquement, espaces retirés) :
     *  - abonnement (en haut de la facture) : obligatoire ;
     *  - paiement / talon (en bas de la facture) : facultative, mais au bon format si elle est saisie.
     * [longueur min, longueur max] — les mêmes valeurs sont dans factures-crud.js.
     */
    public const REFERENCES = [
        'electricite' => ['abonnement' => [9, 9], 'paiement' => [16, 16]],
        'eau'         => ['abonnement' => [8, 9], 'paiement' => [12, 16]],
    ];

    /** Longueur maximale de saisie (identique pour les deux fournisseurs). */
    public const REFERENCE_MAX = ['reference_abonnement' => 9, 'reference_paiement' => 16];

    /** Aucune facture avant cette date (évite les erreurs de saisie). */
    public const DATE_MIN = '2016-01-01';

    /** Durée autorisée d'une période : au moins 15 jours ; au plus 12 mois (STEG) ou 6 mois (SONEDE). */
    public const DUREES = [
        'electricite' => ['min_jours' => 15, 'max_mois' => 12, 'nom' => 'STEG',   'note' => 'régularisation'],
        'eau'         => ['min_jours' => 15, 'max_mois' => 6,  'nom' => 'SONEDE', 'note' => 'plafond réglementaire'],
    ];

    /** La date limite de paiement tombe au plus 90 jours après la fin de la période. */
    public const ECHEANCE_MAX_JOURS = 90;

    // ==============================================================
    // PÉRIMÈTRE : quelles factures l'utilisateur connecté a le droit de voir
    // ==============================================================

    /**
     * @return array{user?: int, type?: string} périmètre à appliquer à toutes les requêtes
     *   citoyen → ses factures ; steg → électricité ; sonede → eau ; admin → tout
     */
    public static function scopeFor(UserModel $user): array
    {
        return match ($user->getRole()) {
            'citoyen' => ['user' => $user->getIdUtilisateur()],
            'steg'    => ['type' => 'electricite'],
            'sonede'  => ['type' => 'eau'],
            'admin'   => [],
            default   => ['user' => -1],   // tout autre rôle : aucune facture
        };
    }

    /** Ajoute le périmètre à une clause WHERE (paramètres préparés). */
    private function scopeSql(array $scope, array &$params): string
    {
        $sql = '';
        if (isset($scope['user'])) {
            $sql .= ' AND f.id_utilisateur = :scope_user';
            $params['scope_user'] = $scope['user'];
        }
        if (isset($scope['type'])) {
            $sql .= ' AND f.type_ressource = :scope_type';
            $params['scope_type'] = $scope['type'];
        }
        return $sql;
    }

    // ==============================================================
    // LECTURE
    // ==============================================================

    /**
     * Filtres reçus de l'URL, nettoyés : seules les valeurs autorisées sont gardées.
     * @return array{q: string, type: string, statut: string, tri: string}
     */
    public static function cleanFilters(array $get): array
    {
        $q = trim((string) ($get['q'] ?? ''));
        $type = (string) ($get['type'] ?? '');
        $statut = (string) ($get['statut'] ?? '');
        $tri = (string) ($get['tri'] ?? 'recentes');
        return [
            'q'      => mb_substr($q, 0, 50),
            'type'   => in_array($type, FactureModel::TYPES, true) ? $type : '',
            'statut' => in_array($statut, FactureModel::STATUTS, true) ? $statut : '',
            'tri'    => array_key_exists($tri, self::TRIS) ? $tri : 'recentes',
        ];
    }

    /**
     * Liste des factures du périmètre, avec recherche, filtres et tri.
     * @return FactureModel[]
     */
    public function search(array $scope, array $filters): array
    {
        $params = [];
        $sql = "SELECT f.*, CONCAT(u.prenom, ' ', u.nom) AS nom_client, u.email AS email_client
                FROM facture f
                JOIN utilisateur u ON u.id_utilisateur = f.id_utilisateur
                WHERE 1 = 1" . $this->scopeSql($scope, $params);

        if ($filters['q'] !== '') {
            // Recherche dans les deux références (et par citoyen pour le Back Office).
            // Les références sont enregistrées sans espaces : on les retire aussi de la recherche.
            $refLike = '%' . addcslashes((string) preg_replace('/\s+/u', '', $filters['q']), '%_\\') . '%';
            $params['q1'] = $refLike;
            $params['q2'] = $refLike;
            if (isset($scope['user'])) {
                $sql .= ' AND (f.reference_abonnement LIKE :q1 OR f.reference_paiement LIKE :q2)';
            } else {
                $sql .= " AND (f.reference_abonnement LIKE :q1 OR f.reference_paiement LIKE :q2
                               OR u.email LIKE :q3 OR CONCAT(u.prenom, ' ', u.nom) LIKE :q4)";
                $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
                $params['q3'] = $like;
                $params['q4'] = $like;
            }
        }
        if ($filters['type'] !== '') {
            $sql .= ' AND f.type_ressource = :type';
            $params['type'] = $filters['type'];
        }
        if ($filters['statut'] !== '') {
            $sql .= ' AND f.statut = :statut';
            $params['statut'] = $filters['statut'];
        }
        $sql .= ' ORDER BY ' . self::TRIS[$filters['tri']];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(fn (array $row) => FactureModel::fromArray($row), $stmt->fetchAll());
    }

    /** Une facture, seulement si elle est dans le périmètre (sinon null). */
    public function find(int $id, array $scope): ?FactureModel
    {
        $params = ['id' => $id];
        $sql = "SELECT f.*, CONCAT(u.prenom, ' ', u.nom) AS nom_client, u.email AS email_client
                FROM facture f
                JOIN utilisateur u ON u.id_utilisateur = f.id_utilisateur
                WHERE f.id_facture = :id" . $this->scopeSql($scope, $params) . ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? FactureModel::fromArray($row) : null;
    }

    /**
     * Facture précédente du même titulaire et du même fournisseur (comparaison de la page « Voir »).
     * Même périmètre que find() : on ne lit jamais une facture hors du $scope.
     */
    public function precedente(FactureModel $facture, array $scope): ?FactureModel
    {
        $params = [
            'id'   => $facture->getIdFacture(),
            'user' => $facture->getIdUtilisateur(),
            'type' => $facture->getTypeRessource(),
            'fin'  => $facture->getPeriodeFin(),
        ];
        $sql = "SELECT f.* FROM facture f
                WHERE f.id_facture <> :id AND f.id_utilisateur = :user AND f.type_ressource = :type
                  AND f.periode_fin < :fin" . $this->scopeSql($scope, $params) . '
                ORDER BY f.periode_fin DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? FactureModel::fromArray($row) : null;
    }

    /**
     * Chiffres de synthèse du périmètre.
     * @return array{total: int, aPayer: int, montantAPayer: float, electricite: int, eau: int, montantTotal: float, montantElectricite: float, montantEau: float}
     */
    public function summary(array $scope): array
    {
        $params = [];
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(f.statut = 'a_payer'), 0) AS a_payer,
                       COALESCE(SUM(CASE WHEN f.statut = 'a_payer' THEN f.montant ELSE 0 END), 0) AS montant_a_payer,
                       COALESCE(SUM(f.type_ressource = 'electricite'), 0) AS electricite,
                       COALESCE(SUM(f.type_ressource = 'eau'), 0) AS eau,
                       COALESCE(SUM(f.montant), 0) AS montant_total,
                       COALESCE(SUM(CASE WHEN f.type_ressource = 'electricite' THEN f.montant ELSE 0 END), 0) AS montant_electricite,
                       COALESCE(SUM(CASE WHEN f.type_ressource = 'eau' THEN f.montant ELSE 0 END), 0) AS montant_eau
                FROM facture f WHERE 1 = 1" . $this->scopeSql($scope, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $r = $stmt->fetch() ?: [];
        return [
            'total'         => (int) ($r['total'] ?? 0),
            'aPayer'        => (int) ($r['a_payer'] ?? 0),
            'montantAPayer' => (float) ($r['montant_a_payer'] ?? 0),
            'electricite'   => (int) ($r['electricite'] ?? 0),
            'eau'           => (int) ($r['eau'] ?? 0),
            'montantTotal'  => (float) ($r['montant_total'] ?? 0),
            'montantElectricite' => (float) ($r['montant_electricite'] ?? 0),
            'montantEau'    => (float) ($r['montant_eau'] ?? 0),
        ];
    }

    /** @return UserModel[] comptes citoyens actifs (choix du titulaire dans le Back Office) */
    public function getCitoyens(): array
    {
        $stmt = $this->db->query(
            "SELECT id_utilisateur, nom, prenom, email, role, statut_compte FROM utilisateur
             WHERE role = 'citoyen' AND statut_compte = 'actif' ORDER BY nom, prenom"
        );
        return array_map(fn (array $row) => UserModel::fromArray($row), $stmt->fetchAll());
    }

    // ==============================================================
    // VALIDATION
    // ==============================================================

    /**
     * Contrôle complet d'une facture (règles de base + règles propres aux factures).
     * Les mêmes règles sont vérifiées en JavaScript (factures-crud.js) ; seul le
     * chevauchement avec une autre facture n'est vérifiable qu'ici (il faut la base).
     * @param int $idUtilisateur titulaire de la facture
     * @param int|null $exclureId facture modifiée (ignorée dans les contrôles doublon / chevauchement)
     * @return array<string, string>
     */
    public function check(array $post, int $idUtilisateur, ?int $exclureId = null): array
    {
        $post = self::normaliserReferences($post);
        $errors = $this->validate($post, self::RULES);
        $v = fn (string $k): string => trim((string) ($post[$k] ?? ''));
        $type = isset($errors['type_ressource']) ? '' : $v('type_ressource');

        // ---- Références d'abonnement et de paiement (format selon le fournisseur)
        $errors += self::referencesErrors($v('reference_abonnement'), $v('reference_paiement'), $type);

        // ---- Consommation et montant : un nombre réel, avec ou sans l'unité
        if (!isset($errors['quantite'])) {
            $message = self::quantiteError($v('quantite'), $type);
            if ($message !== null) {
                $errors['quantite'] = $message;
            }
        }
        if (!isset($errors['montant'])) {
            $message = self::montantError($v('montant'));
            if ($message !== null) {
                $errors['montant'] = $message;
            }
        }

        // ---- Dates
        foreach (self::dateErrors($v('periode_debut'), $v('periode_fin'), $v('date_echeance'), $type, date('Y-m-d')) as $champ => $message) {
            if (!isset($errors[$champ])) {
                $errors[$champ] = $message;
            }
        }

        // ---- Base de données : talon déjà enregistré, période qui chevauche une autre facture
        if ($type !== '' && $v('reference_paiement') !== '' && !isset($errors['reference_paiement'])
            && $this->paiementExists($idUtilisateur, $type, $v('reference_paiement'), $exclureId)) {
            $errors['reference_paiement'] = 'Vous avez déjà enregistré une facture avec cette référence de paiement.';
        }
        if ($type !== '' && !isset($errors['periode_debut']) && !isset($errors['periode_fin'])) {
            $autre = $this->chevauchement($idUtilisateur, $type, $v('periode_debut'), $v('periode_fin'), $exclureId);
            if ($autre !== null) {
                $errors['periode_debut'] = sprintf(
                    'Cette période chevauche une autre facture %s déjà enregistrée (du %s au %s, abonnement %s).',
                    self::DUREES[$type]['nom'], dateFr($autre['periode_debut']), dateFr($autre['periode_fin']),
                    $autre['reference_abonnement']
                );
            }
        }
        return $errors;
    }

    /** Retire tous les espaces des deux références (« 93797 480 0 » → « 937974800 »). */
    public static function normaliserReferences(array $post): array
    {
        foreach (array_keys(self::REFERENCE_MAX) as $champ) {
            $post[$champ] = preg_replace('/\s+/u', '', (string) ($post[$champ] ?? '')) ?? '';
        }
        return $post;
    }

    /**
     * Erreurs des deux références (déjà sans espaces) selon le fournisseur.
     * @return array<string, string>
     */
    public static function referencesErrors(string $abonnement, string $paiement, string $type): array
    {
        $messages = [
            'electricite' => [
                'reference_abonnement' => "La référence d'abonnement STEG doit contenir exactement 9 chiffres.",
                'reference_paiement'   => 'La référence de paiement STEG (talon) doit contenir exactement 16 chiffres.',
            ],
            'eau' => [
                'reference_abonnement' => "La référence d'abonnement SONEDE (district + police) doit contenir entre 8 et 9 chiffres.",
                'reference_paiement'   => 'La référence de paiement SONEDE (talon) doit contenir entre 12 et 16 chiffres.',
            ],
        ];
        $errors = [];
        if ($abonnement === '') {
            $errors['reference_abonnement'] = "Indiquez la référence d'abonnement (en haut de la facture).";
        }
        $valeurs = ['reference_abonnement' => [$abonnement, 'abonnement'], 'reference_paiement' => [$paiement, 'paiement']];
        foreach ($valeurs as $champ => [$valeur, $cle]) {
            if ($valeur === '' || isset($errors[$champ])) {
                continue;
            }
            if ($type === '') {
                // Fournisseur pas encore choisi : seulement « chiffres » et longueur maximale
                if (!preg_match('/^\d{1,' . self::REFERENCE_MAX[$champ] . '}$/', $valeur)) {
                    $errors[$champ] = sprintf('Chiffres uniquement (%d au plus).', self::REFERENCE_MAX[$champ]);
                }
                continue;
            }
            [$min, $max] = self::REFERENCES[$type][$cle];
            if (!preg_match('/^\d{' . $min . ',' . $max . '}$/', $valeur)) {
                $errors[$champ] = $messages[$type][$champ];
            }
        }
        return $errors;
    }

    /**
     * Lit « 22.5 », « 22,5 », « 22.5 m³ », « 455kWh »… → [nombre, unité saisie en minuscules].
     * @return array{0: float, 1: string}|null null si ce n'est pas un nombre réel (éventuellement suivi d'une unité)
     */
    private static function parseNombre(string $valeur, string $unites, int $decimales): ?array
    {
        $motif = '/^(\d{1,9}(?:[.,]\d{1,' . $decimales . '})?)\s*(' . $unites . ')?$/iu';
        if (!preg_match($motif, trim($valeur), $m)) {
            return null;
        }
        return [(float) str_replace(',', '.', $m[1]), mb_strtolower($m[2] ?? '')];
    }

    public static function quantiteError(string $valeur, string $type): ?string
    {
        $exemple = $type === 'eau' ? '22.5 ou 22.5 m³' : '455 ou 455 kWh';
        $lu = self::parseNombre($valeur, 'kwh|m³|m3', 2);
        if ($lu === null) {
            return 'Saisissez un nombre réel (2 décimales au plus), avec ou sans unité : ex. ' . $exemple . '.';
        }
        [$nombre, $unite] = $lu;
        if ($unite !== '' && $type === 'eau' && $unite === 'kwh') {
            return "Une facture d'eau se mesure en m³, pas en kWh (ex. 22.5 m³).";
        }
        if ($unite !== '' && $type === 'electricite' && $unite !== 'kwh') {
            return "Une facture d'électricité se mesure en kWh, pas en m³ (ex. 455 kWh).";
        }
        if ($nombre <= 0) {
            return 'La consommation doit être supérieure à 0.';
        }
        if ($nombre > self::QUANTITE_MAX) {
            return 'Consommation trop élevée (999 999,99 au maximum).';
        }
        return null;
    }

    public static function montantError(string $valeur): ?string
    {
        $lu = self::parseNombre($valeur, 'tnd', 3);
        if ($lu === null) {
            return 'Saisissez un nombre réel (3 décimales au plus), avec ou sans TND : ex. 500 ou 500 TND.';
        }
        if ($lu[0] <= 0) {
            return 'Le montant doit être supérieur à 0.';
        }
        if ($lu[0] > self::MONTANT_MAX) {
            return 'Montant trop élevé (99 999,999 TND au maximum).';
        }
        return null;
    }

    /**
     * Règles des dates (début, fin, date limite de paiement) selon le fournisseur.
     * Les dates sont au format AAAA-MM-JJ (déjà vérifié par la règle « date »).
     * @return array<string, string>
     */
    public static function dateErrors(string $debut, string $fin, string $echeance, string $type, string $aujourdhui): array
    {
        $errors = [];
        $estDate = fn (string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
        $minFr = dateFr(self::DATE_MIN);

        foreach (['periode_debut' => [$debut, 'Le début'], 'periode_fin' => [$fin, 'La fin']] as $champ => [$d, $nom]) {
            if (!$estDate($d)) {
                continue;
            }
            if ($d < self::DATE_MIN) {
                $errors[$champ] = "Date trop ancienne : les factures antérieures au $minFr ne sont pas acceptées.";
            } elseif ($d > $aujourdhui) {
                $errors[$champ] = "$nom de la période ne peut pas être dans le futur.";
            }
        }

        if ($estDate($debut) && $estDate($fin) && !isset($errors['periode_debut']) && !isset($errors['periode_fin'])) {
            if ($debut >= $fin) {
                $errors['periode_fin'] = 'La fin de la période doit être strictement après le début.';
            } elseif ($type !== '') {
                $regle = self::DUREES[$type];
                $jours = (int) (new DateTimeImmutable($debut))->diff(new DateTimeImmutable($fin))->days;
                $limite = (new DateTimeImmutable($debut))->modify('+' . $regle['max_mois'] . ' months')->format('Y-m-d');
                if ($jours < $regle['min_jours']) {
                    $errors['periode_fin'] = sprintf('Une période %s dure au moins %d jours (ici %d jour%s).',
                        $regle['nom'], $regle['min_jours'], $jours, $jours > 1 ? 's' : '');
                } elseif ($fin > $limite) {
                    $errors['periode_fin'] = sprintf('Une période %s dure au plus %d mois (%s) : la fin doit être au plus le %s.',
                        $regle['nom'], $regle['max_mois'], $regle['note'], dateFr($limite));
                }
            }
        }

        if ($echeance !== '' && $estDate($echeance) && $estDate($fin) && !isset($errors['periode_fin'])) {
            $max = (new DateTimeImmutable($fin))->modify('+' . self::ECHEANCE_MAX_JOURS . ' days')->format('Y-m-d');
            if ($echeance < $fin) {
                $errors['date_echeance'] = 'La date limite de paiement doit être le jour de la fin de la période ou après.';
            } elseif ($echeance > $max) {
                $errors['date_echeance'] = sprintf('La date limite de paiement tombe au plus %d jours après la fin de la période (au plus tard le %s).',
                    self::ECHEANCE_MAX_JOURS, dateFr($max));
            }
        }
        return $errors;
    }

    /** Le citoyen a-t-il déjà une facture de ce fournisseur avec ce talon ? */
    private function paiementExists(int $idUtilisateur, string $type, string $paiement, ?int $exclureId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM facture WHERE id_utilisateur = :u AND type_ressource = :t AND reference_paiement = :r AND id_facture <> :x LIMIT 1'
        );
        $stmt->execute(['u' => $idUtilisateur, 't' => $type, 'r' => $paiement, 'x' => $exclureId ?? 0]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Facture du même fournisseur et du même titulaire dont la période chevauche [début, fin].
     * Deux périodes qui se suivent (l'une finit le jour où l'autre commence) ne se chevauchent pas.
     * @return array{reference_abonnement: string, periode_debut: string, periode_fin: string}|null
     */
    private function chevauchement(int $idUtilisateur, string $type, string $debut, string $fin, ?int $exclureId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT reference_abonnement, periode_debut, periode_fin FROM facture
             WHERE id_utilisateur = :u AND type_ressource = :t AND id_facture <> :x
               AND periode_debut < :fin AND periode_fin > :debut
             ORDER BY periode_debut LIMIT 1'
        );
        $stmt->execute(['u' => $idUtilisateur, 't' => $type, 'x' => $exclureId ?? 0, 'fin' => $fin, 'debut' => $debut]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Nombre saisi sans son unité : « 22,5 m³ » → 22.5 ; « 500TND » → 500. */
    private function toFloat(string $value): float
    {
        return (float) str_replace(',', '.', preg_replace('/\s*(kwh|m³|m3|tnd)$/iu', '', trim($value)));
    }

    /** Construit l'objet à enregistrer à partir du formulaire déjà validé. */
    private function fromPost(array $post, int $idUtilisateur): FactureModel
    {
        $f = new FactureModel();
        $f->setIdUtilisateur($idUtilisateur);
        $f->setTypeRessource(trim((string) $post['type_ressource']));
        $f->setReferenceAbonnement((string) $post['reference_abonnement']);
        $paiement = (string) ($post['reference_paiement'] ?? '');
        $f->setReferencePaiement($paiement !== '' ? $paiement : null);   // jamais de chaîne vide en base
        $f->setPeriodeDebut(trim((string) $post['periode_debut']));
        $f->setPeriodeFin(trim((string) $post['periode_fin']));
        $f->setQuantite($this->toFloat(trim((string) $post['quantite'])));
        $f->setMontant($this->toFloat(trim((string) $post['montant'])));
        $echeance = trim((string) ($post['date_echeance'] ?? ''));
        $f->setDateEcheance($echeance !== '' ? $echeance : null);
        $f->setStatut(trim((string) $post['statut']));
        return $f;
    }

    // ==============================================================
    // CRÉATION / MODIFICATION / SUPPRESSION
    // ==============================================================

    /**
     * @return array<string, string> erreurs (vide = facture créée)
     */
    public function create(array $post, int $idUtilisateur, array $scope): array
    {
        // Le Back Office STEG/SONEDE ne peut créer que des factures de sa ressource
        if (isset($scope['type']) && ($post['type_ressource'] ?? '') !== $scope['type']) {
            return ['type_ressource' => 'Vous ne pouvez gérer que les factures ' . self::typeLabel($scope['type']) . '.'];
        }
        $post = self::normaliserReferences($post);
        $errors = $this->check($post, $idUtilisateur);
        if ($errors !== []) {
            return $errors;
        }
        $f = $this->fromPost($post, $idUtilisateur);
        $stmt = $this->db->prepare(
            'INSERT INTO facture (id_utilisateur, type_ressource, reference_abonnement, reference_paiement,
                                  periode_debut, periode_fin, quantite, montant, date_echeance, statut, source)
             VALUES (:u, :t, :ra, :rp, :pd, :pf, :q, :m, :e, :s, :src)'
        );
        $stmt->execute([
            'u' => $f->getIdUtilisateur(), 't' => $f->getTypeRessource(),
            'ra' => $f->getReferenceAbonnement(), 'rp' => $f->getReferencePaiement(),
            'pd' => $f->getPeriodeDebut(), 'pf' => $f->getPeriodeFin(), 'q' => $f->getQuantite(),
            'm' => $f->getMontant(), 'e' => $f->getDateEcheance(), 's' => $f->getStatut(), 'src' => 'manuelle',
        ]);
        return [];
    }

    /**
     * @return array<string, string> erreurs (vide = facture modifiée) ; ['form' => …] si introuvable
     */
    public function update(int $id, array $post, array $scope): array
    {
        $existante = $this->find($id, $scope);
        if ($existante === null) {
            return ['form' => 'Facture introuvable.'];
        }
        if (isset($scope['type']) && ($post['type_ressource'] ?? '') !== $scope['type']) {
            return ['type_ressource' => 'Vous ne pouvez gérer que les factures ' . self::typeLabel($scope['type']) . '.'];
        }
        $post = self::normaliserReferences($post);
        $errors = $this->check($post, $existante->getIdUtilisateur(), $id);
        if ($errors !== []) {
            return $errors;
        }
        $f = $this->fromPost($post, $existante->getIdUtilisateur());
        $params = [
            'id' => $id, 't' => $f->getTypeRessource(),
            'ra' => $f->getReferenceAbonnement(), 'rp' => $f->getReferencePaiement(),
            'pd' => $f->getPeriodeDebut(), 'pf' => $f->getPeriodeFin(), 'q' => $f->getQuantite(),
            'm' => $f->getMontant(), 'e' => $f->getDateEcheance(), 's' => $f->getStatut(),
        ];
        $sql = 'UPDATE facture f SET f.type_ressource = :t, f.reference_abonnement = :ra, f.reference_paiement = :rp,
                       f.periode_debut = :pd, f.periode_fin = :pf,
                       f.quantite = :q, f.montant = :m, f.date_echeance = :e, f.statut = :s
                WHERE f.id_facture = :id' . $this->scopeSql($scope, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return [];
    }

    /** Supprime une facture du périmètre. Retourne false si elle n'existe pas (ou pas autorisée). */
    public function delete(int $id, array $scope): bool
    {
        $params = ['id' => $id];
        $sql = 'DELETE f FROM facture f WHERE f.id_facture = :id' . $this->scopeSql($scope, $params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() === 1;
    }

    // ==============================================================
    // LIBELLÉS (affichage)
    // ==============================================================

    public static function typeLabel(string $type): string
    {
        return ['electricite' => 'Électricité (STEG)', 'eau' => 'Eau (SONEDE)'][$type] ?? $type;
    }

    /** « STEG » ou « SONEDE ». */
    public static function fournisseur(string $type): string
    {
        return self::DUREES[$type]['nom'] ?? $type;
    }

    /** Titre lisible d'une facture : « Facture STEG du 01/09/2026 au 31/10/2026 ». */
    public static function titre(FactureModel $f): string
    {
        return sprintf('Facture %s du %s au %s', self::fournisseur($f->getTypeRessource()),
            dateFr($f->getPeriodeDebut()), dateFr($f->getPeriodeFin()));
    }

    /**
     * Référence affichée par groupes de chiffres (lecture plus facile) :
     * abonnement par 3 (« 937 974 800 »), talon par 4 (« 0000 0499 3797 4800 »).
     */
    public static function formatReference(?string $reference, int $groupe = 3): string
    {
        return $reference === null || $reference === '' ? '—' : trim(chunk_split($reference, $groupe, ' '));
    }

    public static function unite(string $type): string
    {
        return $type === 'eau' ? 'm³' : 'kWh';
    }

    public static function statutLabel(string $statut): string
    {
        return ['a_payer' => 'À payer', 'payee' => 'Payée'][$statut] ?? $statut;
    }

    public static function formatMontant(float $montant): string
    {
        return number_format($montant, 3, ',', ' ') . ' TND';
    }

    public static function formatQuantite(float $quantite, string $type): string
    {
        return number_format($quantite, 2, ',', ' ') . ' ' . self::unite($type);
    }

    /**
     * Données d'une facture pour le JavaScript de « Factures & IA » (graphiques, analyse, assistant).
     * Passées dans un attribut data-* (JSON échappé par e() dans la vue) : aucun script en ligne (CSP).
     */
    public static function pourJs(FactureModel $f): array
    {
        return [
            'id'          => $f->getIdFacture(),
            'type'        => $f->getTypeRessource(),
            'provider'    => self::fournisseur($f->getTypeRessource()),
            'refAbo'      => $f->getReferenceAbonnement(),
            'refPaie'     => $f->getReferencePaiement() ?? '',
            'periodStart' => $f->getPeriodeDebut(),
            'periodEnd'   => $f->getPeriodeFin(),
            'quantity'    => $f->getQuantite(),
            'amount'      => $f->getMontant(),
            'dueDate'     => $f->getDateEcheance() ?? '',
            'status'      => $f->getStatut(),
            'urlVoir'     => url('facture-voir', ['id' => $f->getIdFacture()]),
            'urlModifier' => url('facture-modifier', ['id' => $f->getIdFacture()]),
        ];
    }

    /** Valeurs d'un formulaire vide (ajout). */
    public static function emptyForm(string $type = ''): array
    {
        return ['type_ressource' => $type, 'reference_abonnement' => '', 'reference_paiement' => '', 'periode_debut' => '', 'periode_fin' => '',
                'quantite' => '', 'montant' => '', 'date_echeance' => '', 'statut' => 'a_payer', 'id_utilisateur' => ''];
    }

    /** Valeurs du formulaire pré-remplies avec une facture existante (modification). */
    public static function toForm(FactureModel $f): array
    {
        return [
            'type_ressource' => $f->getTypeRessource(),
            'reference_abonnement' => $f->getReferenceAbonnement(),
            'reference_paiement'   => $f->getReferencePaiement() ?? '',
            'periode_debut'  => $f->getPeriodeDebut(),
            'periode_fin'    => $f->getPeriodeFin(),
            'quantite'       => rtrim(rtrim(number_format($f->getQuantite(), 2, '.', ''), '0'), '.'),
            'montant'        => number_format($f->getMontant(), 3, '.', ''),
            'date_echeance'  => $f->getDateEcheance() ?? '',
            'statut'         => $f->getStatut(),
            'id_utilisateur' => (string) $f->getIdUtilisateur(),
        ];
    }

    /** Valeurs saisies (pour réafficher le formulaire après une erreur). */
    public static function postedForm(array $post): array
    {
        $post = self::normaliserReferences($post);   // références réaffichées sans espaces
        $out = [];
        foreach (array_keys(self::emptyForm()) as $k) {
            $out[$k] = trim((string) ($post[$k] ?? ''));
        }
        return $out;
    }
}
