# Rapport Technique - Intégration Bundle Marketing AI v3.33.0

**Date** : 2025-11-28
**Bundle Version** : v3.33.0
**Application** : myCfia
**Objectif** : Intégration événements temps réel + progression visuelle détaillée

---

## 🎯 Objectifs de l'Intégration

### Objectif Principal (Option C)
Améliorer **toutes les pages de génération existantes** pour offrir :

1. ✅ **Progression temps réel détaillée** : Afficher les sous-étapes de chaque génération (ex: "Scraping en cours..." → "Analyse LLM..." → "Génération keywords...")
2. ✅ **Barre de progression réelle** : Remplacer le spinner fixe par une barre 0% → 100% avec mise à jour temps réel
3. ✅ **Événements asynchrones** : Toutes les générations doivent passer par le worker Messenger (asynchrone)
4. ✅ **Notifications futures** : Préparer l'infrastructure pour un système de notifications

### Générations Concernées
- **Enrichment** (enrichissement projet)
- **Personas** (génération personas)
- **Strategy** (analyse stratégique)
- **Assets** (création assets marketing)

---

## 📊 État Actuel de l'Intégration

### ✅ Ce qui fonctionne

| Génération | Route Start | Route Progress | Template Loader | Controller JS | Async via Messenger |
|------------|-------------|----------------|-----------------|---------------|---------------------|
| **Enrichment** | `enrichment_start` (POST) | `enrichment_generating` (GET) | ✅ `enrichment/generating.html.twig` | ✅ `enrichment_controller.js` | ✅ Oui |
| **Personas** | `persona_generate` (POST) | `persona_generating` (GET) | ✅ `persona/generating.html.twig` | ✅ `persona_controller.js` | ✅ Oui |
| **Strategy** | `strategy_new` (POST) | `strategy_generating` (GET) | ✅ `strategy/generating.html.twig` | ✅ `generation_controller.js` | ✅ Oui |
| **Assets** | `asset_new` (POST) | `asset_generating` (GET) | ✅ `asset/generating.html.twig` | ✅ `generation_controller.js` | ✅ Oui |

**Toutes les générations sont déjà asynchrones** via `AgentTaskManager::dispatch*()`.

### 🔴 Ce qui manque

#### 1. Événements TaskProgressEvent non utilisés

**État actuel** : Les controllers JS écoutent uniquement 3 événements :
```javascript
// enrichment_controller.js (exemple)
this.eventSource.addEventListener('TaskStartedEvent', ...);
this.eventSource.addEventListener('TaskCompletedEvent', ...);
this.eventSource.addEventListener('TaskFailedEvent', ...);
```

**Événement manquant** : `TaskProgressEvent`
- ✅ **Disponible** dans le bundle v3.33.0 (`src/Event/TaskProgressEvent.php`)
- ❌ **NON dispatché** par les agents pendant l'exécution
- ❌ **NON écouté** par les controllers JS frontend

#### 2. Agents ne dispatchent pas de progression

**Vérification effectuée** :
```bash
$ grep -r "ProgressReporter\|reportProgress" vendor/gorillias/marketing-ai-bundle/src/Agent
# Résultat : Aucune occurrence
```

**Constat** :
- ✅ Infrastructure `ProgressReporterInterface` présente dans le bundle
- ✅ Implémentation `EventDispatcherProgressReporter` disponible
- ❌ **Aucun agent n'utilise le ProgressReporter** pour dispatcher TaskProgressEvent

**Agents analysés** :
- `ProjectEnrichmentAgent::enrichProject()` - Pas de dispatch TaskProgressEvent
- `PersonaGeneratorAgent::generatePersona()` - Pas de dispatch TaskProgressEvent
- `StrategyAnalystAgent::analyzeStrategy()` - Pas de dispatch TaskProgressEvent
- `ContentCreatorAgent::createAsset()` - Pas de dispatch TaskProgressEvent

---

## 🔍 Analyse Technique Détaillée

### TaskProgressEvent - Spécifications

**Fichier** : `vendor/gorillias/marketing-ai-bundle/src/Event/TaskProgressEvent.php`

**Structure de l'événement** :
```php
final readonly class TaskProgressEvent
{
    public const NAME = 'gorillias.marketing.task.progress';

    public function __construct(
        public string $taskId,           // UUID de la tâche
        public int $percentage,          // 0-100
        public string $phase,            // "initialization", "detection", "validation", "scoring"
        public string $message,          // "Validation concurrent 15/33..."
        public array $metadata = [],     // current_item, total_items, eta_seconds
        public array $context = [],
    ) { }

    public function toMercurePayload(): array; // Prêt pour SSE
}
```

**Méthodes utiles** :
- `getClampedPercentage()` : Retourne pourcentage entre 0-100
- `getItemProgress()` : Retourne `{current: int, total: int}` si disponible
- `getEtaSeconds()` : Retourne estimation temps restant
- `getCurrentStep()` : Retourne nom de l'étape actuelle

### ProgressReporterInterface - Utilisation attendue

**Fichier** : `vendor/gorillias/marketing-ai-bundle/src/Contract/ProgressReporterInterface.php`

**Documentation du bundle** :
```php
/**
 * Permet de dispatcher des TaskProgressEvent pendant l'exécution.
 *
 * USAGE DANS UN AGENT/TOOL :
 *
 * $progressReporter->reportProgress(
 *     percentage: 25,
 *     phase: 'scraping',
 *     message: 'Scraping website data...',
 *     metadata: ['current_page' => 1, 'total_pages' => 4]
 * );
 */
interface ProgressReporterInterface
{
    public function reportProgress(
        int $percentage,
        string $phase,
        string $message,
        array $metadata = []
    ): void;
}
```

**Implémentation disponible** : `EventDispatcherProgressReporter`

---

## 💡 Exemples d'Implémentation Attendue

### Exemple 1 : ProjectEnrichmentAgent avec progression

**Scénario** : Enrichissement projet avec 4 phases

```php
class ProjectEnrichmentAgent
{
    public function enrichProject(...$args): array
    {
        // Phase 1 : Scraping (0-25%)
        $progressReporter->reportProgress(
            percentage: 5,
            phase: 'scraping',
            message: 'Démarrage du scraping website...',
            metadata: []
        );

        $brandAnalysis = $this->brandStyleAnalyzer->analyzeBrandFromUrl($websiteUrl);

        $progressReporter->reportProgress(
            percentage: 25,
            phase: 'scraping',
            message: 'Scraping terminé - Données récupérées',
            metadata: ['pages_scraped' => 3]
        );

        // Phase 2 : Keywords Google Ads (25-50%)
        $progressReporter->reportProgress(
            percentage: 30,
            phase: 'keywords_extraction',
            message: 'Extraction keywords Google Ads...',
            metadata: []
        );

        $googleAdsKeywords = $this->googleAdsClient->getKeywordIdeas(...);

        $progressReporter->reportProgress(
            percentage: 50,
            phase: 'keywords_extraction',
            message: 'Keywords extraits',
            metadata: ['keywords_count' => count($googleAdsKeywords)]
        );

        // Phase 3 : Analyse LLM (50-80%)
        $progressReporter->reportProgress(
            percentage: 55,
            phase: 'llm_analysis',
            message: 'Analyse IA en cours...',
            metadata: ['model' => 'mistral-large-latest']
        );

        $llmResponse = $this->agent->execute(...);

        $progressReporter->reportProgress(
            percentage: 80,
            phase: 'llm_analysis',
            message: 'Analyse IA terminée',
            metadata: ['tokens_used' => $llmResponse->usage->totalTokens]
        );

        // Phase 4 : Finalisation (80-100%)
        $progressReporter->reportProgress(
            percentage: 90,
            phase: 'finalization',
            message: 'Finalisation des suggestions...',
            metadata: []
        );

        // ... traitement final ...

        $progressReporter->reportProgress(
            percentage: 100,
            phase: 'completed',
            message: 'Enrichissement terminé',
            metadata: []
        );

        return $result;
    }
}
```

### Exemple 2 : Frontend - Écoute TaskProgressEvent

**Fichier** : `enrichment_controller.js`

```javascript
connectToMercure() {
    const topic = `/tasks/${this.taskIdValue}`;
    // ... configuration EventSource ...

    // ✅ Événements existants (déjà écoutés)
    this.eventSource.addEventListener('TaskStartedEvent', (event) => {
        this.handleStart(JSON.parse(event.data));
    });

    this.eventSource.addEventListener('TaskCompletedEvent', (event) => {
        this.handleComplete(JSON.parse(event.data));
    });

    this.eventSource.addEventListener('TaskFailedEvent', (event) => {
        this.handleError(JSON.parse(event.data));
    });

    // 🆕 NOUVEAU : Écoute TaskProgressEvent
    this.eventSource.addEventListener('TaskProgressEvent', (event) => {
        const data = JSON.parse(event.data);
        this.handleProgress(data);
    });
}

handleProgress(data) {
    // Mettre à jour la barre de progression
    const percentage = data.percentage;
    this.progressBarTarget.style.width = `${percentage}%`;
    this.progressBarTarget.textContent = `${percentage}%`;

    // Mettre à jour le message de phase
    this.phaseMessageTarget.textContent = data.message;

    // Afficher l'étape actuelle
    if (data.metadata.current_step) {
        this.currentStepTarget.textContent = data.metadata.current_step;
    }

    // Afficher ETA si disponible
    if (data.metadata.eta_seconds) {
        const eta = Math.round(data.metadata.eta_seconds);
        this.etaTarget.textContent = `Temps restant : ${eta}s`;
    }
}
```

**Template HTML** : `enrichment/generating.html.twig`

```twig
<div class="progress mb-3" style="height: 25px;">
    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
         role="progressbar"
         style="width: 0%"
         data-marketing-enrichment-target="progressBar">
        0%
    </div>
</div>

<div class="text-center mb-3">
    <strong data-marketing-enrichment-target="phaseMessage">
        Initialisation...
    </strong>
</div>

<div class="small text-muted text-center">
    <div data-marketing-enrichment-target="currentStep"></div>
    <div data-marketing-enrichment-target="eta"></div>
</div>
```

---

## 🎬 Flux Complet Attendu

### Scénario : Génération Enrichment

```
1. User clique "Enrichir le projet"
   ↓
2. POST /marketing/projects/{id}/enrichment/start
   ↓
3. ProjectController::enrichmentStart()
   → $agentTaskManager->dispatchEnrichmentTask(...)
   → Redirection vers enrichment_generating
   ↓
4. GET /marketing/projects/{id}/enrichment/generating/{taskId}
   → Template + Controller JS chargés
   → EventSource connecté sur topic /tasks/{taskId}
   ↓
5. Worker Messenger traite la tâche
   → AgentTaskHandler::__invoke()
   → ProjectEnrichmentAgent::enrichProject()
   ↓
6. 🆕 Agent dispatch TaskProgressEvent (plusieurs fois)
   ├─ 5% : "Démarrage scraping..."
   ├─ 25% : "Scraping terminé"
   ├─ 50% : "Keywords extraits"
   ├─ 80% : "Analyse IA terminée"
   └─ 100% : "Enrichissement terminé"
   ↓
7. Frontend reçoit les événements via Mercure SSE
   → Mise à jour barre de progression en temps réel
   → Affichage des messages de phase
   ↓
8. TaskCompletedEvent final
   → Redirection vers enrichment_review
```

---

## 📋 Questions pour le Dev du Bundle

### 1. TaskProgressEvent - Implémentation dans les agents

**Question** : Est-ce que les agents (`ProjectEnrichmentAgent`, `PersonaGeneratorAgent`, etc.) sont censés dispatcher `TaskProgressEvent` pendant leur exécution ?

**Observations** :
- ✅ `TaskProgressEvent` existe dans `src/Event/`
- ✅ `ProgressReporterInterface` existe dans `src/Contract/`
- ✅ `EventDispatcherProgressReporter` existe dans `src/Implementation/`
- ❌ Aucun agent n'utilise `ProgressReporter` actuellement

**Attente** : Les agents devraient recevoir un `ProgressReporter` en injection et l'utiliser pour dispatcher des événements de progression à intervalles réguliers.

### 2. Injection du ProgressReporter

**Question** : Comment injecter le `ProgressReporter` dans les agents ?

**Options possibles** :
- **Option A** : Via le constructeur de l'agent (DI Symfony)
- **Option B** : Via le contexte `AgentExecutionContext`
- **Option C** : Via un trait `ProgressReportingTrait`

**Code actuel** : `AgentExecutionContext` ne contient pas de `ProgressReporter`

### 3. AgentTaskHandler - Intégration ProgressReporter

**Question** : Est-ce que `AgentTaskHandler` doit créer et passer un `ProgressReporter` à l'agent avant d'exécuter la méthode ?

**Exemple attendu** :
```php
class AgentTaskHandler
{
    public function __invoke(AgentTaskMessage $message): void
    {
        // Créer ProgressReporter pour cette tâche
        $progressReporter = new EventDispatcherProgressReporter(
            taskId: $message->uuid,
            eventDispatcher: $this->eventDispatcher
        );

        // Passer au contexte ou directement à l'agent ?
        $context = $message->context;
        $context->setProgressReporter($progressReporter); // ???

        // Exécuter l'agent
        $result = $agent->{$message->methodName}(...$args);
    }
}
```

### 4. Publication Mercure des TaskProgressEvent

**Question** : Est-ce que les `TaskProgressEvent` sont automatiquement publiés sur Mercure, ou faut-il créer un Event Subscriber côté application ?

**Cas actuel** :
- ✅ `TaskStartedEvent`, `TaskCompletedEvent`, `TaskFailedEvent` → Publiés automatiquement ?
- ❌ `TaskProgressEvent` → Publication manquante ?

**Subscriber attendu côté application** :
```php
class TaskProgressMercureSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TaskProgressEvent::class => 'onTaskProgress',
        ];
    }

    public function onTaskProgress(TaskProgressEvent $event): void
    {
        $update = new Update(
            topics: sprintf('/tasks/%s', $event->taskId),
            data: json_encode($event->toMercurePayload())
        );

        $this->hub->publish($update);
    }
}
```

### 5. Documentation et Exemples

**Question** : Existe-t-il une documentation ou des exemples d'utilisation de `TaskProgressEvent` dans le bundle ?

**Fichiers à vérifier** :
- README du bundle
- Tests unitaires (`tests/Unit/Event/TaskProgressEventTest.php` ?)
- Exemples d'implémentation

---

## 🔧 Actions Requises

### Côté Bundle (à confirmer avec le dev)

1. [ ] **Injecter ProgressReporter** dans les agents
2. [ ] **Dispatcher TaskProgressEvent** dans `ProjectEnrichmentAgent::enrichProject()`
3. [ ] **Dispatcher TaskProgressEvent** dans `PersonaGeneratorAgent::generatePersona()`
4. [ ] **Dispatcher TaskProgressEvent** dans `StrategyAnalystAgent::analyzeStrategy()`
5. [ ] **Dispatcher TaskProgressEvent** dans `ContentCreatorAgent::createAsset()`
6. [ ] **Publier sur Mercure** les TaskProgressEvent automatiquement

### Côté Application (myCfia)

1. [ ] Créer `TaskProgressMercureSubscriber` pour publier events sur Mercure
2. [ ] Modifier `enrichment_controller.js` pour écouter `TaskProgressEvent`
3. [ ] Modifier `persona_controller.js` pour écouter `TaskProgressEvent`
4. [ ] Modifier `generation_controller.js` pour écouter `TaskProgressEvent`
5. [ ] Améliorer templates HTML avec barres de progression réelles

---

## 📊 Résumé Exécutif

### Statut Actuel
- ✅ **Toutes les générations sont asynchrones** (worker Messenger)
- ✅ **Toutes les générations ont des pages de loader** avec EventSource Mercure
- ✅ **Infrastructure TaskProgressEvent disponible** dans le bundle v3.33.0
- ❌ **Agents ne dispatchent PAS TaskProgressEvent** (fonctionnalité non implémentée)
- ❌ **Frontend n'écoute PAS TaskProgressEvent** (en attente implémentation bundle)

### Blocage Principal
**Les agents du bundle ne dispatchent pas de progression intermédiaire**, empêchant l'affichage temps réel des sous-étapes.

### Solution Requise
**Le dev du bundle doit** :
1. Implémenter le dispatch de `TaskProgressEvent` dans les agents
2. Fournir la documentation d'intégration

**L'application peut ensuite** :
1. Écouter ces événements côté frontend
2. Afficher la progression détaillée

---

## 📎 Annexes

### Fichiers Clés du Bundle Analysés
- `src/Event/TaskProgressEvent.php`
- `src/Contract/ProgressReporterInterface.php`
- `src/Implementation/EventDispatcherProgressReporter.php`
- `src/Agent/ProjectEnrichmentAgent.php`
- `src/Agent/PersonaGeneratorAgent.php`
- `src/Agent/StrategyAnalystAgent.php`
- `src/Agent/ContentCreatorAgent.php`
- `src/MessageHandler/AgentTaskHandler.php`

### Fichiers Application myCfia
- `app/assets/controllers/marketing/enrichment_controller.js`
- `app/assets/controllers/marketing/persona_controller.js`
- `app/assets/controllers/marketing/generation_controller.js`
- `app/templates/marketing/enrichment/generating.html.twig`
- `app/templates/marketing/persona/generating.html.twig`
- `app/templates/marketing/strategy/generating.html.twig`
- `app/templates/marketing/asset/generating.html.twig`
