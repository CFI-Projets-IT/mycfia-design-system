# Rapport d'Analyse : Mercure, Stimulus et Turbo dans myCfia

**Date** : 2025-12-16
**Projet** : myCfia - Plateforme d'automatisation marketing multi-canal avec IA conversationnelle
**Chemin** : `/home/krystdev/Bureau/KrystdevCom/Clients/Gorillias/myCfia/`

---

## 📋 Table des Matières

1. [Architecture Mercure](#1-architecture-mercure)
2. [Contrôleurs Stimulus](#2-contrôleurs-stimulus)
3. [Utilisation dans les Templates](#3-utilisation-dans-les-templates)
4. [Intégration Turbo](#4-intégration-turbo)
5. [JavaScript Moderne](#5-javascript-moderne)
6. [Recommandations](#6-recommandations)
7. [Points d'Attention Design System](#7-points-dattention-design-system)

---

## 1. Architecture Mercure

### 1.1. Configuration

**Fichier** : `app/config/packages/mercure.yaml`

```yaml
mercure:
    hubs:
        default:
            url: '%env(MERCURE_URL)%'              # Communication inter-conteneurs
            public_url: '%env(MERCURE_PUBLIC_URL)%' # Connexion client JavaScript
            jwt:
                secret: '%env(MERCURE_JWT_SECRET)%'
                publish: ['*']
                subscribe: ['*']
```

**Variables d'environnement** (`.env.local`) :

```bash
# Communication serveur → Mercure (Docker inter-conteneurs)
MERCURE_URL=http://mercure/.well-known/mercure

# Communication client → Mercure (via proxy FrankenPHP)
MERCURE_PUBLIC_URL=http://localhost:8080/.well-known/mercure

# JWT Secret partagé (256 bits minimum)
MERCURE_JWT_SECRET=hDMV1fWJTNIGn2XblSW4h7RvB1FuwGuSoMTyXLUMTjA=
```

**Remarque critique** : Le Mercure Hub est accessible via un **proxy FrankenPHP** sur le même port que l'application (8080) pour éviter les problèmes CORS. Pas de port dédié Mercure exposé à l'extérieur.

---

### 1.2. Topics Mercure Utilisés

L'application utilise deux systèmes de topics :

#### **Système 1 : Marketing AI Bundle (v2.6.0+)**

**Topic** : `/tasks/{taskId}`

Utilisé pour toutes les **tâches asynchrones IA** (génération personas, stratégie, assets, enrichissement, détection concurrents).

**Événements SSE** :
- `TaskStartedEvent` : Tâche démarrée
- `TaskProgressEvent` : Progression temps réel (pourcentage, phase, message, métadonnées)
- `TaskCompletedEvent` : Tâche terminée avec succès
- `TaskFailedEvent` : Échec avec possibilité de retry automatique

**Format JSON** :
```json
{
  "type": "TaskProgressEvent",
  "taskId": "abc123def",
  "percentage": 45,
  "phase": "generation",
  "message": "Génération asset 2/5...",
  "metadata": {
    "current_phase": 2,
    "total_phases": 5,
    "assetType": "linkedin_post"
  },
  "timestamp": "2025-12-16T14:30:00+00:00"
}
```

#### **Système 2 : MarketingGenerationPublisher (Custom)**

**Topic** : `marketing/project/{projectId}`

Utilisé pour la **génération de stratégie** (système legacy maintenu pour compatibilité).

**Événements** :
- `start` : Démarrage génération
- `progress` : Progression (compatible avec système 1)
- `complete` : Complétion
- `error` : Erreur

---

### 1.3. Diagramme Architecture Mercure

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT (Navigateur)                      │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Stimulus Controller (generation_controller.js)          │   │
│  │                                                            │   │
│  │  1. Connexion EventSource                                 │   │
│  │     → http://localhost:8080/.well-known/mercure          │   │
│  │     → topic=/tasks/{taskId}                              │   │
│  │                                                            │   │
│  │  2. Écoute événements :                                   │   │
│  │     • TaskStartedEvent                                    │   │
│  │     • TaskProgressEvent (throttling 500ms)               │   │
│  │     • TaskCompletedEvent                                  │   │
│  │     • TaskFailedEvent (retry automatique)                │   │
│  │                                                            │   │
│  │  3. Mise à jour UI temps réel :                          │   │
│  │     • Barres de progression                               │   │
│  │     • Messages descriptifs                                │   │
│  │     • Phases détaillées                                   │   │
│  └──────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ SSE (Server-Sent Events)
                             │ HTTP/1.1 Keep-Alive
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    FRANKENPHP (Proxy Reverse)                    │
│                                                                   │
│  /.well-known/mercure  →  http://mercure/.well-known/mercure    │
│                                                                   │
│  Avantages :                                                      │
│  • Same-Origin (pas de CORS)                                     │
│  • Port unique (8080)                                            │
│  • HTTPS automatique (production)                                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      MERCURE HUB (Docker)                        │
│                                                                   │
│  Conteneur : myCfia_mercure                                      │
│  Port interne : 3000                                             │
│  JWT Secret : hDMV1fWJTNIGn2XblSW4h7RvB1FuwGuSoMTyXLUMTjA=      │
│                                                                   │
│  Topics actifs :                                                 │
│  • /tasks/{taskId}            (Marketing AI Bundle)             │
│  • marketing/project/{id}     (Legacy, stratégie uniquement)    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ Publication
                             ▲
┌─────────────────────────────────────────────────────────────────┐
│                    BACKEND SYMFONY (FrankenPHP)                  │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  MercurePublisherSubscriber                              │   │
│  │                                                            │   │
│  │  Écoute événements Symfony :                             │   │
│  │  • TaskStartedEvent  → Publie sur Mercure                │   │
│  │  • TaskProgressEvent → Publie sur Mercure (v3.34.0)     │   │
│  │  • TaskCompletedEvent → Publie sur Mercure               │   │
│  │  • TaskFailedEvent   → Publie sur Mercure                │   │
│  │                                                            │   │
│  │  HubInterface (Symfony Mercure Bundle)                    │   │
│  │  → new Update(topics, data, type)                        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  MarketingGenerationPublisher (Service custom)           │   │
│  │                                                            │   │
│  │  • publishStart(projectId, stage, message)               │   │
│  │  • publishProgress(projectId, stage, message, data)      │   │
│  │  • publishComplete(projectId, stage, message, metadata)  │   │
│  │  • publishError(projectId, stage, error, technical)      │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

### 1.4. Services Backend

#### **MercurePublisherSubscriber** (`app/src/EventSubscriber/Marketing/MercurePublisherSubscriber.php`)

**Responsabilité** : Écouter les événements du **Marketing AI Bundle** et les publier automatiquement sur Mercure.

**Événements écoutés** :

```php
public static function getSubscribedEvents(): array
{
    return [
        TaskStartedEvent::class => ['onTaskStarted', 10],
        TaskProgressEvent::class => ['onTaskProgress', 10], // v3.34.0
        TaskCompletedEvent::class => ['onTaskCompleted', 10],
        TaskFailedEvent::class => ['onTaskFailed', 10],
    ];
}
```

**Exemple publication** :

```php
public function onTaskProgress(TaskProgressEvent $event): void
{
    $taskId = $event->taskId;

    $update = new Update(
        topics: "/tasks/{$taskId}",
        data: json_encode([
            'type' => 'TaskProgressEvent',
            'taskId' => $taskId,
            'percentage' => $event->percentage,
            'phase' => $event->phase,
            'message' => $event->message,
            'metadata' => $event->metadata,
            'timestamp' => $event->progressedAt->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR),
        private: false,
        type: 'TaskProgressEvent', // Définit le champ SSE "event:"
    );

    $this->hub->publish($update);
}
```

**Note** : Priorité `10` pour publier **avant** les autres subscribers qui pourraient lancer des exceptions.

#### **MarketingGenerationPublisher** (`app/src/Service/MarketingGenerationPublisher.php`)

**Responsabilité** : Service custom pour publier des événements de génération marketing (système legacy).

**Méthodes publiques** :

```php
public function publishStart(int $projectId, string $stage, string $message): void
public function publishProgress(int $projectId, string $stage, string $message, array $data = []): void
public function publishComplete(int $projectId, string $stage, string $message, array $metadata = []): void
public function publishError(int $projectId, string $stage, string $errorMessage, string $technicalError = ''): void
```

**Format des messages** :

```php
[
    'type' => 'progress', // start|progress|complete|error
    'projectId' => 1,
    'stage' => 'strategy', // personas|strategy|assets
    'message' => 'Génération en cours...',
    'data' => ['progress' => 45, 'current_phase' => 2],
    'timestamp' => '2025-12-16 14:30:00'
]
```

**Topic utilisé** : `marketing/project/{projectId}`

---

### 1.5. Gestion des Erreurs et Retry

Le système Mercure implémente un **mécanisme de retry automatique** côté backend (Marketing AI Bundle) et côté frontend (Stimulus).

**Côté Backend** :
- Le bundle dispatche `TaskFailedEvent` avec `is_recoverable` pour signaler si l'erreur est temporaire
- Le handler tente automatiquement 3 fois avec backoff exponentiel

**Côté Frontend** (`persona_controller.js` ligne 116-139) :

```javascript
this.eventSource.addEventListener('TaskFailedEvent', (event) => {
    const data = JSON.parse(event.data);

    this.retryCount++;
    this.hasSeenFailure = true;

    // Si c'est une erreur récupérable et qu'on n'a pas dépassé les retries
    const isRecoverable = data.is_recoverable !== false;
    const canRetry = this.retryCount <= this.maxRetries;

    if (isRecoverable && canRetry) {
        console.log(`Tentative ${this.retryCount}/${this.maxRetries} échouée, en attente de retry automatique...`);
        this.handleRetry(data, this.retryCount);
        // Ne PAS fermer l'EventSource, continuer d'écouter
    } else {
        // Erreur définitive après tous les retries
        this.handleError(data);
    }
});
```

**Interface utilisateur** :
- Affichage du retry en cours : `"Tentative 1/3 échouée, nouvelle tentative en cours..."`
- Badge warning avec icône `bi-exclamation-triangle`
- Si succès final : `"Réussi après 2 tentative(s) échouée(s)"`

---

## 2. Contrôleurs Stimulus

### 2.1. Liste Complète des Controllers

L'application compte **10 contrôleurs Stimulus** organisés par domaine métier :

| Contrôleur | Chemin | Responsabilité |
|------------|--------|----------------|
| **chat_controller** | `controllers/chat/` | Interface chat IA, envoi messages, auto-resize textarea |
| **datatable_controller** | `controllers/data/` | Filtrage tables en temps réel, recherche, tri |
| **csrf_protection_controller** | `controllers/security/` | Protection CSRF pour Turbo, génération tokens dynamiques |
| **range_display_controller** | `controllers/ui/` | Affichage dynamique valeur input range |
| **sidebar_controller** | `controllers/ui/` | Toggle sidebar desktop/mobile, localStorage |
| **theme_controller** | `controllers/ui/` | Changement thème (light/dark-blue/dark-red), animations |
| **enrichment_controller** | `controllers/marketing/` | Génération enrichissement projet, SSE Mercure |
| **competitor_controller** | `controllers/marketing/` | Détection concurrents, SSE Mercure |
| **persona_controller** | `controllers/marketing/` | Génération personas (mode multiple), SSE Mercure, retry |
| **generation_controller** | `controllers/marketing/` | Génération stratégie/assets, SSE Mercure, polling BDD |

**Total** : 10 controllers (4 UI, 1 data, 1 sécurité, 4 marketing).

---

### 2.2. Analyse Détaillée par Controller

#### **1. chat_controller.js** (`app/assets/controllers/chat/chat_controller.js`)

**Responsabilité** : Gestion interface chat IA conversationnelle.

**Targets** :
- `input` : Textarea pour saisie message
- `messages` : Container des messages
- `sendButton` : Bouton d'envoi

**Actions** :
- `sendMessage(event)` : Soumission formulaire
- `handleKeydown(event)` : Entrée pour envoyer, Shift+Entrée pour nouvelle ligne
- `autoResize()` : Auto-resize textarea (max 150px)
- `scrollToBottom()` : Scroll automatique vers derniers messages
- `copyMessage(event)` : Copie message dans presse-papier

**État actuel** : Interface fonctionnelle avec **réponses IA placeholder**. L'intégration réelle avec Symfony AI Bundle est prévue dans les sprints S0-S11.

**Code clé** :

```javascript
sendMessage(event) {
    event.preventDefault();
    const message = this.inputTarget.value.trim();
    if (!message) return;

    // Ajouter message utilisateur
    this.addMessage(message, 'user');

    // Réinitialiser champ
    this.inputTarget.value = '';
    this.autoResize();

    // Simuler réponse IA (à remplacer par LiveComponent)
    this.simulateAIResponse();
}
```

**Remarque** : Aucune utilisation de Mercure actuellement. Le chat temps réel sera implémenté avec **Symfony UX Live Component** + Mercure dans les sprints futurs.

---

#### **2. datatable_controller.js** (`app/assets/controllers/data/datatable_controller.js`)

**Responsabilité** : Filtrage en temps réel des tables de données.

**Targets** :
- `table` : Élément `<table>` à filtrer
- `search` : Input de recherche

**Actions** :
- `filter(event)` : Filtrer lignes selon recherche (toLowerCase, includes)
- `reset()` : Réinitialiser filtre

**Fonctionnalités** :
- Recherche insensible à la casse
- Affichage message "Aucun résultat" si vide
- Ignorance des lignes avec `colspan` (messages)

**Exemple utilisation** :

```html
<div data-controller="datatable">
    <input type="search" data-datatable-target="search" data-action="input->datatable#filter">
    <table data-datatable-target="table">
        <!-- ... -->
    </table>
</div>
```

---

#### **3. sidebar_controller.js** (`app/assets/controllers/ui/sidebar_controller.js`)

**Responsabilité** : Gestion menu latéral responsive.

**Targets** :
- `sidebar` : Élément sidebar

**Actions** :
- `toggle()` : Toggle classe `collapsed`, sauvegarde localStorage
- `closeMobile(event)` : Fermer sidebar sur mobile après clic lien

**Fonctionnalités** :
- Persistance état sidebar dans `localStorage.getItem('sidebarCollapsed')`
- Responsive : fermeture auto sur mobile (`< 768px`)

**Code clé** :

```javascript
toggle() {
    const sidebar = document.querySelector('.app-sidebar');
    if (sidebar) {
        sidebar.classList.toggle('collapsed');

        // Sauvegarder dans localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }
}
```

---

#### **4. theme_controller.js** (`app/assets/controllers/ui/theme_controller.js`)

**Responsabilité** : Changement de thème avec animations.

**Values** :
- `current` : Thème actuel (light|dark-blue|dark-red)

**Fonctionnalités** :
- Écoute événements Live Component (`live:update-finished`)
- Applique classe `theme-{name}` sur `<body>`
- Animation transition CSS (`theme-transition` 300ms)

**Code clé** :

```javascript
applyTheme(theme) {
    // Retirer toutes les classes de thème
    document.body.classList.remove('theme-light', 'theme-dark-blue', 'theme-dark-red');

    // Ajouter nouvelle classe
    document.body.classList.add(`theme-${theme}`);

    // Animation transition
    document.body.classList.add('theme-transition');
    setTimeout(() => {
        document.body.classList.remove('theme-transition');
    }, 300);
}
```

**Intégration** :

```twig
<div data-controller="theme" data-theme-current-value="{{ app.user.theme }}">
    {{ component('ThemeSelector') }}
</div>
```

---

#### **5. range_display_controller.js** (`app/assets/controllers/ui/range_display_controller.js`)

**Responsabilité** : Affichage dynamique de la valeur d'un input range.

**Targets** :
- `value` : Élément affichant la valeur

**Fonctionnalités** :
- Auto-détection input range parent (`.closest('.mb-4')`)
- Support suffixe personnalisé (`data-suffix`)

**Exemple utilisation** :

```html
<div class="mb-4">
    <input type="range" min="1" max="5" value="3" data-suffix="persona(s)">
    <span data-controller="range-display" data-range-display-target="value">3 persona(s)</span>
</div>
```

---

#### **6. csrf_protection_controller.js** (`app/assets/controllers/security/csrf_protection_controller.js`)

**Responsabilité** : Protection CSRF pour formulaires Turbo.

**Fonctionnalités** :
- Génération token CSRF dynamique à la soumission
- Double-submit cookie (Symfony SameOriginCsrfTokenManager)
- Header CSRF pour requêtes Turbo (`turbo:submit-start`)
- Nettoyage cookie après soumission (`turbo:submit-end`)

**Code clé** :

```javascript
// Génération token avant soumission
document.addEventListener('submit', (event) => {
    generateCsrfToken(event.target);
}, true);

// Ajout header CSRF pour Turbo
document.addEventListener('turbo:submit-start', (event) => {
    const headers = generateCsrfHeaders(event.detail.formSubmission.formElement);
    Object.keys(headers).map((k) => {
        event.detail.formSubmission.fetchRequest.headers[k] = headers[k];
    });
});
```

**Format cookie** :

```javascript
// HTTP
{csrf_name}_{csrf_token}={csrf_name}; path=/; samesite=strict

// HTTPS
__Host-{csrf_name}_{csrf_token}={csrf_name}; path=/; samesite=strict; secure
```

**Note** : Utilisation automatique, pas de configuration nécessaire.

---

#### **7. enrichment_controller.js** (`app/assets/controllers/marketing/enrichment_controller.js`)

**Responsabilité** : Génération enrichissement projet avec Mercure SSE.

**Values** :
- `projectId` : ID du projet
- `taskId` : ID de la tâche asynchrone
- `mercureUrl` : URL publique Mercure
- `mercureJwt` : JWT Mercure (optionnel)
- `nextUrl` : URL de redirection après succès

**Targets** :
- `spinner`, `statusMessage`, `successMessage`, `errorMessage`
- `progressBar`, `progressPercentage`, `progressMessage`
- `phaseIndicator`, `elapsedTime`

**Fonctionnalités** :
- Connexion EventSource Mercure (`/tasks/{taskId}`)
- Écoute événements `TaskStartedEvent`, `TaskProgressEvent`, `TaskCompletedEvent`, `TaskFailedEvent`
- Mise à jour barre progression temps réel (v3.34.0)
- Timer temps écoulé (refresh 1s)
- Redirection automatique après succès (2s)

**Exemple template** :

```twig
<div
    data-controller="marketing-enrichment"
    data-marketing-enrichment-task-id-value="{{ taskId }}"
    data-marketing-enrichment-project-id-value="{{ project.id }}"
    data-marketing-enrichment-mercure-url-value="{{ mercureUrl }}"
    data-marketing-enrichment-next-url-value="{{ path('marketing_project_show', {id: project.id}) }}"
>
    <div class="progress">
        <div class="progress-bar" data-marketing-enrichment-target="progressBar"></div>
    </div>
    <span data-marketing-enrichment-target="progressPercentage">0%</span>
    <span data-marketing-enrichment-target="progressMessage">Initialisation...</span>
</div>
```

---

#### **8. competitor_controller.js** (`app/assets/controllers/marketing/competitor_controller.js`)

**Responsabilité** : Détection asynchrone de concurrents avec Mercure SSE.

**Values / Targets** : Identiques à `enrichment_controller`

**Différences** :
- Message succès personnalisé : `"{count} concurrent(s) détecté(s)"`
- Même mécanisme EventSource + progression temps réel

**Code succès** :

```javascript
showSuccess(data) {
    const competitorsCount = data.result?.competitors?.length || 0;
    this.resultSummaryTarget.textContent = `${competitorsCount} concurrent(s) détecté(s) avec succès !`;

    // Redirection après 2s
    setTimeout(() => {
        window.location.href = this.nextUrlValue;
    }, 2000);
}
```

---

#### **9. persona_controller.js** (`app/assets/controllers/marketing/persona_controller.js`)

**Responsabilité** : Génération personas avec **mode multiple** (plusieurs personas en parallèle).

**Values** :
- `projectId`, `taskId`, `stage`, `mercureUrl`, `mercureJwt`, `nextUrl`
- `multiple` : Boolean, active mode multi-assets

**Targets** : Identiques + `completedCount`, `assetsList`, `assetsContainer`

**Fonctionnalités spécifiques** :
- **Mode multiple** : Affichage liste assets avec statut individuel
- **Retry automatique** : Max 3 tentatives, gestion `is_recoverable`
- **Polling retry** : Continue d'écouter EventSource après échec
- **Badge succès/retry** : `"Réussi après 2 tentative(s)"`

**Code retry** :

```javascript
this.eventSource.addEventListener('TaskFailedEvent', (event) => {
    const data = JSON.parse(event.data);

    this.retryCount++;
    this.hasSeenFailure = true;

    const isRecoverable = data.is_recoverable !== false;
    const canRetry = this.retryCount <= this.maxRetries;

    if (isRecoverable && canRetry) {
        console.log(`Tentative ${this.retryCount}/${this.maxRetries} échouée, en attente de retry...`);
        this.handleRetry(data, this.retryCount);
        // Ne PAS fermer EventSource !
    } else {
        this.handleError(data);
    }
});
```

**Affichage multi-assets** :

```javascript
updateAssetStatus(assetType, status, message = '') {
    // Chercher ou créer l'élément asset
    let assetElement = this.assetsContainerTarget.querySelector(`[data-asset-type="${assetType}"]`);

    // Mettre à jour icône selon statut
    switch (status) {
        case 'in_progress':
            icon.className = 'bi bi-arrow-repeat spinner-border spinner-border-sm text-primary';
            break;
        case 'completed':
            icon.className = 'bi bi-check-circle-fill text-success';
            break;
        case 'error':
            icon.className = 'bi bi-x-circle-fill text-danger';
            break;
    }
}
```

---

#### **10. generation_controller.js** (`app/assets/controllers/marketing/generation_controller.js`)

**Responsabilité** : Génération stratégie et assets marketing avec **polling BDD**.

**Values** : Identiques à `persona_controller` + `generationType` (strategy|asset|personas)

**Fonctionnalités spécifiques** :
- **Système dual** : Écoute topic `/tasks/{taskId}` (bundle) ET `marketing/project/{projectId}` (legacy)
- **Polling BDD pour stratégie** : Vérification que stratégie est persistée en BDD avant redirection
- **Fix race condition** : StrategyAnalystAgent peut prendre 20-30s, polling évite redirection prématurée

**Code polling** :

```javascript
pollStrategyCompletion() {
    const maxAttempts = 40; // 40 secondes max
    let attempts = 0;

    const pollInterval = setInterval(async () => {
        attempts++;

        // Vérifier statut projet
        const response = await fetch(`/marketing/projects/${projectId}/status`);
        const data = await response.json();

        // Si stratégie existe en BDD → rediriger
        if (data.has_strategy === true) {
            clearInterval(pollInterval);
            this.showSuccess({});
        }

        // Max attempts atteint → rediriger quand même
        if (attempts >= maxAttempts) {
            clearInterval(pollInterval);
            this.showSuccess({});
        }
    }, 1000); // Poll toutes les secondes
}
```

**Gestion événements legacy** :

```javascript
connectToMercure() {
    // Topic bundle (prioritaire)
    const topic = `/tasks/${this.taskIdValue}`;

    this.eventSource.addEventListener('TaskCompletedEvent', (event) => {
        const data = JSON.parse(event.data);

        if (data.stage === 'strategy') {
            // Polling BDD avant redirection
            this.pollStrategyCompletion();
        } else {
            // Redirection immédiate pour assets/personas
            setTimeout(() => this.showSuccess(data), 2000);
        }
    });
}
```

---

### 2.3. Patterns Communs

Tous les controllers marketing partagent des patterns cohérents :

**1. Connexion Mercure** :

```javascript
connectToMercure() {
    const topic = `/tasks/${this.taskIdValue}`;
    const mercureUrl = new URL(this.mercureUrlValue);
    mercureUrl.searchParams.append('topic', topic);

    if (this.mercureJwtValue) {
        mercureUrl.searchParams.append('authorization', this.mercureJwtValue);
    }

    this.eventSource = new EventSource(mercureUrl.toString());

    // Écoute événements SSE nommés
    this.eventSource.addEventListener('TaskStartedEvent', (event) => { /* ... */ });
    this.eventSource.addEventListener('TaskProgressEvent', (event) => { /* ... */ });
    // ...
}
```

**2. Gestion progression** :

```javascript
handleProgress(data) {
    const { percentage, message, metadata } = data;

    // Barre de progression
    if (this.hasProgressBarTarget) {
        this.progressBarTarget.style.width = `${percentage}%`;
        this.progressBarTarget.setAttribute('aria-valuenow', percentage);
    }

    // Pourcentage
    if (this.hasProgressPercentageTarget) {
        this.progressPercentageTarget.textContent = `${percentage}%`;
    }

    // Message descriptif
    if (this.hasProgressMessageTarget) {
        this.progressMessageTarget.textContent = message;
    }

    // Indicateur de phase
    if (this.hasPhaseIndicatorTarget && metadata.current_phase) {
        this.phaseIndicatorTarget.textContent = `Phase ${metadata.current_phase}/${metadata.total_phases}`;
    }
}
```

**3. Nettoyage disconnect** :

```javascript
disconnect() {
    if (this.eventSource) {
        this.eventSource.close(); // Fermer connexion SSE
    }
    if (this.elapsedTimer) {
        clearInterval(this.elapsedTimer); // Nettoyer timer
    }
}
```

**4. Timer temps écoulé** :

```javascript
startElapsedTimer() {
    this.elapsedTimer = setInterval(() => {
        const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
        if (this.hasElapsedTimeTarget) {
            this.elapsedTimeTarget.textContent = `${elapsed}s`;
        }
    }, 1000);
}
```

---

## 3. Utilisation dans les Templates

### 3.1. Pages Utilisant Stimulus

**8 templates** utilisent Stimulus de manière intensive :

| Template | Controllers | Usage |
|----------|-------------|-------|
| `marketing/strategy/generating.html.twig` | `marketing-generation` | Génération stratégie avec polling BDD |
| `marketing/persona/generating.html.twig` | `marketing-persona` | Génération personas avec retry automatique |
| `marketing/asset/generating.html.twig` | `marketing-generation` | Génération assets avec mode multiple |
| `marketing/competitor/generating.html.twig` | `marketing-competitor` | Détection concurrents |
| `marketing/enrichment/generating.html.twig` | `marketing-enrichment` | Enrichissement projet |
| `marketing/persona/generate.html.twig` | `range-display` | Formulaire sélection nombre personas |
| `components/sidebar.html.twig` | `sidebar` | Menu latéral responsive |
| `components/topbar.html.twig` | `sidebar` | Bouton toggle sidebar |

---

### 3.2. Exemple Complet : Génération Stratégie

**Fichier** : `app/templates/marketing/strategy/generating.html.twig`

```twig
{% extends 'layouts/home.html.twig' %}

{% block content %}
<div class="container" style="max-width: 800px;">
    {# En-tête avec animation #}
    <div class="card shadow-lg border-0 mb-4">
        <div class="card-body text-center py-5">
            <h2 class="mb-3">Génération de stratégie marketing en cours</h2>

            {# Barre de progression temps réel (v3.34.0) #}
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold text-primary" data-progress-message>
                        Initialisation...
                    </div>
                    <div class="badge bg-primary" data-phase-indicator>
                        Phase 0/4
                    </div>
                </div>

                <div class="progress mb-2" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         style="width: 0%"
                         data-progress-bar>
                        <span class="fw-semibold" data-progress-percentage>0%</span>
                    </div>
                </div>
            </div>

            {# Message de statut dynamique #}
            <div id="status-message" class="alert alert-info">
                <i class="bi bi-hourglass-split"></i>
                Analyse du secteur et des objectifs...
            </div>
        </div>
    </div>
</div>

{# Stimulus controller pour gérer Mercure EventSource #}
<div
    data-controller="marketing-generation"
    data-marketing-generation-task-id-value="{{ taskId }}"
    data-marketing-generation-project-id-value="{{ project.id }}"
    data-marketing-generation-mercure-url-value="{{ mercureUrl }}"
    data-marketing-generation-mercure-jwt-value="{{ mercureJwt }}"
    data-marketing-generation-next-url-value="{{ path('marketing_strategy_show', {id: project.id}) }}"
    data-marketing-generation-generation-type-value="strategy"
>
</div>
{% endblock %}
```

**Éléments clés** :
- `data-progress-bar` : Barre mise à jour par `handleProgress()`
- `data-progress-percentage` : Texte pourcentage
- `data-progress-message` : Message descriptif temps réel
- `data-phase-indicator` : Badge phase courante
- `#status-message` : Statut général (success/error)

**Flux utilisateur** :

1. Utilisateur arrive sur `/marketing/strategy/generate/{id}`
2. Backend crée tâche asynchrone, dispatch `TaskStartedEvent`, redirige vers `generating`
3. JavaScript se connecte à Mercure (`/tasks/{taskId}`)
4. Réception `TaskProgressEvent` toutes les 500ms → mise à jour UI
5. Réception `TaskCompletedEvent` → polling BDD pour confirmer stratégie
6. Redirection vers `/marketing/strategy/show/{id}` après confirmation

---

### 3.3. Intégration Bootstrap 5

Tous les templates utilisent **Bootstrap 5.3.8** pour l'UI :

**Classes principales** :
- `progress`, `progress-bar`, `progress-bar-striped`, `progress-bar-animated`
- `alert`, `alert-info`, `alert-success`, `alert-danger`
- `badge`, `bg-primary`, `bg-success`, `bg-warning`
- `spinner-border`, `spinner-border-sm`
- `card`, `card-body`, `shadow-lg`
- `d-flex`, `justify-content-between`, `align-items-center`
- `bi bi-*` (Bootstrap Icons)

**Icônes utilisées** :
- `bi-hourglass-split` : En cours
- `bi-check-circle-fill` : Succès
- `bi-exclamation-triangle` : Erreur/Warning
- `bi-robot` : Agent IA
- `bi-graph-up-arrow` : Stratégie
- `bi-lightning-charge` : Temps réel

---

## 4. Intégration Turbo

### 4.1. Turbo Drive

**Activation globale** : Turbo Drive est actif par défaut sur toute l'application via `import '@hotwired/turbo'` dans `bootstrap.js`.

**Comportement** :
- Navigation SPA-like automatique
- Préservation scroll position
- Cache intelligent des pages visitées

**Désactivation locale** : Aucune page ne désactive Turbo actuellement.

---

### 4.2. Turbo Frames

**Utilisation** : Chargement partiel de la sidebar chat.

**Fichier** : `app/templates/layouts/home.html.twig` (ligne 35-42)

```twig
{# Section Favoris - Composant Twig dynamique avec Turbo Frame #}
<turbo-frame id="sidebar-favorites" src="{{ path('chat_sidebar_frame', {section: 'favorites'}) }}">
    {{ component('ConversationSidebar', {section: 'favorites'}) }}
</turbo-frame>

{# Section Historique - Composant Twig dynamique avec Turbo Frame #}
<turbo-frame id="sidebar-history" src="{{ path('chat_sidebar_frame', {section: 'history'}) }}">
    {{ component('ConversationSidebar', {section: 'history'}) }}
</turbo-frame>
```

**Template cible** : `app/templates/chat/sidebar_frame.html.twig`

```twig
<turbo-frame id="sidebar-{{ section }}">
    {{ component('ConversationSidebar', {section: section}) }}
</turbo-frame>
```

**Comportement** :
- Chargement initial : Affichage placeholder `{{ component() }}`
- Lazy loading : Chargement via `src` dès affichage frame
- Navigation scopée : Clics liens internes restent dans le frame

**Utilité** :
- Éviter duplication code sidebar desktop/mobile
- Actualisation partielle liste conversations
- Meilleure séparation des responsabilités

---

### 4.3. Turbo Streams

**Utilisation actuelle** : Aucune utilisation de Turbo Streams détectée.

**Raison** : Mercure SSE est utilisé à la place pour les mises à jour temps réel (plus adapté pour notifications push serveur).

**Différence avec Mercure** :
- **Turbo Streams** : Mises à jour DOM via réponses HTTP Turbo Stream (POST/PATCH formulaires)
- **Mercure SSE** : Notifications push serveur via EventSource (génération asynchrone en arrière-plan)

**Potentiel futur** : Utilisation possible de Turbo Streams pour :
- Mise à jour liste conversations sidebar après nouveau message chat
- Actualisation liste projets marketing après création
- Notifications toast en temps réel

---

### 4.4. Protection CSRF Turbo

**Mécanisme** : Controller `csrf_protection_controller.js` gère automatiquement les tokens CSRF pour formulaires Turbo.

**Événements écoutés** :
- `turbo:submit-start` : Ajout header CSRF avant envoi
- `turbo:submit-end` : Nettoyage cookie CSRF après réponse

**Configuration Symfony** (`config/packages/framework.yaml`) :

```yaml
framework:
    csrf_protection:
        enabled: true
        check_header: true # Vérification header X-CSRF-Token
```

---

## 5. JavaScript Moderne

### 5.1. Point d'Entrée : app.js

**Fichier** : `app/assets/app.js`

```javascript
import './bootstrap.js'; // Stimulus + Turbo

// Import CSS (AssetMapper)
import './styles/fonts.css';
import './styles/variables.css';
import './styles/themes/light.css';
import './styles/themes/dark-blue.css';
import './styles/themes/dark-red.css';
import './styles/components/glass-effects.css';
// ...

// Bootstrap
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// JS components
import './js/ui/division-selector.js';
import './js/marketing/enrichment-review.js';
import './js/marketing/persona-selection.js';
import './js/marketing/persona-configure.js';

// Initialisation tooltips/popovers
document.addEventListener('DOMContentLoaded', () => {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltips].map(el => new bootstrap.Tooltip(el));

    const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popovers].map(el => new bootstrap.Popover(el));
});
```

**Architecture** :
- **CSS-first** : Tous les styles importés via AssetMapper (pas de bundler webpack)
- **Bootstrap globale** : `window.bootstrap` pour usage dans HTML
- **ES Modules** : Import/export modernes
- **DOMContentLoaded** : Initialisation automatique composants Bootstrap

---

### 5.2. Stimulus Bootstrap

**Fichier** : `app/assets/bootstrap.js`

```javascript
import { startStimulusApp } from '@symfony/stimulus-bundle';
import '@hotwired/turbo';

// Import controllers marketing custom
import PersonaController from './controllers/marketing/persona_controller.js';
import EnrichmentController from './controllers/marketing/enrichment_controller.js';
import GenerationController from './controllers/marketing/generation_controller.js';
import CompetitorController from './controllers/marketing/competitor_controller.js';

const app = startStimulusApp();

// Enregistrement manuel controllers marketing
app.register('marketing-persona', PersonaController);
app.register('marketing-enrichment', EnrichmentController);
app.register('marketing-generation', GenerationController);
app.register('marketing-competitor', CompetitorController);
```

**Raison** : Les controllers marketing sont enregistrés manuellement car ils ne suivent pas la convention de nommage auto-discovery Stimulus (`*_controller.js` dans `controllers/`).

**Convention** :
- **Auto-discovery** : `chat_controller.js` → `data-controller="chat"`
- **Manuel** : `persona_controller.js` → `app.register('marketing-persona')` → `data-controller="marketing-persona"`

---

### 5.3. JavaScript Non-Stimulus

**Fichiers** : 8 fichiers JavaScript "classiques" (non-Stimulus).

| Fichier | Responsabilité | Raison Non-Stimulus |
|---------|---------------|---------------------|
| `js/ui/division-selector.js` | Switch multi-tenant divisions | Composant global singleton, classe ES6 |
| `js/marketing/enrichment-review.js` | Validation formulaire enrichissement | Script simple one-off |
| `js/marketing/persona-selection.js` | Sélection personas | Script simple one-off |
| `js/marketing/persona-configure.js` | Configuration personas | Script simple one-off |
| `js/marketing/strategy-detection.js` | Détection stratégie | Script simple one-off |
| `js/chat/chat.js` | Interface chat (legacy) | Remplacé par chat_controller.js |
| `js/chat/conversation-sidebar.js` | Sidebar conversations | Logique métier spécifique |
| `js/components/data/datatable-renderer.js` | Rendu tableaux | Utilitaire réutilisable |

**Pattern observé** : Scripts métier complexes ou singletons restent en JS classique, interactions UI simples utilisent Stimulus.

---

### 5.4. Exemple : division-selector.js

**Architecture** : Classe ES6 singleton avec initialisation DOMContentLoaded.

```javascript
class DivisionSelector {
    constructor() {
        this.selectorElement = document.getElementById('division-selector');
        this.currentDivisionName = document.getElementById('current-division-name');
        this.divisionsList = document.getElementById('divisions-list');
        this.isLoading = false;
        this.isLoaded = false;

        this.init();
    }

    async init() {
        if (this.isLoading || this.isLoaded) return;
        this.isLoading = true;

        try {
            await this.loadDivisions();
            this.isLoaded = true;
        } catch (error) {
            this.showError('Impossible de charger les divisions');
        } finally {
            this.isLoading = false;
        }
    }

    async loadDivisions() {
        const response = await fetch('/api/tenant/divisions');
        const data = await response.json();
        this.renderDivisions(data.divisions);
    }

    async switchDivision(idDivision, nomDivision) {
        const confirmed = confirm(`Changer vers "${nomDivision}" ?`);
        if (!confirmed) return;

        const response = await fetch('/api/tenant/switch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idDivision })
        });

        if (response.ok) {
            window.location.reload(); // Recharger pour appliquer nouveau contexte
        }
    }
}

// Singleton global
let instance = null;
document.addEventListener('DOMContentLoaded', () => {
    if (!instance && document.getElementById('division-selector')) {
        instance = new DivisionSelector();
    }
});

export default DivisionSelector;
```

**Bonnes pratiques** :
- Singleton pour éviter instances multiples
- Vérification existence DOM avant initialisation
- Gestion états `isLoading`, `isLoaded`
- Async/await pour requêtes API
- Export ES6 pour testabilité

**Pourquoi pas Stimulus ?**
Ce composant est trop spécifique et global (multi-tenant), avec logique métier complexe (switch contexte, reload page). Stimulus est mieux adapté pour interactions UI réutilisables.

---

### 5.5. ESLint et Prettier

**Configuration** : `.eslintrc.json` et `.prettierrc.json` présents (non analysés ici).

**Scripts npm** disponibles :

```bash
npm run lint              # Analyse ESLint
npm run lint:fix          # Correction auto ESLint
npm run format            # Formatage Prettier
npm run format:check      # Vérification formatage
npm run quality           # Lint + format check
npm run quality:fix       # Lint + format (correction complète)
```

**Usage recommandé** : Exécuter `npm run quality:fix` après chaque modification JavaScript (selon CLAUDE.md).

---

## 6. Recommandations

### 6.1. Points Forts

1. **Architecture Mercure mature** : Double système topics (bundle + legacy) avec fallback gracieux
2. **Gestion retry intelligente** : Retry automatique backend + polling frontend transparent
3. **Progression temps réel robuste** : Throttling 500ms, phases détaillées, métadonnées riches
4. **Controllers Stimulus bien structurés** : Séparation domaines (ui, data, marketing), patterns cohérents
5. **Turbo Frames utilisé intelligemment** : Lazy loading sidebar, évite duplication code
6. **Protection CSRF automatique** : Transparent pour développeurs, compatible Turbo
7. **Bootstrap 5 moderne** : Icônes, composants, classes utilitaires cohérentes

---

### 6.2. Points d'Amélioration

#### **1. Consolider les Systèmes Mercure**

**Problème** : Deux systèmes coexistent (bundle `/tasks/{taskId}` + legacy `marketing/project/{id}`), créant duplication et complexité.

**Solution** :
- Migrer **toutes** les générations marketing vers bundle unique
- Supprimer `MarketingGenerationPublisher` (legacy)
- Utiliser uniquement `MercurePublisherSubscriber` + bundle

**Impact** :
- Code backend simplifié (-177 lignes)
- Un seul controller Stimulus marketing (`generation_controller.js`)
- Maintenance plus facile

---

#### **2. Standardiser Nommage Controllers Stimulus**

**Problème** : Mix auto-discovery + enregistrement manuel (confusion).

**Solution** :
- Renommer `persona_controller.js` → `marketing_persona_controller.js`
- Renommer `enrichment_controller.js` → `marketing_enrichment_controller.js`
- Renommer `generation_controller.js` → `marketing_generation_controller.js`
- Renommer `competitor_controller.js` → `marketing_competitor_controller.js`
- Supprimer enregistrements manuels dans `bootstrap.js`

**Impact** :
- Auto-discovery Stimulus fonctionne partout
- Convention cohérente : `{domain}_{feature}_controller.js` → `data-controller="{domain}-{feature}"`

---

#### **3. Extraire Logique Commune Marketing**

**Problème** : Code dupliqué dans 4 controllers marketing (`connectToMercure`, `handleProgress`, `startElapsedTimer`).

**Solution** :
- Créer classe base `MarketingBaseController` avec méthodes communes
- Hériter dans controllers spécifiques

**Exemple** :

```javascript
// controllers/marketing/base_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        taskId: String,
        mercureUrl: String,
        mercureJwt: String,
        nextUrl: String
    };

    static targets = [
        'progressBar', 'progressPercentage', 'progressMessage',
        'phaseIndicator', 'elapsedTime', 'spinner'
    ];

    connect() {
        this.startTime = Date.now();
        this.startElapsedTimer();
        this.connectToMercure();
    }

    disconnect() {
        if (this.eventSource) this.eventSource.close();
        if (this.elapsedTimer) clearInterval(this.elapsedTimer);
    }

    connectToMercure() {
        const topic = `/tasks/${this.taskIdValue}`;
        const mercureUrl = new URL(this.mercureUrlValue);
        mercureUrl.searchParams.append('topic', topic);

        if (this.mercureJwtValue) {
            mercureUrl.searchParams.append('authorization', this.mercureJwtValue);
        }

        this.eventSource = new EventSource(mercureUrl.toString());

        // Écoute événements
        this.eventSource.addEventListener('TaskProgressEvent', (event) => {
            this.handleProgress(JSON.parse(event.data));
        });
    }

    handleProgress(data) {
        const { percentage, message, metadata } = data;

        if (this.hasProgressBarTarget) {
            this.progressBarTarget.style.width = `${percentage}%`;
        }
        if (this.hasProgressPercentageTarget) {
            this.progressPercentageTarget.textContent = `${percentage}%`;
        }
        if (this.hasProgressMessageTarget) {
            this.progressMessageTarget.textContent = message;
        }
        if (this.hasPhaseIndicatorTarget && metadata?.current_phase) {
            this.phaseIndicatorTarget.textContent =
                `Phase ${metadata.current_phase}/${metadata.total_phases}`;
        }
    }

    startElapsedTimer() {
        this.elapsedTimer = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
            if (this.hasElapsedTimeTarget) {
                this.elapsedTimeTarget.textContent = `${elapsed}s`;
            }
        }, 1000);
    }
}
```

```javascript
// controllers/marketing/enrichment_controller.js
import MarketingBaseController from './base_controller.js';

export default class extends MarketingBaseController {
    connect() {
        super.connect();
        console.log('Enrichment controller connected');
    }

    handleComplete(data) {
        // Logique spécifique enrichissement
        this.showSuccess(data);
    }
}
```

**Impact** :
- Code partagé : -300 lignes dupliquées
- Maintenance simplifiée : modification unique
- Héritage cohérent : pattern orienté objet

---

#### **4. Ajouter Tests Unitaires Stimulus**

**Problème** : Aucun test détecté pour controllers Stimulus.

**Solution** :
- Utiliser `@hotwired/stimulus-testing` + Vitest/Jest
- Tester logique critique (polling, retry, progression)

**Exemple** :

```javascript
// controllers/marketing/generation_controller.test.js
import { Application } from '@hotwired/stimulus';
import GenerationController from './generation_controller';

describe('GenerationController', () => {
    let application;
    let controller;

    beforeEach(() => {
        application = Application.start();
        application.register('generation', GenerationController);

        document.body.innerHTML = `
            <div data-controller="generation"
                 data-generation-task-id-value="abc123"
                 data-generation-mercure-url-value="http://localhost:8080/.well-known/mercure">
                <div data-generation-target="progressBar"></div>
                <div data-generation-target="progressPercentage"></div>
            </div>
        `;

        controller = application.getControllerForElementAndIdentifier(
            document.querySelector('[data-controller="generation"]'),
            'generation'
        );
    });

    afterEach(() => {
        application.stop();
    });

    test('handleProgress updates progress bar', () => {
        controller.handleProgress({
            percentage: 50,
            message: 'Test progress',
            metadata: {}
        });

        expect(controller.progressBarTarget.style.width).toBe('50%');
        expect(controller.progressPercentageTarget.textContent).toBe('50%');
    });

    test('polling strategy completion retries on failure', async () => {
        // Mock fetch
        global.fetch = jest.fn()
            .mockResolvedValueOnce({ ok: true, json: async () => ({ has_strategy: false }) })
            .mockResolvedValueOnce({ ok: true, json: async () => ({ has_strategy: true }) });

        controller.pollStrategyCompletion();

        // Wait for polling
        await new Promise(resolve => setTimeout(resolve, 2100));

        expect(global.fetch).toHaveBeenCalledTimes(2);
    });
});
```

**Impact** :
- Confiance code : détection régression
- Documentation vivante : comportements documentés par tests
- Refactoring sûr : tests verts = code fonctionnel

---

#### **5. Améliorer Gestion Erreurs Mercure**

**Problème** : Perte connexion Mercure non gérée côté UI (EventSource reconnexion automatique mais pas de feedback utilisateur).

**Solution** :
- Détecter `eventSource.readyState === EventSource.CLOSED`
- Afficher toast "Connexion temps réel perdue, reconnexion..."
- Limiter tentatives reconnexion (max 5)

**Exemple** :

```javascript
connectToMercure() {
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;

    this.eventSource = new EventSource(mercureUrl.toString());

    this.eventSource.onerror = (error) => {
        console.error('EventSource error:', error);

        if (this.eventSource.readyState === EventSource.CLOSED) {
            this.reconnectAttempts++;

            if (this.reconnectAttempts > this.maxReconnectAttempts) {
                this.showError('Connexion temps réel perdue. Veuillez recharger la page.');
                return;
            }

            this.showWarning(`Reconnexion en cours... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
        }
    };
}
```

**Impact** :
- Meilleure UX : utilisateur informé problème réseau
- Évite attente infinie : max reconnexions + message explicite

---

#### **6. Migrer JS Non-Stimulus vers Stimulus**

**Candidats** :
- `js/marketing/enrichment-review.js` → `enrichment_review_controller.js`
- `js/marketing/persona-selection.js` → `persona_selection_controller.js`
- `js/marketing/persona-configure.js` → `persona_configure_controller.js`

**Raison** : Logique UI simple (radio buttons, formulaires) → parfait pour Stimulus.

**Exemple** :

```javascript
// controllers/marketing/enrichment_review_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['radio', 'validateButton', 'selectedInput'];

    selectName(event) {
        const selectedValue = event.target.value;

        // Mettre à jour champ caché
        this.selectedInputTarget.value = selectedValue;

        // Activer bouton validation
        this.validateButtonTarget.disabled = false;
        this.validateButtonTarget.classList.remove('btn-secondary');
        this.validateButtonTarget.classList.add('btn-primary');

        // Indicateur visuel
        this.radioTargets.forEach(radio => {
            const indicator = radio.closest('label').querySelector('.selected-indicator');
            indicator.classList.toggle('d-none', radio !== event.target);
        });
    }
}
```

**Impact** :
- Cohérence architecture : tout en Stimulus
- Réutilisabilité : contrôleurs testables et modulaires
- Maintenance : conventions partagées

---

### 6.3. Recommandations Design System

#### **1. Composants Réutilisables Mercure**

**Créer templates Twig partiels** pour barres progression :

```twig
{# templates/components/mercure_progress.html.twig #}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-semibold text-primary" data-progress-message>
            {{ initialMessage|default('Initialisation...') }}
        </div>
        <div class="badge bg-primary" data-phase-indicator>
            Phase 0/{{ totalPhases|default(4) }}
        </div>
    </div>

    <div class="progress mb-2" style="height: 25px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated"
             style="width: 0%"
             data-progress-bar>
            <span class="fw-semibold" data-progress-percentage>0%</span>
        </div>
    </div>

    <div class="text-muted small text-center">
        <i class="bi bi-robot me-1"></i> {{ agentName|default('Agent IA') }} en temps réel
    </div>
</div>
```

**Utilisation** :

```twig
{% include 'components/mercure_progress.html.twig' with {
    initialMessage: 'Génération des personas...',
    totalPhases: 5,
    agentName: 'PersonaGeneratorAgent'
} %}
```

**Impact** :
- Cohérence visuelle partout
- Modification unique pour tous les usages
- Documentation centralisée

---

#### **2. Variables CSS Mercure**

**Ajouter dans `styles/variables.css`** :

```css
:root {
    /* Mercure progression */
    --mercure-progress-height: 25px;
    --mercure-progress-bg: var(--bs-gray-200);
    --mercure-progress-bar-bg: var(--bs-primary);
    --mercure-progress-bar-animated-duration: 1s;

    /* Mercure messages */
    --mercure-message-font-size: 0.95rem;
    --mercure-message-color: var(--bs-primary);

    /* Mercure phases */
    --mercure-phase-badge-bg: var(--bs-primary);
    --mercure-phase-badge-color: white;
}
```

**Impact** :
- Personnalisation facile (thèmes dark)
- Cohérence design system
- Maintenance CSS simplifiée

---

#### **3. Classes Utilitaires Stimulus**

**Ajouter dans `styles/app.css`** :

```css
/* Stimulus controllers états */
[data-controller] {
    /* Debug mode : border rouge en développement */
    /* outline: 1px solid rgba(255, 0, 0, 0.2); */
}

/* États loading */
[data-controller].is-loading {
    pointer-events: none;
    opacity: 0.6;
    cursor: wait;
}

/* États disabled */
[data-controller].is-disabled {
    pointer-events: none;
    opacity: 0.5;
}

/* États error */
[data-controller].has-error {
    border: 2px solid var(--bs-danger);
    background-color: rgba(var(--bs-danger-rgb), 0.1);
}

/* Animation connexion Mercure */
@keyframes mercure-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.mercure-connecting {
    animation: mercure-pulse 2s ease-in-out infinite;
}
```

**Utilisation** :

```javascript
connect() {
    this.element.classList.add('is-loading');
    this.connectToMercure();
}

handleComplete() {
    this.element.classList.remove('is-loading');
}

handleError() {
    this.element.classList.add('has-error');
}
```

**Impact** :
- États visuels cohérents
- Feedback utilisateur clair
- Debug facilité (outline en dev)

---

#### **4. Documentation Composants Mercure**

**Créer** `docs/MERCURE_COMPONENTS.md` avec :
- Liste composants Mercure disponibles
- Props attendues (`taskId`, `mercureUrl`, etc.)
- Événements émis et écoutés
- Exemples d'intégration

**Intégrer dans** `CONTEXT_ENGINEERING/BEST_PRACTICES/`.

---

## 7. Points d'Attention Design System

### 7.1. Cohérence Visuelle

**Observations** :

1. **Barres de progression** : 3 variantes différentes détectées
   - Variante 1 : Barre 25px avec pourcentage inside
   - Variante 2 : Barre 20px avec pourcentage outside
   - Variante 3 : Barre 15px sans texte

   **Recommandation** : Standardiser sur variante 1 (25px, inside).

2. **Badges phases** : Position incohérente
   - Parfois top-right de la barre
   - Parfois bottom de la card

   **Recommandation** : Toujours top-right, alignement `justify-content-between`.

3. **Icônes agents IA** :
   - `bi-robot` : Personas, Assets, Enrichissement
   - `bi-graph-up-arrow` : Stratégie
   - `bi-search` : Concurrents

   **Recommandation** : Mapping agent → icône documenté dans design system.

---

### 7.2. Accessibilité

**Points à améliorer** :

1. **ARIA progress** : Attributs manquants sur certaines barres

   **Fix** :
   ```html
   <div class="progress" role="progressbar"
        aria-label="Progression génération"
        aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
   ```

2. **Live regions** : Messages dynamiques non annoncés par lecteurs d'écran

   **Fix** :
   ```html
   <div data-progress-message aria-live="polite" aria-atomic="true">
       Initialisation...
   </div>
   ```

3. **Focus keyboard** : Boutons Stimulus non focusables via clavier

   **Fix** : Vérifier `tabindex` et navigation Tab.

---

### 7.3. Thèmes (Dark Mode)

**Observations** : 3 thèmes disponibles (light, dark-blue, dark-red).

**Points d'attention Mercure** :
- Couleurs progress bar lisibles en dark
- Contraste badges phases
- Couleurs icônes agents IA

**Variables CSS recommandées** :

```css
/* Theme light */
.theme-light {
    --mercure-progress-bg: #e9ecef;
    --mercure-progress-bar-bg: var(--bs-primary);
    --mercure-message-color: var(--bs-primary);
}

/* Theme dark-blue */
.theme-dark-blue {
    --mercure-progress-bg: rgba(255, 255, 255, 0.1);
    --mercure-progress-bar-bg: var(--bs-info);
    --mercure-message-color: var(--bs-info);
}

/* Theme dark-red */
.theme-dark-red {
    --mercure-progress-bg: rgba(255, 255, 255, 0.1);
    --mercure-progress-bar-bg: var(--bs-danger);
    --mercure-message-color: var(--bs-danger);
}
```

---

### 7.4. Responsive

**Observations** :

1. **Barres progression mobile** : Texte pourcentage trop petit (`< 576px`)

   **Fix** :
   ```css
   @media (max-width: 575.98px) {
       .progress { height: 30px !important; }
       [data-progress-percentage] { font-size: 1rem; }
   }
   ```

2. **Badges phases mobile** : Déborde container étroit

   **Fix** :
   ```css
   @media (max-width: 575.98px) {
       [data-phase-indicator] { font-size: 0.75rem; }
   }
   ```

3. **Messages descriptifs mobile** : Texte tronqué

   **Fix** :
   ```css
   [data-progress-message] {
       overflow: hidden;
       text-overflow: ellipsis;
       white-space: nowrap;
   }
   ```

---

### 7.5. Animation et Performance

**Observations** :

1. **Progress bar animée** : `progress-bar-animated` active en permanence

   **Impact** : Consommation CPU inutile après complétion

   **Fix** :
   ```javascript
   handleComplete() {
       this.progressBarTarget.classList.remove('progress-bar-animated');
   }
   ```

2. **EventSource reconnexion** : Pas de limite max tentatives

   **Impact** : Connexions infinies en cas d'erreur serveur

   **Fix** : Ajouter `maxReconnectAttempts` (voir recommandation 5).

3. **Timer elapsed** : Interval 1s non stoppé après disconnect

   **Impact** : Fuite mémoire si navigation Turbo

   **Fix** : Déjà implémenté dans `disconnect()` des controllers.

---

## 8. Récapitulatif Architecture

### 8.1. Flux Complet Génération Stratégie

```
1. UTILISATEUR
   └─> Clique "Générer stratégie" sur /marketing/strategy/generate/{id}

2. BACKEND (Controller Symfony)
   ├─> Crée tâche asynchrone (Marketing AI Bundle)
   ├─> Dispatch événement TaskStartedEvent
   ├─> Redirige vers /marketing/strategy/generating/{id}
   └─> Retour immédiat (non-bloquant)

3. WORKER ASYNCHRONE (Symfony Messenger)
   ├─> Exécute StrategyAnalystAgent
   ├─> Dispatch TaskProgressEvent toutes les 500ms (throttling)
   ├─> Appelle Mistral AI API
   ├─> Génère stratégie marketing
   ├─> Persiste en BDD (Project->strategy)
   └─> Dispatch TaskCompletedEvent

4. MERCURE HUB
   ├─> Reçoit événements du MercurePublisherSubscriber
   ├─> Publie sur topic /tasks/{taskId}
   └─> EventSource clients reçoivent updates

5. FRONTEND (Stimulus generation_controller.js)
   ├─> Connexion EventSource à Mercure
   ├─> Écoute TaskProgressEvent → mise à jour UI temps réel
   ├─> Écoute TaskCompletedEvent
   ├─> Lance polling BDD (/api/projects/{id}/status)
   ├─> Attend confirmation has_strategy === true
   ├─> Affiche succès + redirection vers /marketing/strategy/show/{id}
   └─> Ferme EventSource

6. RÉSULTAT
   └─> Utilisateur voit stratégie générée avec temps réel fluide
```

---

### 8.2. Stack Technologique

| Couche | Technologies |
|--------|-------------|
| **Backend** | Symfony 7.3, PHP 8.3, FrankenPHP (Caddy), MariaDB 11 |
| **Messaging** | Symfony Messenger (Doctrine transport) |
| **Temps Réel** | Mercure Hub v0.16, Server-Sent Events (SSE) |
| **Frontend** | Stimulus 3.x, Turbo Drive + Frames, Bootstrap 5.3.8, Bootstrap Icons |
| **JavaScript** | ES Modules, AssetMapper (pas de bundler), Prettier + ESLint |
| **IA** | Mistral AI Large (via Symfony AI Bundle), Marketing AI Bundle custom |
| **Docker** | Docker Compose, FrankenPHP, Mercure Hub, MariaDB, MailHog, phpMyAdmin |

---

### 8.3. Métriques

| Métrique | Valeur |
|----------|--------|
| **Controllers Stimulus** | 10 |
| **Templates utilisant Stimulus** | 8 |
| **Topics Mercure actifs** | 2 (`/tasks/{id}`, `marketing/project/{id}`) |
| **Services Mercure Backend** | 2 (`MercurePublisherSubscriber`, `MarketingGenerationPublisher`) |
| **Événements SSE** | 4 (Started, Progress, Completed, Failed) |
| **Fichiers JS non-Stimulus** | 8 |
| **Lignes code JavaScript total** | ~3000 (estimation) |
| **Turbo Frames utilisés** | 2 (sidebar favorites + history) |
| **Turbo Streams utilisés** | 0 |

---

## 9. Conclusion

L'application myCfia utilise une architecture Mercure/Stimulus/Turbo **mature et bien structurée** :

**Forces principales** :
- Temps réel robuste avec retry automatique
- Controllers Stimulus modulaires et réutilisables
- Intégration Bootstrap 5 cohérente
- Protection CSRF transparente pour Turbo

**Axes d'amélioration prioritaires** :
1. Consolider systèmes Mercure (bundle unique)
2. Standardiser nommage controllers Stimulus
3. Extraire logique commune marketing (classe base)
4. Ajouter tests unitaires Stimulus
5. Améliorer gestion erreurs connexion Mercure

**Recommandations Design System** :
- Créer composants Twig réutilisables pour barres progression
- Ajouter variables CSS Mercure pour thèmes
- Standardiser classes utilitaires états Stimulus
- Documenter mapping agents IA → icônes

**Prêt pour intégration nouveau design** : Oui, avec attention particulière sur :
- Cohérence visuelle barres progression
- Accessibilité ARIA progress + live regions
- Responsive mobile (badges phases, messages)
- Thèmes dark (contraste Mercure components)

---

**Auteur** : Claude Sonnet 4.5
**Date** : 2025-12-16
**Version** : 1.0
