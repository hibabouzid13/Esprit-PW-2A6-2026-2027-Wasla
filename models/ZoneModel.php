<?php
/**
 * models/ZoneModel.php
 * Représente UNE ligne de la table « zone » (attributs + getters/setters uniquement).
 * Le CRUD des zones appartient au Module 1 (branche module1-interruptions).
 */

declare(strict_types=1);

class ZoneModel
{
    private int $idZone = 0;
    private string $nomZone = '';
    private string $typeZone = 'gouvernorat';
    private ?int $idZoneParent = null;

    public function __construct()
    {
    }

    public function getIdZone(): int { return $this->idZone; }
    public function getNomZone(): string { return $this->nomZone; }
    public function getTypeZone(): string { return $this->typeZone; }
    public function getIdZoneParent(): ?int { return $this->idZoneParent; }

    public function setIdZone(int $id): void { $this->idZone = $id; }
    public function setNomZone(string $nom): void { $this->nomZone = $nom; }
    public function setTypeZone(string $type): void { $this->typeZone = $type; }
    public function setIdZoneParent(?int $id): void { $this->idZoneParent = $id; }

    public static function fromArray(array $row): self
    {
        $zone = new self();
        $zone->setIdZone((int) ($row['id_zone'] ?? 0));
        $zone->setNomZone((string) ($row['nom_zone'] ?? ''));
        $zone->setTypeZone((string) ($row['type_zone'] ?? 'gouvernorat'));
        $zone->setIdZoneParent(isset($row['id_zone_parent']) ? (int) $row['id_zone_parent'] : null);
        return $zone;
    }
}
