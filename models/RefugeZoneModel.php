<?php
/**
 * models/RefugeZoneModel.php — Module 3 (Louay)
 * Représente UNE ligne de la table « refuge_zone » (attributs + getters/setters + fromArray).
 * Aucune requête SQL ici : tout est dans controllers/RefugeZoneController.php.
 */

declare(strict_types=1);

class RefugeZoneModel
{
    /** Valeurs possibles de la colonne SET « services ». */
    public const SERVICES = ['climatisation', 'eau_potable', 'recharge', 'wifi', 'sanitaires', 'espace_enfants'];

    /** Valeurs possibles de la colonne ENUM « statut ». */
    public const STATUTS = ['en_attente', 'ouvert', 'complet', 'ferme'];

    private int $idRefuge = 0;
    private int $idEntreprise = 0;
    private string $nom = '';
    private string $adresse = '';
    private int $idZone = 0;
    private ?float $latitude = null;
    private ?float $longitude = null;
    /** @var string[] */
    private array $services = [];
    private int $capacite = 0;
    private int $placesDisponibles = 0;
    private ?string $horaires = null;
    private bool $accessiblePmr = false;
    private string $statut = 'en_attente';
    private string $dateCreation = '';

    // Champs remplis par les jointures (zone, utilisateur de rôle entreprise)
    private ?string $nomZone = null;
    private ?string $nomEntreprise = null;
    private ?string $telephoneEntreprise = null;

    public function __construct()
    {
    }

    // ---------------- GETTERS ----------------
    public function getIdRefuge(): int { return $this->idRefuge; }
    public function getIdEntreprise(): int { return $this->idEntreprise; }
    public function getNom(): string { return $this->nom; }
    public function getAdresse(): string { return $this->adresse; }
    public function getIdZone(): int { return $this->idZone; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function getLongitude(): ?float { return $this->longitude; }
    /** @return string[] */
    public function getServices(): array { return $this->services; }
    public function getCapacite(): int { return $this->capacite; }
    public function getPlacesDisponibles(): int { return $this->placesDisponibles; }
    public function getHoraires(): ?string { return $this->horaires; }
    public function isAccessiblePmr(): bool { return $this->accessiblePmr; }
    public function getStatut(): string { return $this->statut; }
    public function getDateCreation(): string { return $this->dateCreation; }
    public function getNomZone(): ?string { return $this->nomZone; }
    public function getNomEntreprise(): ?string { return $this->nomEntreprise; }
    public function getTelephoneEntreprise(): ?string { return $this->telephoneEntreprise; }

    /** Places occupées (réservations en cours). */
    public function getPlacesOccupees(): int { return $this->capacite - $this->placesDisponibles; }

    // ---------------- SETTERS ----------------
    public function setIdRefuge(int $id): void { $this->idRefuge = $id; }
    public function setIdEntreprise(int $id): void { $this->idEntreprise = $id; }
    public function setNom(string $nom): void { $this->nom = $nom; }
    public function setAdresse(string $adresse): void { $this->adresse = $adresse; }
    public function setIdZone(int $id): void { $this->idZone = $id; }
    public function setLatitude(?float $lat): void { $this->latitude = $lat; }
    public function setLongitude(?float $lon): void { $this->longitude = $lon; }
    /** @param string[] $services */
    public function setServices(array $services): void { $this->services = array_values($services); }
    public function setCapacite(int $capacite): void { $this->capacite = $capacite; }
    public function setPlacesDisponibles(int $places): void { $this->placesDisponibles = $places; }
    public function setHoraires(?string $horaires): void { $this->horaires = $horaires; }
    public function setAccessiblePmr(bool $pmr): void { $this->accessiblePmr = $pmr; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setDateCreation(string $date): void { $this->dateCreation = $date; }
    public function setNomZone(?string $nom): void { $this->nomZone = $nom; }
    public function setNomEntreprise(?string $nom): void { $this->nomEntreprise = $nom; }
    public function setTelephoneEntreprise(?string $tel): void { $this->telephoneEntreprise = $tel; }

    // ---------------- CONVERSION ligne SQL → objet ----------------
    public static function fromArray(array $row): self
    {
        $r = new self();
        $r->setIdRefuge((int) ($row['id_refuge'] ?? 0));
        $r->setIdEntreprise((int) ($row['id_entreprise'] ?? 0));
        $r->setNom((string) ($row['nom'] ?? ''));
        $r->setAdresse((string) ($row['adresse'] ?? ''));
        $r->setIdZone((int) ($row['id_zone'] ?? 0));
        $r->setLatitude(isset($row['latitude']) ? (float) $row['latitude'] : null);
        $r->setLongitude(isset($row['longitude']) ? (float) $row['longitude'] : null);
        $services = (string) ($row['services'] ?? '');
        $r->setServices($services === '' ? [] : explode(',', $services));
        $r->setCapacite((int) ($row['capacite'] ?? 0));
        $r->setPlacesDisponibles((int) ($row['places_disponibles'] ?? 0));
        $r->setHoraires(isset($row['horaires']) ? (string) $row['horaires'] : null);
        $r->setAccessiblePmr(!empty($row['accessible_pmr']));
        $r->setStatut((string) ($row['statut'] ?? 'en_attente'));
        $r->setDateCreation((string) ($row['date_creation'] ?? ''));
        $r->setNomZone(isset($row['nom_zone']) ? (string) $row['nom_zone'] : null);
        $r->setNomEntreprise(isset($row['nom_entreprise']) ? (string) $row['nom_entreprise'] : null);
        $r->setTelephoneEntreprise(isset($row['telephone_entreprise']) ? (string) $row['telephone_entreprise'] : null);
        return $r;
    }
}
