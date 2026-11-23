<?php
/**
 * controllers/InterruptionController.php — Module 1 (Houssem)
 * CRUD des interruptions officielles (table « interruption »), Back Office uniquement.
 *
 * Droits par rôle (en plus du requireRole(BACKOFFICE_ROLES) fait dans la route) :
 *   admin  → électricité et eau
 *   steg   → électricité uniquement
 *   sonede → eau uniquement
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/FormMessages.php';
require_once __DIR__ . '/ZoneController.php';
require_once __DIR__ . '/../models/InterruptionModel.php';

class InterruptionController extends Controller
{
    use FormMessages;

    /** Messages des champs : les mêmes dans la vue (data-message-*) et ici (withMessages). */
    public const MESSAGES = [
        'type_ressource'  => ['required' => 'Choisissez la ressource coupée.'],
        'id_zone'         => ['required' => 'Sélectionnez la zone touchée.'],
        'statut'          => ['required' => 'Sélectionnez le statut.'],
        'date_debut'      => ['required' => 'Indiquez la date de début.'],
        'date_fin_prevue' => ['required' => 'Indiquez la fin prévue.', 'after' => 'La fin prévue doit être après le début.'],
        'cause'           => ['required' => 'Indiquez la cause de la coupure.'],
    ];

    public const TYPES   = ['electricite', 'eau'];
    public const STATUTS = ['planifiee', 'en_cours', 'terminee'];

    /** @return string[] ressources que ce rôle peut gérer */
    public static function typesFor(string $role): array
    {
        return match ($role) {
            'admin'  => self::TYPES,
            'steg'   => ['electricite'],
            'sonede' => ['eau'],
            default  => [],
        };
    }

    public static function canManage(string $role, string $type): bool
    {
        return in_array($type, self::typesFor($role), true);
    }

    // ==============================================================
    // LECTURE
    // ==============================================================

    /** @return InterruptionModel[] interruptions visibles par ce rôle, les plus récentes d'abord */
    public function getAll(string $role): array
    {
        $types = self::typesFor($role);
        if (!$types) {
            return [];
        }
        $in = implode(',', array_fill(0, count($types), '?'));
        $stmt = $this->db->prepare(
            "SELECT i.*, z.nom_zone
               FROM interruption i
               JOIN zone z ON z.id_zone = i.id_zone
              WHERE i.type_ressource IN ($in)
              ORDER BY i.date_debut DESC, i.id_interruption DESC"
        );
        $stmt->execute($types);
        return array_map(fn (array $row) => InterruptionModel::fromArray($row), $stmt->fetchAll());
    }

    /**
     * État de chaque gouvernorat pour la carte publique (Front Office), calculé UNIQUEMENT
     * depuis les interruptions publiées dans le Back Office :
     *   outage → au moins une interruption « en_cours »
     *            (ou « planifiee » dont la date de début est passée et la fin prévue pas encore atteinte)
     *   watch  → au moins une interruption « planifiee » à venir
     *   normal → aucune interruption active ni à venir (les « terminee » sont ignorées)
     * Chaque gouvernorat porte aussi la liste de ses interruptions actives / à venir (panneau de détail).
     *
     * @return array<int, array{name: string, status: string, summary: string, interruptions: array<int, array<string, mixed>>}>
     */
    public function getMapStatuses(): array
    {
        $zones = $this->db->query(
            "SELECT id_zone, nom_zone FROM zone WHERE type_zone = 'gouvernorat' ORDER BY nom_zone"
        )->fetchAll();

        // Interruptions encore pertinentes : en cours, ou planifiées dont la fin prévue n'est pas passée
        $stmt = $this->db->query(
            "SELECT id_interruption, id_zone, type_ressource, statut, cause, date_debut, date_fin_prevue,
                    (statut = 'en_cours' OR date_debut <= NOW()) AS active
               FROM interruption
              WHERE statut = 'en_cours'
                 OR (statut = 'planifiee' AND date_fin_prevue > NOW())
              ORDER BY active DESC, date_debut ASC"
        );
        $parZone = [];
        foreach ($stmt as $row) {
            $parZone[(int) $row['id_zone']][] = [
                'id'     => (int) $row['id_interruption'],
                'type'   => (string) $row['type_ressource'],
                'statut' => (string) $row['statut'],
                'active' => (bool) $row['active'],
                'cause'  => (string) $row['cause'],
                'debut'  => date('d/m/Y H:i', strtotime((string) $row['date_debut'])),
                'fin'    => date('d/m/Y H:i', strtotime((string) $row['date_fin_prevue'])),
            ];
        }

        // « Coupure d'eau », « Coupure d'électricité » ou « Coupures d'eau et d'électricité »
        $libelle = static function (array $items): string {
            $types = array_unique(array_column($items, 'type'));
            if (count($types) > 1) {
                return 'Coupures d\'eau et d\'électricité';
            }
            return 'Coupure' . (count($items) > 1 ? 's' : '') . ($types[0] === 'eau' ? ' d\'eau' : ' d\'électricité');
        };

        $result = [];
        foreach ($zones as $z) {
            $liste   = $parZone[(int) $z['id_zone']] ?? [];
            $actives = array_values(array_filter($liste, fn (array $i) => $i['active']));
            $avenir  = array_values(array_filter($liste, fn (array $i) => !$i['active']));
            if ($actives) {
                $status  = 'outage';
                $summary = $libelle($actives) . ' en cours';
                if ($avenir) {
                    $summary .= ' · ' . count($avenir) . ' à venir';
                }
            } elseif ($avenir) {
                $status  = 'watch';
                $summary = $libelle($avenir) . ' prévue' . (count($avenir) > 1 ? 's' : '')
                         . ' le ' . substr($avenir[0]['debut'], 0, 5) . ' à ' . substr($avenir[0]['debut'], 11);
            } else {
                $status  = 'normal';
                $summary = 'Aucune interruption';
            }
            $result[] = [
                'name'          => (string) $z['nom_zone'],
                'status'        => $status,
                'summary'       => $summary,
                'interruptions' => $liste,
            ];
        }
        return $result;
    }

    /**
     * Fil du « Centre de notifications » (service Alertes personnalisées), construit UNIQUEMENT
     * depuis les interruptions du Back Office : une notification par interruption.
     *   en_cours (ou planifiée déjà commencée) → sévérité « critique », active
     *   planifiee à venir                      → sévérité « elevee », active
     *   terminee                               → sévérité « info », historique (non active)
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAlertFeed(int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, z.nom_zone FROM interruption i JOIN zone z ON z.id_zone = i.id_zone
              ORDER BY (i.statut = \'terminee\') ASC, i.date_publication DESC, i.id_interruption DESC
              LIMIT ' . max(1, $limit)
        );
        $stmt->execute();

        $now  = time();
        $fmt  = static fn (?string $d): string => $d ? date('d/m/Y à H:i', strtotime($d)) : '';
        $feed = [];
        foreach ($stmt->fetchAll() as $row) {
            $i       = InterruptionModel::fromArray($row);
            $estEau  = $i->getTypeRessource() === 'eau';
            $quoi    = $estEau ? 'Coupure d\'eau' : 'Coupure d\'électricité';
            $gov     = $i->getNomZone();
            $debut   = strtotime($i->getDateDebut());
            $termine = $i->getStatut() === 'terminee';
            $actif   = !$termine && ($i->getStatut() === 'en_cours' || $debut <= $now);

            if ($termine) {
                $severity = 'info';
                $title    = ($estEau ? 'Eau rétablie' : 'Électricité rétablie') . ' — ' . $gov;
                $text     = 'Interruption terminée' . ($i->getDateFinReelle() ? ' le ' . $fmt($i->getDateFinReelle()) : '')
                          . '. Cause : ' . $i->getCause() . '.';
                $date     = $i->getDateFinReelle() ?? $i->getDateFinPrevue();
            } elseif ($actif) {
                $severity = 'critique';
                $title    = $quoi . ' en cours — ' . $gov;
                $text     = 'Cause : ' . $i->getCause() . '. Début le ' . $fmt($i->getDateDebut())
                          . ', rétablissement prévu le ' . $fmt($i->getDateFinPrevue()) . '.';
                $date     = $i->getDatePublication();
            } else {
                $severity = 'elevee';
                $title    = $quoi . ' programmée — ' . $gov;
                $text     = 'Cause : ' . $i->getCause() . '. Du ' . $fmt($i->getDateDebut())
                          . ' au ' . $fmt($i->getDateFinPrevue()) . '.';
                $date     = $i->getDatePublication();
            }

            $feed[] = [
                'id'       => 'int-' . $i->getIdInterruption(),
                'category' => $i->getTypeRessource(),      // eau | electricite
                'service'  => $i->getTypeRessource(),
                'severity' => $severity,
                'active'   => !$termine,
                'title'    => $title,
                'text'     => $text,
                'gov'      => $gov,
                'area'     => 'Tout le gouvernorat',
                'date'     => date('c', strtotime((string) $date)),
            ];
        }
        return $feed;
    }

    public function getById(int $id): ?InterruptionModel
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, z.nom_zone FROM interruption i JOIN zone z ON z.id_zone = i.id_zone
              WHERE i.id_interruption = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? InterruptionModel::fromArray($row) : null;
    }

    // ==============================================================
    // ÉCRITURE
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = enregistré)
     */
    public function create(array $post, int $idAuteur, string $role): array
    {
        $errors = $this->check($post, $role);
        if ($errors) {
            return $errors;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO interruption (type_ressource, id_zone, statut, cause, date_debut, date_fin_prevue, date_fin_reelle, id_auteur)
             VALUES (:type, :zone, :statut, :cause, :debut, :fin, :fin_reelle, :auteur)'
        );
        $stmt->execute($this->params($post) + ['auteur' => $idAuteur]);
        return [];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = enregistré)
     */
    public function update(InterruptionModel $existing, array $post, string $role): array
    {
        $errors = $this->check($post, $role);
        if ($errors) {
            return $errors;
        }
        $params = $this->params($post);
        // On garde la fin réelle déjà enregistrée si l'interruption reste terminée
        if ($params['statut'] === 'terminee' && $existing->getDateFinReelle() !== null) {
            $params['fin_reelle'] = $existing->getDateFinReelle();
        }
        $stmt = $this->db->prepare(
            'UPDATE interruption
                SET type_ressource = :type, id_zone = :zone, statut = :statut, cause = :cause,
                    date_debut = :debut, date_fin_prevue = :fin, date_fin_reelle = :fin_reelle
              WHERE id_interruption = :id'
        );
        $stmt->execute($params + ['id' => $existing->getIdInterruption()]);
        return [];
    }

    /** Clôture : statut « terminee » + fin réelle = maintenant. */
    public function close(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE interruption SET statut = 'terminee', date_fin_reelle = NOW()
              WHERE id_interruption = :id AND statut <> 'terminee'"
        );
        $stmt->execute(['id' => $id]);
    }

    /** Les signalements et alertes liés gardent leur ligne (clé étrangère ON DELETE SET NULL). */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM interruption WHERE id_interruption = :id');
        $stmt->execute(['id' => $id]);
    }

    // ==============================================================
    // CONTRÔLE DE SAISIE (serveur)
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    private function check(array $post, string $role): array
    {
        $errors = $this->withMessages($this->validate($post, [
            'type_ressource'  => 'required|in:' . implode(',', self::TYPES),
            'id_zone'         => 'required|int',
            'statut'          => 'required|in:' . implode(',', self::STATUTS),
            'date_debut'      => 'required',
            'date_fin_prevue' => 'required',
            'cause'           => 'required|min:4|max:255',
        ]));

        if (!isset($errors['type_ressource']) && !self::canManage($role, (string) $post['type_ressource'])) {
            $errors['type_ressource'] = 'Votre rôle ne permet pas de publier ce type d\'interruption.';
        }
        if (!isset($errors['id_zone']) && !(new ZoneController())->exists((int) $post['id_zone'])) {
            $errors['id_zone'] = 'Zone inconnue.';
        }

        $debut = isset($errors['date_debut']) ? null : self::parseDateTime((string) $post['date_debut']);
        $fin   = isset($errors['date_fin_prevue']) ? null : self::parseDateTime((string) $post['date_fin_prevue']);
        if (!isset($errors['date_debut']) && $debut === null) {
            $errors['date_debut'] = 'Date et heure invalides.';
        }
        if (!isset($errors['date_fin_prevue']) && $fin === null) {
            $errors['date_fin_prevue'] = 'Date et heure invalides.';
        }
        if ($debut !== null && $fin !== null && $fin <= $debut) {
            $errors['date_fin_prevue'] = self::MESSAGES['date_fin_prevue']['after'];
        }

        return $errors;
    }

    /** Accepte « AAAA-MM-JJTHH:MM » (champ datetime-local) ou « AAAA-MM-JJ HH:MM[:SS] ». */
    private static function parseDateTime(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $format) {
            $d = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($d !== false && $d->format($format) === $value) {
                return $d;
            }
        }
        return null;
    }

    /** @param array<string, mixed> $post */
    private function params(array $post): array
    {
        $statut = (string) $post['statut'];
        return [
            'type'       => (string) $post['type_ressource'],
            'zone'       => (int) $post['id_zone'],
            'statut'     => $statut,
            'cause'      => trim((string) $post['cause']),
            'debut'      => self::parseDateTime((string) $post['date_debut'])->format('Y-m-d H:i:s'),
            'fin'        => self::parseDateTime((string) $post['date_fin_prevue'])->format('Y-m-d H:i:s'),
            'fin_reelle' => $statut === 'terminee' ? date('Y-m-d H:i:s') : null,
        ];
    }
}
