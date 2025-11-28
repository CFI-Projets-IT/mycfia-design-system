# Analyse Profonde de l'Infrastructure Existante - Bundle v3.33.0

**Date** : 2025-01-28
**Objectif** : Identifier ce qui existe, ce qui convient, ce qui doit être modifié (pas créé)

---

## 📊 Résumé Exécutif

L'application dispose **DÉJÀ** d'une infrastructure complète Mercure SSE et d'un workflow de génération de campagne fonctionnel. La migration vers TaskChain v3.33.0 nécessite principalement :

- ✅ **Réutiliser** : 80% de l'infrastructure Mercure existante
- ⚠️ **Adapter** : Routes et contrôleurs pour utiliser TaskChain
- ❌ **Créer** : CampaignChainBuilder + TaskChainMercureSubscriber + 2 champs Entity

---

## 🎯 Infrastructure Mercure SSE Existante (DÉJÀ COMPLÈTE)

### ✅ Service `MercureJwtGenerator` (EXISTE - app/src/Service/MercureJwtGenerator.php)

**Statut** : ✅ **PARFAIT - Aucune modification nécessaire**

```php
public function generatePublisherToken(array $topics): string
public function generateSubscriberToken(array $topics): string
```

**Fonctionnalités complètes** :
- Génération JWT publisher + subscriber
- HMAC-SHA256 avec clé secrète
- Base64URL encoding standard
- Documentation PHPDoc complète

**Action** : ✅ **Réutiliser tel quel** pour TaskChain

---

### ✅ Service `MercureNotificationPublisher` (EXISTE - app/src/Service/MercureNotificationPublisher.php)

**Statut** : ✅ **PARFAIT - Implémente déjà NotificationPublisherInterface du bundle**

```php
public function publish($userId, string $type, array $data): void
public function publishTaskEvent(string $taskUuid, string $event, array $data): void
```

**Fonctionnalités complètes** :
- Topics `/user/{userId}` pour notifications personnelles
- Topics `/task/{taskUuid}` pour événements de tâches
- Validation UUID RFC 4122
- Logging complet via monolog.logger.marketing.general

**Action** : ✅ **Réutiliser tel quel** - Bundle v3.33.0 l'utilise déjà automatiquement

---

### ✅ EventSubscriber `MercurePublisherSubscriber` (EXISTE - app/src/EventSubscriber/Marketing/MercurePublisherSubscriber.php)

**Statut** : ✅ **COMPLET - Écoute déjà TaskStartedEvent, TaskCompletedEvent, TaskFailedEvent**

```php
public function onTaskStarted(TaskStartedEvent $event): void
public function onTaskCompleted(TaskCompletedEvent $event): void
public function onTaskFailed(TaskFailedEvent $event): void
```

**Fonctionnalités** :
- Publie sur topics `/tasks/{taskId}` avec priorité 10
- Format SSE avec champ "event:" pour filtrage JS
- Gestion erreurs avec logging

**Action** : ⚠️ **Étendre légèrement** pour ajouter support des TaskChainEvents (ChainStarted, ChainCompleted, ChainFailed)

---

### ✅ EventSubscriber `AgentExecutionSubscriber` (EXISTE - app/src/EventSubscriber/Marketing/AgentExecutionSubscriber.php)

**Statut** : ✅ **COMPLET - Logging LLM + Alerting + Notifications Mercure**

```php
public function onAgentStarted(AgentExecutionStartedEvent $event): void
public function onAgentCompleted(AgentExecutionCompletedEvent $event): void
public function onAgentFailed(AgentExecutionFailedEvent $event): void
```

**Fonctionnalités** :
- Log métrique LLM pour Grafana (tokens, durée, coût)
- Alerting sur seuils (30s, 10k tokens, $0.10)
- Notifications Mercure via MercureNotificationPublisher

**Action** : ✅ **Aucune modification** - Fonctionne déjà pour les agents TaskChain

---

### ✅ Contrôleur Stimulus `enrichment_controller.js` (EXISTE - app/assets/controllers/marketing/enrichment_controller.js)

**Statut** : ⚠️ **FONCTIONNEL pour single task - À adapter pour TaskChain**

```javascript
connectToMercure() {
    const topic = `/tasks/${this.taskIdValue}`;
    // EventSource + listeners TaskStartedEvent, TaskCompletedEvent, TaskFailedEvent
}
```

**Fonctionnalités** :
- Connexion EventSource avec JWT
- Listeners SSE pour TaskStartedEvent, TaskCompletedEvent, TaskFailedEvent
- Timer temps écoulé
- Auto-redirection après succès

**Action** : ⚠️ **Dupliquer + adapter** en `campaign_chain_controller.js` pour écouter :
- `ChainStartedEvent` sur `/chain/{chainId}`
- `ChainStepCompletedEvent` pour barre progression
- `ChainCompletedEvent` pour redirection finale

---

## 🏗️ Infrastructure Workflow Campagne Existante

### ✅ Entité `Project` (EXISTE - app/src/Entity/Project.php)

**Statut** : ⚠️ **QUASI-COMPLET - Manque 2 champs TaskChain**

**Champs existants (964 lignes)** :
- ✅ `selectedAssetTypes` (array) - Ligne 134
- ✅ `brandIdentity` (text) - Ligne 205
- ✅ `businessIntelligence` (text) - Ligne 232
- ✅ `keywordsData` (json) - Ligne 250
- ✅ Relations : `personas`, `strategy`, `competitorAnalyses`, `assets`

**Champs manquants** :
- ❌ `currentChainId` (string, nullable) - UUID de la chaîne en cours
- ❌ `chainStatus` (string, nullable) - pending, running, completed, failed

**Action** : ⚠️ **Ajouter 2 champs** + migration Doctrine

---

### ✅ Entité `Asset` (EXISTE - app/src/Entity/Asset.php)

**Statut** : ✅ **COMPLET - Aucune modification**

**Action** : ✅ **Aucune modification nécessaire**

---

### ✅ Contrôleur `ProjectController` (EXISTE - app/src/Controller/Marketing/ProjectController.php)

**Statut** : ⚠️ **FONCTIONNEL pour enrichment - À adapter pour TaskChain**

**Code existant (ligne 156)** :
```php
$taskId = $this->agentTaskManager->dispatchProjectEnrichment(
    $project,
    $this->getUserIdOrThrow(),
);
```

**Routes existantes** :
- `marketing_project_enrichment_review` (ligne 236)
- `marketing_project_enrichment_generating` (ligne 287) - **Utilise déjà Mercure SSE**

**Action** : ⚠️ **Ajouter nouvelles routes TaskChain** :
- `marketing_project_campaign_chain_start` - Démarre la chaîne complète
- `marketing_project_campaign_chain_progress` - Affiche progression temps réel
- **Garder les routes existantes** pour compatibilité ascendante

---

### ✅ Template `enrichment/generating.html.twig` (EXISTE)

**Statut** : ⚠️ **FONCTIONNEL pour single task - À dupliquer pour TaskChain**

**Fonctionnalités** :
- Stimulus controller `data-controller="marketing-enrichment"`
- Connexion Mercure avec JWT
- Barre progression animée
- Timer temps écoulé

**Action** : ⚠️ **Dupliquer** en `campaign/chain_progress.html.twig` avec :
- 6 étapes visuelles (enrichment → personas → strategy → assets)
- Barre progression réelle basée sur ChainStepCompletedEvent
- Affichage étape en cours

---

## 🔗 EventSubscribers Workflow Existants

### ✅ Subscribers de Chaînage Existants (GARDENT LEUR RÔLE)

**Fichiers** :
- `PersonasGeneratedEventSubscriber.php` - Déclenche génération stratégie
- `StrategyOptimizedEventSubscriber.php` - Déclenche génération assets
- `CompetitorToStrategySubscriber.php` - Intègre analyse concurrence
- `AssetsCompletedEventSubscriber.php` - Finalise campagne

**Action** : ✅ **Aucune modification** - TaskChainOrchestrator les appellera automatiquement via événements Symfony

---

## ❌ Composants À CRÉER (Nouveaux)

### 1️⃣ Service `CampaignChainBuilder`

**Fichier** : `app/src/Service/Marketing/CampaignChainBuilder.php`

**Responsabilité** : Construire la `TaskChainDefinition` pour la génération de campagne complète

```php
public function buildCampaignChain(Project $project, int $userId): TaskChainDefinition
{
    return new TaskChainDefinition(
        chainId: Uuid::v7()->toString(),
        steps: [
            new TaskChainStep('enrichment', ProjectEnrichmentAgent::class, 'enrich'),
            new TaskChainStep('personas', PersonaDevelopmentAgent::class, 'generate'),
            new TaskChainStep('strategy', StrategyAgent::class, 'optimize'),
            new TaskChainStep('assets', AssetGenerationAgent::class, 'generateAll'),
        ],
        userId: $userId,
        context: new AgentContext(userId: $userId, projectId: $project->getId()),
    );
}
```

---

### 2️⃣ EventSubscriber `TaskChainMercureSubscriber`

**Fichier** : `app/src/EventSubscriber/Marketing/TaskChainMercureSubscriber.php`

**Responsabilité** : Publier les événements TaskChain sur Mercure

```php
public function onChainStarted(ChainStartedEvent $event): void
public function onChainStepCompleted(ChainStepCompletedEvent $event): void
public function onChainCompleted(ChainCompletedEvent $event): void
public function onChainFailed(ChainFailedEvent $event): void
```

**Topics Mercure** :
- `/chain/{chainId}` - Événements de chaîne
- `/chain/{chainId}/step/{stepName}` - Progression par étape

---

### 3️⃣ Contrôleur Stimulus `campaign_chain_controller.js`

**Fichier** : `app/assets/controllers/marketing/campaign_chain_controller.js`

**Responsabilité** : Frontend pour progression temps réel TaskChain

```javascript
connectToMercure() {
    const topic = `/chain/${this.chainIdValue}`;

    this.eventSource.addEventListener('ChainStartedEvent', ...);
    this.eventSource.addEventListener('ChainStepCompletedEvent', ...);
    this.eventSource.addEventListener('ChainCompletedEvent', ...);
    this.eventSource.addEventListener('ChainFailedEvent', ...);
}
```

---

## 📋 Tableau Récapitulatif

| Composant | Statut | Action | Temps estimé |
|-----------|--------|--------|--------------|
| **MercureJwtGenerator** | ✅ Complet | Réutiliser tel quel | 0h |
| **MercureNotificationPublisher** | ✅ Complet | Réutiliser tel quel | 0h |
| **MercurePublisherSubscriber** | ⚠️ Quasi-complet | Étendre pour ChainEvents | 1h |
| **AgentExecutionSubscriber** | ✅ Complet | Aucune modification | 0h |
| **enrichment_controller.js** | ⚠️ Fonctionnel | Dupliquer pour TaskChain | 2h |
| **Project Entity** | ⚠️ Manque 2 champs | Ajouter currentChainId + chainStatus | 1h |
| **Asset Entity** | ✅ Complet | Aucune modification | 0h |
| **ProjectController** | ⚠️ Fonctionnel | Ajouter routes TaskChain | 2h |
| **generating.html.twig** | ⚠️ Fonctionnel | Dupliquer pour TaskChain | 1h |
| **EventSubscribers workflow** | ✅ Complets | Aucune modification | 0h |
| **CampaignChainBuilder** | ❌ Nouveau | Créer | 2h |
| **TaskChainMercureSubscriber** | ❌ Nouveau | Créer | 2h |
| **campaign_chain_controller.js** | ❌ Nouveau | Créer | 2h |

**Temps total estimé** : 13h (vs 32h initialement planifié ❌)

---

## 🎯 Conclusion

### Infrastructure Déjà Présente (80%)

- ✅ **Mercure SSE complètement opérationnel**
- ✅ **JWT Generator production-ready**
- ✅ **Workflow de génération de campagne fonctionnel**
- ✅ **Event Subscribers de chaînage automatiques**
- ✅ **Templates et contrôleurs Stimulus pour SSE**

### Modifications Réelles Nécessaires

1. **Backend (7h)** :
   - Créer `CampaignChainBuilder` (2h)
   - Créer `TaskChainMercureSubscriber` (2h)
   - Étendre `MercurePublisherSubscriber` (1h)
   - Ajouter 2 champs à `Project` + migration (1h)
   - Ajouter routes TaskChain à `ProjectController` (2h)

2. **Frontend (6h)** :
   - Dupliquer + adapter `enrichment_controller.js` → `campaign_chain_controller.js` (2h)
   - Dupliquer + adapter template `generating.html.twig` → `chain_progress.html.twig` (1h)
   - Design UI 6 étapes avec progression (2h)
   - Tests E2E Mercure SSE (1h)

---

## 📌 Prochaine Étape

Mettre à jour les plans d'exécution **Phase 1** et **Phase 2** pour :
- Supprimer création de `MercureJwtGenerator` (existe déjà)
- Supprimer création de routes enrichment (existent déjà)
- Focus sur création `CampaignChainBuilder` + `TaskChainMercureSubscriber`
- Réduire estimations de 32h → 13h