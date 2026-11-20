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
     * Les règles propres aux factures (format, unités, durées…) sont dans check().
     */
    private const RULES = [
        'type_ressource' => 'required|in:electricite,eau',
        'reference'      => 'required|max:30',
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

    /** Préfixe obligatoire de la référence : STG-123… (STEG) ou SND-123… (SONEDE). */
    public const PREFIXES = ['electricite' => 'STG', 'eau' => 'SND'];

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
            // Recherche par référence (et par citoyen pour le Back Office)
            $sql .= isset($scope['user'])
                ? ' AND f.reference LIKE :q1'
                : " AND (f.reference LIKE :q1 OR u.email LIKE :q2 OR CONCAT(u.prenom, ' ', u.nom) LIKE :q3)";
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $params['q1'] = $like;
            if (!isset($scope['user'])) {
                $params['q2'] = $like;
                $params['q3'] = $like;
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
     * Chiffres de synthèse du périmètre.
     * @return array{total: int, aPayer: int, montantAPayer: float, electricite: int, eau: int, montantTotal: float}
     */
    public function summary(array $scope): array
    {
        $params = [];
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(f.statut = 'a_payer'), 0) AS a_payer,
                       COALESCE(SUM(CASE WHEN f.statut = 'a_payer' THEN f.montant ELSE 0 END), 0) AS montant_a_payer,
                       COALESCE(SUM(f.type_ressource = 'electricite'), 0) AS electricite,
                       COALESCE(SUM(f.type_ressource = 'eau'), 0) AS eau,
                       COALESCE(SUM(f.montant), 0) AS montant_total
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
        $errors = $this->validate($post, self::RULES);
        $v = fn (string $k): string => trim((string) ($post[$k] ?? ''));
        $type = isset($errors['type_ressource']) ? '' : $v('type_ressource');

        // ---- Référence : STG-chiffres (STEG) ou SND-chiffres (SONEDE)
        if (!isset($errors['reference'])) {
            $message = self::referenceError(mb_strtoupper($v('reference')), $type);
            if ($message !== null) {
                $errors['reference'] = $message;
            }
        }

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

        // ---- Base de données : référence déjà utilisée, période qui chevauche une autre facture
        if ($type !== '' && !isset($errors['reference'])
            && $this->referenceExists($idUtilisateur, $type, mb_strtoupper($v('reference')), $exclureId)) {
            $errors['reference'] = 'Cette référence est déjà enregistrée pour une autre facture.';
        }
        if ($type !== '' && !isset($errors['periode_debut']) && !isset($errors['periode_fin'])) {
            $autre = $this->chevauchement($idUtilisateur, $type, $v('periode_debut'), $v('periode_fin'), $exclureId);
            if ($autre !== null) {
                $errors['periode_debut'] = sprintf(
                    'Cette période chevauche la facture %s (du %s au %s) déjà enregistrée pour la %s.',
                    $autre['reference'], dateFr($autre['periode_debut']), dateFr($autre['periode_fin']),
                    self::DUREES[$type]['nom']
                );
            }
        }
        return $errors;
    }

    /** Message d'erreur de la référence, ou null si elle est correcte. */
    public static function referenceError(string $reference, string $type): ?string
    {
        if (!preg_match('/^(STG|SND)-\d{1,20}$/', $reference)) {
            return match ($type) {
                'electricite' => 'Format attendu : STG- suivi de chiffres uniquement (ex. STG-20260918).',
                'eau'         => 'Format attendu : SND- suivi de chiffres uniquement (ex. SND-20260930).',
                default       => 'Format attendu : STG-chiffres (STEG) ou SND-chiffres (SONEDE).',
            };
        }
        $prefixe = substr($reference, 0, 3);
        if ($type !== '' && $prefixe !== self::PREFIXES[$type]) {
            return $type === 'electricite'
                ? 'Une facture STEG commence par STG- (SND- est réservé aux factures SONEDE).'
                : 'Une facture SONEDE commence par SND- (STG- est réservé aux factures STEG).';
        }
        return null;
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

    private function referenceExists(int $idUtilisateur, string $type, string $reference, ?int $exclureId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM facture WHERE id_utilisateur = :u AND type_ressource = :t AND reference = :r AND id_facture <> :x LIMIT 1'
        );
        $stmt->execute(['u' => $idUtilisateur, 't' => $type, 'r' => $reference, 'x' => $exclureId ?? 0]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Facture du même fournisseur et du même titulaire dont la période chevauche [début, fin].
     * Deux périodes qui se suivent (l'une finit le jour où l'autre commence) ne se chevauchent pas.
     * @return array{reference: string, periode_debut: string, periode_fin: string}|null
     */
    private function chevauchement(int $idUtilisateur, string $type, string $debut, string $fin, ?int $exclureId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT reference, periode_debut, periode_fin FROM facture
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
        $f->setReference(mb_strtoupper(trim((string) $post['reference'])));
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
        $post['reference'] = mb_strtoupper(trim((string) ($post['reference'] ?? '')));
        $errors = $this->check($post, $idUtilisateur);
        if ($errors !== []) {
            return $errors;
        }
        $f = $this->fromPost($post, $idUtilisateur);
        $stmt = $this->db->prepare(
            'INSERT INTO facture (id_utilisateur, type_ressource, reference, periode_debut, periode_fin,
                                  quantite, montant, date_echeance, statut, source)
             VALUES (:u, :t, :r, :pd, :pf, :q, :m, :e, :s, :src)'
        );
        $stmt->execute([
            'u' => $f->getIdUtilisateur(), 't' => $f->getTypeRessource(), 'r' => $f->getReference(),
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
        $post['reference'] = mb_strtoupper(trim((string) ($post['reference'] ?? '')));
        $errors = $this->check($post, $existante->getIdUtilisateur(), $id);
        if ($errors !== []) {
            return $errors;
        }
        $f = $this->fromPost($post, $existante->getIdUtilisateur());
        $params = [
            'id' => $id, 't' => $f->getTypeRessource(), 'r' => $f->getReference(),
            'pd' => $f->getPeriodeDebut(), 'pf' => $f->getPeriodeFin(), 'q' => $f->getQuantite(),
            'm' => $f->getMontant(), 'e' => $f->getDateEcheance(), 's' => $f->getStatut(),
        ];
        $sql = 'UPDATE facture f SET f.type_ressource = :t, f.reference = :r, f.periode_debut = :pd, f.periode_fin = :pf,
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

    /** Valeurs d'un formulaire vide (ajout). */
    public static function emptyForm(string $type = ''): array
    {
        return ['type_ressource' => $type, 'reference' => '', 'periode_debut' => '', 'periode_fin' => '',
                'quantite' => '', 'montant' => '', 'date_echeance' => '', 'statut' => 'a_payer', 'id_utilisateur' => ''];
    }

    /** Valeurs du formulaire pré-remplies avec une facture existante (modification). */
    public static function toForm(FactureModel $f): array
    {
        return [
            'type_ressource' => $f->getTypeRessource(),
            'reference'      => $f->getReference(),
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
        $out = [];
        foreach (array_keys(self::emptyForm()) as $k) {
            $out[$k] = trim((string) ($post[$k] ?? ''));
        }
        return $out;
    }
}
