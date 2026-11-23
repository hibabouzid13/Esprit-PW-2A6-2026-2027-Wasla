<?php
/**
 * models/UserModel.php
 * Représente UNE ligne de la table « utilisateur ».
 *
 * Règle du workshop : un Model ne contient QUE des attributs privés,
 * des getters/setters et fromArray(). Aucune requête SQL, aucune logique métier
 * (tout cela est dans les contrôleurs).
 */

declare(strict_types=1);

class UserModel
{
    /** Rôles possibles (valeurs de la colonne role). Entreprise et fournisseur sont des rôles, pas des tables.
     *  Un bénévole ou un propriétaire de panneaux solaires est un citoyen : pas de rôle à part. */
    public const ROLES = ['citoyen', 'admin', 'steg', 'sonede', 'entreprise', 'fournisseur'];

    /** Spécialités possibles d'un fournisseur (colonne specialite). */
    public const SPECIALITES = ['electricien', 'plombier', 'maintenance', 'solaire', 'hydraulique'];

    // ------------------------------------------------------------
    // ATTRIBUTS
    // ------------------------------------------------------------
    private int $idUtilisateur = 0;
    private string $nom = '';
    private string $prenom = '';
    private string $email = '';
    private ?string $telephone = null;
    private string $motDePasse = '';
    private string $role = 'citoyen';
    private string $statutCompte = 'actif';
    private ?int $idZone = null;
    private ?string $adresse = null;

    // Informations professionnelles (rôles entreprise et fournisseur ; null pour les autres)
    private ?string $nomOrganisation = null;
    private ?string $matriculeFiscal = null;
    private ?string $secteurActivite = null;
    private ?string $specialite = null;
    private ?string $description = null;
    private bool $compteVerifie = false;
    private ?float $noteMoyenne = null;

    private string $dateInscription = '';
    private ?string $derniereConnexion = null;

    /** Nom de la zone (rempli seulement quand la requête fait une jointure avec zone). */
    private ?string $nomZone = null;

    public function __construct()
    {
    }

    // ------------------------------------------------------------
    // GETTERS
    // ------------------------------------------------------------
    public function getIdUtilisateur(): int { return $this->idUtilisateur; }
    public function getNom(): string { return $this->nom; }
    public function getPrenom(): string { return $this->prenom; }
    public function getEmail(): string { return $this->email; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function getMotDePasse(): string { return $this->motDePasse; }
    public function getRole(): string { return $this->role; }
    public function getStatutCompte(): string { return $this->statutCompte; }
    public function getIdZone(): ?int { return $this->idZone; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function getNomOrganisation(): ?string { return $this->nomOrganisation; }
    public function getMatriculeFiscal(): ?string { return $this->matriculeFiscal; }
    public function getSecteurActivite(): ?string { return $this->secteurActivite; }
    public function getSpecialite(): ?string { return $this->specialite; }
    public function getDescription(): ?string { return $this->description; }
    public function isCompteVerifie(): bool { return $this->compteVerifie; }
    public function getNoteMoyenne(): ?float { return $this->noteMoyenne; }
    public function getDateInscription(): string { return $this->dateInscription; }
    public function getDerniereConnexion(): ?string { return $this->derniereConnexion; }
    public function getNomZone(): ?string { return $this->nomZone; }

    // ------------------------------------------------------------
    // SETTERS
    // ------------------------------------------------------------
    public function setIdUtilisateur(int $id): void { $this->idUtilisateur = $id; }
    public function setNom(string $nom): void { $this->nom = $nom; }
    public function setPrenom(string $prenom): void { $this->prenom = $prenom; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setTelephone(?string $telephone): void { $this->telephone = $telephone; }
    public function setMotDePasse(string $motDePasse): void { $this->motDePasse = $motDePasse; }
    public function setRole(string $role): void { $this->role = $role; }
    public function setStatutCompte(string $statut): void { $this->statutCompte = $statut; }
    public function setIdZone(?int $idZone): void { $this->idZone = $idZone; }
    public function setAdresse(?string $adresse): void { $this->adresse = $adresse; }
    public function setNomOrganisation(?string $nom): void { $this->nomOrganisation = $nom; }
    public function setMatriculeFiscal(?string $matricule): void { $this->matriculeFiscal = $matricule; }
    public function setSecteurActivite(?string $secteur): void { $this->secteurActivite = $secteur; }
    public function setSpecialite(?string $specialite): void { $this->specialite = $specialite; }
    public function setDescription(?string $description): void { $this->description = $description; }
    public function setCompteVerifie(bool $verifie): void { $this->compteVerifie = $verifie; }
    public function setNoteMoyenne(?float $note): void { $this->noteMoyenne = $note; }
    public function setDateInscription(string $date): void { $this->dateInscription = $date; }
    public function setDerniereConnexion(?string $date): void { $this->derniereConnexion = $date; }
    public function setNomZone(?string $nomZone): void { $this->nomZone = $nomZone; }

    // ------------------------------------------------------------
    // CONVERSION ligne SQL → objet
    // ------------------------------------------------------------
    public static function fromArray(array $row): self
    {
        $user = new self();
        $user->setIdUtilisateur((int) ($row['id_utilisateur'] ?? 0));
        $user->setNom((string) ($row['nom'] ?? ''));
        $user->setPrenom((string) ($row['prenom'] ?? ''));
        $user->setEmail((string) ($row['email'] ?? ''));
        $user->setTelephone(isset($row['telephone']) ? (string) $row['telephone'] : null);
        $user->setMotDePasse((string) ($row['mot_de_passe'] ?? ''));
        $user->setRole((string) ($row['role'] ?? 'citoyen'));
        $user->setStatutCompte((string) ($row['statut_compte'] ?? 'actif'));
        $user->setIdZone(isset($row['id_zone']) ? (int) $row['id_zone'] : null);
        $user->setAdresse(isset($row['adresse']) ? (string) $row['adresse'] : null);
        $user->setNomOrganisation(isset($row['nom_organisation']) ? (string) $row['nom_organisation'] : null);
        $user->setMatriculeFiscal(isset($row['matricule_fiscal']) ? (string) $row['matricule_fiscal'] : null);
        $user->setSecteurActivite(isset($row['secteur_activite']) ? (string) $row['secteur_activite'] : null);
        $user->setSpecialite(isset($row['specialite']) ? (string) $row['specialite'] : null);
        $user->setDescription(isset($row['description']) ? (string) $row['description'] : null);
        $user->setCompteVerifie(!empty($row['compte_verifie']));
        $user->setNoteMoyenne(isset($row['note_moyenne']) ? (float) $row['note_moyenne'] : null);
        $user->setDateInscription((string) ($row['date_inscription'] ?? ''));
        $user->setDerniereConnexion(isset($row['derniere_connexion']) ? (string) $row['derniere_connexion'] : null);
        $user->setNomZone(isset($row['nom_zone']) ? (string) $row['nom_zone'] : null);
        return $user;
    }
}
