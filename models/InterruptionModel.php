<?php
/**
 * models/InterruptionModel.php — Module 1 (Houssem)
 * Représente UNE ligne de la table « interruption » (attributs + getters/setters uniquement).
 * nomZone n'est pas une colonne : il vient de la jointure avec « zone » (affichage).
 */

declare(strict_types=1);

class InterruptionModel
{
    private int $idInterruption = 0;
    private string $typeRessource = 'electricite';
    private int $idZone = 0;
    private string $nomZone = '';
    private string $statut = 'planifiee';
    private string $cause = '';
    private string $dateDebut = '';
    private string $dateFinPrevue = '';
    private ?string $dateFinReelle = null;
    private ?int $idAuteur = null;
    private string $datePublication = '';

    public function __construct()
    {
    }

    public function getIdInterruption(): int { return $this->idInterruption; }
    public function getTypeRessource(): string { return $this->typeRessource; }
    public function getIdZone(): int { return $this->idZone; }
    public function getNomZone(): string { return $this->nomZone; }
    public function getStatut(): string { return $this->statut; }
    public function getCause(): string { return $this->cause; }
    public function getDateDebut(): string { return $this->dateDebut; }
    public function getDateFinPrevue(): string { return $this->dateFinPrevue; }
    public function getDateFinReelle(): ?string { return $this->dateFinReelle; }
    public function getIdAuteur(): ?int { return $this->idAuteur; }
    public function getDatePublication(): string { return $this->datePublication; }

    public function setIdInterruption(int $id): void { $this->idInterruption = $id; }
    public function setTypeRessource(string $type): void { $this->typeRessource = $type; }
    public function setIdZone(int $id): void { $this->idZone = $id; }
    public function setNomZone(string $nom): void { $this->nomZone = $nom; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setCause(string $cause): void { $this->cause = $cause; }
    public function setDateDebut(string $date): void { $this->dateDebut = $date; }
    public function setDateFinPrevue(string $date): void { $this->dateFinPrevue = $date; }
    public function setDateFinReelle(?string $date): void { $this->dateFinReelle = $date; }
    public function setIdAuteur(?int $id): void { $this->idAuteur = $id; }
    public function setDatePublication(string $date): void { $this->datePublication = $date; }

    public static function fromArray(array $row): self
    {
        $i = new self();
        $i->setIdInterruption((int) ($row['id_interruption'] ?? 0));
        $i->setTypeRessource((string) ($row['type_ressource'] ?? 'electricite'));
        $i->setIdZone((int) ($row['id_zone'] ?? 0));
        $i->setNomZone((string) ($row['nom_zone'] ?? ''));
        $i->setStatut((string) ($row['statut'] ?? 'planifiee'));
        $i->setCause((string) ($row['cause'] ?? ''));
        $i->setDateDebut((string) ($row['date_debut'] ?? ''));
        $i->setDateFinPrevue((string) ($row['date_fin_prevue'] ?? ''));
        $i->setDateFinReelle(isset($row['date_fin_reelle']) ? (string) $row['date_fin_reelle'] : null);
        $i->setIdAuteur(isset($row['id_auteur']) ? (int) $row['id_auteur'] : null);
        $i->setDatePublication((string) ($row['date_publication'] ?? ''));
        return $i;
    }
}
