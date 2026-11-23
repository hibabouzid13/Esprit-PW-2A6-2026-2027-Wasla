<?php
/**
 * models/SignalementModel.php — Module 1 (Houssem)
 * Représente UNE ligne de la table « signalement » (attributs + getters/setters uniquement).
 * nomZone n'est pas une colonne : il vient de la jointure avec « zone » (affichage).
 */

declare(strict_types=1);

class SignalementModel
{
    private int $idSignalement = 0;
    private int $idUtilisateur = 0;
    private int $idZone = 0;
    private string $nomZone = '';
    private string $typeRessource = 'electricite';
    private ?string $adresse = null;
    private ?string $message = null;
    private string $statut = 'en_attente';
    private ?int $idInterruption = null;
    private string $dateSignalement = '';

    public function __construct()
    {
    }

    public function getIdSignalement(): int { return $this->idSignalement; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function getIdZone(): int { return $this->idZone; }
    public function getNomZone(): string { return $this->nomZone; }
    public function getTypeRessource(): string { return $this->typeRessource; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function getMessage(): ?string { return $this->message; }
    public function getStatut(): string { return $this->statut; }
    public function getIdInterruption(): ?int { return $this->idInterruption; }
    public function getDateSignalement(): string { return $this->dateSignalement; }

    public function setIdSignalement(int $id): void { $this->idSignalement = $id; }
    public function setIdUtilisateur(int $id): void { $this->idUtilisateur = $id; }
    public function setIdZone(int $id): void { $this->idZone = $id; }
    public function setNomZone(string $nom): void { $this->nomZone = $nom; }
    public function setTypeRessource(string $type): void { $this->typeRessource = $type; }
    public function setAdresse(?string $adresse): void { $this->adresse = $adresse; }
    public function setMessage(?string $message): void { $this->message = $message; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setIdInterruption(?int $id): void { $this->idInterruption = $id; }
    public function setDateSignalement(string $date): void { $this->dateSignalement = $date; }

    public static function fromArray(array $row): self
    {
        $s = new self();
        $s->setIdSignalement((int) ($row['id_signalement'] ?? 0));
        $s->setIdUtilisateur((int) ($row['id_utilisateur'] ?? 0));
        $s->setIdZone((int) ($row['id_zone'] ?? 0));
        $s->setNomZone((string) ($row['nom_zone'] ?? ''));
        $s->setTypeRessource((string) ($row['type_ressource'] ?? 'electricite'));
        $s->setAdresse(isset($row['adresse']) ? (string) $row['adresse'] : null);
        $s->setMessage(isset($row['message']) ? (string) $row['message'] : null);
        $s->setStatut((string) ($row['statut'] ?? 'en_attente'));
        $s->setIdInterruption(isset($row['id_interruption']) ? (int) $row['id_interruption'] : null);
        $s->setDateSignalement((string) ($row['date_signalement'] ?? ''));
        return $s;
    }
}
