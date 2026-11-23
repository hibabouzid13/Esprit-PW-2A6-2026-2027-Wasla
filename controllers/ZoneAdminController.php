<?php
/**
 * controllers/ZoneAdminController.php — Module 1 (Houssem)
 * CRUD complet des zones géographiques (Back Office, super admin).
 * Hérite de ZoneController (fichier commun : getGouvernorats(), exists()) sans le modifier.
 *
 * Une zone est un « gouvernorat » (pas de parent) ou une « delegation » (parent = un gouvernorat).
 * Suppression : refusée tant que la zone est utilisée (interruptions, signalements, lieux suivis,
 * refuges, demandes d'assistance… : clés étrangères ON DELETE RESTRICT), qu'elle a des délégations
 * ou que des comptes y sont rattachés.
 */

declare(strict_types=1);

require_once __DIR__ . '/ZoneController.php';
require_once __DIR__ . '/FormMessages.php';

class ZoneAdminController extends ZoneController
{
    use FormMessages;

    /** Messages des champs : les mêmes dans la vue (data-message-*) et ici (withMessages). */
    public const MESSAGES = [
        'nom_zone'  => ['required' => 'Indiquez le nom de la zone.'],
        'type_zone' => ['required' => 'Choisissez le type de zone.'],
    ];

    public const TYPES = ['gouvernorat', 'delegation'];

    /** Tables qui utilisent une zone (table => libellé affiché si la suppression est refusée). */
    private const USAGES = [
        'interruption'         => 'interruption(s)',
        'signalement'          => 'signalement(s)',
        'utilisateur'          => 'compte(s)',
        'lieu_suivi'           => 'lieu(x) suivi(s)',
        'refuge_zone'          => 'refuge(s)',
        'demande_assistance'   => "demande(s) d'assistance",
        'personne_prioritaire' => 'personne(s) prioritaire(s)',
        'installation_solaire' => 'installation(s) solaire(s)',
        'alerte'               => 'alerte(s)',
    ];

    // ==============================================================
    // LECTURE (Back Office)
    // ==============================================================

    /**
     * Toutes les zones pour le Back Office : gouvernorat parent + nombre de délégations.
     *
     * @return array<int, array{zone: ZoneModel, parent: ?string, delegations: int}>
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT z.*, p.nom_zone AS nom_parent,
                    (SELECT COUNT(*) FROM zone d WHERE d.id_zone_parent = z.id_zone) AS nb_delegations
               FROM zone z
               LEFT JOIN zone p ON p.id_zone = z.id_zone_parent
              ORDER BY COALESCE(p.nom_zone, z.nom_zone), z.type_zone = 'delegation', z.nom_zone"
        );
        return array_map(fn (array $row) => [
            'zone'        => ZoneModel::fromArray($row),
            'parent'      => $row['nom_parent'] !== null ? (string) $row['nom_parent'] : null,
            'delegations' => (int) $row['nb_delegations'],
        ], $stmt->fetchAll());
    }

    public function getById(int $id): ?ZoneModel
    {
        $stmt = $this->db->prepare('SELECT * FROM zone WHERE id_zone = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? ZoneModel::fromArray($row) : null;
    }

    // ==============================================================
    // ÉCRITURE (Back Office, super admin)
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = enregistré)
     */
    public function create(array $post): array
    {
        $errors = $this->check($post, null);
        if ($errors) {
            return $errors;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO zone (nom_zone, type_zone, id_zone_parent) VALUES (:nom, :type, :parent)'
        );
        $stmt->execute($this->params($post));
        return [];
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = enregistré)
     */
    public function update(ZoneModel $zone, array $post): array
    {
        $errors = $this->check($post, $zone);
        if ($errors) {
            return $errors;
        }
        $stmt = $this->db->prepare(
            'UPDATE zone SET nom_zone = :nom, type_zone = :type, id_zone_parent = :parent WHERE id_zone = :id'
        );
        $stmt->execute($this->params($post) + ['id' => $zone->getIdZone()]);
        return [];
    }

    /** @return string message d'erreur, ou '' si la zone a été supprimée */
    public function delete(ZoneModel $zone): string
    {
        $usages = $this->usages($zone->getIdZone());
        if ($usages) {
            return 'Zone utilisée : ' . implode(', ', $usages) . '. Supprimez ou déplacez d\'abord ces éléments.';
        }
        $stmt = $this->db->prepare('DELETE FROM zone WHERE id_zone = :id');
        $stmt->execute(['id' => $zone->getIdZone()]);
        return '';
    }

    /**
     * Ce qui empêche de supprimer la zone, ex. ["2 interruption(s)", "1 délégation(s)"].
     *
     * @return string[]
     */
    public function usages(int $idZone): array
    {
        $found = [];
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM zone WHERE id_zone_parent = :id');
        $stmt->execute(['id' => $idZone]);
        if ($n = (int) $stmt->fetchColumn()) {
            $found[] = $n . ' délégation(s)';
        }
        foreach (self::USAGES as $table => $label) {
            // Nom de table pris dans la constante ci-dessus (jamais dans la saisie) : pas d'injection possible
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM `$table` WHERE id_zone = :id");
            $stmt->execute(['id' => $idZone]);
            if ($n = (int) $stmt->fetchColumn()) {
                $found[] = $n . ' ' . $label;
            }
        }
        return $found;
    }

    // ==============================================================
    // CONTRÔLE DE SAISIE (serveur) — mêmes règles que les data-rules du formulaire
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    private function check(array $post, ?ZoneModel $current): array
    {
        $errors = $this->withMessages($this->validate($post, [
            'nom_zone'       => 'required|min:2|max:100',
            'type_zone'      => 'required|in:' . implode(',', self::TYPES),
            'id_zone_parent' => 'int',
        ]));
        if ($errors) {
            return $errors;
        }

        $type   = (string) $post['type_zone'];
        $nom    = trim((string) $post['nom_zone']);
        $parent = trim((string) ($post['id_zone_parent'] ?? ''));
        $parent = $parent === '' ? null : (int) $parent;

        if ($type === 'gouvernorat' && $parent !== null) {
            return ['id_zone_parent' => 'Un gouvernorat n\'a pas de zone parente.'];
        }
        if ($type === 'delegation') {
            if ($parent === null) {
                return ['id_zone_parent' => 'Choisissez le gouvernorat de cette délégation.'];
            }
            $p = $this->getById($parent);
            if ($p === null || $p->getTypeZone() !== 'gouvernorat') {
                return ['id_zone_parent' => 'La zone parente doit être un gouvernorat.'];
            }
            if ($current !== null && $parent === $current->getIdZone()) {
                return ['id_zone_parent' => 'Une zone ne peut pas être sa propre parente.'];
            }
        }
        // Un gouvernorat qui a des délégations ne peut pas devenir une délégation
        if ($current !== null && $current->getTypeZone() === 'gouvernorat' && $type === 'delegation') {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM zone WHERE id_zone_parent = :id');
            $stmt->execute(['id' => $current->getIdZone()]);
            if ((int) $stmt->fetchColumn() > 0) {
                return ['type_zone' => 'Ce gouvernorat a des délégations : il ne peut pas devenir une délégation.'];
            }
        }

        // Nom unique au même niveau (même parent). MySQL ne l'impose pas quand le parent est NULL.
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM zone
              WHERE nom_zone = :nom AND id_zone_parent <=> :parent AND id_zone <> :id'
        );
        $stmt->execute(['nom' => $nom, 'parent' => $parent, 'id' => $current ? $current->getIdZone() : 0]);
        if ((int) $stmt->fetchColumn() > 0) {
            return ['nom_zone' => 'Une zone porte déjà ce nom à ce niveau.'];
        }
        return [];
    }

    /** @param array<string, mixed> $post */
    private function params(array $post): array
    {
        $parent = trim((string) ($post['id_zone_parent'] ?? ''));
        return [
            'nom'    => trim((string) $post['nom_zone']),
            'type'   => (string) $post['type_zone'],
            'parent' => (string) $post['type_zone'] === 'delegation' && $parent !== '' ? (int) $parent : null,
        ];
    }
}
