# Listing des Tables - Base de Données MarketingBundle

**Date** : 2025-10-21
**Projet** : gorillias-marketing-bundle
**Type** : Dictionnaire complet des tables SQL

---

## 📋 Vue d'ensemble

**Total tables** : 13 tables principales
**Groupes fonctionnels** : 4 domaines (Core, Marketing, Content, System)

---

## 🏢 Groupe CORE - Gestion Utilisateurs et Projets (3 tables)

### 1. `user`

**Nom technique** : `user`
**Entité Doctrine** : `User`

**Rôle** : Gestion des comptes utilisateurs de la plateforme GORILLIAS

**Responsabilités** :
- Authentification et autorisation des utilisateurs
- Stockage des informations personnelles (nom, prénom, email)
- Gestion des rôles et permissions
- Traçabilité création/modification compte

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `email` | VARCHAR(255) | Email de connexion (unique) |
| `password` | VARCHAR(255) | Mot de passe hashé |
| `first_name` | VARCHAR(100) | Prénom |
| `last_name` | VARCHAR(100) | Nom de famille |
| `role` | VARCHAR(50) | Rôle utilisateur (ROLE_USER, ROLE_ADMIN) |
| `created_at` | DATETIME | Date de création du compte |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **1:N** avec `client` (un utilisateur possède plusieurs clients)

**Index** :
- `UNIQUE idx_email` sur `email`
- `INDEX idx_role` sur `role`

**Volumétrie estimée** : 50-100 utilisateurs (plateforme 100 clients)

---

### 2. `client`

**Nom technique** : `client`
**Entité Doctrine** : `Client`

**Rôle** : Gestion des clients finaux de l'agence marketing

**Responsabilités** :
- Identification des clients (entreprises/particuliers)
- Informations de contact client
- Relation avec l'utilisateur GORILLIAS propriétaire
- Traçabilité des clients

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `user_id` | INT | FK vers `user` (propriétaire) |
| `name` | VARCHAR(255) | Nom du client |
| `email` | VARCHAR(255) | Email de contact |
| `company` | VARCHAR(255) | Nom de l'entreprise |
| `phone` | VARCHAR(50) | Téléphone |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **N:1** avec `user` (plusieurs clients par utilisateur)
- **1:N** avec `project` (un client possède plusieurs projets marketing)

**Index** :
- `INDEX idx_user` sur `user_id`
- `INDEX idx_company` sur `company`

**Volumétrie estimée** : 100-200 clients (plateforme 100 projets actifs)

---

### 3. `project`

**Nom technique** : `project`
**Entité Doctrine** : `Project`

**Rôle** : Gestion centralisée des projets marketing

**Responsabilités** :
- Centralisation de tous les éléments d'un projet marketing
- Configuration du projet (type client, budget, objectifs)
- État du projet (actif, pausé, terminé, archivé)
- Source d'information initiale (URL, fichier, projet existant)
- Point d'entrée pour toutes les entités marketing

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `client_id` | INT | FK vers `client` |
| `name` | VARCHAR(255) | Nom du projet |
| `context` | TEXT | Contexte métier du projet |
| `source_type` | ENUM | Type de source (URL, FILE, PROJECT) |
| `source` | VARCHAR(2500) | URL ou chemin fichier source |
| `state` | ENUM | État (ACTIVE, PAUSED, COMPLETED, ARCHIVED) |
| `client_type` | ENUM | Type client (B2B, B2C, B2B_B2C) |
| `company_type` | ENUM | Taille (STARTUP, SME, ENTERPRISE) |
| `company_name` | VARCHAR(255) | Nom de l'entreprise cliente |
| `company_category` | VARCHAR(255) | Secteur d'activité |
| `budget` | INT | Budget marketing total (€) |
| `goal_type` | ENUM | Objectif (AWARENESS, CONSIDERATION, CONVERSION, RETENTION) |
| `catchment_area_type` | ENUM | Zone cible (LOCAL, NATIONAL, INTERNATIONAL) |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **N:1** avec `client`
- **1:N** avec `persona`, `strategy`, `competitor`, `asset`, `task`, `agent`, `rag_file`, `opportunity`, `catchment_area`

**Index** :
- `INDEX idx_client` sur `client_id`
- `INDEX idx_state` sur `state`
- `INDEX idx_created` sur `created_at`
- `INDEX idx_category` sur `company_category`

**Volumétrie estimée** : 100-500 projets (base moyenne)

---

## 🎯 Groupe MARKETING - Personas, Stratégies, Concurrence (4 tables)

### 4. `persona`

**Nom technique** : `persona`
**Entité Doctrine** : `Persona`

**Rôle** : Stockage des personas marketing générées par PersonaGeneratorAgent

**Responsabilités** :
- Identification des audiences cibles (buyer personas)
- Stockage des données démographiques (âge, genre, localisation, revenu)
- Challenges et objectifs du persona
- Canaux de communication préférés
- Scoring qualité de la persona
- Lien avec données vectorielles ChromaDB (RAG)

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `project_id` | INT | FK vers `project` |
| `name` | VARCHAR(255) | Nom du persona (ex: "Sarah CTO Tech") |
| `demographics` | JSON | Données démographiques `{age, gender, location, income, education}` |
| `challenges` | JSON | Challenges du persona `["Challenge 1", "Challenge 2"]` |
| `goals` | JSON | Objectifs du persona `["Goal 1", "Goal 2"]` |
| `channels` | JSON | Canaux préférés `["linkedin", "facebook", "google"]` |
| `conversion_rate` | FLOAT | Taux de conversion estimé (0.0-1.0) |
| `quality_score` | FLOAT | Score qualité IA (0.0-1.0) |
| `vector_id` | VARCHAR(36) | UUID ChromaDB (collection `personas`) |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **N:1** avec `project`
- **1:N** avec `audience_data` (données audience par plateforme)

**Index** :
- `INDEX idx_project` sur `project_id`
- `INDEX idx_quality` sur `quality_score`
- `INDEX idx_created` sur `created_at`

**Volumétrie estimée** : 3-5 personas par projet → 400 personas (100 projets)

**Agent responsable** : `PersonaGeneratorAgent`

---

### 5. `audience_data`

**Nom technique** : `audience_data`
**Entité Doctrine** : `AudienceData`

**Rôle** : Données d'audience par plateforme publicitaire pour chaque persona

**Responsabilités** :
- Taille audience disponible par plateforme (Facebook, LinkedIn, Google Ads)
- Taux de portée (reach rate)
- CTR moyen par plateforme
- Métadonnées spécifiques plateforme

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `persona_id` | INT | FK vers `persona` |
| `platform` | VARCHAR(50) | Nom plateforme (facebook, linkedin, google, instagram) |
| `audience_size` | INT | Taille audience disponible |
| `reach_rate` | FLOAT | Taux de portée (0.0-1.0) |
| `ctr` | FLOAT | Click-through rate moyen (0.0-1.0) |
| `metadata` | JSON | Données spécifiques plateforme |

**Relations** :
- **N:1** avec `persona`

**Index** :
- `UNIQUE idx_persona_platform` sur `(persona_id, platform)`

**Volumétrie estimée** : 5 plateformes × 400 personas = 2,000 enregistrements

**Tool responsable** : `BudgetOptimizerTool` (calcul potentiels)

---

### 6. `strategy`

**Nom technique** : `strategy`
**Entité Doctrine** : `Strategy`

**Rôle** : Stockage des stratégies marketing générées par StrategyAnalystAgent

**Responsabilités** :
- Définition de la stratégie marketing (AIDA, Inbound, Outbound)
- Objectifs par phase du funnel (awareness, consideration, conversion)
- Tactiques marketing détaillées
- KPIs mesurables
- Allocation budgétaire par canal
- Scoring confiance de la stratégie
- Lien ChromaDB pour recherche sémantique

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `project_id` | INT | FK vers `project` |
| `name` | VARCHAR(255) | Nom de la stratégie |
| `type` | ENUM | Type (AIDA, CONTENT_MARKETING, INBOUND, OUTBOUND) |
| `objectives` | JSON | Objectifs `{awareness: "...", consideration: "...", conversion: "..."}` |
| `tactics` | JSON | Tactiques `["SEO", "Content marketing", "Paid ads"]` |
| `kpis` | JSON | KPIs `{traffic: 10000, leads: 500, conversions: 50}` |
| `budget_allocation` | JSON | Budgets `{google_ads: 5000, linkedin_ads: 3000, seo: 2000}` |
| `confidence_score` | FLOAT | Score confiance IA (0.0-1.0) |
| `vector_id` | VARCHAR(36) | UUID ChromaDB (collection `strategies`) |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **N:1** avec `project`
- **1:N** avec `asset` (une stratégie génère plusieurs assets)

**Index** :
- `INDEX idx_project` sur `project_id`
- `INDEX idx_type` sur `type`
- `INDEX idx_confidence` sur `confidence_score`

**Volumétrie estimée** : 5-10 stratégies par projet → 750 stratégies (100 projets)

**Agent responsable** : `StrategyAnalystAgent`

---

### 7. `competitor`

**Nom technique** : `competitor`
**Entité Doctrine** : `Competitor`

**Rôle** : Stockage des analyses concurrentielles générées par CompetitorAnalystAgent

**Responsabilités** :
- Identification des concurrents directs/indirects
- URL du site concurrent
- Forces et faiblesses (SWOT)
- Positionnement concurrent
- État d'activité (actif, inactif)
- Lien ChromaDB pour intelligence concurrentielle

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `project_id` | INT | FK vers `project` |
| `name` | VARCHAR(255) | Nom du concurrent |
| `url` | VARCHAR(2500) | Site web concurrent |
| `strengths` | JSON | Forces `["Force 1", "Force 2"]` |
| `weaknesses` | JSON | Faiblesses `["Faiblesse 1", "Faiblesse 2"]` |
| `positioning` | TEXT | Positionnement marché détaillé |
| `state` | ENUM | État (ACTIVE, INACTIVE) |
| `vector_id` | VARCHAR(36) | UUID ChromaDB (collection `competitors`) |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **N:1** avec `project`

**Index** :
- `INDEX idx_project` sur `project_id`
- `INDEX idx_state` sur `state`
- `INDEX idx_name` sur `name`

**Volumétrie estimée** : 10-20 concurrents par projet → 1,500 concurrents (100 projets)

**Agent responsable** : `CompetitorAnalystAgent`

---

## 📝 Groupe CONTENT - Assets Marketing (1 table)

### 8. `asset`

**Nom technique** : `asset`
**Entité Doctrine** : `Asset`

**Rôle** : Stockage de tous les contenus marketing générés par ContentCreatorAgent

**Responsabilités** :
- Génération et stockage de contenus multi-formats
- Liaison avec stratégie marketing
- État de publication (draft, ready, published, archived)
- Métadonnées spécifiques par type d'asset
- Scoring qualité du contenu

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `project_id` | INT | FK vers `project` |
| `strategy_id` | INT | FK vers `strategy` (nullable) |
| `type` | ENUM | Type (GOOGLE_ADS, FACEBOOK_POST, LINKEDIN_POST, INSTAGRAM_POST, EMAIL, BING_ADS, IAB, ARTICLE) |
| `title` | VARCHAR(255) | Titre du contenu |
| `content` | TEXT | Contenu textuel principal |
| `metadata` | JSON | Métadonnées spécifiques type `{hashtags, cta, format, etc.}` |
| `quality_score` | FLOAT | Score qualité IA (0.0-1.0) |
| `state` | ENUM | État (DRAFT, READY, PUBLISHED, ARCHIVED) |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **N:1** avec `project`
- **N:1** avec `strategy` (optionnel)

**Index** :
- `INDEX idx_project` sur `project_id`
- `INDEX idx_strategy` sur `strategy_id`
- `INDEX idx_type` sur `type`
- `INDEX idx_state` sur `state`
- `INDEX idx_quality` sur `quality_score`

**Volumétrie estimée** : 50-100 assets par projet → 7,500 assets (100 projets)

**Agent responsable** : `ContentCreatorAgent`

**AssetBuilders** : 8 builders spécialisés (GoogleAds, Facebook, LinkedIn, Instagram, Email, Bing, IAB, Article)

---

## ⚙️ Groupe SYSTEM - Tâches et Intelligence (5 tables)

### 9. `task`

**Nom technique** : `task`
**Entité Doctrine** : `Task`

**Rôle** : Gestion des tâches asynchrones exécutées par les agents IA

**Responsabilités** :
- File d'attente des tâches asynchrones (Symfony Messenger)
- Traçabilité complète de l'exécution
- État de progression (pending, processing, done, error)
- Stockage des arguments d'entrée et résultats de sortie
- Gestion des erreurs et retry

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `project_id` | INT | FK vers `project` (nullable) |
| `agent_name` | VARCHAR(100) | Nom agent IA (persona_generator, strategy_analyst) |
| `tool_name` | VARCHAR(100) | Nom méthode tool (generatePersona, analyzeStrategy) |
| `arguments` | JSON | Arguments d'entrée `{sector: "tech B2B", target: "CTO"}` |
| `result` | JSON | Résultat de sortie (array agent) |
| `state` | ENUM | État (PENDING, PROCESSING, DONE, ERROR) |
| `error_message` | TEXT | Message d'erreur si échec |
| `started_at` | DATETIME | Date démarrage exécution |
| `completed_at` | DATETIME | Date fin exécution |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **N:1** avec `project` (optionnel)

**Index** :
- `INDEX idx_project` sur `project_id`
- `INDEX idx_state` sur `state`
- `INDEX idx_agent` sur `agent_name`
- `INDEX idx_created` sur `created_at`

**Volumétrie estimée** : 100-500 tasks par projet → 30,000 tasks (100 projets)

**Service responsable** : `AgentTaskManager` (Symfony Messenger)

---

### 10. `agent`

**Nom technique** : `agent`
**Entité Doctrine** : `Agent`

**Rôle** : Stockage des agents IA personnalisés créés par les utilisateurs

**Responsabilités** :
- Configuration d'agents IA sur-mesure
- Personnalité et tone of voice
- Valeurs de marque (brand values)
- Prompts personnalisés (system/user)
- Gestion des fichiers RAG liés
- Sélection des moteurs IA (OpenAI, Anthropic, Gemini)

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `project_id` | INT | FK vers `project` (nullable) |
| `name` | VARCHAR(255) | Nom de l'agent |
| `description` | TEXT | Description du rôle de l'agent |
| `tone_of_voice` | VARCHAR(100) | Tone (professional, casual, friendly, technical) |
| `personality_traits` | JSON | Traits `["empathique", "analytique", "créatif"]` |
| `brand_values` | JSON | Valeurs `["innovation", "transparence", "excellence"]` |
| `custom_prompts` | JSON | Prompts `{system: "...", user: "..."}` |
| `rag_ids` | JSON | IDs fichiers RAG `[1, 5, 12]` |
| `ai_engines` | JSON | Moteurs IA `["openai", "anthropic", "gemini"]` |
| `created_at` | DATETIME | Date de création |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **N:1** avec `project` (optionnel)
- **1:N** avec `rag_file` (fichiers RAG liés)

**Index** :
- `INDEX idx_project` sur `project_id`
- `INDEX idx_name` sur `name`

**Volumétrie estimée** : 1-3 agents custom par projet → 200 agents (100 projets)

**Interface** : GORILLIAS Custom Agent Builder

---

### 11. `rag_file`

**Nom technique** : `rag_file`
**Entité Doctrine** : `RagFile`

**Rôle** : Stockage des fichiers RAG (Retrieval Augmented Generation) pour contexte agents IA

**Responsabilités** :
- Upload de fichiers pour contexte agent (PDF, DOCX, TXT)
- Extraction et parsing du contenu textuel
- Vectorisation et indexation ChromaDB
- Métadonnées fichier (taille, type MIME, etc.)
- Liaison avec agents IA personnalisés

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `project_id` | INT | FK vers `project` |
| `agent_id` | INT | FK vers `agent` (nullable) |
| `filename` | VARCHAR(255) | Nom fichier stocké (UUID) |
| `original_name` | VARCHAR(255) | Nom original du fichier |
| `type` | VARCHAR(100) | Type fichier (pdf, docx, txt, url) |
| `content` | TEXT | Contenu textuel extrait |
| `vector_id` | VARCHAR(36) | UUID ChromaDB (collection `documents`) |
| `metadata` | JSON | Métadonnées `{size, mime_type, pages, etc.}` |
| `created_at` | DATETIME | Date d'upload |

**Relations** :
- **N:1** avec `project`
- **N:1** avec `agent` (optionnel)

**Index** :
- `INDEX idx_project` sur `project_id`
- `INDEX idx_agent` sur `agent_id`
- `INDEX idx_type` sur `type`

**Volumétrie estimée** : 10-50 fichiers RAG par projet → 3,000 fichiers (100 projets)

**Service responsable** : RAG Upload Service + VectorizerInterface (OpenAI Embeddings)

---

### 12. `opportunity`

**Nom technique** : `opportunity`
**Entité Doctrine** : `Opportunity`

**Rôle** : Identification automatique des opportunités marketing par les agents IA

**Responsabilités** :
- Détection opportunités (gaps marché, nouveaux canaux, optimisations)
- Priorisation (low, medium, high, critical)
- Estimation impact/effort
- Suivi statut (identified, planned, in_progress, completed)

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `reference` | UUID | Référence unique universelle |
| `project_id` | INT | FK vers `project` |
| `title` | VARCHAR(255) | Titre de l'opportunité |
| `description` | TEXT | Description détaillée |
| `priority` | ENUM | Priorité (LOW, MEDIUM, HIGH, CRITICAL) |
| `estimated_impact` | FLOAT | Impact estimé (0.0-1.0) |
| `estimated_effort` | FLOAT | Effort estimé (0.0-1.0) |
| `state` | ENUM | État (IDENTIFIED, PLANNED, IN_PROGRESS, COMPLETED) |
| `created_at` | DATETIME | Date d'identification |
| `updated_at` | DATETIME | Date de dernière modification |

**Relations** :
- **N:1** avec `project`

**Index** :
- `INDEX idx_project` sur `project_id`
- `INDEX idx_priority` sur `priority`
- `INDEX idx_state` sur `state`

**Volumétrie estimée** : 5-15 opportunités par projet → 1,000 opportunités (100 projets)

**Agents responsables** : Tous les agents peuvent identifier des opportunités

---

### 13. `catchment_area`

**Nom technique** : `catchment_area`
**Entité Doctrine** : `CatchmentArea`

**Rôle** : Définition des zones de chalandise (zones géographiques cibles)

**Responsabilités** :
- Ciblage géographique des campagnes marketing
- Définition zones locales (code postal, ville, rayon km)
- Zones régionales (région, pays)
- Paramétrage multi-zones (France + Belgique)

**Champs principaux** :
| Champ | Type | Description |
|-------|------|-------------|
| `id` | INT | Identifiant unique auto-incrémenté |
| `project_id` | INT | FK vers `project` |
| `name` | VARCHAR(255) | Nom de la zone ("Paris 10km", "France") |
| `type` | ENUM | Type (POSTAL_CODE, CITY, REGION, COUNTRY) |
| `value` | VARCHAR(255) | Valeur zone (75001, Paris, Île-de-France, FR) |
| `radius` | INT | Rayon en km (pour type CITY) |
| `metadata` | JSON | Métadonnées `{coordinates, population, etc.}` |

**Relations** :
- **N:1** avec `project`

**Index** :
- `INDEX idx_project` sur `project_id`
- `INDEX idx_type` sur `type`
- `INDEX idx_value` sur `value`

**Volumétrie estimée** : 1-5 zones par projet → 300 zones (100 projets)

**Usage** :
- CompetitorAnalystAgent (recherche concurrents locaux)
- PersonaGeneratorAgent (personas adaptés géographie)
- BudgetOptimizerTool (calculs audience locale)

---

## 📊 Synthèse par Groupe

### Groupe CORE (3 tables)
| Table | Enregistrements/Projet | Total 100 Projets |
|-------|------------------------|-------------------|
| `user` | - | 50 |
| `client` | - | 100 |
| `project` | 1 | 100 |
| **TOTAL CORE** | **1** | **250** |

### Groupe MARKETING (4 tables)
| Table | Enregistrements/Projet | Total 100 Projets |
|-------|------------------------|-------------------|
| `persona` | 3-5 | 400 |
| `audience_data` | 15-25 | 2,000 |
| `strategy` | 5-10 | 750 |
| `competitor` | 10-20 | 1,500 |
| **TOTAL MARKETING** | **33-60** | **4,650** |

### Groupe CONTENT (1 table)
| Table | Enregistrements/Projet | Total 100 Projets |
|-------|------------------------|-------------------|
| `asset` | 50-100 | 7,500 |
| **TOTAL CONTENT** | **50-100** | **7,500** |

### Groupe SYSTEM (5 tables)
| Table | Enregistrements/Projet | Total 100 Projets |
|-------|------------------------|-------------------|
| `task` | 100-500 | 30,000 |
| `agent` | 1-3 | 200 |
| `rag_file` | 10-50 | 3,000 |
| `opportunity` | 5-15 | 1,000 |
| `catchment_area` | 1-5 | 300 |
| **TOTAL SYSTEM** | **117-573** | **34,500** |

---

## 🎯 Total Base de Données

**13 tables SQL**
**~46,900 enregistrements** (base 100 projets)
**Taille estimée** : 500 MB - 1 GB

---

## 🔗 Mapping ChromaDB

### Collections Vectorielles (5 collections)

| Collection ChromaDB | Table SQL Liée | Champ Lien | Usage |
|---------------------|----------------|------------|-------|
| `personas` | `persona` | `vector_id` | Recherche sémantique personas similaires |
| `competitors` | `competitor` | `vector_id` | Intelligence concurrentielle |
| `brands` | `agent` | `rag_ids` (JSON) | Brand guidelines et style guides |
| `strategies` | `strategy` | `vector_id` | Recherche stratégies similaires |
| `documents` | `rag_file` | `vector_id` | Documents RAG génériques |

---

## 📝 Index Recommandés (Résumé)

### Index Performance Critiques

```sql
-- Core
CREATE INDEX idx_client_user ON client(user_id);
CREATE INDEX idx_project_client ON project(client_id);
CREATE INDEX idx_project_state ON project(state);

-- Marketing
CREATE INDEX idx_persona_project ON persona(project_id);
CREATE INDEX idx_persona_quality ON persona(quality_score);
CREATE INDEX idx_strategy_project ON strategy(project_id);
CREATE INDEX idx_competitor_project ON competitor(project_id);

-- Content
CREATE INDEX idx_asset_project_type_state ON asset(project_id, type, state);

-- System
CREATE INDEX idx_task_state_agent ON task(state, agent_name);
CREATE INDEX idx_rag_project_agent ON rag_file(project_id, agent_id);
```

**Total index recommandés** : ~30 index

---

## 🎨 Relations Hiérarchiques

```
User (1)
  └── Client (N)
        └── Project (N)
              ├── Persona (N)
              │     └── AudienceData (N)
              ├── Strategy (N)
              │     └── Asset (N)
              ├── Competitor (N)
              ├── Asset (N)
              ├── Task (N)
              ├── Agent (N)
              │     └── RagFile (N)
              ├── RagFile (N)
              ├── Opportunity (N)
              └── CatchmentArea (N)
```

---

## 🚀 Ordre de Création Recommandé

**Migration Doctrine** : Ordre respectant les dépendances FK

1. **Tables sans dépendances** : `user`
2. **Dépend de User** : `client`
3. **Dépend de Client** : `project`
4. **Dépend de Project** : `persona`, `strategy`, `competitor`, `agent`, `opportunity`, `catchment_area`
5. **Dépend de Persona** : `audience_data`
6. **Dépend de Strategy** : `asset` (FK strategy_id nullable)
7. **Dépend de Project + Agent** : `rag_file`
8. **Dépend de Project** : `task`

---

## 📋 Enums PHP 8.3

### Liste Complète des Enums

**14 enums à créer** :

1. `ProjectSourceTypeEnum` : URL, FILE, PROJECT
2. `ProjectStateEnum` : ACTIVE, PAUSED, COMPLETED, ARCHIVED
3. `ProjectClientTypeEnum` : B2B, B2C, B2B_B2C
4. `ProjectCompanyTypeEnum` : STARTUP, SME, ENTERPRISE
5. `ProjectGoalTypeEnum` : AWARENESS, CONSIDERATION, CONVERSION, RETENTION
6. `ProjectCatchmentAreaTypeEnum` : LOCAL, NATIONAL, INTERNATIONAL
7. `StrategyTypeEnum` : AIDA, CONTENT_MARKETING, INBOUND, OUTBOUND
8. `AssetTypeEnum` : GOOGLE_ADS, FACEBOOK_POST, LINKEDIN_POST, INSTAGRAM_POST, EMAIL, BING_ADS, IAB, ARTICLE
9. `AssetStateEnum` : DRAFT, READY, PUBLISHED, ARCHIVED
10. `TaskStateEnum` : PENDING, PROCESSING, DONE, ERROR
11. `CompetitorStateEnum` : ACTIVE, INACTIVE
12. `OpportunityPriorityEnum` : LOW, MEDIUM, HIGH, CRITICAL
13. `OpportunityStateEnum` : IDENTIFIED, PLANNED, IN_PROGRESS, COMPLETED
14. `CatchmentAreaTypeEnum` : POSTAL_CODE, CITY, REGION, COUNTRY

---

## 🔍 Requêtes SQL Fréquentes

### Dashboard Projet

```sql
-- Vue d'ensemble projet
SELECT
    p.name AS project_name,
    COUNT(DISTINCT persona.id) AS total_personas,
    COUNT(DISTINCT strategy.id) AS total_strategies,
    COUNT(DISTINCT competitor.id) AS total_competitors,
    COUNT(DISTINCT asset.id) AS total_assets,
    p.budget,
    p.state
FROM project p
LEFT JOIN persona ON persona.project_id = p.id
LEFT JOIN strategy ON strategy.project_id = p.id
LEFT JOIN competitor ON competitor.project_id = p.id
LEFT JOIN asset ON asset.project_id = p.id
WHERE p.id = ?
GROUP BY p.id;
```

### Top Personas par Qualité

```sql
-- Meilleures personas du projet
SELECT
    name,
    quality_score,
    demographics->>'$.age' AS age,
    demographics->>'$.gender' AS gender,
    conversion_rate
FROM persona
WHERE project_id = ?
ORDER BY quality_score DESC
LIMIT 5;
```

### Assets par Type et État

```sql
-- Répartition assets
SELECT
    type,
    state,
    COUNT(*) AS total,
    AVG(quality_score) AS avg_quality
FROM asset
WHERE project_id = ?
GROUP BY type, state;
```

### Tasks en Erreur

```sql
-- Tasks échouées à retry
SELECT
    reference,
    agent_name,
    tool_name,
    error_message,
    created_at
FROM task
WHERE state = 'ERROR'
ORDER BY created_at DESC;
```

---

**Document créé** : 2025-10-21
**Type** : Dictionnaire complet tables SQL
**Total tables** : 13 tables + 5 collections ChromaDB
