<?php
/**
 * models/DemandeAssistanceModel.php — Module 4 (Bechir)
 * Représente UNE ligne de la table « demande_assistance ».
 *
 * Règle du workshop : uniquement des attributs privés, des getters/setters et fromArray().
 * Aucune requête SQL ici : tout est dans DemandeAssistanceController.
 *
 * Une demande sans id_fournisseur est une publication du « Relais de voisinage » (demande ou offre) ;
 * avec id_fournisseur, c'est une demande d'intervention adressée à un compte de rôle « fournisseur ».
 */

declare(strict_types=1);

class DemandeAssistanceModel
{
    /** Valeurs possibles des colonnes ENUM (clé = valeur SQL, valeur = libellé affiché). */
    public const SENS = ['demande' => "Demande d'aide", 'offre' => "Offre d'aide"];

    public const CATEGORIES = [
        'eau'         => 'Eau potable',
        'recharge'    => 'Recharge',
        'electricite' => 'Électricité',
        'generateur'  => 'Générateur',
        'hebergement' => 'Hébergement',
        'transport'   => 'Transport',
        'aines'       => 'Aide aux aînés',
        'autre'       => 'Autre',
    ];

    /** Signalement « Priorité santé » : jamais proposé dans les formulaires publics, traité au Back Office. */
    public const CATEGORIE_SANTE = 'sante';

    /** Tous les libellés (catégories publiques + Priorité santé), pour l'affichage et les filtres du Back Office. */
    public const TOUTES_CATEGORIES = self::CATEGORIES + [self::CATEGORIE_SANTE => 'Priorité santé'];

    public const URGENCES = ['faible' => 'Peu urgent', 'moyenne' => 'Modéré', 'haute' => 'Urgent'];

    public const CRENEAUX = ['matin' => 'Matin (8h – 12h)', 'apres_midi' => 'Après-midi (12h – 17h)', 'soir' => 'Fin de journée (17h – 20h)'];

    public const STATUTS = [
        'ouverte'         => 'Ouverte',
        'prise_en_charge' => 'Prise en charge',
        'resolue'         => 'Résolue',
        'annulee'         => 'Annulée',
    ];

    // ------------------------------------------------------------
    // ATTRIBUTS (colonnes de la table)
    // ------------------------------------------------------------
    private int $idDemande = 0;
    private int $idUtilisateur = 0;
    private string $sens = 'demande';
    private string $categorie = 'autre';
    private string $titre = '';
    private string $description = '';
    private int $idZone = 0;
    private ?string $quartier = null;
    private string $urgence = 'moyenne';
    private ?int $idFournisseur = null;
    private ?string $dateSouhaitee = null;
    private ?string $creneau = null;
    private ?string $telephone = null;
    private string $statut = 'ouverte';
    private string $dateCreation = '';

    // Informations jointes (remplies seulement quand la requête fait la jointure)
    private ?string $nomZone = null;
    private ?string $auteurPrenom = null;
    private ?string $auteurNom = null;
    private ?string $fournisseurNom = null;
    private int $nbReponses = 0;

    public function __construct()
    {
    }

    // ------------------------------------------------------------
    // GETTERS
    // ------------------------------------------------------------
    public function getIdDemande(): int { return $this->idDemande; }
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function getSens(): string { return $this->sens; }
    public function getCategorie(): string { return $this->categorie; }
    public function getTitre(): string { return $this->titre; }
    public function getDescription(): string { return $this->description; }
    public function getIdZone(): int { return $this->idZone; }
    public function getQuartier(): ?string { return $this->quartier; }
    public function getUrgence(): string { return $this->urgence; }
    public function getIdFournisseur(): ?int { return $this->idFournisseur; }
    public function getDateSouhaitee(): ?string { return $this->dateSouhaitee; }
    public function getCreneau(): ?string { return $this->creneau; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function getStatut(): string { return $this->statut; }
    public function getDateCreation(): string { return $this->dateCreation; }
    public function getNomZone(): ?string { return $this->nomZone; }
    public function getAuteurPrenom(): ?string { return $this->auteurPrenom; }
    public function getAuteurNom(): ?string { return $this->auteurNom; }
    public function getFournisseurNom(): ?string { return $this->fournisseurNom; }
    public function getNbReponses(): int { return $this->nbReponses; }

    // ------------------------------------------------------------
    // SETTERS
    // ------------------------------------------------------------
    public function setIdDemande(int $id): void { $this->idDemande = $id; }
    public function setIdUtilisateur(int $id): void { $this->idUtilisateur = $id; }
    public function setSens(string $sens): void { $this->sens = $sens; }
    public function setCategorie(string $categorie): void { $this->categorie = $categorie; }
    public function setTitre(string $titre): void { $this->titre = $titre; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setIdZone(int $idZone): void { $this->idZone = $idZone; }
    public function setQuartier(?string $quartier): void { $this->quartier = $quartier; }
    public function setUrgence(string $urgence): void { $this->urgence = $urgence; }
    public function setIdFournisseur(?int $id): void { $this->idFournisseur = $id; }
    public function setDateSouhaitee(?string $date): void { $this->dateSouhaitee = $date; }
    public function setCreneau(?string $creneau): void { $this->creneau = $creneau; }
    public function setTelephone(?string $telephone): void { $this->telephone = $telephone; }
    public function setStatut(string $statut): void { $this->statut = $statut; }
    public function setDateCreation(string $date): void { $this->dateCreation = $date; }
    public function setNomZone(?string $nomZone): void { $this->nomZone = $nomZone; }
    public function setAuteurPrenom(?string $prenom): void { $this->auteurPrenom = $prenom; }
    public function setAuteurNom(?string $nom): void { $this->auteurNom = $nom; }
    public function setFournisseurNom(?string $nom): void { $this->fournisseurNom = $nom; }
    public function setNbReponses(int $n): void { $this->nbReponses = $n; }

    // ------------------------------------------------------------
    // CONVERSION ligne SQL → objet
    // ------------------------------------------------------------
    public static function fromArray(array $row): self
    {
        $d = new self();
        $d->setIdDemande((int) ($row['id_demande'] ?? 0));
        $d->setIdUtilisateur((int) ($row['id_utilisateur'] ?? 0));
        $d->setSens((string) ($row['sens'] ?? 'demande'));
        $d->setCategorie((string) ($row['categorie'] ?? 'autre'));
        $d->setTitre((string) ($row['titre'] ?? ''));
        $d->setDescription((string) ($row['description'] ?? ''));
        $d->setIdZone((int) ($row['id_zone'] ?? 0));
        $d->setQuartier(isset($row['quartier']) ? (string) $row['quartier'] : null);
        $d->setUrgence((string) ($row['urgence'] ?? 'moyenne'));
        $d->setIdFournisseur(isset($row['id_fournisseur']) ? (int) $row['id_fournisseur'] : null);
        $d->setDateSouhaitee(isset($row['date_souhaitee']) ? (string) $row['date_souhaitee'] : null);
        $d->setCreneau(isset($row['creneau']) ? (string) $row['creneau'] : null);
        $d->setTelephone(isset($row['telephone']) ? (string) $row['telephone'] : null);
        $d->setStatut((string) ($row['statut'] ?? 'ouverte'));
        $d->setDateCreation((string) ($row['date_creation'] ?? ''));
        $d->setNomZone(isset($row['nom_zone']) ? (string) $row['nom_zone'] : null);
        $d->setAuteurPrenom(isset($row['auteur_prenom']) ? (string) $row['auteur_prenom'] : null);
        $d->setAuteurNom(isset($row['auteur_nom']) ? (string) $row['auteur_nom'] : null);
        $d->setFournisseurNom(isset($row['fournisseur_nom']) ? (string) $row['fournisseur_nom'] : null);
        $d->setNbReponses((int) ($row['nb_reponses'] ?? 0));
        return $d;
    }
}
