# Schémas Architecture Hybride CFI-MyCfia

**Date** : 2025-10-22
**Projet** : myCfia - Architecture Recommandée (Option 3)

---

## 📐 Vue d'Ensemble Système

```mermaid
graph TB
    subgraph "BDD CFI Production (Responsabilité CFI)"
        CFI_DB[(BDD CFI<br/>SQL Server<br/>Production)]
    end

    subgraph "BDD Commune CFI (Responsabilité CFI)"
        COMMON_DB[(BDD Commune CFI<br/>SQL Server<br/>Vues Matérialisées)]
        STOCKS_VIEW[("stocks_readonly<br/>refresh 5min")]
        OPERATIONS_VIEW[("operations_readonly<br/>refresh 5min")]
        FACTURES_VIEW[("factures_readonly<br/>refresh 1h")]
        CAMPAGNES_VIEW[("campagnes_readonly<br/>refresh 10min")]
    end

    subgraph "SFTP CFI (Responsabilité CFI)"
        SFTP[("SFTP Server<br/>sftp.cfitech.io<br/>/Partenaires/MyCfia/In/")]
    end

    subgraph "MyCfia Application (Responsabilité MyCfia)"
        MYCFIA_BACKEND["MyCfia Backend<br/>Symfony 7.3"]
        MYCFIA_DB[(BDD MyCfia<br/>PostgreSQL<br/>Bundle Gorillas)]
        AI_CHAT["Chat IA<br/>Symfony AI Bundle"]
    end

    %% Flux Synchronisation BDD (CFI)
    CFI_DB -->|Sync auto| COMMON_DB
    COMMON_DB --> STOCKS_VIEW
    COMMON_DB --> OPERATIONS_VIEW
    COMMON_DB --> FACTURES_VIEW
    COMMON_DB --> CAMPAGNES_VIEW

    %% Flux Lecture (MyCfia)
    STOCKS_VIEW -->|API Read| MYCFIA_BACKEND
    OPERATIONS_VIEW -->|API Read| MYCFIA_BACKEND
    FACTURES_VIEW -->|API Read| MYCFIA_BACKEND
    CAMPAGNES_VIEW -->|API Read| MYCFIA_BACKEND

    %% Flux Écriture (MyCfia → CFI)
    MYCFIA_BACKEND -->|Export JSON/CSV| SFTP
    SFTP -->|Batch Import| CFI_DB

    %% Flux Interne MyCfia
    MYCFIA_DB -->|Doctrine ORM| MYCFIA_BACKEND
    MYCFIA_BACKEND -->|AI Tools| AI_CHAT

    %% Styles
    classDef dbStyle fill:#e3f2fd,stroke:#1976d2,stroke-width:3px
    classDef viewStyle fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px
    classDef appStyle fill:#fff3e0,stroke:#f57c00,stroke-width:2px
    classDef sftpStyle fill:#e8f5e9,stroke:#388e3c,stroke-width:2px

    class CFI_DB,COMMON_DB,MYCFIA_DB dbStyle
    class STOCKS_VIEW,OPERATIONS_VIEW,FACTURES_VIEW,CAMPAGNES_VIEW viewStyle
    class MYCFIA_BACKEND,AI_CHAT appStyle
    class SFTP sftpStyle
```

---

## 🔄 Flux de Lecture (CFI → MyCfia)

```mermaid
sequenceDiagram
    participant User as Utilisateur MyCfia
    participant Chat as Chat IA (Frontend)
    participant Backend as MyCfia Backend<br/>(Responsabilité MyCfia)
    participant Tools as AI Tools (CfiStocksTool)<br/>(Responsabilité MyCfia)
    participant Common as BDD Commune CFI (Read-Only)<br/>(Responsabilité CFI)
    participant API as API CFI (Fallback)<br/>(Responsabilité CFI)

    User->>Chat: "Combien de stock pour Flyer A4 ?"
    Chat->>Backend: POST /api/chat/message
    Backend->>Backend: Parse intent (LLM)
    Backend->>Tools: Call queryStocks(nom_produit="Flyer A4")

    alt BDD Commune disponible
        Tools->>Common: SELECT * FROM stocks_readonly WHERE nom_produit LIKE '%Flyer A4%'
        Common-->>Tools: [Stock 1: 1250 unités, Stock 2: 500 unités]
        Note over Tools,Common: Latence: ~10-50ms
    else BDD Commune indisponible (Fallback)
        Tools->>API: POST /Stocks/getStocks
        API-->>Tools: [Stock 1: 1250 unités, Stock 2: 500 unités]
        Note over Tools,API: Latence: ~100-200ms
    end

    Tools-->>Backend: Result JSON + Metadata (date_maj)
    Backend->>Backend: Generate LLM Response
    Backend-->>Chat: Response with source cards
    Chat-->>User: "Flyer A4 : 1,250 unités en stock<br/>(Dernière MAJ : 10:45, il y a 3 min)"
```

---

## 📤 Flux d'Écriture (MyCfia → CFI)

```mermaid
sequenceDiagram
    participant User as Utilisateur MyCfia
    participant UI as MyCfia Frontend
    participant Backend as MyCfia Backend<br/>(Responsabilité MyCfia)
    participant Export as CampaignExportService<br/>(Responsabilité MyCfia)
    participant SFTP as SFTP CFI<br/>(Responsabilité CFI)
    participant Batch as CFI Batch Import<br/>(Responsabilité CFI)
    participant CFI_DB as BDD CFI Production<br/>(Responsabilité CFI)
    participant Common as BDD Commune CFI<br/>(Responsabilité CFI)

    User->>UI: Valider campagne SMS "Promo Été 2025"
    UI->>Backend: POST /api/campaigns/{id}/validate
    Backend->>Backend: Validation business rules
    Backend->>Export: exportToCfi(Project)

    Export->>Export: generateCampaignJson(Project)
    Note over Export: JSON: campagne, client, variables, produit
    Export->>Export: generateAssetsCsv(Project)
    Note over Export: CSV: assets personnalisation (ZP_ASSETS_*)

    Export->>SFTP: Upload JSON + CSV<br/>MYCFIA_CAMP_123_20251022_143000.json<br/>MYCFIA_CAMP_123_20251022_143000.csv
    Note over Export,SFTP: Fichier .LOCK pendant upload
    SFTP-->>Export: Upload OK

    Export-->>Backend: Success (JSON + CSV uploadés)
    Backend-->>UI: Success + Tracking ID
    UI-->>User: "Campagne envoyée vers CFI<br/>Import prévu dans ~5-10 min"

    loop Toutes les 5 minutes (CFI)
        Batch->>SFTP: Scan /Partenaires/MyCfia/In/
        SFTP-->>Batch: Liste fichiers JSON
        Batch->>Batch: Parse JSON + Validate schema
        Batch->>CFI_DB: INSERT INTO campagnes, variables, fichiers, produits
        CFI_DB-->>Batch: Campagne ID 456
        Batch->>SFTP: Archive fichiers → /Partenaires/MyCfia/Archive/
    end

    Note over CFI_DB: Campagne prête pour envoi

    CFI_DB->>Common: Sync auto (vue matérialisée)
    Note over Common: REFRESH MATERIALIZED VIEW campagnes_readonly

    User->>UI: Rafraîchir statut campagne
    UI->>Backend: GET /api/campaigns/{id}/status
    Backend->>Common: SELECT * FROM campagnes_readonly WHERE reference_mycfia = '...'
    Common-->>Backend: Campagne trouvée (statut: importee_cfi)
    Backend-->>UI: Statut: "Importée dans CFI, en attente envoi"
    UI-->>User: "Campagne importée avec succès ✅"
```

---

## 🏗️ Architecture Technique MyCfia (Multi-Database)

```mermaid
graph TB
    subgraph "MyCfia Application (Symfony 7.3 - Responsabilité MyCfia)"
        CONTROLLER["Controllers<br/>(SecurityController, DashboardController,<br/>CampaignController)"]
        SERVICE["Services<br/>(CfiAuthService, CampaignExportService,<br/>AiChatService)"]
        TOOLS["AI Tools<br/>(CfiStocksTool, CfiOperationsTool,<br/>CfiFacturesTool, CfiCampagnesTool)"]
        EM_MYCFIA["Entity Manager: mycfia<br/>(Bundle Gorillas)"]
        EM_COMMON["Entity Manager: cfi_common<br/>(Read-Only CFI)"]
    end

    subgraph "BDD MyCfia (PostgreSQL - Responsabilité MyCfia)"
        ENTITIES_MYCFIA["Entities MyCfia<br/>(User, Client, Project,<br/>Persona, Asset, Strategy,<br/>Competitor, Task, Agent,<br/>RagFile, Opportunity)"]
        DB_MYCFIA[(PostgreSQL<br/>13 tables)]
    end

    subgraph "BDD Commune CFI (SQL Server Read-Only - Responsabilité CFI)"
        ENTITIES_COMMON["Entities CfiCommon<br/>(StockReadonly,<br/>OperationReadonly,<br/>FactureReadonly,<br/>CampagneReadonly)"]
        DB_COMMON[(SQL Server<br/>Vues Matérialisées)]
    end

    subgraph "SFTP CFI (Responsabilité CFI)"
        SFTP_SERVICE["AvanciSftpService<br/>(phpseclib3)"]
        SFTP_SERVER[("SFTP Server<br/>sftp.cfitech.io")]
    end

    %% Flux Controllers → Services
    CONTROLLER --> SERVICE
    SERVICE --> TOOLS

    %% Flux Entity Managers
    EM_MYCFIA -->|Doctrine ORM| ENTITIES_MYCFIA
    ENTITIES_MYCFIA --> DB_MYCFIA

    EM_COMMON -->|Doctrine ORM<br/>Read-Only| ENTITIES_COMMON
    ENTITIES_COMMON --> DB_COMMON

    %% Flux AI Tools
    TOOLS -->|Query| EM_COMMON
    TOOLS -->|Query| EM_MYCFIA

    %% Flux Export SFTP
    SERVICE -->|Export| SFTP_SERVICE
    SFTP_SERVICE -->|Upload| SFTP_SERVER

    %% Styles
    classDef controllerStyle fill:#e3f2fd,stroke:#1976d2,stroke-width:2px
    classDef serviceStyle fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px
    classDef dbStyle fill:#fff3e0,stroke:#f57c00,stroke-width:3px
    classDef sftpStyle fill:#e8f5e9,stroke:#388e3c,stroke-width:2px

    class CONTROLLER,EM_MYCFIA,EM_COMMON controllerStyle
    class SERVICE,TOOLS,SFTP_SERVICE serviceStyle
    class DB_MYCFIA,DB_COMMON dbStyle
    class SFTP_SERVER sftpStyle
```

---

## 🔐 Architecture Sécurité & Multi-Tenancy

```mermaid
graph TB
    subgraph "Utilisateurs"
        USER1["User Niveau 1<br/>(Siège Social)<br/>Tenant ID: 1"]
        USER2["User Niveau 2<br/>(Département Paris)<br/>Tenant ID: 2"]
        USER3["User Niveau 3<br/>(Agence Paris Nord)<br/>Tenant ID: 3"]
    end

    subgraph "MyCfia Backend (Filtrage Tenant - Responsabilité MyCfia)"
        AUTH["CfiAuthService<br/>(Auth CFI API)"]
        SESSION["CfiSessionService<br/>(Session + Tenant ID)"]
        TENANT_FILTER["Tenant Filter<br/>(WHERE tenant_id = ?)"]
    end

    subgraph "BDD Commune CFI (Responsabilité CFI)"
        VIEW_STOCKS["stocks_readonly<br/>(tenant_id INDEX)"]
        VIEW_OPS["operations_readonly<br/>(tenant_id INDEX)"]
    end

    subgraph "Règles Cascade Descendante"
        RULE["Niveau N peut voir :<br/>- N (lui-même)<br/>- N+1 (enfants directs)<br/>- N+2 (petits-enfants)<br/>- ... (descendants)"]
    end

    %% Flux Authentification
    USER1 -->|Login| AUTH
    USER2 -->|Login| AUTH
    USER3 -->|Login| AUTH

    AUTH -->|Store Tenant ID| SESSION

    %% Flux Requête avec Filtrage
    SESSION -->|tenant_id = 1| TENANT_FILTER
    TENANT_FILTER -->|Query| VIEW_STOCKS
    VIEW_STOCKS -->|Results: Tenant 1,2,3| TENANT_FILTER

    SESSION -->|tenant_id = 2| TENANT_FILTER
    TENANT_FILTER -->|Query| VIEW_OPS
    VIEW_OPS -->|Results: Tenant 2,3| TENANT_FILTER

    SESSION -->|tenant_id = 3| TENANT_FILTER
    TENANT_FILTER -->|Query| VIEW_STOCKS
    VIEW_STOCKS -->|Results: Tenant 3 only| TENANT_FILTER

    %% Règle Cascade
    RULE -.->|Apply| TENANT_FILTER

    %% Styles
    classDef userStyle fill:#e3f2fd,stroke:#1976d2,stroke-width:2px
    classDef authStyle fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px
    classDef dbStyle fill:#fff3e0,stroke:#f57c00,stroke-width:2px
    classDef ruleStyle fill:#e8f5e9,stroke:#388e3c,stroke-width:2px,stroke-dasharray: 5 5

    class USER1,USER2,USER3 userStyle
    class AUTH,SESSION,TENANT_FILTER authStyle
    class VIEW_STOCKS,VIEW_OPS dbStyle
    class RULE ruleStyle
```

---

## 📊 Synchronisation BDD Commune CFI (Vues Matérialisées)

```mermaid
graph LR
    subgraph "BDD CFI Production (Responsabilité CFI)"
        STOCKS_TABLE[("Table: stocks<br/>INSERT/UPDATE/DELETE")]
        OPS_TABLE[("Table: operations<br/>INSERT/UPDATE/DELETE")]
    end

    subgraph "SQL Server Agent Jobs (Responsabilité CFI)"
        JOB_5MIN["Job: Refresh_5min<br/>(stocks, operations)"]
        JOB_10MIN["Job: Refresh_10min<br/>(campagnes)"]
        JOB_1H["Job: Refresh_1h<br/>(factures)"]
    end

    subgraph "BDD Commune CFI (Responsabilité CFI)"
        STOCKS_VIEW[("Vue Matérialisée:<br/>stocks_readonly<br/>WITH DATA")]
        OPS_VIEW[("Vue Matérialisée:<br/>operations_readonly<br/>WITH DATA")]
        CAMP_VIEW[("Vue Matérialisée:<br/>campagnes_readonly<br/>WITH DATA")]
        FACT_VIEW[("Vue Matérialisée:<br/>factures_readonly<br/>WITH DATA")]
    end

    subgraph "MyCfia Application (Responsabilité MyCfia)"
        MYCFIA["MyCfia Backend<br/>Entity Manager: cfi_common<br/>Read-Only Queries"]
    end

    %% Flux Synchronisation (CFI)
    STOCKS_TABLE -->|Trigger| JOB_5MIN
    OPS_TABLE -->|Trigger| JOB_5MIN

    JOB_5MIN -->|REFRESH MATERIALIZED VIEW| STOCKS_VIEW
    JOB_5MIN -->|REFRESH MATERIALIZED VIEW| OPS_VIEW

    JOB_10MIN -->|REFRESH MATERIALIZED VIEW| CAMP_VIEW

    JOB_1H -->|REFRESH MATERIALIZED VIEW| FACT_VIEW

    %% Flux Lecture MyCfia
    STOCKS_VIEW -->|SELECT * WHERE tenant_id = ?| MYCFIA
    OPS_VIEW -->|SELECT * WHERE tenant_id = ?| MYCFIA
    CAMP_VIEW -->|SELECT * WHERE tenant_id = ?| MYCFIA
    FACT_VIEW -->|SELECT * WHERE tenant_id = ?| MYCFIA

    %% Annotations
    Note1["Latence max: 5-10min<br/>Acceptable pour lecture"]
    Note2["Performance: ~10-50ms<br/>vs API: ~100-200ms"]

    JOB_5MIN -.-> Note1
    MYCFIA -.-> Note2

    %% Styles
    classDef tableStyle fill:#e3f2fd,stroke:#1976d2,stroke-width:2px
    classDef jobStyle fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px
    classDef viewStyle fill:#fff3e0,stroke:#f57c00,stroke-width:2px
    classDef appStyle fill:#e8f5e9,stroke:#388e3c,stroke-width:2px

    class STOCKS_TABLE,OPS_TABLE tableStyle
    class JOB_5MIN,JOB_10MIN,JOB_1H jobStyle
    class STOCKS_VIEW,OPS_VIEW,CAMP_VIEW,FACT_VIEW viewStyle
    class MYCFIA appStyle
```

---

## 🛡️ Résilience & Fallback

```mermaid
graph TB
    subgraph "Scénario Normal"
        USER1["Utilisateur MyCfia"]
        AI_TOOL1["AI Tool: queryStocks()<br/>(Responsabilité MyCfia)"]
        COMMON1[(BDD Commune CFI<br/>Disponible ✅<br/>(Responsabilité CFI))]
        RESULT1["Résultat: ~10-50ms<br/>Données fraîches (max 5min)"]
    end

    subgraph "Scénario Dégradé (Panne BDD Commune)"
        USER2["Utilisateur MyCfia"]
        AI_TOOL2["AI Tool: queryStocks()<br/>(Responsabilité MyCfia)"]
        COMMON2[(BDD Commune CFI<br/>Indisponible ❌<br/>(Responsabilité CFI))]
        FALLBACK["Fallback automatique:<br/>CfiApiService<br/>(Responsabilité MyCfia)"]
        API[(API CFI Swagger<br/>Disponible ✅<br/>(Responsabilité CFI))]
        RESULT2["Résultat: ~100-200ms<br/>Données temps réel"]
        LOG["Logger: Warning<br/>'BDD Commune CFI down,<br/>fallback API CFI'"]
    end

    subgraph "Scénario Critique (Panne Totale)"
        USER3["Utilisateur MyCfia"]
        AI_TOOL3["AI Tool: queryStocks()<br/>(Responsabilité MyCfia)"]
        COMMON3[(BDD Commune CFI<br/>Indisponible ❌<br/>(Responsabilité CFI))]
        API2[(API CFI Swagger<br/>Indisponible ❌<br/>(Responsabilité CFI))]
        ERROR["Erreur: 'Service CFI<br/>temporairement indisponible.<br/>Réessayez dans quelques minutes.'"]
        ALERT["Alertes:<br/>- Email admin<br/>- Slack/Teams notification<br/>- Incident Monitoring"]
    end

    %% Flux Normal
    USER1 --> AI_TOOL1
    AI_TOOL1 --> COMMON1
    COMMON1 --> RESULT1

    %% Flux Dégradé
    USER2 --> AI_TOOL2
    AI_TOOL2 --> COMMON2
    COMMON2 -->|Exception| FALLBACK
    FALLBACK --> API
    API --> RESULT2
    COMMON2 -.->|Log| LOG

    %% Flux Critique
    USER3 --> AI_TOOL3
    AI_TOOL3 --> COMMON3
    COMMON3 -->|Exception| API2
    API2 -->|Exception| ERROR
    API2 -.->|Trigger| ALERT

    %% Styles
    classDef normalStyle fill:#e8f5e9,stroke:#388e3c,stroke-width:2px
    classDef degradeStyle fill:#fff3e0,stroke:#f57c00,stroke-width:2px
    classDef critiqueStyle fill:#ffebee,stroke:#d32f2f,stroke-width:2px

    class USER1,AI_TOOL1,COMMON1,RESULT1 normalStyle
    class USER2,AI_TOOL2,COMMON2,FALLBACK,API,RESULT2,LOG degradeStyle
    class USER3,AI_TOOL3,COMMON3,API2,ERROR,ALERT critiqueStyle
```

---

## 📈 Matrice Décisionnelle (Comparaison Options)

```mermaid
graph TB
    subgraph "Option 1: API Pure (Score: 72/100)"
        O1_PERF["Performance: 70/100<br/>Latence réseau 50-200ms"]
        O1_SCALE["Scalabilité: 95/100<br/>Indépendance totale"]
        O1_MAINT["Maintenance: 75/100<br/>2 BDD distinctes"]
        O1_SECU["Sécurité: 95/100<br/>Isolation complète"]
    end

    subgraph "Option 2: BDD Commune (Score: 58/100) ❌"
        O2_PERF["Performance: 90/100<br/>Lecture locale rapide"]
        O2_SCALE["Scalabilité: 50/100<br/>Goulot d'étranglement"]
        O2_MAINT["Maintenance: 45/100<br/>Couplage fort"]
        O2_SECU["Sécurité: 55/100<br/>Isolation difficile"]
    end

    subgraph "Option 3: Hybride (Score: 88/100) ✅"
        O3_PERF["Performance: 85/100<br/>Lecture cache + Écriture async"]
        O3_SCALE["Scalabilité: 95/100<br/>Indépendance préservée"]
        O3_MAINT["Maintenance: 70/100<br/>Complexité modérée"]
        O3_SECU["Sécurité: 90/100<br/>Isolation + Contrôle granulaire"]
    end

    subgraph "Option 4: Event-Driven (Score: 76/100)"
        O4_PERF["Performance: 80/100<br/>Async découplage"]
        O4_SCALE["Scalabilité: 95/100<br/>Traitement parallèle"]
        O4_MAINT["Maintenance: 60/100<br/>Complexité broker"]
        O4_SECU["Sécurité: 85/100<br/>Isolation + Retry"]
    end

    subgraph "Option 5: SFTP Pure (Score: 69/100)"
        O5_PERF["Performance: 55/100<br/>Batch latence 10-30min"]
        O5_SCALE["Scalabilité: 70/100<br/>Fichiers volumineux"]
        O5_MAINT["Maintenance: 80/100<br/>Simplicité"]
        O5_SECU["Sécurité: 75/100<br/>SSH sécurisé"]
    end

    DECISION["Décision Finale:<br/>Option 3 - Architecture Hybride<br/>Score: 88/100 ✅"]

    O3_PERF --> DECISION
    O3_SCALE --> DECISION
    O3_MAINT --> DECISION
    O3_SECU --> DECISION

    %% Styles
    classDef bestStyle fill:#e8f5e9,stroke:#388e3c,stroke-width:3px
    classDef goodStyle fill:#fff3e0,stroke:#f57c00,stroke-width:2px
    classDef badStyle fill:#ffebee,stroke:#d32f2f,stroke-width:2px

    class O3_PERF,O3_SCALE,O3_MAINT,O3_SECU,DECISION bestStyle
    class O1_PERF,O1_SCALE,O1_MAINT,O1_SECU,O4_PERF,O4_SCALE,O4_MAINT,O4_SECU,O5_PERF,O5_SCALE,O5_MAINT,O5_SECU goodStyle
    class O2_PERF,O2_SCALE,O2_MAINT,O2_SECU badStyle
```

---

## 🔧 Diagramme Responsabilités CFI vs MyCfia

```mermaid
graph TB
    subgraph "Responsabilités CFI"
        CFI1["Créer BDD Commune CFI<br/>(SQL Server)"]
        CFI2["Configurer vues matérialisées<br/>(stocks, opérations, factures, campagnes)"]
        CFI3["Configurer SQL Server Agent jobs<br/>(refresh automatique 5-10min)"]
        CFI4["Créer compte mycfia_readonly<br/>(permissions lecture seule)"]
        CFI5["Configurer SFTP server<br/>(import campagnes)"]
        CFI6["Batch import SFTP<br/>(toutes les 5min)"]
    end

    subgraph "Responsabilités MyCfia"
        MYCFIA1["Configurer Entity Managers Doctrine<br/>(mycfia + cfi_common)"]
        MYCFIA2["Créer Entities CfiCommon<br/>(StockReadonly, OperationReadonly, etc.)"]
        MYCFIA3["Créer AI Tools<br/>(CfiStocksTool, CfiOperationsTool, etc.)"]
        MYCFIA4["Créer CampaignExportService<br/>(export JSON/CSV vers SFTP)"]
        MYCFIA5["Implémenter fallback automatique<br/>(API CFI si BDD Commune down)"]
        MYCFIA6["Tests unitaires + intégration + E2E"]
    end

    CFI1 --> CFI2
    CFI2 --> CFI3
    CFI3 --> CFI4
    CFI4 --> CFI5
    CFI5 --> CFI6

    MYCFIA1 --> MYCFIA2
    MYCFIA2 --> MYCFIA3
    MYCFIA3 --> MYCFIA4
    MYCFIA4 --> MYCFIA5
    MYCFIA5 --> MYCFIA6

    CFI4 -.->|Fourni credentials| MYCFIA1
    CFI5 -.->|Fourni accès SFTP| MYCFIA4

    %% Styles
    classDef cfiStyle fill:#e3f2fd,stroke:#1976d2,stroke-width:2px
    classDef mycfiaStyle fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px

    class CFI1,CFI2,CFI3,CFI4,CFI5,CFI6 cfiStyle
    class MYCFIA1,MYCFIA2,MYCFIA3,MYCFIA4,MYCFIA5,MYCFIA6 mycfiaStyle
```

---

**Document créé** : 2025-10-22
**Type** : Schémas Architecture Hybride (Option 3)
**Format** : Mermaid (compatible GitHub, GitLab, VS Code Preview)
**Version** : 2.0
**Statut** : ✅ Révisé - Prêt pour validation
