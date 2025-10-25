# 📋 Swagger API - Structures DTOs Complètes

**Source** : https://test.cfitech.io/API/swagger/v1/swagger.json
**Date extraction** : 2025-10-22
**Dernière mise à jour** : 2025-10-24 (Ajout champ `clef` dans UtilisateurGorilliasDto)
**Version API** : myCFI_API v1.0 (OpenAPI 3.0.1)

---

## 🚀 NOUVEAUTÉS PRINCIPALES

### 🆕 Version 2025-10-24
- **Champ `clef`** ajouté dans `UtilisateurGorilliasDto`
- UUID utilisateur retourné lors de l'authentification
- **TODO** : Implémenter l'usage du champ `clef` dans les appels futurs (à définir avec équipe CFI)

### 🆕 Version 2025-10-22 - Endpoints Critiques Ajoutés (4 nouveaux)

1. **`POST /Utilisateurs/getDroitsUtilisateur`** - **SYSTÈME DE PERMISSIONS** => Mise en application, effectuer, lecture seulement
   - Récupère tous les droits de l'utilisateur connecté
   - 25 permissions différentes (utilisateurs, divisions, stocks, opérations, campagnes, factures...)
   - **Impact** : Gestion fine des permissions dans l'UI

2. **`POST /Division/getDivisions`** - **DIVISIONS ENFANTS**
   - Récupère les divisions enfants de l'utilisateur loggé (hiérarchie)
   - **Impact** : Afficher uniquement les divisions accessibles à l'utilisateur

3. **`POST /Division/getUtilisateurs`** - **UTILISATEURS ENFANTS**
   - Récupère les utilisateurs enfants de l'utilisateur loggé (hiérarchie)
   - **Impact** : Gestion hiérarchique des utilisateurs

4. **`POST /Facturations/getFacture`** - **FACTURE INDIVIDUELLE** => Mise en application, effectuer
   - Récupère une facture spécifique si l'utilisateur a le droit
   - Request : `{idFacture: 0}`
   - **Impact** : Accès sécurisé aux factures individuelles avec vérification des droits

---

## 🔐 Authentification

### Security Scheme

**Type** : `apiKey`
**Emplacement** : `header`
**Nom du header** : `Jeton`

**Format** :
```http
Jeton: {token}
```

**⚠️ IMPORTANT** : Ce n'est **PAS** `Authorization: Bearer {token}` mais bien `Jeton: {token}` !

---

## 📡 Endpoints & DTOs

### 1. Utilisateurs - Authentification

#### POST `/Utilisateurs/getUtilisateurGorillias`

**Description** : Authentification via token UUID provenant de Gorillias

**Request Body** : `GetUtilisateurByClefDto`

```json
{
  "jetonUtilisateur": "string | null",
  "clefApi": "string | null"
}
```

**Champs** :
- `jetonUtilisateur` : Le token UUID récupéré depuis `https://gorillias.io/?token={UUID}`
- `clefApi` : Clé API fournie par CFI (nullable)

**Response 200** : `UtilisateurGorilliasDto`

```json
{
  "id": 4370,
  "idDivision": 1114,
  "nomDivision": "Caisse d'Epargne IDF",
  "nom": "bichon",
  "prenom": "Christopher",
  "email": "contact@krystdev.com",
  "type_d_option_GA": "GEN1",
  "jeton": "2c2b2af3-f534-4555-8c90-64440fdd780a",
  "clef": "8f0b9445-0a83-4231-b5fe-9c9d1d7a3daf"
}
```

**Champs** :
- `id` : Identifiant unique utilisateur (int32)
- `idDivision` : Identifiant de la division/organisation (int32)
- `nomDivision` : Nom lisible de la division (string | null)
- `nom` : Nom de famille utilisateur (string | null)
- `prenom` : Prénom utilisateur (string | null)
- `email` : Email utilisateur (string | null)
- `type_d_option_GA` : Type d'option GA (ex: "GEN1") (string | null)
- `jeton` : **Token CFI à utiliser pour les appels suivants** (string | null)
- `clef` : **🆕 Clé utilisateur UUID** - À utiliser dans les appels futurs (string | null)

**Response 400** : `string` - Message d'erreur

---

#### POST `/Utilisateurs/getUtilisateurMyCFiA`

**Description** : Authentification par identifiant et mot de passe

**Request Body** : `GetUtilisateurByLoginMDP`

```json
{
  "identifiant": "string | null",
  "mdp": "string | null",
  "clefApi": "string | null"
}
```

**Champs** :
- `identifiant` : Login/identifiant de l'utilisateur
- `mdp` : Mot de passe de l'utilisateur
- `clefApi` : Clé API fournie par CFI (nullable)

**Response 200** : `UtilisateurGorilliasDto` (identique à `getUtilisateurGorillias`)

**Response 400** : `string` - Message d'erreur

**Usage** :
- Alternative à l'authentification par token UUID
- Permet connexion classique avec login/password
- Retourne le même DTO avec `jeton` pour les appels suivants

---

#### POST `/Utilisateurs/utilisateurLogout`

**Description** : Déconnexion de l'utilisateur

**Request Body** : Aucun (body vide)

**Response 200** : `UtilisateurGorilliasDto`

```json
{
  "id": 123,
  "idDivision": 456,
  "nomDivision": "Division Paris",
  "nom": "Bichon",
  "prenom": "Christophe",
  "email": "c.bichon@CEIDF",
  "type_d_option_GA": "???",
  "jeton": null
}
```

**Response 400** : `string` - Message d'erreur

**Usage** :
- Invalide le jeton actuel de l'utilisateur
- À appeler lors de la déconnexion
- **⚠️ IMPORTANT** : Requiert le header `Jeton: {token}` actif

---

#### POST `/Utilisateurs/getDroitsUtilisateur`

**🆕 NOUVEL ENDPOINT CRITIQUE**

**Description** : Récupère tous les droits et permissions de l'utilisateur connecté

**Request Body** : Aucun (body vide, utilise le token du header `Jeton`)

**Response 200** : `DroitsUtilisateurDto`

```json
{
  "connexion": true,
  "pwa": true,
  "administrateur": false,
  "logistique": false,
  "production": false,
  "developpement": false,
  "utilisateurs_Modif": true,
  "utilisateurs_Crea": false,
  "utilisateurs_Supp": false,
  "utilisateurs_EmpruntIdentite": false,
  "divisions_Crea": false,
  "divisions_Modif": true,
  "divisions_Visu": true,
  "stocks_Crea": false,
  "stocks_Modif": true,
  "stocks_Visu": true,
  "stocks_Supp": false,
  "operations_Crea": false,
  "operations_Valid": false,
  "operations_Visu": true,
  "campagnes_Commande": true,
  "campagnes_Edit": false,
  "reprises_Visu": false,
  "npaI_Crea": false,
  "npaI_Visu": false,
  "factures_Visu": true,
  "signataire": false,
  "valideur": false,
  "telechargementHD": 100.0
}
```

**Champs (25 permissions + 1 quota)** :
- `connexion` : Droit de connexion (boolean)
- `pwa` : Accès à l'application PWA (boolean)
- `administrateur` : Statut administrateur (boolean)
- `logistique` : Accès module logistique (boolean)
- `production` : Accès module production (boolean)
- `developpement` : Accès module développement (boolean)
- `utilisateurs_Modif` : Modifier les utilisateurs (boolean)
- `utilisateurs_Crea` : Créer des utilisateurs (boolean)
- `utilisateurs_Supp` : Supprimer des utilisateurs (boolean)
- `utilisateurs_EmpruntIdentite` : Emprunter l'identité d'un utilisateur (boolean)
- `divisions_Crea` : Créer des divisions (boolean)
- `divisions_Modif` : Modifier des divisions (boolean)
- `divisions_Visu` : Visualiser les divisions (boolean)
- `stocks_Crea` : Créer des stocks (boolean)
- `stocks_Modif` : Modifier des stocks (boolean)
- `stocks_Visu` : Visualiser les stocks (boolean)
- `stocks_Supp` : Supprimer des stocks (boolean)
- `operations_Crea` : Créer des opérations (boolean)
- `operations_Valid` : Valider des opérations (boolean)
- `operations_Visu` : Visualiser les opérations (boolean)
- `campagnes_Commande` : Commander des campagnes (boolean)
- `campagnes_Edit` : Éditer des campagnes (boolean)
- `reprises_Visu` : Visualiser les reprises (boolean)
- `npaI_Crea` : Créer des NPAI (boolean)
- `npaI_Visu` : Visualiser les NPAI (boolean)
- `factures_Visu` : Visualiser les factures (boolean)
- `signataire` : Rôle signataire (boolean)
- `valideur` : Rôle valideur (boolean)
- `telechargementHD` : Quota de téléchargement HD en Go (double)

**Response 400** : `string` - Message d'erreur

**Usage** :
- **Appeler au login** pour récupérer tous les droits de l'utilisateur
- **Stocker en session** pour contrôler l'accès aux fonctionnalités
- **Masquer/afficher** les boutons et menus selon les droits
- **Vérifier côté backend** avant chaque opération sensible

---

### 2. Divisions

#### POST `/Division/getDivisions`

**🆕 NOUVEL ENDPOINT**

**Description** : Récupère les divisions **enfants** de l'utilisateur loggé (structure hiérarchique)

**Request Body** : Aucun (body vide, utilise le token du header `Jeton`)

**Response 200** : `Array<DivisionDto>`

```json
[
  {
    "id": 456,
    "nom": "Division Paris"
  },
  {
    "id": 789,
    "nom": "Division Lyon"
  }
]
```

**Champs** :
- `id` : Identifiant unique de la division (int32)
- `nom` : Nom de la division (string | null)

**Response 400** : `string` - Message d'erreur

**Usage** :
- Récupérer **uniquement les divisions accessibles** à l'utilisateur (hiérarchie)
- Utile pour afficher un sélecteur de divisions dans l'interface
- **⚠️ IMPORTANT** : Ne retourne que les divisions enfants, pas toutes les divisions

---

#### POST `/Division/getUtilisateurs`

**🆕 NOUVEL ENDPOINT**

**Description** : Récupère les utilisateurs **enfants** de l'utilisateur loggé (structure hiérarchique)

**Request Body** : Aucun (body vide, utilise le token du header `Jeton`)

**Response 200** : `Array<UtilisateurDto>`

```json
[
  {
    "id": 123,
    "idDivision": 456,
    "nomDivision": "Division Paris",
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "j.dupont@ceidf.fr"
  },
  {
    "id": 124,
    "idDivision": 456,
    "nomDivision": "Division Paris",
    "nom": "Martin",
    "prenom": "Sophie",
    "email": "s.martin@ceidf.fr"
  }
]
```

**Champs** :
- `id` : Identifiant unique utilisateur (int32)
- `idDivision` : Division de l'utilisateur (int32)
- `nomDivision` : Nom de la division (string | null)
- `nom` : Nom de famille (string | null)
- `prenom` : Prénom (string | null)
- `email` : Email (string | null)

**Response 400** : `string` - Message d'erreur

**Usage** :
- Gestion hiérarchique des utilisateurs
- Afficher uniquement les utilisateurs accessibles à l'utilisateur connecté
- Utile pour la délégation de droits et la gestion d'équipe

---

### 3. Campagnes

#### POST `/Campagnes/getLignesCampagnes`

**Request Body** : `GetCampagnesDto`

```json
{
  "idDivision": 456,
  "dateCreationMin": "2025-01-01T00:00:00Z",
  "dateCreationMax": "2025-01-31T23:59:59Z"
}
```

**Champs** :
- `idDivision` : Filtre par division (int32 | null, optionnel)
- `dateCreationMin` : Date de création minimale (datetime | null, ISO 8601, optionnel)
- `dateCreationMax` : Date de création maximale (datetime | null, ISO 8601, optionnel)

**Response 200** : `Array<LigneCampagneDto>`

```json
[
  {
    "id": 789,
    "idDivision": 456,
    "codeClient": "CLI123",
    "dateCreation": "2025-01-15T10:30:00Z",
    "nom": "Campagne Janvier 2025"
  }
]
```

**Champs** :
- `id` : Identifiant unique de la campagne (int32)
- `idDivision` : Division propriétaire (int32)
- `codeClient` : Code client associé (string | null)
- `dateCreation` : Date de création de la campagne (datetime, ISO 8601)
- `nom` : Nom/libellé de la campagne (string | null)

**Response 400** : `string` - Message d'erreur

---

### 4. Opérations

#### POST `/Operations/getLignesOperations`

**Request Body** : `GetOperationsDto`

```json
{
  "debutDateEnvoi": "2025-01-01T00:00:00Z",
  "finDateEnvoi": "2025-01-31T23:59:59Z",
  "idDivision": 456,
  "idEtats": [1, 2, 3],
  "dateFacturation": "2025-01-15T00:00:00Z"
}
```

**Champs** :
- `debutDateEnvoi` : Date de début d'envoi (**OBLIGATOIRE**, datetime, ISO 8601)
- `finDateEnvoi` : Date de fin d'envoi (datetime | null, ISO 8601, optionnel)
- `idDivision` : Filtre par division (int32 | null, optionnel)
- `idEtats` : Filtre par états (array<int32> | null, optionnel)
- `dateFacturation` : Filtre par date de facturation (datetime | null, ISO 8601, optionnel)

**Response 200** : `Array<LigneOperationDto>`

```json
[
  {
    "id": 1001,
    "idCampagne": 789,
    "nomCampagne": "Campagne Janvier 2025",
    "dateCreation": "2025-01-10T14:20:00Z",
    "codeClient": "CLI123",
    "nom": "Envoi SMS Promo",
    "idDivision": 456,
    "idTypeOperation": 2,
    "nbAdresse": 1500.0,
    "qteParAdresse": 1.0,
    "idEtat": 3,
    "dateEnvoi": "2025-01-15T09:00:00Z",
    "prixPresta": 120.50,
    "prixTransport": 30.00,
    "prixAffranchisement": 50.00,
    "prixTotal": 200.50,
    "dateFacturation": "2025-01-20T00:00:00Z"
  }
]
```

**Champs** :
- `id` : Identifiant unique de l'opération (int32)
- `idCampagne` : Lien vers la campagne parente (int32)
- `nomCampagne` : Nom de la campagne (string | null)
- `dateCreation` : Date de création (datetime, ISO 8601)
- `codeClient` : Code client (string | null)
- `nom` : Nom/description de l'opération (string | null)
- `idDivision` : Division propriétaire (int32)
- `idTypeOperation` : Type d'opération (int32) - **À CLARIFIER**
- `nbAdresse` : Nombre d'adresses/destinataires (double)
- `qteParAdresse` : Quantité par adresse (double)
- `idEtat` : État de l'opération (int32)
- `dateEnvoi` : Date d'envoi effective (datetime | null, ISO 8601)
- `prixPresta` : Prix prestation HT (double | null)
- `prixTransport` : Prix transport HT (double | null)
- `prixAffranchisement` : Prix affranchissement HT (double | null)
- `prixTotal` : Prix total HT (double | null)
- `dateFacturation` : Date de facturation (datetime | null, ISO 8601)

**Response 400** : `string` - Message d'erreur

---

#### POST `/Operations/getEtatsOperation`

**Description** : Récupère la liste des états d'opération

**Request Body** : Aucun (body vide)

**Response 200** : `Array<EtatOperationDto>`

```json
[
  {
    "id": 1,
    "nom": "En attente"
  },
  {
    "id": 2,
    "nom": "En cours"
  },
  {
    "id": 3,
    "nom": "Envoyé"
  }
]
```

**Champs** :
- `id` : Identifiant de l'état (int32)
- `nom` : Libellé de l'état (string | null)

**Response 400** : `string` - Message d'erreur

---

### 5. Stocks

#### POST `/Stocks/getStocks`

**Description** : Récupère la liste des stocks

**Request Body** : Aucun (body vide)

**Response 200** : `Array<StockDto>`

```json
[
  {
    "id": 501,
    "idDivision": 456,
    "nom": "Carte de visite",
    "codeClient": "CLI123",
    "refStockage": "STOCK-A12",
    "qte": 5000.0,
    "stockMinimum": 1000.0,
    "hauteurCm": 5.5,
    "largeurCm": 8.5,
    "profondeurCm": 0.1,
    "poidsG": 200.0,
    "commentaire": "Stock principal"
  }
]
```

**Champs** :
- `id` : Identifiant unique du stock (int32)
- `idDivision` : Division propriétaire (int32)
- `nom` : Nom/description du stock (string | null)
- `codeClient` : Code client associé (string | null)
- `refStockage` : Référence de stockage interne (string | null)
- `qte` : Quantité en stock (double | null)
- `stockMinimum` : Seuil d'alerte (double | null)
- `hauteurCm` : Hauteur en cm (double | null)
- `largeurCm` : Largeur en cm (double | null)
- `profondeurCm` : Profondeur en cm (double | null)
- `poidsG` : Poids en grammes (double | null)
- `commentaire` : Commentaire libre (string | null)

**Response 400** : `string` - Message d'erreur

---

### 6. Facturations

#### POST `/Facturations/getFacturations`

**Description** : Récupère les facturations sur une période

**Request Body** : `GetFacturationsDto`

```json
{
  "debut": "2025-01-01T00:00:00Z",
  "fin": "2025-01-31T23:59:59Z"
}
```

**Champs** :
- `debut` : Date de début de période (**OBLIGATOIRE**, datetime, ISO 8601)
- `fin` : Date de fin de période (**OBLIGATOIRE**, datetime, ISO 8601)

**Response 200** : `Array<FacturationDto>`

```json
[
  {
    "id": 601,
    "dateMiseADispo": "2025-01-05T08:00:00Z",
    "moisFacturation": "2025-01-01T00:00:00Z",
    "factures": [
      {
        "id": 701,
        "adresse": "123 Rue de la Paix, Paris",
        "nomCommande": "Commande #2025-001",
        "demandeur": "C. Bichon",
        "montantTTC": 240.60,
        "montantHT": 200.50,
        "idTypeCout": 1,
        "idTypePaiement": 2,
        "idDelaiPaiement": 3,
        "lignes": [
          {
            "id": 801,
            "libelle": "Envoi SMS Premium",
            "qte": 1500.0,
            "montantHT": 150.00,
            "tauxTVA": 20.0
          }
        ]
      }
    ]
  }
]
```

**FacturationDto - Champs** :
- `id` : Identifiant de la facturation (int32)
- `dateMiseADispo` : Date de mise à disposition (datetime, ISO 8601)
- `moisFacturation` : Mois concerné (datetime, ISO 8601)
- `factures` : Tableau des factures (array<FactureDto> | null)

**FactureDto - Champs** :
- `id` : Identifiant de la facture (int32)
- `adresse` : Adresse de facturation (string | null)
- `nomCommande` : Nom/référence de la commande (string | null)
- `demandeur` : Nom du demandeur (string | null)
- `montantTTC` : Montant TTC (double)
- `montantHT` : Montant HT (double)
- `idTypeCout` : Type de coût (int32) - **À CLARIFIER**
- `idTypePaiement` : Type de paiement (int32) - **À CLARIFIER**
- `idDelaiPaiement` : Délai de paiement (int32) - **À CLARIFIER**
- `lignes` : Lignes de détail (array<LigneFactureDto> | null)

**LigneFactureDto - Champs** :
- `id` : Identifiant de la ligne (int32)
- `libelle` : Libellé de la ligne (string | null)
- `qte` : Quantité (double | null)
- `montantHT` : Montant HT de la ligne (double | null)
- `tauxTVA` : Taux de TVA en % (double | null) - 20.0 = 20%

**Response 400** : `string` - Message d'erreur

---

#### POST `/Facturations/getFacture`

**🆕 NOUVEL ENDPOINT CRITIQUE**

**Description** : Récupère une facture spécifique si l'utilisateur a le droit `factures_Visu`

**Request Body** : `GetFacture`

```json
{
  "idFacture": 701
}
```

**Champs** :
- `idFacture` : Identifiant de la facture à récupérer (int32)

**Response 200** : `FactureDto`

```json
{
  "id": 701,
  "adresse": "123 Rue de la Paix, Paris",
  "nomCommande": "Commande #2025-001",
  "demandeur": "C. Bichon",
  "montantTTC": 240.60,
  "montantHT": 200.50,
  "idTypeCout": 1,
  "idTypePaiement": 2,
  "idDelaiPaiement": 3,
  "lignes": [
    {
      "id": 801,
      "libelle": "Envoi SMS Premium",
      "qte": 1500.0,
      "montantHT": 150.00,
      "tauxTVA": 20.0
    }
  ]
}
```

**Response 400** : `string` - Message d'erreur (y compris si l'utilisateur n'a pas le droit)

**Usage** :
- **Vérifier le droit** `factures_Visu` avant d'afficher le bouton de détail
- Récupérer une facture pour affichage détaillé ou téléchargement PDF
- **Sécurité** : L'API vérifie automatiquement les droits de l'utilisateur

---

## 📊 Codes HTTP

### Succès

- **200 OK** : Requête réussie
  - Content-Type : `application/json`, `text/json`, ou `text/plain`
  - Corps : Selon le DTO de réponse

### Erreurs

- **400 Bad Request** : Erreur de validation ou droits insuffisants
  - Content-Type : `application/json`, `text/json`, ou `text/plain`
  - Corps : `string` - Message d'erreur

---

## 🎯 Récapitulatif des Nouveautés (2025-10-22)

### ✅ Endpoints Ajoutés (4 nouveaux critiques)

1. **`POST /Utilisateurs/getDroitsUtilisateur`** : Système complet de permissions
   - Request : Aucun body (utilise le token)
   - Response : `DroitsUtilisateurDto` avec 25 permissions + 1 quota
   - **Impact MAJEUR** : Gestion fine des droits utilisateurs dans toute l'application

2. **`POST /Division/getDivisions`** : Divisions enfants (hiérarchie)
   - Request : Aucun body (utilise le token)
   - Response : `Array<DivisionDto>` des divisions accessibles
   - **Impact** : Affichage contextuel selon la hiérarchie

3. **`POST /Division/getUtilisateurs`** : Utilisateurs enfants (hiérarchie)
   - Request : Aucun body (utilise le token)
   - Response : `Array<UtilisateurDto>` des utilisateurs accessibles
   - **Impact** : Gestion d'équipe hiérarchique

4. **`POST /Facturations/getFacture`** : Facture individuelle sécurisée
   - Request : `{idFacture: 0}`
   - Response : `FactureDto` avec lignes détaillées
   - **Impact** : Accès détail facture avec vérification des droits

### 📋 DTOs Critiques

1. **`DroitsUtilisateurDto`** (nouveau) - 26 champs :
   - 25 permissions booléennes
   - 1 quota `telechargementHD` (double)
   - **Utilisations** : Contrôle d'accès, UI conditionnelle, validation backend

2. **`UtilisateurDto`** (nouveau) - 6 champs :
   - Structure simplifiée pour la liste des utilisateurs
   - Pas de champ `jeton` ni `type_d_option_GA`

3. **`GetFacture`** (nouveau) - 1 champ :
   - `idFacture` (int32) pour récupérer une facture spécifique

---

## ❓ Questions Restantes pour CFI

### 🔴 CRITIQUES

1. **`type_d_option_GA`** dans `UtilisateurGorilliasDto` :
   - Que signifie ce champ ?
   - Quelles sont les valeurs possibles ?

2. **Types d'opération** (`idTypeOperation`) :
   - Liste des valeurs possibles ?
   - Endpoint pour récupérer la liste de référence ?

3. **Types de coût, paiement, délai** (Factures) :
   - `idTypeCout` : Liste des valeurs ?
   - `idTypePaiement` : Liste des valeurs ?
   - `idDelaiPaiement` : Liste des valeurs ?

4. **Pagination manquante** :
   - Limite maximale de résultats par endpoint ?
   - Comment gérer les gros volumes ?

5. **PDFs des factures** :
   - Comment accéder aux PDFs de factures ?
   - Via `getFacture` puis génération côté frontend ?

6. **Hiérarchie des divisions/utilisateurs** :
   - Comment fonctionne la hiérarchie parent/enfant ?
   - Un utilisateur peut-il appartenir à plusieurs divisions ?

7. **Quota `telechargementHD`** :
   - Unité : Go ?
   - Par jour/mois/an ?
   - Que se passe-t-il en cas de dépassement ?

8. **Droits dynamiques** :
   - Les droits peuvent-ils changer pendant une session ?
   - Faut-il rappeler `getDroitsUtilisateur` périodiquement ?

---

## 📝 Prochaines Actions

1. **✅ Implémenter le système de permissions** :
   - Appeler `/Utilisateurs/getDroitsUtilisateur` au login
   - Stocker les droits en session/state
   - Conditionner l'UI selon les droits

2. **✅ Hiérarchie divisions/utilisateurs** :
   - Intégrer `/Division/getDivisions` pour le sélecteur
   - Intégrer `/Division/getUtilisateurs` pour la gestion d'équipe

3. **✅ Factures détaillées** :
   - Implémenter `/Facturations/getFacture` pour les détails
   - Vérifier le droit `factures_Visu` avant affichage

4. **Tester les endpoints** avec le token pour valider les structures

5. **Poser les questions restantes** à CFI (projets@cfitech.io)

---

**Dernière mise à jour** : 2025-10-22
**Source** : Swagger JSON v1.0 (version corrigée)
**Statut** : ~90% documenté - Questions restantes pour CFI
**Changements majeurs** : Système de permissions complet, hiérarchie divisions/utilisateurs, facture individuelle
