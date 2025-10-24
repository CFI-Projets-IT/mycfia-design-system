-- 1. Table de référence pour les types de canaux (email, sms et papier)
CREATE TABLE types_canaux (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nom_canal NVARCHAR(50) NOT NULL UNIQUE
);

-- 2. Table campagnes
CREATE TABLE campagnes (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nom NVARCHAR(100) NOT NULL,
    type_canal_id INT NOT NULL
        CONSTRAINT fk_campagnes_types_canaux
            FOREIGN KEY(type_canal_id)
            REFERENCES types_canaux(id)
            ON DELETE NO ACTION,
    date_debut DATE       NULL,
    date_fin   DATE       NULL,
    description NVARCHAR(MAX) NULL
);

-- 3. Table gamme produit (personnalisation par campagne et client)
CREATE TABLE gamme (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nom NVARCHAR(50)  NULL,
    SMS bit NOT NULL DEFAULT(0),
    EMAIL bit NOT NULL DEFAULT(0),
    PAPIER bit NOT NULL DEFAULT(0)
);

-- 4. Table clients
CREATE TABLE clients (
    client_id INT IDENTITY(1,1) PRIMARY KEY,
    RS NVARCHAR(100)      NOT NULL,
    gamme_id INT           NULL
        CONSTRAINT fk_clients_gamme
            FOREIGN KEY(gamme_id)
            REFERENCES gamme(id)
            ON DELETE SET NULL,
    courriel_contact NVARCHAR(255) NULL,
    telephone_contact NVARCHAR(50)  NULL,
    date_creation DATETIME2 NOT NULL DEFAULT(GETDATE()),
    date_upload DATETIME2 NULL
);

-- 5. Table adresses (siège / facturation)
CREATE TABLE adresses (
    adresse_id     INT IDENTITY(1,1) PRIMARY KEY,
    client_id      INT NOT NULL
        CONSTRAINT fk_adresses_clients
            FOREIGN KEY(client_id)
            REFERENCES clients(client_id)
            ON DELETE NO ACTION,
    type_adresse   VARCHAR(20) NOT NULL
        CONSTRAINT chk_type_adresse
            CHECK(type_adresse IN ('siège','facturation')),
    adresse1       NVARCHAR(100) NOT NULL,
    adresse2       NVARCHAR(100) NULL,
    adresse3       NVARCHAR(100) NULL,
    adresse4       NVARCHAR(100) NULL,
    adresse5       NVARCHAR(100) NULL,
    code_postal    NVARCHAR(100) NOT NULL,
    ville          NVARCHAR(100) NOT NULL,
    date_creation  DATETIME2    NOT NULL DEFAULT(GETDATE())
);

-- 6. Table variables (personnalisation par campagne et client)
CREATE TABLE variables (
    id INT IDENTITY(1,1) PRIMARY KEY,
    campagne_id INT NOT NULL
        CONSTRAINT fk_variables_campagnes
            FOREIGN KEY(campagne_id)
            REFERENCES campagnes(id)
            ON DELETE CASCADE,
    client_id INT NOT NULL
        CONSTRAINT fk_variables_clients
            FOREIGN KEY(client_id)
            REFERENCES clients(client_id)
            ON DELETE CASCADE,
    cle_variable NVARCHAR(100) NOT NULL,
    valeur_variable NVARCHAR(MAX)  NULL,
    description NVARCHAR(MAX)      NULL
);

-- 7. Table fichiers (assets et livrables, liés à la campagne et/ou client)
CREATE TABLE fichiers (
    id INT IDENTITY(1,1) PRIMARY KEY,
    campagne_id INT    NULL
        CONSTRAINT fk_fichiers_campagnes
            FOREIGN KEY(campagne_id)
            REFERENCES campagnes(id)
            ON DELETE SET NULL,
    client_id   INT    NULL
        CONSTRAINT fk_fichiers_clients
            FOREIGN KEY(client_id)
            REFERENCES clients(client_id)
            ON DELETE SET NULL,
    nom_fichier NVARCHAR(255) NOT NULL,
    type_fichier NVARCHAR(50)  NULL,
    chemin_fichier NVARCHAR(MAX) NULL,
    extension NVARCHAR(10) NOT NULL,
    date_upload DATETIME2 NOT NULL DEFAULT(GETDATE())
);

-- 8. Table produits (Papier, email et sms)
CREATE TABLE produits(
    id INT IDENTITY(1,1)  PRIMARY KEY,
    client_id  INT    NULL
        CONSTRAINT fk_produits_clients
            FOREIGN KEY(client_id)
            REFERENCES clients(client_id)
            ON DELETE SET NULL,
    nom  NVARCHAR(50)  NULL,
    types_canaux_id  INT    NULL
        CONSTRAINT fk_produits_types_canaux
            FOREIGN KEY(types_canaux_id)
            REFERENCES types_canaux(id)
            ON DELETE CASCADE
);

-- 9. Table produits_sms
CREATE TABLE produits_sms (
    produit_id    INT PRIMARY KEY
        CONSTRAINT fk_produits_sms_produits
            FOREIGN KEY(produit_id)
            REFERENCES produits(id),
    prix_unitaire_sms DECIMAL(10,2) NULL,
    nb_sms INT NOT NULL DEFAULT(1),
    nom_emmetteur NVARCHAR(10) NULL,
    date_depot INT NOT NULL DEFAULT(1),
    text NVARCHAR(MAX) NULL
);

-- 10. Table produits_email
CREATE TABLE produits_email (
    produit_id     INT PRIMARY KEY
        CONSTRAINT fk_produits_email_produits
            FOREIGN KEY(produit_id)
            REFERENCES produits(id),
    sujet          VARCHAR(255) NULL,
    etiquette_html NVARCHAR(MAX) NULL,
    prix_unitaire_email FLOAT NULL,
    date_depot INT NOT NULL DEFAULT(1)
);

-- 11. Table produits_papier
CREATE TABLE produits_papier (
    produit_id   INT PRIMARY KEY
        CONSTRAINT fk_produits_papier_produits
            FOREIGN KEY(produit_id)
            REFERENCES produits(id),
    prix_unitaire_papier_document DECIMAL(10,2) NULL,
    prix_unitaire_papier_msp DECIMAL(10,2) NULL,
    prix_unitaire_papier_faconnage DECIMAL(10,2) NULL,
    rv BIT NULL,
    noir BIT NULL,
    date_depot INT NOT NULL DEFAULT(1),
    fond_perdu BIT NULL,
    hauteur DECIMAL(10,2) NULL,
    largeur DECIMAL(10,2) NULL,
    papier NVARCHAR(10) NULL,
    grammage NVARCHAR(10) NULL,
    finition NVARCHAR(10) NULL
);

-- 12. Référentiel des types d’actions réalisées par les partenaires
CREATE TABLE types_actions (
    type_action_id   INT IDENTITY(1,1) PRIMARY KEY,
    code_action      NVARCHAR(50)  NOT NULL UNIQUE,
    libelle          NVARCHAR(100) NOT NULL
);

-- 13. Table des partenaires (prestataires externes)
CREATE TABLE partenaires (
    partenaire_id   INT IDENTITY(1,1) PRIMARY KEY,
    nom              NVARCHAR(100) NOT NULL,
    contact          NVARCHAR(255) NULL,
    telephone        NVARCHAR(50)  NULL,
    date_creation    DATETIME2     NOT NULL DEFAULT(GETDATE())
);

-- 14. Journal des actions des partenaires
CREATE TABLE actions_partenaires (
    action_id        INT IDENTITY(1,1) PRIMARY KEY,
    partenaire_id    INT             NOT NULL
        CONSTRAINT fk_actions_partenaires_partenaires
            FOREIGN KEY(partenaire_id)
            REFERENCES partenaires(partenaire_id)
            ON DELETE CASCADE,
    campagne_id      INT             NULL
        CONSTRAINT fk_actions_partenaires_campagnes
            FOREIGN KEY(campagne_id)
            REFERENCES campagnes(id)
            ON DELETE SET NULL,
    client_id        INT             NULL
        CONSTRAINT fk_actions_partenaires_clients
            FOREIGN KEY(client_id)
            REFERENCES clients(client_id)
            ON DELETE SET NULL,
    type_action_id   INT             NOT NULL
        CONSTRAINT fk_actions_partenaires_types_actions
            FOREIGN KEY(type_action_id)
            REFERENCES types_actions(type_action_id)
            ON DELETE NO ACTION,
    date_action      DATETIME2       NOT NULL DEFAULT(GETDATE()),
    details          NVARCHAR(MAX)   NULL
);

-- 15. Indexes pour optimiser les jointures et requêtes
-- 15.1. Index pour les FK dans campagnes
CREATE INDEX idx_campagnes_type_canal
    ON campagnes(type_canal_id);

-- 15.2. Index pour les recherches par période dans campagnes
CREATE INDEX idx_campagnes_dates
    ON campagnes(date_debut, date_fin);

-- 15.3. Index pour la table adresses (filtrer par client et type)
CREATE INDEX idx_adresses_client_type
    ON adresses(client_id, type_adresse);

-- 15.4. Index pour les FK dans variables
CREATE INDEX idx_variables_campagne
    ON variables(campagne_id);
CREATE INDEX idx_variables_client
    ON variables(client_id);

-- 15.5. Index composite pour accélérer les recherches par clé de variable
CREATE INDEX idx_variables_campagne_client_cle
    ON variables(campagne_id, client_id, cle_variable);

-- 15.6. Index pour les FK dans fichiers
CREATE INDEX idx_fichiers_campagne
    ON fichiers(campagne_id);
CREATE INDEX idx_fichiers_client
    ON fichiers(client_id);

-- 15.7. Index pour les FK dans produits
CREATE INDEX idx_produits_client
    ON produits(client_id);
CREATE INDEX idx_produits_type_canal
    ON produits(types_canaux_id);

-- 15.8. Index pour la table produits_sms (FK déjà PK, mais en cas de filtrage)
CREATE INDEX idx_produits_sms_id
    ON produits_sms(produit_id);

-- 15.9. Index pour la table produits_email (FK déjà PK)
CREATE INDEX idx_produits_email_id
    ON produits_email(produit_id);

-- 15.10. Index pour la table produits_papier (FK déjà PK)
CREATE INDEX idx_produits_papier_id
    ON produits_papier(produit_id);

-- 15.11. Index sur le contact client pour les recherches fréquentes
CREATE INDEX idx_clients_courriel
    ON clients(courriel_contact);
CREATE INDEX idx_clients_telephone
    ON clients(telephone_contact);

-- 15.12. Index pour les FK dans actions_partenaires (partenaire)
CREATE INDEX idx_actions_partenaires_partenaire
    ON actions_partenaires(partenaire_id);

-- 15.13. Index pour les FK dans actions_partenaires (campagne)
CREATE INDEX idx_actions_partenaires_campagne
    ON actions_partenaires(campagne_id);

-- 15.14. Index pour les FK dans actions_partenaires (client)
CREATE INDEX idx_actions_partenaires_client
    ON actions_partenaires(client_id);

-- 15.15. Index pour les FK dans actions_partenaires (type d’action)
CREATE INDEX idx_actions_partenaires_type_action
    ON actions_partenaires(type_action_id);

-- 15.16. Index pour les recherches par date dans actions_partenaires
CREATE INDEX idx_actions_partenaires_date
    ON actions_partenaires(date_action);
