<?php
/**
 * controllers/SignalementController.php — Module 1 (Houssem)
 * Signalements de coupure envoyés par les utilisateurs connectés (table « signalement »).
 * Le contrôle d'accès (être connecté) est fait dans la route, avant tout appel.
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/FormMessages.php';
require_once __DIR__ . '/ZoneController.php';
require_once __DIR__ . '/InterruptionController.php';
require_once __DIR__ . '/../models/SignalementModel.php';

class SignalementController extends Controller
{
    use FormMessages;

    /** Messages du formulaire « Signaler une coupure » : les mêmes dans carte.php et ici. */
    public const MESSAGES = [
        'zone'    => ['required' => 'Sélectionnez le gouvernorat.'],
        'type'    => ['required' => 'Choisissez le type de réseau.'],
        'address' => ['required' => "Indiquez l'adresse ou la zone."],
    ];

    /**
     * Valide puis enregistre un signalement.
     *
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = enregistré)
     */
    public function create(array $post, int $idUtilisateur): array
    {
        $errors = $this->withMessages($this->validate($post, [
            'zone'    => 'required|int',
            'type'    => 'required|in:electricite,eau',
            'address' => 'required|min:3|max:255',
            'message' => 'max:1000',
        ]));

        if (!isset($errors['zone']) && !(new ZoneController())->exists((int) $post['zone'])) {
            $errors['zone'] = 'Gouvernorat inconnu.';
        }
        if ($errors) {
            return $errors;
        }

        $message = trim((string) ($post['message'] ?? ''));
        $stmt = $this->db->prepare(
            'INSERT INTO signalement (id_utilisateur, id_zone, type_ressource, adresse, message)
             VALUES (:id_utilisateur, :id_zone, :type_ressource, :adresse, :message)'
        );
        $stmt->execute([
            'id_utilisateur' => $idUtilisateur,
            'id_zone'        => (int) $post['zone'],
            'type_ressource' => (string) $post['type'],
            'adresse'        => trim((string) $post['address']),
            'message'        => $message !== '' ? $message : null,
        ]);

        return [];
    }

    // ==============================================================
    // BACK OFFICE (admin, STEG, SONEDE) — mêmes droits par ressource
    // que les interruptions : voir InterruptionController::typesFor()
    // ==============================================================

    public const STATUTS = ['en_attente', 'confirme', 'rejete'];

    /**
     * Tous les signalements visibles par ce rôle, avec l'auteur et le nombre de « moi aussi ».
     *
     * @return array<int, array{signalement: SignalementModel, auteur: string, email: string, confirmations: int}>
     */
    public function getAllForAdmin(string $role): array
    {
        $types = InterruptionController::typesFor($role);
        if (!$types) {
            return [];
        }
        $in = implode(',', array_fill(0, count($types), '?'));
        $stmt = $this->db->prepare(
            "SELECT s.*, z.nom_zone, u.prenom, u.nom, u.email,
                    (SELECT COUNT(*) FROM confirmation c WHERE c.id_signalement = s.id_signalement) AS nb_confirmations
               FROM signalement s
               JOIN zone z        ON z.id_zone = s.id_zone
               JOIN utilisateur u ON u.id_utilisateur = s.id_utilisateur
              WHERE s.type_ressource IN ($in)
              ORDER BY s.date_signalement DESC, s.id_signalement DESC"
        );
        $stmt->execute($types);
        return array_map(fn (array $row) => [
            'signalement'   => SignalementModel::fromArray($row),
            'auteur'        => trim($row['prenom'] . ' ' . $row['nom']),
            'email'         => (string) $row['email'],
            'confirmations' => (int) $row['nb_confirmations'],
        ], $stmt->fetchAll());
    }

    public function getById(int $id): ?SignalementModel
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, z.nom_zone FROM signalement s JOIN zone z ON z.id_zone = s.id_zone
              WHERE s.id_signalement = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? SignalementModel::fromArray($row) : null;
    }

    /**
     * Traitement par le Back Office : nouveau statut + rattachement facultatif à une interruption
     * officielle (même zone et même ressource que le signalement).
     *
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs (vide = enregistré)
     */
    public function process(SignalementModel $signalement, array $post): array
    {
        $errors = $this->validate($post, [
            'statut'          => 'required|in:' . implode(',', self::STATUTS),
            'id_interruption' => 'int',
        ]);
        if ($errors) {
            return $errors;
        }

        $idInterruption = trim((string) ($post['id_interruption'] ?? ''));
        $idInterruption = $idInterruption === '' ? null : (int) $idInterruption;

        if ($idInterruption !== null) {
            $interruption = (new InterruptionController())->getById($idInterruption);
            if ($interruption === null) {
                return ['id_interruption' => 'Interruption introuvable.'];
            }
            if ($interruption->getIdZone() !== $signalement->getIdZone()
                || $interruption->getTypeRessource() !== $signalement->getTypeRessource()) {
                return ['id_interruption' => 'L\'interruption doit concerner la même zone et la même ressource.'];
            }
            if ($post['statut'] === 'rejete') {
                return ['statut' => 'Un signalement rejeté ne peut pas être rattaché à une interruption.'];
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE signalement SET statut = :statut, id_interruption = :id_interruption
              WHERE id_signalement = :id'
        );
        $stmt->execute([
            'statut'          => (string) $post['statut'],
            'id_interruption' => $idInterruption,
            'id'              => $signalement->getIdSignalement(),
        ]);
        return [];
    }

    /** Les « moi aussi » liés sont supprimés avec (clé étrangère ON DELETE CASCADE). */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM signalement WHERE id_signalement = :id');
        $stmt->execute(['id' => $id]);
    }

    // ==============================================================
    // FRONT OFFICE
    // ==============================================================

    /** @return SignalementModel[] les signalements de l'utilisateur, du plus récent au plus ancien */
    public function getByUser(int $idUtilisateur, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, z.nom_zone
               FROM signalement s
               JOIN zone z ON z.id_zone = s.id_zone
              WHERE s.id_utilisateur = :id
              ORDER BY s.date_signalement DESC, s.id_signalement DESC
              LIMIT ' . max(1, $limit)
        );
        $stmt->execute(['id' => $idUtilisateur]);
        return array_map(fn (array $row) => SignalementModel::fromArray($row), $stmt->fetchAll());
    }
}
