<?php
/**
 * controllers/ReservationRefugeController.php — Module 3 (Louay)
 * Réservations de places dans une refuge zone (table reservation_refuge).
 *
 * Règle métier : une réservation « en_attente » ou « confirmee » OCCUPE des places
 * (refuge_zone.places_disponibles diminue) ; « annulee » ou « terminee » les LIBÈRE.
 * Chaque changement se fait dans une transaction avec verrou (SELECT … FOR UPDATE)
 * pour que deux citoyens ne puissent pas réserver la dernière place en même temps.
 */

declare(strict_types=1);

require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/Module3Saisie.php';
require_once __DIR__ . '/../models/ReservationRefugeModel.php';

class ReservationRefugeController extends Controller
{
    use Module3Saisie;

    /** Nombre maximal de personnes par réservation (contrainte chk_reservation_personnes). */
    public const PERSONNES_MAX = 8;

    /**
     * Rôles autorisés à réserver une place. Le fournisseur (compte professionnel) n'en fait pas partie ;
     * un bénévole ou un propriétaire de panneaux solaires est un citoyen (pas de rôle à part).
     */
    public const ROLES_RESERVATION = ['citoyen'];

    /** Messages du formulaire de réservation : utilisés par PHP ET écrits dans la vue (data-message-*). */
    public const MESSAGES = [
        'nom_contact'      => ['required' => 'Indiquez votre nom.'],
        'telephone'        => ['required' => 'Indiquez un numéro de téléphone.'],
        'nombre_personnes' => ['required'  => 'Indiquez le nombre de personnes.',
                               'max-value' => 'Au plus {max} personnes par réservation.'],
    ];

    private const SELECT = 'SELECT x.*, r.nom AS nom_refuge, r.adresse AS adresse_refuge, z.nom_zone
                            FROM reservation_refuge x
                            JOIN refuge_zone r ON r.id_refuge = x.id_refuge
                            JOIN zone z ON z.id_zone = r.id_zone';

    // ==============================================================
    // LECTURE
    // ==============================================================

    /** @return ReservationRefugeModel[] réservations d'un citoyen */
    public function getByUser(int $idUtilisateur): array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE x.id_utilisateur = :id ORDER BY x.date_reservation DESC');
        $stmt->execute(['id' => $idUtilisateur]);
        return $this->hydrate($stmt->fetchAll());
    }

    /** @return ReservationRefugeModel[] réservations reçues sur les refuges d'une entreprise */
    public function getByEntreprise(int $idEntreprise): array
    {
        $stmt = $this->db->prepare(
            self::SELECT . " WHERE r.id_entreprise = :id
                             ORDER BY x.statut = 'en_attente' DESC, x.date_reservation DESC"
        );
        $stmt->execute(['id' => $idEntreprise]);
        return $this->hydrate($stmt->fetchAll());
    }

    /** @return array<int, int> id_refuge => personnes réservées par cet utilisateur (réservations actives) */
    public function activeByUser(int $idUtilisateur): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_refuge, SUM(nombre_personnes) AS n FROM reservation_refuge
              WHERE id_utilisateur = :id AND statut IN ('en_attente','confirmee')
              GROUP BY id_refuge"
        );
        $stmt->execute(['id' => $idUtilisateur]);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['id_refuge']] = (int) $row['n'];
        }
        return $map;
    }

    // ==============================================================
    // RÉSERVER (citoyen)
    // ==============================================================

    /**
     * @param array<string, mixed> $post
     * @return array<string, string> erreurs par champ (vide = succès)
     */
    public function reserve(int $idRefuge, array $post, UserModel $user): array
    {
        if (!in_array($user->getRole(), self::ROLES_RESERVATION, true)) {
            return ['form' => 'Votre type de compte ne permet pas de réserver une place.'];
        }

        // Mêmes règles que les data-rules du formulaire
        $errors = $this->validateWithMessages($post, [
            'nom_contact'      => 'required|min:3|max:100',
            'telephone'        => 'required|phone',
            'nombre_personnes' => 'required|int|positive',
        ]);
        $personnes = (int) self::text($post, 'nombre_personnes');
        if (!isset($errors['nombre_personnes']) && $personnes > self::PERSONNES_MAX) {
            $errors['nombre_personnes'] = self::message('nombre_personnes', 'max-value', ['{max}' => self::PERSONNES_MAX]);
        }
        $besoins = $post['besoins'] ?? [];
        $besoins = is_array($besoins) ? array_values(array_unique(array_filter($besoins, 'is_string'))) : [];
        if (array_diff($besoins, ReservationRefugeModel::BESOINS) !== []) {
            $errors['besoins'] = 'Besoin non autorisé.';
        }
        if ($errors !== []) {
            return $errors;
        }

        $this->db->beginTransaction();
        try {
            // Verrou sur le refuge : personne d'autre ne peut modifier ses places pendant ce temps
            $stmt = $this->db->prepare(
                "SELECT r.places_disponibles, r.statut
                   FROM refuge_zone r JOIN utilisateur u ON u.id_utilisateur = r.id_entreprise
                  WHERE r.id_refuge = :id AND u.compte_verifie = 1 AND u.statut_compte = 'actif'
                  FOR UPDATE"
            );
            $stmt->execute(['id' => $idRefuge]);
            $refuge = $stmt->fetch();

            if (!$refuge || $refuge['statut'] !== 'ouvert') {
                $this->db->rollBack();
                return ['form' => "Ce lieu n'accepte pas de réservation pour le moment."];
            }
            if ($personnes > (int) $refuge['places_disponibles']) {
                $this->db->rollBack();
                return ['nombre_personnes' => 'Il ne reste que ' . (int) $refuge['places_disponibles'] . ' place(s).'];
            }

            $stmt = $this->db->prepare(
                "SELECT 1 FROM reservation_refuge
                  WHERE id_refuge = :refuge AND id_utilisateur = :user AND statut IN ('en_attente','confirmee') LIMIT 1"
            );
            $stmt->execute(['refuge' => $idRefuge, 'user' => $user->getIdUtilisateur()]);
            if ($stmt->fetchColumn()) {
                $this->db->rollBack();
                return ['form' => 'Vous avez déjà une réservation en cours dans ce lieu. Annulez-la pour en faire une nouvelle.'];
            }

            $stmt = $this->db->prepare(
                "INSERT INTO reservation_refuge (id_refuge, id_utilisateur, nom_contact, telephone, nombre_personnes, besoins, statut)
                 VALUES (:refuge, :user, :nom, :tel, :personnes, :besoins, 'en_attente')"
            );
            $stmt->execute([
                'refuge'    => $idRefuge,
                'user'      => $user->getIdUtilisateur(),
                'nom'       => self::text($post, 'nom_contact'),
                'tel'       => preg_replace('/[\s.-]/u', '', self::text($post, 'telephone')),
                'personnes' => $personnes,
                'besoins'   => $besoins === [] ? null : implode(',', $besoins),
            ]);

            $this->changePlaces($idRefuge, -$personnes);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Réservation refuge : ' . $e->getMessage());
            return ['form' => "La réservation n'a pas pu être enregistrée. Veuillez réessayer."];
        }
        return [];
    }

    // ==============================================================
    // CHANGEMENTS DE STATUT
    // ==============================================================

    /** Le citoyen annule SA réservation (seulement si elle est encore active). */
    public function cancelByUser(int $idReservation, UserModel $user): bool
    {
        return $this->changeStatut($idReservation, 'annulee', 'x.id_utilisateur = :owner', $user->getIdUtilisateur());
    }

    /**
     * L'entreprise traite une réservation reçue sur un de SES refuges :
     * confirmee (accueil prévu), annulee (refusée) ou terminee (personnes reparties).
     */
    public function setStatutByEntreprise(int $idReservation, string $statut, UserModel $entreprise): bool
    {
        if ($entreprise->getRole() !== 'entreprise' || !in_array($statut, ['confirmee', 'annulee', 'terminee'], true)) {
            return false;
        }
        return $this->changeStatut($idReservation, $statut, 'r.id_entreprise = :owner', $entreprise->getIdUtilisateur());
    }

    /**
     * @param string $ownerCondition condition SQL fixe (jamais saisie par l'utilisateur) qui vérifie la propriété
     */
    private function changeStatut(int $idReservation, string $statut, string $ownerCondition, int $ownerId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT x.id_refuge, x.nombre_personnes, x.statut
                   FROM reservation_refuge x JOIN refuge_zone r ON r.id_refuge = x.id_refuge
                  WHERE x.id_reservation = :id AND $ownerCondition
                  FOR UPDATE"
            );
            $stmt->execute(['id' => $idReservation, 'owner' => $ownerId]);
            $row = $stmt->fetch();

            // Une réservation annulée ou terminée ne peut plus changer
            if (!$row || !in_array($row['statut'], ReservationRefugeModel::STATUTS_ACTIFS, true) || $row['statut'] === $statut) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare('UPDATE reservation_refuge SET statut = :statut WHERE id_reservation = :id');
            $stmt->execute(['statut' => $statut, 'id' => $idReservation]);

            if (!in_array($statut, ReservationRefugeModel::STATUTS_ACTIFS, true)) {
                $this->changePlaces((int) $row['id_refuge'], (int) $row['nombre_personnes']); // places libérées
            }
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Statut réservation refuge : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute (+n) ou retire (−n) des places, puis met à jour « ouvert » / « complet ».
     * Un refuge fermé ou en attente garde son statut.
     * (MySQL évalue les SET de gauche à droite : le CASE voit déjà les nouvelles places.)
     */
    private function changePlaces(int $idRefuge, int $delta): void
    {
        $stmt = $this->db->prepare(
            "UPDATE refuge_zone
                SET places_disponibles = LEAST(capacite, GREATEST(0, places_disponibles + :delta)),
                    statut = CASE WHEN statut IN ('ouvert','complet')
                                  THEN IF(places_disponibles = 0, 'complet', 'ouvert')
                                  ELSE statut END
              WHERE id_refuge = :id"
        );
        $stmt->execute(['delta' => $delta, 'id' => $idRefuge]);
    }

    /** @return ReservationRefugeModel[] */
    private function hydrate(array $rows): array
    {
        return array_map(fn (array $row) => ReservationRefugeModel::fromArray($row), $rows);
    }
}
