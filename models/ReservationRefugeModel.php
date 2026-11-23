<?php
/**
 * models/ReservationRefugeModel.php — Module 3 (Louay)
 * Représente UNE ligne de la table « reservation_refuge » (attributs + getters/setters + fromArray).
 * Aucune requête SQL ici : tout est dans controllers/ReservationRefugeController.php.
 */

declare(strict_types=1);

class ReservationRefugeModel
{
    /** Valeurs possibles de la colonne SET « besoins ». */
    public const BESOINS = ['bebe', 'medical', 'pmr', 'senior'];

    /** Valeurs possibles de la colonne ENUM « statut ». */
    public const STATUTS = ['en_attente', 'confirmee', 'annulee', 'terminee'];

    /** Statuts qui occupent encore des places dans le refuge. */
    public const STATUTS_ACTIFS = ['en_attente', 'confirmee'];

    private int $idReservation = 0;
    private int $idRefuge = 0;
    private int $idUtilisateur = 0;
    private string $nomContact = '';
    private string $telephone = '';
    private int $nombrePersonnes = 1;
    /** @var string[] */
    private array $besoins = [];
    private string $dateReservation = '';
    private string $statut = 'en_attente';

    // Champs remplis par les jointures
    private ?string $nomRefuge = null;
    private ?string $adresseRefuge = null;
    private ?string $nomZone = null;

    public function __construct()
    {
    }

    // ---------------- GETTERS ----------------
    public function getIdReservation(): int { return $this->idReservation; }
    public function getIdRefuge(): int { return $this->idRefuge; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function getNomContact(): string { return $this->nomContact; }
    public function getTelephone(): string { return $this->telephone; }
    public function getNombrePersonnes(): int { return $this->nombrePersonnes; }
    /** @return string[] */
    public function getBesoins(): array { return $this->besoins; }
    public function getDateReservation(): string { return $this->dateReservation; }
    public function getStatut(): string { return $this->statut; }
    public function getNomRefuge(): ?string { return $this->nomRefuge; }
    public function getAdresseRefuge(): ?string { return $this->adresseRefuge; }
    public function getNomZone(): ?string { return $this->nomZone; }

    public function isActive(): bool { return in_array($this->statut, self::STATUTS_ACTIFS, true); }

    // ---------------- SETTERS ----------------
    public function setIdReservation(int $id): void { $this->idReservation = $id; }
    public function setIdRefuge(int $id): void { $this->idRefuge = $id; }
    public function setIdUtilisateur(int $id): void { $this->idUtilisateur = $id; }
    public function setNomContact(string $nom): void { $this->nomContact = $nom; }
    public function setTelephone(string $tel): void { $this->telephone = $tel; }
    public function setNombrePersonnes(int $n): void { $this->nombrePersonnes = $n; }
    /** @param string[] $besoins */
    public function setBesoins(array $besoins): void { $this->besoins = array_values($besoins); }
    public function setDateReservation(string $date): void { $this->dateReservation = $date; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setNomRefuge(?string $nom): void { $this->nomRefuge = $nom; }
    public function setAdresseRefuge(?string $adresse): void { $this->adresseRefuge = $adresse; }
    public function setNomZone(?string $nom): void { $this->nomZone = $nom; }

    // ---------------- CONVERSION ligne SQL → objet ----------------
    public static function fromArray(array $row): self
    {
        $r = new self();
        $r->setIdReservation((int) ($row['id_reservation'] ?? 0));
        $r->setIdRefuge((int) ($row['id_refuge'] ?? 0));
        $r->setIdUtilisateur((int) ($row['id_utilisateur'] ?? 0));
        $r->setNomContact((string) ($row['nom_contact'] ?? ''));
        $r->setTelephone((string) ($row['telephone'] ?? ''));
        $r->setNombrePersonnes((int) ($row['nombre_personnes'] ?? 1));
        $besoins = (string) ($row['besoins'] ?? '');
        $r->setBesoins($besoins === '' ? [] : explode(',', $besoins));
        $r->setDateReservation((string) ($row['date_reservation'] ?? ''));
        $r->setStatut((string) ($row['statut'] ?? 'en_attente'));
        $r->setNomRefuge(isset($row['nom_refuge']) ? (string) $row['nom_refuge'] : null);
        $r->setAdresseRefuge(isset($row['adresse_refuge']) ? (string) $row['adresse_refuge'] : null);
        $r->setNomZone(isset($row['nom_zone']) ? (string) $row['nom_zone'] : null);
        return $r;
    }
}
