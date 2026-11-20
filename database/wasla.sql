-- =====================================================================
-- WASLA — base de données COMPLÈTE (les 4 modules)
-- ---------------------------------------------------------------------
-- Consigne : UN seul membre crée la base et la partage avec l'équipe.
-- Ce fichier est dans la branche main ; chaque membre l'importe tel quel.
--
-- Import : phpMyAdmin → créer la base « wasla » (utf8mb4_unicode_ci)
--          → onglet SQL → coller TOUT ce fichier → Exécuter.
-- Remise à zéro : supprimer la base « wasla », puis réimporter ce fichier.
--
-- Toute modification de la structure passe par une pull request vers main
-- (jamais dans une branche de module), pour que tout le monde ait la même base.
--
-- Accès depuis PHP : uniquement via PDO (config/database.php → Database::getConnexion()).
--
-- Plan :
--   COMMUN    zone, utilisateur, notification
--   MODULE 1  interruption, signalement, confirmation, alerte, lieu_suivi, preference_alerte
--   MODULE 2  facture, consommation, analyse_facture
--   MODULE 3  refuge_zone, reservation_refuge
--   MODULE 4  installation_solaire, demande_assistance, reponse, personne_prioritaire
--
-- Entreprise et fournisseur ne sont PAS des tables : ce sont des RÔLES de l'entité utilisateur.
-- Leurs informations propres (raison sociale, matricule fiscal, spécialité…) sont des colonnes
-- facultatives de utilisateur, remplies seulement pour ces rôles.
-- =====================================================================

SET NAMES utf8mb4;  -- les accents (Béja, Gabès…) restent corrects quel que soit le client

CREATE DATABASE IF NOT EXISTS wasla CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wasla;


-- #####################################################################
-- COMMUN
-- #####################################################################

-- ---------------------------------------------------------------------
-- zone : gouvernorat (24) → délégation. Référencée par presque toutes les tables.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS zone (
    id_zone        INT          NOT NULL AUTO_INCREMENT,
    nom_zone       VARCHAR(100) NOT NULL,
    type_zone      ENUM('gouvernorat','delegation') NOT NULL DEFAULT 'gouvernorat',
    id_zone_parent INT          NULL,
    PRIMARY KEY (id_zone),
    UNIQUE KEY uq_zone_nom_parent (nom_zone, id_zone_parent),
    CONSTRAINT fk_zone_parent FOREIGN KEY (id_zone_parent)
        REFERENCES zone (id_zone) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- utilisateur : TOUS les comptes. Le rôle est une colonne (pas une table par rôle).
--   Inscription publique → toujours 'citoyen' ; les autres rôles sont donnés par l'admin.
--   Un compte 'suspendu' ne peut plus se connecter.
--   ENTREPRISE et FOURNISSEUR sont des rôles : leurs informations sont dans les colonnes
--   « professionnelles » ci-dessous (NULL pour les autres rôles).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateur (
    id_utilisateur     INT          NOT NULL AUTO_INCREMENT,
    nom                VARCHAR(50)  NOT NULL,
    prenom             VARCHAR(50)  NOT NULL,
    email              VARCHAR(150) NOT NULL,
    telephone          VARCHAR(20)  NULL,
    mot_de_passe       VARCHAR(255) NOT NULL,
    role               ENUM('citoyen','admin','steg','sonede','entreprise','fournisseur','benevole','proprietaire')
                                    NOT NULL DEFAULT 'citoyen',
    statut_compte      ENUM('actif','suspendu') NOT NULL DEFAULT 'actif',
    id_zone            INT          NULL,
    adresse            VARCHAR(255) NULL,
    -- Colonnes professionnelles (rôles entreprise et fournisseur)
    nom_organisation   VARCHAR(100) NULL,                 -- raison sociale / nom commercial
    matricule_fiscal   VARCHAR(20)  NULL,                 -- entreprise
    secteur_activite   VARCHAR(60)  NULL,                 -- entreprise : café, hôtel, centre commercial…
    specialite         ENUM('electricien','plombier','maintenance','solaire','hydraulique') NULL,  -- fournisseur
    description        TEXT         NULL,
    compte_verifie     TINYINT(1)   NOT NULL DEFAULT 0,   -- entreprise / fournisseur validé par l'admin
    note_moyenne       DECIMAL(2,1) NULL,                 -- fournisseur : note des citoyens (0 à 5)
    date_inscription   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME     NULL,
    PRIMARY KEY (id_utilisateur),
    UNIQUE KEY uq_utilisateur_email (email),
    UNIQUE KEY uq_utilisateur_matricule (matricule_fiscal),
    KEY idx_utilisateur_role (role),
    KEY idx_utilisateur_specialite_zone (specialite, id_zone),
    CONSTRAINT chk_utilisateur_note CHECK (note_moyenne IS NULL OR (note_moyenne >= 0 AND note_moyenne <= 5)),
    CONSTRAINT fk_utilisateur_zone FOREIGN KEY (id_zone)
        REFERENCES zone (id_zone) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- notification : message envoyé à un utilisateur par n'importe quel module
--   (alerte de coupure, facture anormale, réservation confirmée, réponse reçue…).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notification (
    id_notification INT          NOT NULL AUTO_INCREMENT,
    id_utilisateur  INT          NOT NULL,
    titre           VARCHAR(150) NOT NULL,
    message         TEXT         NOT NULL,
    lien            VARCHAR(255) NULL,          -- ex. index.php?action=factures
    est_lue         TINYINT(1)   NOT NULL DEFAULT 0,
    date_creation   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_notification),
    KEY idx_notification_user_lue (id_utilisateur, est_lue),
    CONSTRAINT fk_notification_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #####################################################################
-- MODULE 1 — Interruptions, signalements & alertes (Houssem)
-- Scénario : un citoyen SIGNALE une coupure ; d'autres la CONFIRMENT ; STEG/SONEDE
-- publie l'INTERRUPTION officielle (et y rattache les signalements) ; une ALERTE
-- est envoyée aux citoyens qui suivent cette zone (LIEU_SUIVI), selon leurs PRÉFÉRENCES.
-- #####################################################################

-- Coupure officielle publiée par l'admin, STEG (électricité) ou SONEDE (eau).
CREATE TABLE IF NOT EXISTS interruption (
    id_interruption  INT          NOT NULL AUTO_INCREMENT,
    type_ressource   ENUM('electricite','eau') NOT NULL,
    id_zone          INT          NOT NULL,
    statut           ENUM('planifiee','en_cours','terminee') NOT NULL DEFAULT 'planifiee',
    cause            VARCHAR(255) NOT NULL,
    date_debut       DATETIME     NOT NULL,
    date_fin_prevue  DATETIME     NOT NULL,
    date_fin_reelle  DATETIME     NULL,
    id_auteur        INT          NULL,          -- compte admin / steg / sonede qui a publié
    date_publication DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_interruption),
    KEY idx_interruption_zone_statut (id_zone, statut),
    CONSTRAINT chk_interruption_dates CHECK (date_fin_prevue > date_debut),
    CONSTRAINT fk_interruption_zone FOREIGN KEY (id_zone)
        REFERENCES zone (id_zone) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_interruption_auteur FOREIGN KEY (id_auteur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coupure signalée par un citoyen (page Carte → « Signaler une coupure »).
CREATE TABLE IF NOT EXISTS signalement (
    id_signalement   INT          NOT NULL AUTO_INCREMENT,
    id_utilisateur   INT          NOT NULL,
    id_zone          INT          NOT NULL,
    type_ressource   ENUM('electricite','eau') NOT NULL,
    adresse          VARCHAR(255) NULL,
    message          TEXT         NULL,
    statut           ENUM('en_attente','confirme','rejete') NOT NULL DEFAULT 'en_attente',
    id_interruption  INT          NULL,          -- rattaché à une interruption officielle
    date_signalement DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_signalement),
    KEY idx_signalement_zone_date (id_zone, date_signalement),
    CONSTRAINT fk_signalement_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_signalement_zone FOREIGN KEY (id_zone)
        REFERENCES zone (id_zone) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_signalement_interruption FOREIGN KEY (id_interruption)
        REFERENCES interruption (id_interruption) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- « Moi aussi » : un autre citoyen confirme un signalement (une seule fois par personne).
CREATE TABLE IF NOT EXISTS confirmation (
    id_confirmation   INT      NOT NULL AUTO_INCREMENT,
    id_signalement    INT      NOT NULL,
    id_utilisateur    INT      NOT NULL,
    date_confirmation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_confirmation),
    UNIQUE KEY uq_confirmation_unique (id_signalement, id_utilisateur),
    CONSTRAINT fk_confirmation_signalement FOREIGN KEY (id_signalement)
        REFERENCES signalement (id_signalement) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_confirmation_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alerte diffusée sur une zone : automatique (générée depuis une interruption ou
-- plusieurs signalements) ou manuelle (écrite par un admin / sous-admin).
CREATE TABLE IF NOT EXISTS alerte (
    id_alerte        INT          NOT NULL AUTO_INCREMENT,
    id_zone          INT          NOT NULL,
    id_interruption  INT          NULL,
    type_ressource   ENUM('electricite','eau','les_deux') NOT NULL,
    categorie        ENUM('coupure','retablissement','chaleur','penurie','incident') NOT NULL,
    gravite          ENUM('info','moderee','elevee','critique') NOT NULL DEFAULT 'info',
    titre            VARCHAR(150) NOT NULL,
    message          TEXT         NOT NULL,
    source           ENUM('automatique','manuelle') NOT NULL DEFAULT 'manuelle',
    id_auteur        INT          NULL,
    date_creation    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_expiration  DATETIME     NULL,
    PRIMARY KEY (id_alerte),
    KEY idx_alerte_zone_date (id_zone, date_creation),
    CONSTRAINT fk_alerte_zone FOREIGN KEY (id_zone)
        REFERENCES zone (id_zone) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_alerte_interruption FOREIGN KEY (id_interruption)
        REFERENCES interruption (id_interruption) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_alerte_auteur FOREIGN KEY (id_auteur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lieux suivis par un citoyen (page Alertes → « Ajouter un lieu »).
CREATE TABLE IF NOT EXISTS lieu_suivi (
    id_lieu        INT          NOT NULL AUTO_INCREMENT,
    id_utilisateur INT          NOT NULL,
    type_lieu      ENUM('domicile','travail','proche','autre') NOT NULL DEFAULT 'domicile',
    libelle        VARCHAR(60)  NOT NULL,       -- ex. « Maison », « Bureau »
    id_zone        INT          NOT NULL,
    quartier       VARCHAR(100) NULL,
    date_ajout     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_lieu),
    UNIQUE KEY uq_lieu_libelle (id_utilisateur, libelle),
    CONSTRAINT fk_lieu_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lieu_zone FOREIGN KEY (id_zone)
        REFERENCES zone (id_zone) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Préférences d'alerte d'un utilisateur (1 ligne par utilisateur : relation 1-1).
CREATE TABLE IF NOT EXISTS preference_alerte (
    id_utilisateur INT        NOT NULL,
    gravite_min    ENUM('info','moderee','elevee','critique') NOT NULL DEFAULT 'moderee',
    canal_site     TINYINT(1) NOT NULL DEFAULT 1,
    canal_email    TINYINT(1) NOT NULL DEFAULT 0,
    canal_sms      TINYINT(1) NOT NULL DEFAULT 0,
    frequence      ENUM('immediate','quotidienne') NOT NULL DEFAULT 'immediate',
    PRIMARY KEY (id_utilisateur),
    CONSTRAINT fk_preference_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #####################################################################
-- MODULE 2 — Analyse des factures & consommation (Hiba)
-- Scénario : le citoyen ajoute ses FACTURES STEG (électricité) / SONEDE (eau) ;
-- chaque facture est répartie en CONSOMMATION mensuelle (graphiques de la page
-- Analyses) ; une ANALYSE compare la facture à la précédente et à la moyenne de
-- sa zone, puis donne un niveau (normal / élevé / anormal) et une recommandation.
-- #####################################################################

CREATE TABLE IF NOT EXISTS facture (
    id_facture     INT           NOT NULL AUTO_INCREMENT,
    id_utilisateur INT           NOT NULL,
    type_ressource ENUM('electricite','eau') NOT NULL,   -- électricité = STEG (kWh), eau = SONEDE (m³)
    reference_abonnement VARCHAR(9)  NOT NULL,           -- en haut de la facture : STEG 9 chiffres, SONEDE 8 ou 9
    reference_paiement   VARCHAR(16) NULL,               -- talon (bas de la facture) : STEG 16 chiffres, SONEDE 12 à 16 ; NULL si non saisie
    periode_debut  DATE          NOT NULL,
    periode_fin    DATE          NOT NULL,
    quantite       DECIMAL(10,2) NOT NULL,               -- kWh ou m³ (unité déduite du type)
    montant        DECIMAL(10,3) NOT NULL,               -- en dinars (3 décimales : millimes)
    date_echeance  DATE          NULL,
    statut         ENUM('a_payer','payee') NOT NULL DEFAULT 'a_payer',
    source         ENUM('manuelle','import') NOT NULL DEFAULT 'manuelle',
    date_ajout     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_facture),
    -- Un même talon ne peut être enregistré qu'une fois par citoyen et par fournisseur
    -- (plusieurs NULL autorisés). Les doublons de facture sont aussi évités par le contrôle
    -- de chevauchement des périodes (FactureController).
    UNIQUE KEY uq_facture_paiement (id_utilisateur, type_ressource, reference_paiement),
    KEY idx_facture_user_periode (id_utilisateur, periode_debut),
    CONSTRAINT chk_facture_ref_abonnement CHECK (reference_abonnement REGEXP '^[0-9]{8,9}$'),
    CONSTRAINT chk_facture_ref_paiement CHECK (reference_paiement IS NULL OR reference_paiement REGEXP '^[0-9]{12,16}$'),
    CONSTRAINT chk_facture_periode CHECK (periode_fin > periode_debut),
    CONSTRAINT chk_facture_quantite CHECK (quantite >= 0),
    CONSTRAINT chk_facture_montant CHECK (montant > 0),
    CONSTRAINT fk_facture_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consommation d'un mois. Liée à la facture qui la couvre (si elle existe).
CREATE TABLE IF NOT EXISTS consommation (
    id_consommation INT           NOT NULL AUTO_INCREMENT,
    id_utilisateur  INT           NOT NULL,
    id_facture      INT           NULL,
    type_ressource  ENUM('electricite','eau') NOT NULL,
    mois            DATE          NOT NULL,              -- toujours le 1er du mois (ex. 2026-09-01)
    quantite        DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id_consommation),
    UNIQUE KEY uq_consommation_mois (id_utilisateur, type_ressource, mois),
    CONSTRAINT chk_consommation_quantite CHECK (quantite >= 0),
    CONSTRAINT fk_consommation_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_consommation_facture FOREIGN KEY (id_facture)
        REFERENCES facture (id_facture) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Résultat de l'analyse d'une facture (relation 1-1 : une analyse par facture).
CREATE TABLE IF NOT EXISTS analyse_facture (
    id_analyse     INT           NOT NULL AUTO_INCREMENT,
    id_facture     INT           NOT NULL,
    variation_pct  DECIMAL(6,2)  NULL,                   -- écart avec la facture précédente (%)
    moyenne_zone   DECIMAL(10,2) NULL,                   -- consommation moyenne de la zone, même période
    niveau         ENUM('normal','eleve','anormal') NOT NULL DEFAULT 'normal',
    recommandation TEXT          NULL,
    date_analyse   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_analyse),
    UNIQUE KEY uq_analyse_facture (id_facture),
    CONSTRAINT fk_analyse_facture FOREIGN KEY (id_facture)
        REFERENCES facture (id_facture) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #####################################################################
-- MODULE 3 — Refuge Zones (Louay)
-- Scénario : un compte de rôle ENTREPRISE (validé par l'admin : compte_verifie = 1)
-- propose des REFUGE_ZONES (lieu climatisé, eau, recharge) pendant les coupures ;
-- un citoyen fait une RÉSERVATION ; les places disponibles diminuent.
-- #####################################################################

CREATE TABLE IF NOT EXISTS refuge_zone (
    id_refuge          INT          NOT NULL AUTO_INCREMENT,
    id_entreprise      INT          NOT NULL,             -- utilisateur de rôle « entreprise »
    nom                VARCHAR(100) NOT NULL,
    adresse            VARCHAR(255) NOT NULL,
    id_zone            INT          NOT NULL,
    latitude           DECIMAL(9,6) NULL,
    longitude          DECIMAL(9,6) NULL,
    services           SET('climatisation','eau_potable','recharge','wifi','sanitaires','espace_enfants') NOT NULL,
    capacite           SMALLINT     NOT NULL,
    places_disponibles SMALLINT     NOT NULL,
    horaires           VARCHAR(100) NULL,                -- ex. « 08:00 – 22:00 »
    accessible_pmr     TINYINT(1)   NOT NULL DEFAULT 0,
    statut             ENUM('en_attente','ouvert','complet','ferme') NOT NULL DEFAULT 'en_attente',
    date_creation      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_refuge),
    KEY idx_refuge_zone_statut (id_zone, statut),
    CONSTRAINT chk_refuge_capacite CHECK (capacite > 0 AND places_disponibles >= 0 AND places_disponibles <= capacite),
    CONSTRAINT fk_refuge_entreprise FOREIGN KEY (id_entreprise)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_refuge_zone FOREIGN KEY (id_zone)
        REFERENCES zone (id_zone) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Réservation d'une place (formulaire de la page Zones refuge : nom, téléphone, personnes, besoins).
CREATE TABLE IF NOT EXISTS reservation_refuge (
    id_reservation   INT          NOT NULL AUTO_INCREMENT,
    id_refuge        INT          NOT NULL,
    id_utilisateur   INT          NOT NULL,
    nom_contact      VARCHAR(100) NOT NULL,
    telephone        VARCHAR(20)  NOT NULL,
    nombre_personnes TINYINT      NOT NULL DEFAULT 1,
    besoins          SET('bebe','medical','pmr','senior') NULL,
    date_reservation DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut           ENUM('en_attente','confirmee','annulee','terminee') NOT NULL DEFAULT 'en_attente',
    PRIMARY KEY (id_reservation),
    KEY idx_reservation_refuge_statut (id_refuge, statut),
    CONSTRAINT chk_reservation_personnes CHECK (nombre_personnes BETWEEN 1 AND 8),
    CONSTRAINT fk_reservation_refuge FOREIGN KEY (id_refuge)
        REFERENCES refuge_zone (id_refuge) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reservation_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #####################################################################
-- MODULE 4 — Assistance d'urgence, solaire & fournisseurs (Bechir)
-- Scénario : un citoyen publie une DEMANDE_ASSISTANCE (Relais de voisinage) ou
-- l'adresse directement à un compte de rôle FOURNISSEUR (page Fournisseurs) ; un bénévole, un
-- fournisseur ou un propriétaire d'INSTALLATION_SOLAIRE envoie une RÉPONSE.
-- Les PERSONNES_PRIORITAIRES (Priorité santé) sont servies en premier pendant une coupure.
-- #####################################################################

-- Panneaux solaires d'un particulier, qui peut proposer de la recharge pendant une coupure.
CREATE TABLE IF NOT EXISTS installation_solaire (
    id_installation       INT          NOT NULL AUTO_INCREMENT,
    id_utilisateur        INT          NOT NULL,         -- compte « proprietaire »
    id_zone               INT          NOT NULL,
    adresse               VARCHAR(255) NOT NULL,
    puissance_kwc         DECIMAL(5,2) NOT NULL,
    capacite_batterie_kwh DECIMAL(5,2) NULL,
    places_recharge       TINYINT      NOT NULL DEFAULT 1,
    disponible            TINYINT(1)   NOT NULL DEFAULT 1,
    date_creation         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_installation),
    CONSTRAINT chk_installation_puissance CHECK (puissance_kwc > 0),
    CONSTRAINT fk_installation_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_installation_zone FOREIGN KEY (id_zone)
        REFERENCES zone (id_zone) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demande (ou offre) d'aide. id_fournisseur rempli = demande adressée à un fournisseur précis
-- (un utilisateur de rôle « fournisseur »).
CREATE TABLE IF NOT EXISTS demande_assistance (
    id_demande     INT          NOT NULL AUTO_INCREMENT,
    id_utilisateur INT          NOT NULL,
    sens           ENUM('demande','offre') NOT NULL DEFAULT 'demande',
    categorie      ENUM('eau','recharge','electricite','generateur','hebergement','transport','aines','autre') NOT NULL,
    titre          VARCHAR(120) NOT NULL,
    description    TEXT         NOT NULL,
    id_zone        INT          NOT NULL,
    quartier       VARCHAR(100) NULL,
    urgence        ENUM('faible','moyenne','haute') NOT NULL DEFAULT 'moyenne',
    id_fournisseur INT          NULL,                     -- utilisateur de rôle « fournisseur »
    date_souhaitee DATE         NULL,
    creneau        ENUM('matin','apres_midi','soir') NULL,
    telephone      VARCHAR(20)  NULL,
    statut         ENUM('ouverte','prise_en_charge','resolue','annulee') NOT NULL DEFAULT 'ouverte',
    date_creation  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_demande),
    KEY idx_demande_zone_statut (id_zone, statut),
    CONSTRAINT fk_demande_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_demande_zone FOREIGN KEY (id_zone)
        REFERENCES zone (id_zone) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_demande_fournisseur FOREIGN KEY (id_fournisseur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Réponse à une demande (bénévole, fournisseur ou propriétaire solaire). Une seule par personne et par demande.
CREATE TABLE IF NOT EXISTS reponse (
    id_reponse      INT      NOT NULL AUTO_INCREMENT,
    id_demande      INT      NOT NULL,
    id_utilisateur  INT      NOT NULL,                   -- qui répond
    id_installation INT      NULL,                       -- si la réponse propose une recharge solaire
    message         TEXT     NOT NULL,
    statut          ENUM('proposee','acceptee','refusee') NOT NULL DEFAULT 'proposee',
    date_reponse    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_reponse),
    UNIQUE KEY uq_reponse_unique (id_demande, id_utilisateur),
    CONSTRAINT fk_reponse_demande FOREIGN KEY (id_demande)
        REFERENCES demande_assistance (id_demande) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reponse_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reponse_installation FOREIGN KEY (id_installation)
        REFERENCES installation_solaire (id_installation) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Personne dont la santé dépend de l'eau ou de l'électricité (page Priorité santé).
CREATE TABLE IF NOT EXISTS personne_prioritaire (
    id_personne         INT          NOT NULL AUTO_INCREMENT,
    id_utilisateur      INT          NOT NULL,           -- qui a fait la déclaration
    pour_qui            ENUM('moi','proche') NOT NULL DEFAULT 'moi',
    nom                 VARCHAR(50)  NOT NULL,
    prenom              VARCHAR(50)  NOT NULL,
    annee_naissance     SMALLINT     NOT NULL,
    lien                VARCHAR(50)  NULL,                -- lien avec le déclarant si « proche »
    telephone           VARCHAR(20)  NOT NULL,
    id_zone             INT          NOT NULL,
    quartier            VARCHAR(100) NULL,
    adresse             VARCHAR(255) NOT NULL,
    besoin              ENUM('dialyse','oxygene','respirateur','froid','mobilite','autre') NOT NULL,
    ressource           ENUM('electricite','eau','les_deux') NOT NULL,
    autonomie_heures    SMALLINT     NULL,               -- autonomie de l'appareil sans courant
    priorite            ENUM('moderee','elevee','critique') NOT NULL DEFAULT 'moderee',
    description         TEXT         NULL,
    contact_urgence_nom VARCHAR(100) NULL,
    contact_urgence_tel VARCHAR(20)  NULL,
    consentement        TINYINT(1)   NOT NULL,           -- accord pour partager ces données avec les secours
    statut              ENUM('en_attente','verifiee','en_cours','resolue') NOT NULL DEFAULT 'en_attente',
    date_creation       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_personne),
    KEY idx_personne_zone_priorite (id_zone, priorite),
    CONSTRAINT chk_personne_consentement CHECK (consentement = 1),
    CONSTRAINT chk_personne_naissance CHECK (annee_naissance BETWEEN 1900 AND 2100),
    CONSTRAINT fk_personne_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_personne_zone FOREIGN KEY (id_zone)
        REFERENCES zone (id_zone) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #####################################################################
-- DONNÉES DE DÉMONSTRATION
-- (fictives ; le script peut être relancé sans créer de doublons)
-- #####################################################################

-- 24 gouvernorats
INSERT IGNORE INTO zone (id_zone, nom_zone, type_zone) VALUES
 (1,'Ariana','gouvernorat'), (2,'Béja','gouvernorat'), (3,'Ben Arous','gouvernorat'),
 (4,'Bizerte','gouvernorat'), (5,'Gabès','gouvernorat'), (6,'Gafsa','gouvernorat'),
 (7,'Jendouba','gouvernorat'), (8,'Kairouan','gouvernorat'), (9,'Kasserine','gouvernorat'),
 (10,'Kébili','gouvernorat'), (11,'Le Kef','gouvernorat'), (12,'Mahdia','gouvernorat'),
 (13,'La Manouba','gouvernorat'), (14,'Médenine','gouvernorat'), (15,'Monastir','gouvernorat'),
 (16,'Nabeul','gouvernorat'), (17,'Sfax','gouvernorat'), (18,'Sidi Bouzid','gouvernorat'),
 (19,'Siliana','gouvernorat'), (20,'Sousse','gouvernorat'), (21,'Tataouine','gouvernorat'),
 (22,'Tozeur','gouvernorat'), (23,'Tunis','gouvernorat'), (24,'Zaghouan','gouvernorat');

-- Comptes de démonstration (mots de passe hachés avec password_hash) :
--   admin@wasla.tn   / Admin@2026    super administrateur
--   steg@wasla.tn    / Steg@2026     sous-admin STEG
--   sonede@wasla.tn  / Sonede@2026   sous-admin SONEDE
--   citoyen@wasla.tn / Citoyen@2026  citoyen
--   entreprise@wasla.tn, fournisseur@wasla.tn, benevole@wasla.tn, solaire@wasla.tn / Demo@2026
-- À CHANGER avant toute mise en ligne.
INSERT IGNORE INTO utilisateur (nom, prenom, email, telephone, mot_de_passe, role, id_zone) VALUES
 ('Admin',     'Super',  'admin@wasla.tn',       NULL,       '$2y$10$H7ywCF6I7Eu/AVI4R3z.T.emq0MYBb/PDbfq1UR2xD1DZBmeUkdj.', 'admin',         NULL),
 ('STEG',      'Agent',  'steg@wasla.tn',        '71000000', '$2y$10$ukcCU.0EvvdZreOmT8sfcuyJm6k2YcTh9qJ/tyhESdn0gZ.o/Gr/i', 'steg',          23),
 ('SONEDE',    'Agent',  'sonede@wasla.tn',      '71000001', '$2y$10$kjLhMiuzTzlVJcxX.rSzEutesMnauz29dwbH/qfaDNyYBtsi3iUa2', 'sonede',        23),
 ('Ben Salah', 'Ahmed',  'citoyen@wasla.tn',     '22123456', '$2y$10$Hhg413MYiGCVp2nV5GTRe.xEFvkuYoJgOpi/s/z6aa4kIOyaLQU1i', 'citoyen',       1),
 ('Gharbi',    'Sami',   'entreprise@wasla.tn',  '71234567', '$2y$10$QBXoZvZ08Uls7ZIW0QhFSe9tIY744Ec7nNRzvBPsOmCI68Z8G3GWu', 'entreprise',    23),
 ('Trabelsi',  'Leila',  'fournisseur@wasla.tn', '98123456', '$2y$10$QBXoZvZ08Uls7ZIW0QhFSe9tIY744Ec7nNRzvBPsOmCI68Z8G3GWu', 'fournisseur',   1),
 ('Jebali',    'Mariem', 'benevole@wasla.tn',    '55123456', '$2y$10$QBXoZvZ08Uls7ZIW0QhFSe9tIY744Ec7nNRzvBPsOmCI68Z8G3GWu', 'benevole',      1),
 ('Mansour',   'Walid',  'solaire@wasla.tn',     '29123456', '$2y$10$QBXoZvZ08Uls7ZIW0QhFSe9tIY744Ec7nNRzvBPsOmCI68Z8G3GWu', 'proprietaire',  1);

-- Informations professionnelles des rôles entreprise et fournisseur (colonnes de utilisateur)
UPDATE utilisateur
   SET nom_organisation = 'Café Le Belvédère', matricule_fiscal = '1234567A', secteur_activite = 'Café',
       adresse = 'Avenue Habib Bourguiba, Tunis', compte_verifie = 1
 WHERE email = 'entreprise@wasla.tn' AND nom_organisation IS NULL;

UPDATE utilisateur
   SET nom_organisation = 'Trabelsi Plomberie', specialite = 'plombier', adresse = 'Ennasr, Ariana',
       description = 'Fuites, chauffe-eau, citernes. Intervention rapide sur Ariana.', compte_verifie = 1, note_moyenne = 4.9
 WHERE email = 'fournisseur@wasla.tn' AND nom_organisation IS NULL;

-- Les lignes suivantes retrouvent les comptes par e-mail (pas d'identifiant écrit en dur).

-- Module 1
INSERT INTO interruption (type_ressource, id_zone, statut, cause, date_debut, date_fin_prevue, id_auteur)
SELECT 'electricite', 1, 'planifiee', 'Travaux sur le poste de distribution Ariana Nord', '2026-12-02 08:00:00', '2026-12-02 14:00:00', u.id_utilisateur
FROM utilisateur u WHERE u.email = 'steg@wasla.tn'
  AND NOT EXISTS (SELECT 1 FROM interruption WHERE id_zone = 1 AND date_debut = '2026-12-02 08:00:00');

INSERT INTO interruption (type_ressource, id_zone, statut, cause, date_debut, date_fin_prevue, id_auteur)
SELECT 'eau', 23, 'en_cours', 'Fuite sur une conduite principale', '2026-11-19 22:00:00', '2026-11-20 18:00:00', u.id_utilisateur
FROM utilisateur u WHERE u.email = 'sonede@wasla.tn'
  AND NOT EXISTS (SELECT 1 FROM interruption WHERE id_zone = 23 AND date_debut = '2026-11-19 22:00:00');

INSERT INTO signalement (id_utilisateur, id_zone, type_ressource, adresse, message, statut, id_interruption)
SELECT u.id_utilisateur, 23, 'eau', 'Rue de Marseille, Tunis', 'Plus d''eau depuis 22 h.', 'confirme', i.id_interruption
FROM utilisateur u JOIN interruption i ON i.id_zone = 23 AND i.date_debut = '2026-11-19 22:00:00'
WHERE u.email = 'citoyen@wasla.tn'
  AND NOT EXISTS (SELECT 1 FROM signalement s WHERE s.id_utilisateur = u.id_utilisateur AND s.adresse = 'Rue de Marseille, Tunis');

INSERT IGNORE INTO confirmation (id_signalement, id_utilisateur)
SELECT s.id_signalement, u.id_utilisateur
FROM signalement s JOIN utilisateur u ON u.email = 'benevole@wasla.tn'
WHERE s.adresse = 'Rue de Marseille, Tunis';

INSERT INTO alerte (id_zone, id_interruption, type_ressource, categorie, gravite, titre, message, source)
SELECT 23, i.id_interruption, 'eau', 'coupure', 'elevee', 'Coupure d''eau à Tunis',
       'Coupure d''eau en cours, rétablissement prévu vers 18 h.', 'automatique'
FROM interruption i
WHERE i.id_zone = 23 AND i.date_debut = '2026-11-19 22:00:00'
  AND NOT EXISTS (SELECT 1 FROM alerte a WHERE a.id_interruption = i.id_interruption);

INSERT IGNORE INTO lieu_suivi (id_utilisateur, type_lieu, libelle, id_zone, quartier)
SELECT id_utilisateur, 'domicile', 'Maison', 1, 'Ennasr' FROM utilisateur WHERE email = 'citoyen@wasla.tn';
INSERT IGNORE INTO lieu_suivi (id_utilisateur, type_lieu, libelle, id_zone, quartier)
SELECT id_utilisateur, 'travail', 'Bureau', 23, 'Lafayette' FROM utilisateur WHERE email = 'citoyen@wasla.tn';

INSERT IGNORE INTO preference_alerte (id_utilisateur, gravite_min, canal_site, canal_email, canal_sms, frequence)
SELECT id_utilisateur, 'moderee', 1, 1, 0, 'immediate' FROM utilisateur WHERE email = 'citoyen@wasla.tn';

-- Module 2 : aucune donnée de démonstration.
-- Les factures (et plus tard les consommations et analyses) sont saisies par les
-- utilisateurs depuis le site : Mes factures (citoyen) ou le Back Office.

-- Module 3
INSERT INTO refuge_zone (id_entreprise, nom, adresse, id_zone, latitude, longitude, services, capacite, places_disponibles, horaires, accessible_pmr, statut)
SELECT e.id_utilisateur, 'Espace climatisé Le Belvédère', 'Avenue Habib Bourguiba, Tunis', 23, 36.800000, 10.180000,
       'climatisation,eau_potable,recharge,wifi', 30, 22, '08:00 – 22:00', 1, 'ouvert'
FROM utilisateur e
WHERE e.email = 'entreprise@wasla.tn' AND e.role = 'entreprise'
  AND NOT EXISTS (SELECT 1 FROM refuge_zone r WHERE r.id_entreprise = e.id_utilisateur);

INSERT INTO reservation_refuge (id_refuge, id_utilisateur, nom_contact, telephone, nombre_personnes, besoins, statut)
SELECT r.id_refuge, u.id_utilisateur, 'Ahmed Ben Salah', '22123456', 3, 'senior', 'confirmee'
FROM refuge_zone r JOIN utilisateur u ON u.email = 'citoyen@wasla.tn'
WHERE r.nom = 'Espace climatisé Le Belvédère'
  AND NOT EXISTS (SELECT 1 FROM reservation_refuge x WHERE x.id_refuge = r.id_refuge AND x.id_utilisateur = u.id_utilisateur);

-- Module 4
INSERT INTO installation_solaire (id_utilisateur, id_zone, adresse, puissance_kwc, capacite_batterie_kwh, places_recharge, disponible)
SELECT u.id_utilisateur, 1, 'Cité Ennasr 2, Ariana', 6.50, 10.00, 3, 1
FROM utilisateur u
WHERE u.email = 'solaire@wasla.tn'
  AND NOT EXISTS (SELECT 1 FROM installation_solaire i WHERE i.id_utilisateur = u.id_utilisateur);

INSERT INTO demande_assistance (id_utilisateur, sens, categorie, titre, description, id_zone, quartier, urgence, telephone)
SELECT u.id_utilisateur, 'demande', 'recharge', 'Recharge d''un concentrateur d''oxygène',
       'Coupure prévue le 2 décembre : besoin de recharger une batterie de concentrateur.', 1, 'Ennasr', 'haute', '22123456'
FROM utilisateur u
WHERE u.email = 'citoyen@wasla.tn'
  AND NOT EXISTS (SELECT 1 FROM demande_assistance d WHERE d.id_utilisateur = u.id_utilisateur AND d.categorie = 'recharge');

INSERT INTO demande_assistance (id_utilisateur, sens, categorie, titre, description, id_zone, quartier, urgence, id_fournisseur, date_souhaitee, creneau, telephone)
SELECT u.id_utilisateur, 'demande', 'eau', 'Fuite sous l''évier',
       'Fuite importante sous l''évier de la cuisine.', 1, 'Ennasr', 'moyenne', f.id_utilisateur, '2026-11-24', 'matin', '22123456'
FROM utilisateur u JOIN utilisateur f ON f.email = 'fournisseur@wasla.tn' AND f.role = 'fournisseur'
WHERE u.email = 'citoyen@wasla.tn'
  AND NOT EXISTS (SELECT 1 FROM demande_assistance d WHERE d.id_utilisateur = u.id_utilisateur AND d.id_fournisseur = f.id_utilisateur);

INSERT IGNORE INTO reponse (id_demande, id_utilisateur, id_installation, message, statut)
SELECT d.id_demande, u.id_utilisateur, i.id_installation,
       'Je peux recharger la batterie chez moi, à 5 minutes à pied.', 'acceptee'
FROM demande_assistance d
JOIN utilisateur u ON u.email = 'solaire@wasla.tn'
JOIN installation_solaire i ON i.id_utilisateur = u.id_utilisateur
WHERE d.categorie = 'recharge';

INSERT INTO personne_prioritaire (id_utilisateur, pour_qui, nom, prenom, annee_naissance, lien, telephone, id_zone, quartier, adresse,
                                  besoin, ressource, autonomie_heures, priorite, contact_urgence_nom, contact_urgence_tel, consentement, statut)
SELECT u.id_utilisateur, 'proche', 'Ben Salah', 'Fatma', 1948, 'Mère', '22123457', 1, 'Ennasr', 'Cité Ennasr 1, Ariana',
       'oxygene', 'electricite', 4, 'critique', 'Ahmed Ben Salah', '22123456', 1, 'verifiee'
FROM utilisateur u
WHERE u.email = 'citoyen@wasla.tn'
  AND NOT EXISTS (SELECT 1 FROM personne_prioritaire p WHERE p.id_utilisateur = u.id_utilisateur AND p.prenom = 'Fatma');

-- Notification commune
INSERT INTO notification (id_utilisateur, titre, message, lien)
SELECT u.id_utilisateur, 'Coupure d''eau à Tunis', 'Une coupure touche votre lieu « Bureau ». Rétablissement prévu vers 18 h.', 'index.php?action=alertes'
FROM utilisateur u
WHERE u.email = 'citoyen@wasla.tn'
  AND NOT EXISTS (SELECT 1 FROM notification n WHERE n.id_utilisateur = u.id_utilisateur AND n.titre = 'Coupure d''eau à Tunis');
