# Intégration Bundle Marketing v3.33.0 - Spécifications

**Version Bundle** : v3.33.0 (commit `70828b9`)
**Date** : 2025-11-27
**Objectif** : Profiter des nouvelles fonctionnalités TaskChain et Mercure SSE pour la génération de campagnes

---

## 📊 Vue d'ensemble

### Nouvelles fonctionnalités du bundle v3.33.0

#### 1. TaskChain Orchestration ⭐⭐⭐⭐⭐
**Impact** : TRÈS ÉLEVÉ pour myCfia

- **Problème résolu** : Orchestration manuelle des 6 étapes de génération de campagne
- **Solution** : Chaînes de tâches avec dépendances automatiques
- **Bénéfices** :
  - Passage automatique des résultats entre étapes
  - Gestion erreurs (étapes optionnelles, retries)
  - Events temps réel pour chaque étape
  - Code maintenable et extensible

**Workflow actuel myCfia** (orchestration manuelle) :
```
1. ProjectEnrichmentAgent (60s)
2. CompetitorIntelligenceTool (130s)
3. CompetitorAnalystAgent (30s)
4. StrategyAnalystAgent (45s)
5. PersonaGeneratorAgent (30s)
6. AssetBuilders × 8 (60s)
Total : ~355s (5min55s) en séquentiel
```

**Avec TaskChain** : Orchestration automatique + events temps réel

---

#### 2. Mercure SSE & Topics standardisés ⭐⭐⭐⭐⭐
**Impact** : TRÈS ÉLEVÉ pour l'UX

- **Problème résolu** : Utilisateur attend 5min55s sans feedback
- **Solution** : Notifications temps réel via Mercure SSE
- **Bénéfices** :
  - Barre de progression en temps réel
  - Affichage des étapes en cours
  - Résultats partiels (personas dès qu'elles sont générées)
  - Topics standardisés (`/marketing/chains/{chainId}/step/completed`)

---

## 🎯 Objectifs d'intégration

### Phase 1 : TaskChain Orchestration
**Durée** : 2-3 jours
**Priorité** : HAUTE

**Livrables** :
- Service `CampaignChainBuilder` pour construire la chaîne de génération
- Adaptation du contrôleur `ProjectController::generateCampaign()`
- Migration de l'orchestration manuelle vers TaskChain
- Tests unitaires et d'intégration

---

### Phase 2 : Mercure SSE Integration
**Durée** : 2-3 jours
**Priorité** : HAUTE

**Livrables** :
- `MercureNotificationPublisher` implémentant `NotificationPublisherInterface`
- `TaskChainMercureSubscriber` pour publier les events
- Contrôleur Stimulus `campaign_progress_controller.js`
- Template Twig avec barre de progression temps réel
- Générateur JWT Mercure pour authentification frontend

---

### Phase 3 : Tests & Déploiement
**Durée** : 2 jours
**Priorité** : HAUTE

**Livrables** :
- Tests unitaires complets
- Tests d'intégration TaskChain + Mercure
- Tests E2E avec génération de campagne complète
- Documentation utilisateur
- Déploiement en production

---

## 📚 Architecture cible

### Workflow TaskChain

```
┌─────────────────────────────────────────────────────────────────┐
│                    CampaignChainBuilder                          │
│   Construit TaskChainDefinition avec 6 étapes + dépendances     │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              AgentTaskManager::dispatchChain()                   │
│   → TaskChainOrchestrator::startChain()                          │
└─────────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┼───────────────┬───────────────┐
              ▼               ▼               ▼               ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│ Enrichissement  │ │ Détection       │ │ Stratégie       │ │ Assets         │
│ (racine)        │ │ dépend: [1]     │ │ dépend: [2,3]   │ │ dépend: [4,5]  │
└─────────────────┘ └─────────────────┘ └─────────────────┘ └─────────────────┘
        │                   │                   │                   │
        └───────────────────┴───────────────────┴───────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              Events → TaskChainMercureSubscriber                 │
│   Publie vers Mercure Hub : /marketing/chains/{id}/step/completed│
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Frontend EventSource                          │
│   Reçoit events → Barre progression + Résultats partiels        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🗂️ Structure de fichiers

### Nouveaux fichiers à créer

```
app/
├── src/
│   ├── Service/
│   │   ├── Marketing/
│   │   │   └── CampaignChainBuilder.php          # Phase 1
│   │   └── MercureNotificationPublisher.php       # Phase 2
│   │
│   └── EventSubscriber/
│       └── Marketing/
│           └── TaskChainMercureSubscriber.php     # Phase 2
│
├── assets/
│   └── controllers/
│       └── campaign_progress_controller.js        # Phase 2
│
├── templates/
│   └── marketing/
│       └── project/
│           └── _campaign_progress.html.twig       # Phase 2
│
└── tests/
    ├── Unit/
    │   └── Service/
    │       └── Marketing/
    │           └── CampaignChainBuilderTest.php   # Phase 3
    │
    └── Integration/
        └── Marketing/
            └── CampaignChainFlowTest.php          # Phase 3
```

### Fichiers à modifier

```
app/
├── src/
│   ├── Controller/
│   │   └── Marketing/
│   │       └── ProjectController.php              # Phase 1 & 2
│   │
│   └── Entity/
│       └── Marketing/
│           └── Project.php                        # Phase 1 (ajouter currentChainId)
│
├── config/
│   ├── services.yaml                              # Phase 1 & 2
│   └── packages/
│       └── mercure.yaml                           # Phase 2 (déjà configuré)
│
└── migrations/                                    # Phase 1
    └── VersionXXX_AddCurrentChainIdToProject.php
```

---

## 📋 Plans d'exécution détaillés

Les plans d'exécution détaillés se trouvent dans :

- **Phase 1** : `CONTEXT_ENGINEERING/PLANS/PLAN_Bundle_v3.33.0_Phase1_TaskChain.md`
- **Phase 2** : `CONTEXT_ENGINEERING/PLANS/PLAN_Bundle_v3.33.0_Phase2_Mercure.md`
- **Phase 3** : `CONTEXT_ENGINEERING/PLANS/PLAN_Bundle_v3.33.0_Phase3_Tests.md`

---

## 🎯 Métriques de succès

### Phase 1 : TaskChain
- ✅ Génération de campagne orchestrée automatiquement
- ✅ Passage des résultats entre étapes fonctionnel
- ✅ Gestion d'erreurs (échec d'une étape)
- ✅ Code contrôleur simplifié (< 50 lignes)

### Phase 2 : Mercure SSE
- ✅ Barre de progression temps réel fonctionnelle
- ✅ Affichage de l'étape en cours
- ✅ Résultats partiels affichés (personas, stratégie)
- ✅ Notifications en cas d'erreur

### Phase 3 : Tests
- ✅ Couverture tests unitaires ≥ 80%
- ✅ Tests d'intégration TaskChain complets
- ✅ Tests E2E avec Mercure SSE
- ✅ Zéro régression sur les fonctionnalités existantes

---

## 📖 Ressources

### Documentation bundle v3.33.0
- `vendor/gorillias/marketing-ai-bundle/docs/guides/mercure-sse-integration.md`
- `vendor/gorillias/marketing-ai-bundle/docs/guides/taskchain-orchestration.md`
- `vendor/gorillias/marketing-ai-bundle/CHANGELOG.md` (v3.33.0)

### Standards myCfia
- `CONTEXT_ENGINEERING/BEST_PRACTICES/CODING_STANDARDS.md`
- `CONTEXT_ENGINEERING/BEST_PRACTICES/SYMFONY_ARCHITECTURE.md`
- `CONTEXT_ENGINEERING/BEST_PRACTICES/GIT_WORKFLOW.md`

---

## ⚠️ Points d'attention

### Performance
- CompetitorIntelligenceTool prend ~130s → doit être asynchrone
- TaskChain gère l'async via Messenger
- Mercure SSE : max ~20 updates/seconde (batching si nécessaire)

### Sécurité
- JWT Mercure avec topics limités par projet
- Authentification utilisateur pour subscription SSE
- Validation des permissions avant dispatch TaskChain

### Compatibilité
- Mercure Hub déjà configuré (docker-compose.yml)
- Symfony Messenger déjà configuré
- Pas de breaking changes sur l'API existante

---

## 🚀 Ordre d'exécution recommandé

1. **Lire** : `PLAN_Bundle_v3.33.0_Phase1_TaskChain.md`
2. **Exécuter** : Phase 1 (2-3 jours)
3. **Valider** : Tests unitaires + génération campagne complète
4. **Lire** : `PLAN_Bundle_v3.33.0_Phase2_Mercure.md`
5. **Exécuter** : Phase 2 (2-3 jours)
6. **Valider** : Tests E2E avec barre de progression
7. **Lire** : `PLAN_Bundle_v3.33.0_Phase3_Tests.md`
8. **Exécuter** : Phase 3 (2 jours)
9. **Déployer** : Production

**Durée totale estimée** : **6-8 jours** (1.5 à 2 semaines)

---

**Dernière mise à jour** : 2025-11-27
**Maintenu par** : Context Engineering