<?php
/**
 * models/ReponseModel.php — Module 4 (Bechir)
 * Représente UNE ligne de la table « reponse » (réponse d'un bénévole, d'un fournisseur
 * ou d'un propriétaire solaire à une demande d'assistance).
 *
 * Uniquement des attributs privés, des getters/setters et fromArray() : le SQL est dans
 * DemandeAssistanceController.
 */

declare(strict_types=1);

class ReponseModel
{
    public const STATUTS = ['proposee' => 'Proposée', 'acceptee' => 'Acceptée', 'refusee' => 'Refusée'];

    // ------------------------------------------------------------
    // ATTRIBUTS
    // ------------------------------------------------------------
    private int $idReponse = 0;
    private int $idDemande = 0;
    private int $idUtilisateur = 0;
    private ?int $idInstallation = null;
    private string $message = '';
    private string $statut = 'proposee';
    private string $dateReponse = '';

    // Informations jointes
    private ?string $auteurPrenom = null;
    private ?string $auteurNom = null;
    private ?string $auteurRole = null;
    private ?string $auteurOrganisation = null;
    private ?string $installationAdresse = null;
    private ?string $demandeTitre = null;

    public function __construct()
    {
    }

    // ------------------------------------------------------------
    // GETTERS
    // ------------------------------------------------------------
    public function getIdReponse(): int { return $this->idReponse; }
    public function getIdDemande(): int { return $this->idDemande; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function getIdInstallation(): ?int { return $this->idInstallation; }
    public function getMessage(): string { return $this->message; }
    public function getStatut(): string { return $this->statut; }
    public function getDateReponse(): string { return $this->dateReponse; }
    public function getAuteurPrenom(): ?string { return $this->auteurPrenom; }
    public function getAuteurNom(): ?string { return $this->auteurNom; }
    public function getAuteurRole(): ?string { return $this->auteurRole; }
    public function getAuteurOrganisation(): ?string { return $this->auteurOrganisation; }
    public function getInstallationAdresse(): ?string { return $this->installationAdresse; }
    public function getDemandeTitre(): ?string { return $this->demandeTitre; }

    // ------------------------------------------------------------
    // SETTERS
    // ------------------------------------------------------------
    public function setIdReponse(int $id): void { $this->idReponse = $id; }
    public function setIdDemande(int $id): void { $this->idDemande = $id; }
    public function setIdUtilisateur(int $id): void { $this->idUtilisateur = $id; }
    public function setIdInstallation(?int $id): void { $this->idInstallation = $id; }
    public function setMessage(string $message): void { $this->message = $message; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setDateReponse(string $date): void { $this->dateReponse = $date; }
    public function setAuteurPrenom(?string $prenom): void { $this->auteurPrenom = $prenom; }
    public function setAuteurNom(?string $nom): void { $this->auteurNom = $nom; }
    public function setAuteurRole(?string $role): void { $this->auteurRole = $role; }
    public function setAuteurOrganisation(?string $org): void { $this->auteurOrganisation = $org; }
    public function setInstallationAdresse(?string $adresse): void { $this->installationAdresse = $adresse; }
    public function setDemandeTitre(?string $titre): void { $this->demandeTitre = $titre; }

    // ------------------------------------------------------------
    // CONVERSION ligne SQL → objet
    // ------------------------------------------------------------
    public static function fromArray(array $row): self
    {
        $r = new self();
        $r->setIdReponse((int) ($row['id_reponse'] ?? 0));
        $r->setIdDemande((int) ($row['id_demande'] ?? 0));
        $r->setIdUtilisateur((int) ($row['id_utilisateur'] ?? 0));
        $r->setIdInstallation(isset($row['id_installation']) ? (int) $row['id_installation'] : null);
        $r->setMessage((string) ($row['message'] ?? ''));
        $r->setStatut((string) ($row['statut'] ?? 'proposee'));
        $r->setDateReponse((string) ($row['date_reponse'] ?? ''));
        $r->setAuteurPrenom(isset($row['auteur_prenom']) ? (string) $row['auteur_prenom'] : null);
        $r->setAuteurNom(isset($row['auteur_nom']) ? (string) $row['auteur_nom'] : null);
        $r->setAuteurRole(isset($row['auteur_role']) ? (string) $row['auteur_role'] : null);
        $r->setAuteurOrganisation(isset($row['auteur_organisation']) ? (string) $row['auteur_organisation'] : null);
        $r->setInstallationAdresse(isset($row['installation_adresse']) ? (string) $row['installation_adresse'] : null);
        $r->setDemandeTitre(isset($row['demande_titre']) ? (string) $row['demande_titre'] : null);
        return $r;
    }
}
