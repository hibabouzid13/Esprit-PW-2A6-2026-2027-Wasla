<?php
/**
 * models/FactureModel.php — Module 2 (Hiba)
 * Représente UNE ligne de la table « facture ».
 *
 * Règle du workshop : uniquement des attributs privés, des getters/setters et fromArray().
 * Aucune requête SQL, aucune logique métier (tout est dans FactureController).
 */

declare(strict_types=1);

class FactureModel
{
    /** Valeurs possibles des colonnes ENUM de la table facture. */
    public const TYPES = ['electricite', 'eau'];
    public const STATUTS = ['a_payer', 'payee'];

    // ------------------------------------------------------------
    // ATTRIBUTS (colonnes de la table facture)
    // ------------------------------------------------------------
    private int $idFacture = 0;
    private int $idUtilisateur = 0;
    private string $typeRessource = 'electricite';
    private string $referenceAbonnement = '';
    private ?string $referencePaiement = null;
    private string $periodeDebut = '';
    private string $periodeFin = '';
    private float $quantite = 0.0;
    private float $montant = 0.0;
    private ?string $dateEcheance = null;
    private string $statut = 'a_payer';
    private string $source = 'manuelle';
    private string $dateAjout = '';

    /** Remplis seulement quand la requête fait une jointure avec utilisateur (Back Office). */
    private ?string $nomClient = null;
    private ?string $emailClient = null;

    public function __construct()
    {
    }

    // ------------------------------------------------------------
    // GETTERS
    // ------------------------------------------------------------
    public function getIdFacture(): int { return $this->idFacture; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function getTypeRessource(): string { return $this->typeRessource; }
    public function getReferenceAbonnement(): string { return $this->referenceAbonnement; }
    public function getReferencePaiement(): ?string { return $this->referencePaiement; }
    public function getPeriodeDebut(): string { return $this->periodeDebut; }
    public function getPeriodeFin(): string { return $this->periodeFin; }
    public function getQuantite(): float { return $this->quantite; }
    public function getMontant(): float { return $this->montant; }
    public function getDateEcheance(): ?string { return $this->dateEcheance; }
    public function getStatut(): string { return $this->statut; }
    public function getSource(): string { return $this->source; }
    public function getDateAjout(): string { return $this->dateAjout; }
    public function getNomClient(): ?string { return $this->nomClient; }
    public function getEmailClient(): ?string { return $this->emailClient; }

    // ------------------------------------------------------------
    // SETTERS
    // ------------------------------------------------------------
    public function setIdFacture(int $id): void { $this->idFacture = $id; }
    public function setIdUtilisateur(int $id): void { $this->idUtilisateur = $id; }
    public function setTypeRessource(string $type): void { $this->typeRessource = $type; }
    public function setReferenceAbonnement(string $reference): void { $this->referenceAbonnement = $reference; }
    public function setReferencePaiement(?string $reference): void { $this->referencePaiement = $reference; }
    public function setPeriodeDebut(string $date): void { $this->periodeDebut = $date; }
    public function setPeriodeFin(string $date): void { $this->periodeFin = $date; }
    public function setQuantite(float $quantite): void { $this->quantite = $quantite; }
    public function setMontant(float $montant): void { $this->montant = $montant; }
    public function setDateEcheance(?string $date): void { $this->dateEcheance = $date; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setSource(string $source): void { $this->source = $source; }
    public function setDateAjout(string $date): void { $this->dateAjout = $date; }
    public function setNomClient(?string $nom): void { $this->nomClient = $nom; }
    public function setEmailClient(?string $email): void { $this->emailClient = $email; }

    // ------------------------------------------------------------
    // CONVERSION ligne SQL → objet
    // ------------------------------------------------------------
    public static function fromArray(array $row): self
    {
        $f = new self();
        $f->setIdFacture((int) ($row['id_facture'] ?? 0));
        $f->setIdUtilisateur((int) ($row['id_utilisateur'] ?? 0));
        $f->setTypeRessource((string) ($row['type_ressource'] ?? 'electricite'));
        $f->setReferenceAbonnement((string) ($row['reference_abonnement'] ?? ''));
        $f->setReferencePaiement(isset($row['reference_paiement']) && $row['reference_paiement'] !== '' ? (string) $row['reference_paiement'] : null);
        $f->setPeriodeDebut((string) ($row['periode_debut'] ?? ''));
        $f->setPeriodeFin((string) ($row['periode_fin'] ?? ''));
        $f->setQuantite((float) ($row['quantite'] ?? 0));
        $f->setMontant((float) ($row['montant'] ?? 0));
        $f->setDateEcheance(isset($row['date_echeance']) ? (string) $row['date_echeance'] : null);
        $f->setStatut((string) ($row['statut'] ?? 'a_payer'));
        $f->setSource((string) ($row['source'] ?? 'manuelle'));
        $f->setDateAjout((string) ($row['date_ajout'] ?? ''));
        $f->setNomClient(isset($row['nom_client']) ? (string) $row['nom_client'] : null);
        $f->setEmailClient(isset($row['email_client']) ? (string) $row['email_client'] : null);
        return $f;
    }
}
