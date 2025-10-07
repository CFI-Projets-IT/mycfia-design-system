# 🔍 Analyse des Informations Manquantes - Questions pour CFI

**Date création** : 2025-01-30
**Dernière mise à jour** : 2025-10-06
**Objectif** : Identifier toutes les informations que CFI doit fournir avant le début du développement

---

## 🎉 MISE À JOUR MAJEURE - Swagger JSON Récupéré !

**Date** : 2025-10-06

### ✅ Informations Obtenues via Swagger JSON

**API - Structures Complètes** :
- ✅ **Schéma d'authentification** : Header `Jeton: {token}` (pas Bearer!)
- ✅ **GetUtilisateurByClefDto** : Structure complète documentée (jetonUtilisateur, clefApi)
- ✅ **UtilisateurGorilliasDto** : 8 champs documentés (id, idDivision, nomDivision, nom, prenom, email, type_d_option_GA, jeton)
- ✅ **GetOperationsDto** : 5 champs documentés (debutDateEnvoi OBLIGATOIRE, finDateEnvoi, idDivision, idEtats, dateFacturation)
- ✅ **LigneOperationDto** : 16 champs documentés
- ✅ **EtatOperationDto** : 2 champs (id, nom)
- ✅ **StockDto** : 5 champs documentés
- ✅ **LigneCampagneDto** : 7 champs documentés
- ✅ **FactureDto** : 10 champs documentés
- ✅ **LigneFactureDto** : 10 champs documentés
- ✅ **FacturationDto** : Structure avec factures[] et lignesFacture[]

**Documentation Complète** : Voir `/CONTEXT_ENGINEERING/SPECIFICATIONS/SWAGGER_DTOS_COMPLETE.md`

### ⚠️ Questions Résolues

Les questions suivantes ont été **complètement ou partiellement résolues** :
- ✅ **Q1.1** : Structure GetUtilisateurByClefDto → RÉSOLU
- ✅ **Q1.2** : Structure UtilisateurGorilliasDto → RÉSOLU (sauf type_d_option_GA)
- ✅ **Q1.4** : Authentification API → RÉSOLU (Header `Jeton: {token}`)
- ✅ **Q8.1** : Structure GetOperationsDto → RÉSOLU
- ✅ **Q8.2** : Structures de réponse → RÉSOLU (LigneOperationDto, StockDto, FactureDto, etc.)

---

## 🚨 DÉCOUVERTE CRITIQUE - Tests API Réels

**Date** : 2025-10-06 (après-midi)

### ❌ BLOQUEUR Sprint S0 : ClefApi OBLIGATOIRE

**Contexte** : Tentative de test de l'endpoint `/Utilisateurs/getUtilisateurGorillias` avec token UUID réel

**Tests effectués** :

#### Test 1 : `clefApi: null`
```json
{
  "jetonUtilisateur": "439f5e26-b861-423e-a962-084c91788b68",
  "clefApi": null
}
```
**Résultat** : ❌ HTTP 400
```json
{
  "title": "One or more validation errors occurred.",
  "status": 400,
  "errors": {
    "ClefApi": ["The ClefApi field is required."]
  }
}
```

#### Test 2 : `clefApi: ""` (chaîne vide)
```json
{
  "jetonUtilisateur": "439f5e26-b861-423e-a962-084c91788b68",
  "clefApi": ""
}
```
**Résultat** : ❌ HTTP 400
```json
"ClefApi invalide"
```

### 🔍 Analyse

**Incohérence Critique** :
- **Swagger JSON** : `"clefApi": { "type": "string", "nullable": true }`
- **API Réelle** : `clefApi` est **OBLIGATOIRE** et doit avoir une **valeur valide**

**Impact** :
- ❌ Impossible de tester l'authentification
- ❌ Impossible de valider le flow complet
- ❌ **BLOQUE complètement le Sprint S0**

**Hypothèses** :
1. `clefApi` est une clé API fournie par CFI par organisation (CEIDF/BNPPRE)
2. `clefApi` est une alternative au flow Gorillias pour intégrations externes
3. Les deux champs (`jetonUtilisateur` ET `clefApi`) sont peut-être requis ensemble

---

## 📊 Vue d'Ensemble - Ce que Nous Avons

### ✅ Informations Disponibles

**Infrastructure** :
- ✅ URL environnement test : https://test.cfitech.io/
- ✅ URL Swagger : https://test.cfitech.io/API/swagger/index.html
- ✅ Swagger JSON complet récupéré et documenté
- ✅ 2 comptes de test (CEIDF + BNPPRE) avec credentials
- ✅ Flow d'authentification Gorillias (connexion → menu → token UUID)
- ✅ Exemples de tokens UUID récupérés

**API** :
- ✅ Liste des 6 endpoints Swagger documentés
- ✅ Structure des 12 DTOs complètement documentée
- ✅ Méthodes HTTP identifiées (tous POST)
- ✅ Schéma d'authentification : Header `Jeton: {token}`
- ✅ Format de tous les champs (types, nullable, required)

**Design** :
- ✅ Maquette Adobe XD complète : https://xd.adobe.com/view/25e0924d-843b-48a4-b03c-9da2cfd4d258-70b3/
- ✅ Design System 95% documenté (palette, typographie, composants)
- ✅ 3 thèmes disponibles (Light, Dark blue, Dark rouge)

**Planification** :
- ✅ Planning 11 sprints (S0-S11)
- ✅ RoadMap 6 releases (R1-R6)
- ✅ Documentation CONTEXT_ENGINEERING complète

**Architecture Multi-Tenancy Hiérarchique** :
- ✅ **myCfia = Application cliente de CFI** (pas de gestion users/tenants)
- ✅ **CFI = Master** : Gestion utilisateurs, tenants, hiérarchie, permissions
- ✅ **Hiérarchie à 5 niveaux** : CFI → Client N1 → N2 → N3 → N4 → N5
- ✅ **Cascade descendante** : Niveau N voit données N+1, N+2, N+3...
- ✅ **Consolidation** : Stocks et factures consolidés automatiquement
- ✅ **Multi-tenant user** : Un utilisateur peut appartenir à plusieurs tenants
- ✅ **Switch dynamique** : Navigation entre tenants visibles via interface
- ✅ **Authentification déléguée** : Users CFI utilisent leurs credentials CFI
- ✅ **Panel d'administration CFI** : Gestion complète avec KPIs
- ✅ **IP Whitelisting** : Déjà effectué (phase dev uniquement)
- ⏳ **Détails techniques** : À découvrir après tests API avec clefApi valide

**Routeurs & APIs** :
- ✅ CFI possède ses propres routeurs SMS et Email
- ✅ Limites SMS confirmées : 160 GSM-7 / 70 Unicode
- ⏳ APIs Email et Courrier en cours de développement chez CFI
- ⏳ SFTP Avanci en cours de finalisation chez CFI

---

## 🏢 Architecture Multi-Tenancy Hiérarchique - Clarifications Client

**Date** : 2025-10-06 (après-midi)

### Vue d'Ensemble

**Principe fondamental** : myCfia est une **application cliente de CFI**, pas un système autonome.

```
┌─────────────────────────────────────┐
│   Application CFI (Master)          │
│                                     │
│  ✅ Gestion utilisateurs             │
│  ✅ Gestion tenants/hiérarchie       │
│  ✅ Gestion rôles/permissions        │
│  ✅ Base de données utilisateurs     │
└─────────────────────────────────────┘
              ▲
              │ API CFI
              ▼
┌─────────────────────────────────────┐
│   myCfia (Application cliente)      │
│                                     │
│  ❌ PAS de création utilisateurs    │
│  ❌ PAS de gestion tenants          │
│  ✅ Authentification via API CFI    │
│  ✅ Respect des droits reçus        │
│  ✅ Interface + Chat IA              │
└─────────────────────────────────────┘
```

### Structure Hiérarchique à N Niveaux

**Q1 - Profondeur de la Hiérarchie** ✅ RÉSOLU
- ✅ **Limite par défaut : 5 niveaux** (configurable)
- ✅ **Scalabilité** : Architecture doit supporter évolution future
- ✅ **Structure type** :
```
Niveau 0: CFI (Super Admin Global)
    │
    ├─ Niveau 1: Client "Siège Social A"
    │       │
    │       ├─ Niveau 2: "Siège Département Paris"
    │       │       │
    │       │       ├─ Niveau 3: "Agence Paris Nord"
    │       │       │       │
    │       │       │       ├─ Niveau 4: "Service Marketing"
    │       │       │       │       │
    │       │       │       │       └─ Niveau 5: "Équipe Digital"
    │       │       │       │
    │       │       │       └─ Users (N3)
    │       │       │
    │       │       └─ Users (N2)
    │       │
    │       └─ Users (N1)
    │
    └─ Niveau 1: Client "Siège Social B"
```

**Q2 - Types d'Utilisateurs par Niveau** ✅ RÉSOLU
- ✅ **Tous types peuvent exister à tous niveaux**
  - ClientSuperAdmin
  - ClientFullAdmin
  - User
- ✅ **Utilisateur multi-niveau possible** : Un user peut appartenir à plusieurs niveaux
- ✅ **Gestion de l'affichage** : Sélection du niveau à voir via l'interface

**Q3 - Création de Sous-Clients** ⏳ EN ATTENTE
- ⏳ **Gestion déléguée à CFI** : myCfia ne gère PAS la création de clients/utilisateurs
- ⏳ **Respect des bonnes pratiques** : Logique gérée côté CFI
- ⏳ **Clarification nécessaire** : Procédure exacte après obtention clefApi

**Q4 - Isolation des Données** ✅ RÉSOLU
- ✅ **Modèle : Cascade descendante**
  - Niveau 1 voit ses données + toutes les données N+1, N+2, N+3...
  - Niveau 2 voit ses données + toutes les données N+2, N+3, N+4...
- ✅ **Règle de visibilité** : N → N+1, N+2, N+3... (vision vers le bas uniquement)
- ❌ **Interdit** : N → N-1 (pas de vision vers le haut)

**Q5 - Gestion des Stocks** ✅ RÉSOLU
- ✅ **Modèle : Consolidation descendante**
  - Niveau 1 voit stock total : Stock(N1) + Stock(N2) + Stock(N3) + ...
  - Niveau 2 voit stock total : Stock(N2) + Stock(N3) + Stock(N4) + ...
- ⏳ **Mécanisme de récupération** : À clarifier avec CFI (après clefApi)

**Q6 - Facturation** ✅ RÉSOLU
- ✅ **Modèle : Consolidation descendante** (identique aux stocks)
  - Niveau 1 voit factures totales : Factures(N1) + Factures(N2) + ...
  - Niveau 2 voit factures totales : Factures(N2) + Factures(N3) + ...
- ⏳ **Mécanisme de récupération** : À clarifier avec CFI (après clefApi)

**Q7 - Utilisateur Multi-Tenant** ✅ RÉSOLU
- ✅ **Autorisé** : Un utilisateur peut appartenir à plusieurs tenants
- ✅ **Gestion interface** : Switch dynamique entre tenants visibles
- ✅ **Exemple** : Un user peut être :
  - User dans "Siège Département Paris"
  - ClientFullAdmin dans "Siège Département Lyon"
  - Voit les données des deux contextes

**Q8 - Héritage des Permissions** ✅ RÉSOLU
- ✅ **Modèle : Héritage automatique en cascade**
  - Les permissions sont héritées automatiquement vers le bas
  - ClientSuperAdmin Niveau 1 a les mêmes droits sur tous ses sous-niveaux
- ⏳ **Permissions exactes** : À découvrir via API CFI (après clefApi)

**Q9 - Navigation dans l'Interface** ✅ RÉSOLU
- ✅ **Modèle : Switch dynamique entre tenants visibles**
  - Sélecteur de tenant dans l'interface myCfia
  - Affichage de l'arborescence des tenants accessibles
  - Changement de contexte à la volée
- ⏳ **Implémentation technique** : À clarifier après tests API

**Q10 - Clé API CFI (clefApi)** ⏳ EN ATTENTE DE CFI
- ⏳ **Procédure en attente** : CFI doit fournir la marche à suivre
- ⏳ **Principe** : Quand un client se connecte sur myCfia, CFI retournera :
  - Token d'authentification
  - ClefApi
  - Toutes informations nécessaires (hiérarchie, permissions, etc.)
- 🔴 **BLOQUEUR ABSOLU** : Sans cette procédure, impossible de tester l'API

**Q11 - Données Transversales** ✅ RÉSOLU
- ✅ **Gestion au niveau CFI uniquement**
  - Référentiels (états opérations, types, etc.)
  - Templates
  - Configurations globales
- ✅ **Pas de gestion par tenant** : Les référentiels sont centralisés chez CFI

### Pagination et Récupération de Données

**Pagination** ✅ CLARIFIÉ
- ✅ **Récupération complète** : Les APIs CFI retournent toutes les données
- ✅ **Pagination côté client** : myCfia gère la pagination dans l'interface
- ✅ **Pas de pagination API** : Pas de paramètres `page`, `limit` dans les requests

**PDF** ✅ CLARIFIÉ
- ✅ **Génération côté CFI** : Les PDFs sont générés par la plateforme CFI
- ✅ **Récupération complète** : myCfia récupère le PDF déjà prêt
- ⏳ **Méthode de récupération** : En attente d'informations CFI
  - Endpoint dédié `/Facturations/getPDF/{id}` ?
  - Champ `pdfUrl` dans les DTOs ?
  - Autre méthode ?

**BDD Commune** ⏳ EN ATTENTE DE CFI
- ⏳ **Structure BDD** : CFI fournira la structure exacte
- ⏳ **Données à push** : CFI indiquera quelles données myCfia doit synchroniser
- ⏳ **Stratégie de synchronisation** : À définir avec CFI

### Authentification Déléguée

**Principe fondamental** ✅ CLARIFIÉ
- ✅ **myCfia ≠ Gestion des utilisateurs**
- ✅ **Utilisateurs CFI** : Les users utilisent leurs identifiants/mots de passe CFI
- ✅ **Authentification déléguée** : myCfia fait le lien avec l'API CFI pour :
  - Vérifier si le user existe
  - Récupérer les informations utilisateur (rôle, permissions, tenants)
  - Obtenir les tokens d'accès
- ✅ **Pas de création users** : Toute gestion utilisateur est chez CFI

---

## 🔐 Questions d'Authentification - En Attente Tests API Réels

**Date** : 2025-10-06 (après-midi)

**⚠️ IMPORTANT** : Ces questions seront clarifiées quand nous pourrons **tester réellement via Swagger** avec une **clefApi valide**.

### Q-AUTH-1 : Flow de Connexion myCfia ⏳ EN ATTENTE

**À terme : 2 options devront être valables**

**Option B - Login Direct** :
```
1. User va directement sur myCfia
2. User saisit identifiant CFI + mot de passe CFI
3. myCfia envoie credentials à API CFI
4. CFI retourne token + infos utilisateur + clefApi
5. myCfia stocke les infos en session
```

**Option C - SSO (Single Sign-On)** :
```
1. User clique sur "myCfia" depuis interface CFI
2. CFI génère un token temporaire
3. Redirection vers myCfia avec token
4. myCfia valide le token auprès de CFI
5. CFI retourne infos utilisateur + clefApi
```

**Statut** : ⏳ Procédure exacte fournie par CFI après obtention clefApi

### Q-AUTH-2 : Récupération de la Hiérarchie ⏳ EN ATTENTE

**Questions** :
- Comment myCfia récupère la structure hiérarchique des tenants ?
- Y a-t-il un endpoint dédié `/Organisations/getHierarchie` ?
- Ces infos sont-elles dans `UtilisateurGorilliasDto` ?
- Liste des tenants visibles par l'utilisateur ?
- Arborescence complète pour switch dynamique ?

**Statut** : ⏳ À découvrir lors des tests Swagger avec clefApi valide

### Q-AUTH-3 : Informations Utilisateur Retournées ⏳ EN ATTENTE

**Informations nécessaires** :
- ❓ Rôle utilisateur : `ClientSuperAdmin` / `ClientFullAdmin` / `User`
- ❓ Niveau dans la hiérarchie : 1, 2, 3, 4, 5
- ❓ Permissions spécifiques : liste des actions autorisées
- ❓ Liste des tenants accessibles : `idDivisions[]`
- ❓ Tenant parent (si niveau > 1) : `idDivisionParente`
- ❓ Tenant actuel par défaut : `idDivisionActuelle`

**Statut** : ⏳ Structure exacte découverte lors des tests API

### Q-AUTH-4 : ClefApi - Procédure de Récupération 🔴 BLOQUEUR

**Question** : Comment récupérer la clefApi quand un client se connecte sur myCfia ?

**Scénarios possibles** :
1. `UtilisateurGorilliasDto` contiendra un nouveau champ `clefApi`
2. CFI fournira une clefApi par tenant lors du déploiement
3. Un endpoint dédié `/Utilisateurs/getClefApi`
4. Autre méthode fournie par CFI

**Statut** : 🔴 **EN ATTENTE DE CFI** - Procédure non fournie

### Q-AUTH-5 : Gestion Multi-Tenant dans les Données ⏳ EN ATTENTE

**Question** : Comment filtrer les données par tenant dans les APIs ?

**Options possibles** :
- **Option A** : myCfia spécifie `idDivision` dans chaque request
- **Option B** : CFI filtre automatiquement selon le token
- **Option C** : Paramètre `includeSubTenants: true` pour cascade

**Statut** : ⏳ Comportement exact découvert lors des tests API

### Q-AUTH-6 : Switch de Tenant ⏳ EN ATTENTE

**Question** : Comment switcher entre tenants visibles ?

**Hypothèse** :
1. User se connecte → myCfia récupère liste des tenants visibles
2. User sélectionne un tenant dans l'interface
3. myCfia appelle les APIs en spécifiant `idDivision`
4. CFI vérifie les droits d'accès

**Statut** : ⏳ Mécanisme exact découvert lors des tests API

### Q-AUTH-7 : Consolidation des Données ⏳ EN ATTENTE

**Question** : Comment récupérer les données consolidées (stocks, factures) ?

**Options possibles** :
- **Option A** : Endpoint dédié `/Stocks/getStocksConsolides`
- **Option B** : Appels multiples + agrégation côté myCfia
- **Option C** : Automatique selon le token (CFI renvoie consolidé)

**Statut** : ⏳ Mécanisme exact découvert lors des tests API

### 📋 Résumé - Dépendances

**Toutes ces questions seront clarifiées quand** :
1. ✅ CFI fournit la procédure de récupération de clefApi
2. ✅ Nous obtenons une clefApi valide
3. ✅ Nous pouvons tester réellement via Swagger avec les identifiants disponibles
4. ✅ Nous analysons les réponses API réelles

**🔴 BLOQUEUR ACTUEL** : Impossibilité de tester sans clefApi valide

---

## ❌ Informations Manquantes Critiques

### 🔴 Questions Restantes pour CFI (PRIORITÉ MAXIMALE)

**Statut** : **1 BLOQUEUR ABSOLU** + plusieurs questions résolues grâce aux clarifications client

**⚠️ BLOQUEUR ABSOLU** : ClefApi obligatoire et valide requise (Q12)

**✅ RÉSOLU** (2025-10-06 après-midi) :
- Token TTL : 30 minutes confirmé
- Architecture Multi-Tenancy clarifiée
- Whitelisting IP : déjà effectué (phase dev uniquement)
- Environnements : comportement identique test/prod
- Routeurs SMS/Email/Courrier : CFI possède ses propres routeurs
- APIs en développement : Email, Courrier, Avanci en cours
- Limites SMS confirmées : 160 GSM-7 / 70 Unicode

---

#### 1. ✅ Flow d'Authentification - QUESTIONS RÉSOLUES

**Q1.1 - Structure GetUtilisateurByClefDto** ✅ RÉSOLU
```json
{
  "jetonUtilisateur": "string|null",  // UUID de gorillias.io
  "clefApi": "string|null"
}
```

**Q1.2 - Structure UtilisateurGorilliasDto** ✅ PARTIELLEMENT RÉSOLU
```json
{
  "id": "int32",
  "idDivision": "int32",
  "nomDivision": "string|null",
  "nom": "string|null",
  "prenom": "string|null",
  "email": "string|null",
  "type_d_option_GA": "string|null",  // ⚠️ Q1.2-RESTANTE : Signification ?
  "jeton": "string|null"
}
```

**Q1.3 - Token Interne** ✅ RÉSOLU
- ✅ Oui, nouveau token retourné dans le champ `jeton` de `UtilisateurGorilliasDto`
- ✅ **Durée de validité : 30 minutes** (confirmé par client)
- ⚠️ Q1.3-RESTANTE : Endpoint de refresh token ? (à clarifier avec CFI)

**Q1.4 - Authentification API** ✅ RÉSOLU
- ✅ Header `Jeton: {token}` (PAS `Authorization: Bearer`!)
- ✅ Schéma confirmé dans Swagger JSON

**Q1.5 - Gestion des Erreurs d'Authentification**
- ❓ Codes HTTP retournés :
  - Token invalide : `401 Unauthorized` ?
  - Token expiré : `401` ou `403` ?
  - Permissions insuffisantes : `403 Forbidden` ?
- ❓ Structure des messages d'erreur JSON :
  ```json
  {
    "error": "???",
    "message": "???",
    "code": "???"
  }
  ```

**Q1.6 - Correlation-ID (Logs)**
- ❓ Comment passer le `Corr-ID` mentionné dans le Planning ?
- ❓ Header `X-Correlation-ID` ?
- ❓ Format attendu ? (UUID, autre ?)
- ❓ Est-il **obligatoire** ou **optionnel** ?

**Q1.7 - Multi-Sessions & Architecture** ✅ CLARIFIÉ
- ✅ **Architecture Multi-Tenancy confirmée** :
  - CFI = Propriétaire et Full Admin de l'application myCfia
  - CFI commercialise l'application à des clients finaux
  - Chaque client peut avoir plusieurs utilisateurs
  - CFI nécessite un **panel d'administration avec KPIs**
- ⚠️ Gestion des sessions multiples par utilisateur à clarifier avec CFI si nécessaire

**Q1.8 - Déconnexion**
- ❓ Y a-t-il un **endpoint de logout** ?
- ❓ Comment invalider un token côté serveur ?

#### 2. ✅ Whitelisting IP - RÉSOLU

**Q2.1-Q2.3 - Statut Whitelisting** ✅ RÉSOLU
- ✅ IP `88.127.116.169` déjà whitelistée
- ✅ Utilisé uniquement pour **phase de développement** (accès Swagger et tests)
- ✅ Cette notion disparaîtra après la phase de développement
- ✅ Pas de besoin de whitelisting pour l'application en production

#### 3. Environnements CFI

**Questions pour CFI** :

**Q3.1 - Environnements Disponibles**
- ❓ Quels sont les **environnements disponibles** ?
  - ✅ Test/Recette : https://test.cfitech.io/
  - ❓ Pré-production : URL ?
  - ❓ Production : URL ?
- ❓ Les **credentials de test** fonctionnent-ils sur tous les environnements ?

**Q3.2 - Différences entre Environnements** ✅ RÉSOLU
- ✅ Comportement **identique** entre test et prod
- ✅ Endpoints identiques
- ⚠️ Volumes de données de test à valider lors des tests réels

#### 4. Rate Limiting & Quotas

**Questions pour CFI** :

**Q4.1 - Limites d'Appels**
- ❓ Y a-t-il un **rate limiting** sur l'API ?
- ❓ Combien d'**appels par minute/heure** maximum ?
- ❓ Le rate limiting est-il **global** ou **par endpoint** ?
- ❓ Le rate limiting est-il **par utilisateur** ou **par IP** ?

**Q4.2 - Comportement en Cas de Dépassement**
- ❓ Code HTTP retourné : `429 Too Many Requests` ?
- ❓ Headers de réponse : `Retry-After` ?
- ❓ Message d'erreur ?

#### 5. Documentation Détaillée

**Questions pour CFI** :

**Q5.1 - Accès à la Documentation Complète**
- ❓ Existe-t-il une **documentation détaillée** au-delà du Swagger ?
- ❓ Guide d'intégration PDF/Confluence/Wiki ?
- ❓ Exemples de **requêtes/réponses** pour chaque endpoint ?

**Q5.2 - Support Technique**
- ❓ Qui est le **contact technique** principal chez CFI ?
- ❓ Délai de réponse moyen pour les **questions techniques** ?
- ❓ Canal de communication : Email (projets@cfitech.io), Teams, Slack ?

---

### 🟡 SPRINT S1 - BDD & Outils Chat (IMPORTANT)

#### 1. Base de Données Commune CFI

**Questions pour CFI** :

**Q6.1 - Accès à la BDD Commune**
- ❓ L'accès à la **BDD commune CFI** se fait uniquement via API ou y a-t-il un **accès direct SQL** ?
- ❓ Si accès direct, quels sont les **credentials** ?
- ❓ Type de BDD : MySQL, MariaDB, PostgreSQL ?
- ❓ Version de la BDD ?

**Q6.2 - Structure des Tables**
- ❓ Existe-t-il un **MCD/MPD** (Modèle Conceptuel/Physique de Données) ?
- ❓ Dictionnaire de données avec **description des champs** ?
- ❓ Liste des **tables accessibles** :
  - Stocks
  - Opérations
  - Factures
  - Campagnes
  - Contacts/Cibles
  - Autres ?

**Q6.3 - Volumétrie**
- ❓ Volumétrie actuelle des données :
  - Nombre de campagnes : ~X ?
  - Nombre d'opérations : ~X ?
  - Nombre de stocks : ~X ?
  - Nombre de factures : ~X ?
- ❓ Croissance mensuelle estimée ?

**Q6.4 - Push de Données vers BDD Commune**
- ❓ Quelles données **myCfia doit pousser** vers la BDD commune ?
- ❓ Via quel endpoint API ?
- ❓ Format des données à envoyer ?
- ❓ Fréquence de synchronisation recommandée ?

#### 2. ✅ IA & Function-calling - STRATÉGIE DÉFINIE

**Q7.1 - Restrictions IA** ✅ RÉSOLU
- ✅ **Stratégie d'anonymisation définie** :
  - **Étape 1** : Déconstruction des données
  - **Étape 2** : Extraction des données nominatives (PII)
  - **Étape 3** : Envoi des données anonymisées au LLM Cloud
  - **Étape 4** : Réception du résultat du LLM
  - **Étape 5** : Reconstruction avec les données nominatives
- ✅ **LLM Cloud autorisé** avec anonymisation préalable
- ✅ Conformité RGPD assurée par le processus d'anonymisation

**Q7.2 - Anonymisation** ✅ RÉSOLU
- ✅ **Processus d'anonymisation à notre charge** (myCfia)
- ✅ Données à anonymiser avant envoi au LLM :
  - Noms/prénoms
  - Adresses
  - Téléphones
  - Emails
  - Toutes données permettant identification directe
- ✅ Utilisation de **tokens de remplacement** pour reconstruction
- ⚠️ **À implémenter** : Service d'anonymisation/reconstruction dans myCfia

---

### 🟢 SPRINT S2/S3 - Chat Lecture v1 & v2

#### 1. Endpoints Lecture - Détails

**Questions pour CFI** :

**Q8.1 - Structure des DTOs de Requête**

Pour chaque endpoint, documenter la structure exacte :

**`GetOperationsDto`** (POST /Operations/getLignesOperations)
```json
{
  "idDivision": "UUID ou INT ?",
  "dateDebut": "Format ISO 8601 ?",
  "dateFin": "Format ISO 8601 ?",
  "page": "INT pour pagination ?",
  "limit": "INT ?",
  "autresChamps": "???"
}
```

**`GetCampagnesDto`** (POST /Campagnes/getLignesCampagnes)
```json
{
  "idDivision": "???",
  "periode": "???",
  "autresChamps": "???"
}
```

**`GetFacturationsDto`** (POST /Facturations/getFacturations)
```json
{
  "idDivision": "???",
  "dateDebut": "???",
  "dateFin": "???",
  "autresChamps": "???"
}
```

**Q8.2 - Structure des DTOs de Réponse**

Pour chaque endpoint, documenter les champs retournés :

**`LigneOperationDto`**
```json
{
  "id": "UUID ?",
  "dateOperation": "???",
  "montant": "FLOAT ?",
  "devise": "EUR ?",
  "description": "???",
  "idDivision": "???",
  "dateMiseAJour": "Pour les 'cartes preuve' ?",
  "lienSource": "URL pour 'voir plus' ?",
  "autresChamps": "???"
}
```

**`StockDto`**
```json
{
  "id": "???",
  "designation": "???",
  "quantite": "???",
  "dateMaj": "???",
  "autresChamps": "???"
}
```

**`FactureDto` et `LigneFactureDto`**
```json
{
  "numeroFacture": "???",
  "dateEmission": "???",
  "montantHT": "???",
  "montantTTC": "???",
  "lienPDF": "URL du PDF ?",
  "nomFichierPDF": "???",
  "lignes": [
    {
      "designation": "???",
      "quantite": "???",
      "prixUnitaire": "???"
    }
  ]
}
```

**Q8.3 - Pagination**
- ❓ Quel est le **système de pagination** utilisé ?
  - Offset/Limit ?
  - Cursor-based ?
- ❓ Nombre maximum de résultats par page ?
- ❓ Comment obtenir le **nombre total** de résultats ?

**Q8.4 - Filtres Disponibles**
- ❓ Liste exhaustive des **filtres** supportés :
  - Par période (dateDebut, dateFin) ?
  - Par division (idDivision) ?
  - Par statut ?
  - Par montant min/max ?
  - Autres ?
- ❓ Les filtres sont-ils **combinables** ?

**Q8.5 - PDFs (Factures)**
- ❓ Les PDFs sont-ils **stockés** sur les serveurs CFI ?
- ❓ Si oui, URL d'accès : `https://test.cfitech.io/files/{id}.pdf` ?
- ❓ Les PDFs sont-ils **générés à la demande** ?
- ❓ Authentification requise pour télécharger un PDF ?
- ❓ Taille moyenne/maximale d'un PDF ?

#### 2. Divisions

**Questions pour CFI** :

**Q9.1 - Liste des Divisions**
- ❓ Existe-t-il un **endpoint** pour récupérer la liste des divisions disponibles ?
- ❓ Structure d'une division :
  ```json
  {
    "id": "UUID ?",
    "nom": "Division Paris ?",
    "code": "DIV_01 ?"
  }
  ```
- ❓ Les divisions sont-elles **spécifiques par organisation** (CEIDF vs BNPPRE) ?

---

### 🟠 SPRINT S5/S6 - SMS

**Q10.1 - Routeur SMS** ✅ RÉSOLU
- ✅ CFI possède son **propre routeur SMS**
- ✅ Limites de caractères **confirmées** : 160 GSM-7 / 70 Unicode
- ⏳ Documentation API du routeur à obtenir quand disponible

**Q10.2 - Synchronisation Statuts SMS** ⏳ EN ATTENTE
- ⏳ Endpoint API en cours de développement chez CFI
- ⏳ Format des statuts à documenter une fois l'API prête

**Q10.3 - Liste Opt-out** ⏳ EN ATTENTE
- ⏳ Gestion centralisée à clarifier avec CFI une fois l'API prête

---

### 🟠 SPRINT S8 - Email

**Q11.1 - Routeur Email** ✅ RÉSOLU
- ✅ CFI possède son **propre routeur Email**
- ⏳ API en cours de développement chez CFI
- ⏳ Documentation (domaine, DNS, configuration) à obtenir une fois l'API prête

**Q11.2 - Webhooks Retours Email** ⏳ EN ATTENTE
- ⏳ API en cours de développement chez CFI
- ⏳ Format des événements webhook à documenter une fois l'API prête

---

### 🟠 SPRINT S9 - Courrier

**Q12.1 - API Impression** ⏳ EN ATTENTE
- ⏳ API en cours de développement chez CFI
- ⏳ Endpoint et format de requête à documenter une fois l'API prête

**Q12.2 - Suivi Impression** ⏳ EN ATTENTE
- ⏳ API en cours de développement chez CFI
- ⏳ Statuts et webhooks à documenter une fois l'API prête

---

### 🟠 SPRINT S11 - AVANCI

**Q13.1 - SFTP Avanci** ⏳ EN COURS FINALISATION
- ⏳ SFTP Avanci en cours de finalisation chez CFI
- ⏳ Credentials (host, port, user, auth) à obtenir une fois finalisé

**Q13.2 - Format Fichiers Leads** ⏳ EN COURS FINALISATION
- ⏳ Structure CSV exacte à documenter une fois finalisé
- ⏳ Encodage, séparateur et format à confirmer

**Q13.3 - Volumétrie Leads** ⏳ EN COURS FINALISATION
- ⏳ Volumétrie et fréquence à documenter une fois finalisé

**Q13.4 - Gestion Erreurs SFTP** ⏳ EN COURS FINALISATION
- ⏳ Processus de gestion d'erreurs à documenter une fois finalisé

---

## 📋 Actions Requises

### ✅ Prochaines Étapes

1. **Envoyer cette liste de questions à CFI** (projets@cfitech.io)
2. **Organiser une réunion technique** avec CFI pour clarifier :
   - Flow d'authentification complet
   - Structure des DTOs
   - Environnements et accès
3. **Tester les endpoints Swagger** avec les tokens récupérés pour documenter les structures réelles
4. **Documenter les réponses** dans `ENVIRONMENTS.md` au fur et à mesure

### 🎯 RÉSUMÉ EXÉCUTIF - Questions Critiques pour CFI

**À envoyer à** : projets@cfitech.io

**Statut** : **1 BLOQUEUR ABSOLU** + plusieurs questions résolues (2025-10-06)

---

#### 🚨 BLOQUEUR ABSOLU - Sprint S0 IMPOSSIBLE SANS RÉPONSE

**Q12** - **🔴 ClefApi OBLIGATOIRE ET VALIDE REQUISE** ⚠️ **URGENT**

**Contexte** : Tests réels de l'API ont révélé une incohérence critique entre le Swagger et l'API.

**Problème** :
- Swagger indique : `"clefApi": "string|null"` (nullable)
- API réelle refuse :
  - `null` → "The ClefApi field is required."
  - `""` → "ClefApi invalide"

**Questions URGENTES** :
1. **Quelle est la valeur valide à utiliser pour `clefApi` ?**
2. **Comment obtenir cette clé API ?** (fournie par CFI ? générée ?)
3. **Y a-t-il une clé par organisation ?** (CEIDF vs BNPPRE)
4. **Les deux champs sont-ils requis ensemble ?** (`jetonUtilisateur` ET `clefApi`)
5. **Quelle est la durée de validité de cette clé ?**
6. **Le Swagger sera-t-il corrigé pour indiquer `required: true` ?**

**Impact** : ❌ **Sans cette clé, impossible de tester l'authentification. Sprint S0 complètement bloqué.**

---

#### 🔴 BLOQUANT Sprint S0 (Authentification) - Questions Restantes

**Q1** - **`type_d_option_GA`** : Quelle est la signification du champ `type_d_option_GA` dans `UtilisateurGorilliasDto` ?

**Q2** - ~~**Durée token**~~ ✅ **RÉSOLU : 30 minutes** (confirmé par client)

**Q3** - **Refresh token** : Existe-t-il un endpoint pour renouveler le token avant expiration ?

**Q4** - **Gestion erreurs** : Structure détaillée des messages d'erreur (codes, format JSON) pour tous les endpoints ?

#### 🟡 IMPORTANT Sprint S2 (Lecture Données)

**Q5** - **`idTypeOperation`** : Mapping des valeurs du champ `idTypeOperation` dans `LigneOperationDto` (1=SMS, 2=Email, 3=Courrier ?)

**Q6** - **Type factures** : Valeurs possibles et signification du champ `type` dans `FactureDto` ("coût", "paiement", "délai" ?)

**Q7** - **Pagination** : Stratégie de pagination pour les grands volumes (limite par requête ? curseur ? compteur total ?)

**Q8** - **PDF Factures** : Comment accéder aux PDFs des factures ? Endpoint dédié ? URL directe ?

#### 🟢 UTILE Sprint S2/S3

**Q9** - **Liste Divisions** : Existe-t-il un endpoint pour récupérer la liste complète des divisions avec leurs IDs ?

**Q10** - **Rate Limiting** : Limites de requêtes par minute/heure/jour ?

**Q11** - **Structures Request manquantes** : Quels sont les champs attendus pour `GetCampagnesDto`, `GetFacturationsDto`, et request body de `/Stocks/getStocks` ?

---

### 📊 Priorisation des Questions - MISE À JOUR

**✅ RÉSOLU (via Swagger JSON)** :
- ✅ Q1.1 : Structure GetUtilisateurByClefDto
- ✅ Q1.2 : Structure UtilisateurGorilliasDto (8 champs)
- ✅ Q1.4 : Authentification API (Header `Jeton: {token}`)
- ✅ Q8.1 : Structure GetOperationsDto (5 champs)
- ✅ Q8.2 : Structures de réponse (LigneOperationDto 16 champs, StockDto 5 champs, FactureDto 10 champs, etc.)

**🔴 CRITIQUE (Bloquant pour S0)** :
- Q1-Q4 : Questions restantes authentification
- Q2.1 à Q2.3 : Whitelisting IP (à tester)
- Q3.1 à Q3.2 : Environnements (à tester)
- Q5.1 à Q5.2 : Support technique

**🟡 IMPORTANT (Nécessaire avant S2)** :
- Q5-Q8 : Questions opérations/factures
- Q9-Q11 : Pagination, divisions, rate limiting
- Q6.1 à Q6.4 : BDD Commune
- Q7.1 à Q7.2 : IA & RGPD

**🟢 MOYEN (Peut attendre)** :
- Q10.1 à Q10.3 : SMS (Sprint S5)
- Q11.1 à Q11.2 : Email (Sprint S8)
- Q12.1 à Q12.2 : Courrier (Sprint S9)

**🟠 FAIBLE (Sprints ultérieurs)** :
- Q13.1 à Q13.4 : Avanci (Sprint S11)

---

**Dernière mise à jour** : 2025-10-06
**Responsable** : Équipe myCfia
**Contact CFI** : projets@cfitech.io

---

## 📋 Changelog

**2025-10-06 (Soir - Architecture Multi-Tenancy Hiérarchique)** :
- ✅ **Nouvelle section majeure** : Architecture Multi-Tenancy Hiérarchique (lignes 143-412)
- ✅ **Q1 - Profondeur** : Limite 5 niveaux par défaut, scalabilité requise
- ✅ **Q2 - Types Utilisateurs** : Tous types à tous niveaux, multi-niveau possible
- ✅ **Q4 - Isolation** : Modèle cascade descendante (N voit N+1, N+2...)
- ✅ **Q5 - Stocks** : Consolidation descendante
- ✅ **Q6 - Facturation** : Consolidation descendante (comme stocks)
- ✅ **Q7 - Multi-Tenant** : Utilisateur peut appartenir à plusieurs tenants
- ✅ **Q8 - Permissions** : Héritage automatique en cascade
- ✅ **Q9 - Navigation** : Switch dynamique entre tenants visibles
- ✅ **Q11 - Données Transversales** : Gérées uniquement au niveau CFI
- ✅ **Pagination** : Récupération complète API → Pagination côté client
- ✅ **PDF** : Génération côté CFI → Récupération complète
- ✅ **Authentification Déléguée** : myCfia = Application cliente, pas de gestion users
- ✅ **7 questions d'authentification** : Q-AUTH-1 à Q-AUTH-7 en attente tests API réels
- ⏳ **Q-AUTH-1** : À terme, Options B (Login Direct) et C (SSO) doivent être valables
- 🔴 **Clarification majeure** : Toutes réponses viendront après tests Swagger avec clefApi valide

**2025-10-06 (Après-midi - Clarifications Client)** :
- ✅ **Architecture Multi-Tenancy confirmée** :
  - CFI = Propriétaire et Full Admin
  - CFI commercialise à des clients finaux
  - Panel d'administration CFI avec KPIs requis
- ✅ **Token TTL confirmé** : 30 minutes
- ✅ **Whitelisting** : Déjà effectué (phase dev uniquement)
- ✅ **Environnements** : Comportement identique test/prod
- ✅ **Routeurs CFI** : SMS, Email, Courrier (propres routeurs CFI)
- ✅ **Limites SMS confirmées** : 160 GSM-7 / 70 Unicode
- ⏳ **APIs en développement** : Email, Courrier en cours dev chez CFI
- ⏳ **Avanci** : SFTP en cours de finalisation chez CFI

**2025-10-06 (Après-midi - Tests API Réels)** :
- 🚨 **DÉCOUVERTE CRITIQUE** : ClefApi obligatoire et valide requise
- ❌ Tests réels révèlent incohérence Swagger vs API réelle
- ❌ `clefApi: null` → Erreur "field is required"
- ❌ `clefApi: ""` → Erreur "ClefApi invalide"
- 🔴 Ajout Question Q12 - **BLOQUEUR ABSOLU Sprint S0**
- ⚠️ Sprint S0 impossible à démarrer sans cette information

**2025-10-06 (Matin - Swagger JSON)** :
- ✅ Récupération et analyse complète du Swagger JSON
- ✅ Documentation de 12 DTOs avec tous les champs et types
- ✅ Résolution de 5 questions critiques (Q1.1, Q1.2, Q1.4, Q8.1, Q8.2)
- ✅ Identification de 8 questions restantes prioritaires
- ✅ Création section "Résumé Exécutif" pour CFI

**2025-01-30** :
- Création initiale avec 59 questions identifiées
