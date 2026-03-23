# 📋 DAP - Plan d'Implémentation Détaillé

**Projet :** myCFiA Design System - Digital Adoption Platform
**Date :** 2026-01-19
**Version :** 1.0

---

## 📑 Table des Matières

1. [Phase 1 : Dashboard Onboarding](#phase-1--dashboard-onboarding)
2. [Phase 2 : Workflow Steps 1-3](#phase-2--workflow-steps-1-3)
3. [Phase 3 : Workflow Steps 4-8](#phase-3--workflow-steps-4-8)
4. [Phase 4 : Analytics & Optimisation](#phase-4--analytics--optimisation)

---

## Phase 1 : Dashboard Onboarding

**Durée estimée :** 2-3 jours
**Priorité :** 🔴 HAUTE
**Objectif :** Accueillir et guider les nouveaux utilisateurs sur le dashboard

---

### ✅ Tâche 1.1 : Créer le CSS DAP

**Fichier :** `assets/css/components/_onboarding-dap.css`

#### Sous-tâches
- [ ] Créer le fichier CSS
- [ ] Définir styles Welcome Modal
- [ ] Définir styles Overlay + Spotlight
- [ ] Définir styles Hotspot pulsant
- [ ] Définir styles Tooltip contextuel
- [ ] Définir animations (pulse, fade-in, wiggle)
- [ ] Définir styles Progress dots
- [ ] Définir styles Help button
- [ ] Définir styles Feature badges
- [ ] Ajouter media queries responsive
- [ ] Tester compatibilité thèmes (light, dark-blue, dark-red)

#### Validation
```bash
# Vérifier que le fichier compile sans erreur
# Tester sur les 3 thèmes
# Valider animations 60fps
```

#### Estimation
⏱️ 4-6 heures

---

### ✅ Tâche 1.2 : Créer le JavaScript DAP

**Fichier :** `assets/js/components/onboarding-dap.js`

#### Sous-tâches
- [ ] Créer la classe `OnboardingDAP`
- [ ] Implémenter `init()` - initialisation
- [ ] Implémenter `showWelcomeModal()` - affichage modal
- [ ] Implémenter `startTour()` - démarrage tour
- [ ] Implémenter `createOverlay()` - création overlay/spotlight
- [ ] Implémenter `showStep()` - affichage étape
- [ ] Implémenter `positionSpotlight()` - positionnement spotlight
- [ ] Implémenter `createHotspot()` - création hotspot pulsant
- [ ] Implémenter `createTooltip()` - création tooltip
- [ ] Implémenter `nextStep()` - navigation suivant
- [ ] Implémenter `skipTour()` - skip tour
- [ ] Implémenter `completeTour()` - fin tour
- [ ] Implémenter `createHelpButton()` - bouton aide
- [ ] Implémenter LocalStorage persistence
- [ ] Gérer événements (resize, keyboard)
- [ ] Ajouter export `initOnboardingDAP()`

#### Configuration Étapes Dashboard
```javascript
const ONBOARDING_STEPS = [
    {
        id: 'welcome',
        type: 'modal',
        target: null,
    },
    {
        id: 'dashboard-overview',
        type: 'hotspot',
        target: '.content',
        title: 'Votre Dashboard',
        content: 'Retrouvez ici toutes vos campagnes...',
    },
    {
        id: 'new-campaign-button',
        type: 'hotspot',
        target: '.btn-ai',
        title: 'Créer une Campagne',
        content: 'Cliquez ici pour démarrer...',
    },
    {
        id: 'sidebar-navigation',
        type: 'hotspot',
        target: '.nav-section:first-child',
        title: 'Navigation Marketing',
        content: 'Accédez rapidement...',
    },
    {
        id: 'campaign-cards',
        type: 'hotspot',
        target: '.card:first-child',
        title: 'Vos Campagnes',
        content: 'Cliquez sur une campagne...',
    },
];
```

#### Validation
```bash
# Vérifier initialisation sans erreur console
# Tester modal s'ouvre après 1s (première visite)
# Tester tour guidé fonctionne (4 étapes)
# Tester skip tour marque localStorage
# Tester help button relance tour
# Tester responsive (resize window)
```

#### Estimation
⏱️ 6-8 heures

---

### ✅ Tâche 1.3 : Intégrer CSS dans main.css

**Fichier :** `assets/css/main.css`

#### Modifications
```css
/* Ajouter après les autres imports de composants */
@import 'components/_campaign-stepper.css';
@import 'components/_campaign-forms.css';
@import 'components/_campaign-loader.css';
/* ... */
@import 'components/_onboarding-dap.css';  /* ⬅️ AJOUTER ICI */
```

#### Validation
```bash
# Vérifier que main.css compile
# Vérifier pas de conflits CSS
# Tester sur page dashboard
```

#### Estimation
⏱️ 15 minutes

---

### ✅ Tâche 1.4 : Intégrer JS dans main.js

**Fichier :** `assets/js/main.js`

#### Modifications

**1. Ajouter l'import**
```javascript
// ... imports existants
import { initCampaignAssetsPlanning } from "./components/campaign-assets-planning.js";
import { initOnboardingDAP } from "./components/onboarding-dap.js";  // ⬅️ AJOUTER
import "./components/campaign-stepper.js";
```

**2. Ajouter l'initialisation**
```javascript
document.addEventListener("DOMContentLoaded", () => {
    // ... initialisations existantes

    // 14. Initialiser l'onboarding DAP (si dashboard)
    if (window.location.pathname.includes('dashboard')) {
        console.log("[main] Initialisation de l'onboarding DAP...");
        initOnboardingDAP();
    }

    console.log("myCFiA Design System - Prêt ✓");
});
```

#### Validation
```bash
# Vérifier console log "[main] Initialisation de l'onboarding DAP..."
# Vérifier pas d'erreurs console
# Tester initialisation seulement sur dashboard
```

#### Estimation
⏱️ 15 minutes

---

### ✅ Tâche 1.5 : Intégrer Modal dans dashboard_light.html

**Fichier :** `campaign_generation/dashboard_light.html`

#### Localisation
Ajouter avant la fermeture de `</main>` (ligne ~400)

#### Code à Insérer
```html
<!-- ========================================
     ONBOARDING WELCOME MODAL
     ======================================== -->
<div class="modal fade" id="onboardingWelcomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-rocket-takeoff" style="color: var(--color-primary); font-size: 1.5rem;"></i>
                    <span>Bienvenue dans Mes Campagnes !</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <p class="lead text-secondary mb-4">
                    Créez des campagnes marketing complètes assistées par IA en 8 étapes simples.
                </p>

                <!-- 4 bénéfices clés -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded" style="background: rgba(0, 48, 128, 0.1); color: var(--color-primary);">
                                <i class="bi bi-lightning-charge-fill fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Génération Automatique</h6>
                                <p class="small text-secondary mb-0">L'IA analyse votre projet et génère contenus, personas et recommandations</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded" style="background: rgba(0, 48, 128, 0.1); color: var(--color-primary);">
                                <i class="bi bi-diagram-3-fill fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Workflow Guidé</h6>
                                <p class="small text-secondary mb-0">8 étapes claires pour créer une campagne multi-canaux professionnelle</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded" style="background: rgba(0, 48, 128, 0.1); color: var(--color-primary);">
                                <i class="bi bi-check2-circle fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Validation Étape par Étape</h6>
                                <p class="small text-secondary mb-0">Vous gardez le contrôle total sur chaque proposition de l'IA</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 rounded" style="background: rgba(0, 48, 128, 0.1); color: var(--color-primary);">
                                <i class="bi bi-graph-up-arrow fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Analytics Temps Réel</h6>
                                <p class="small text-secondary mb-0">Suivez les performances de vos campagnes en direct</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerte info -->
                <div class="alert alert-info d-flex align-items-center gap-2 mb-0">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>
                        <strong>Première visite ?</strong>
                        <span class="text-secondary">Nous vous recommandons de faire le tour guidé (2 minutes)</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>
                    Passer
                </button>
                <button type="button" class="btn btn-primary" id="startOnboardingTour">
                    <i class="bi bi-play-circle me-1"></i>
                    Faire le tour guidé
                </button>
            </div>
        </div>
    </div>
</div>
```

#### Validation
```bash
# Ouvrir dashboard_light.html
# Vérifier modal s'affiche après 1s (première visite)
# Tester bouton "Passer" ferme modal
# Tester bouton "Faire le tour guidé" lance tour
```

#### Estimation
⏱️ 30 minutes

---

### ✅ Tâche 1.6 : Tests Phase 1

#### Tests Fonctionnels
- [ ] Modal s'affiche après 1s (première visite uniquement)
- [ ] Bouton "Passer" ferme modal et marque localStorage
- [ ] Bouton "Faire le tour guidé" lance overlay + spotlight
- [ ] Étape 1/4 : Dashboard overview (spotlight sur .content)
- [ ] Étape 2/4 : Bouton nouvelle campagne (spotlight sur .btn-ai)
- [ ] Étape 3/4 : Navigation sidebar (spotlight sur .nav-section)
- [ ] Étape 4/4 : Cards campagnes (spotlight sur .card:first-child)
- [ ] Progress dots mis à jour à chaque étape
- [ ] Bouton "Suivant" fonctionne
- [ ] Bouton "Passer le tour" fonctionne
- [ ] Bouton "Terminer" (étape 4) ferme overlay
- [ ] Help button (bas gauche) relance tour
- [ ] Help button animation wiggle visible

#### Tests Cross-Browser
- [ ] Chrome 90+
- [ ] Firefox 88+
- [ ] Safari 14+
- [ ] Edge 90+

#### Tests Responsive
- [ ] Desktop 1920x1080
- [ ] Laptop 1366x768
- [ ] Tablet 768x1024 (simplifier tooltip si nécessaire)

#### Tests Thèmes
- [ ] Light theme
- [ ] Dark-blue theme
- [ ] Dark-red theme

#### Tests Accessibilité
- [ ] Navigation clavier (Tab, Enter, Escape)
- [ ] ARIA labels présents
- [ ] Screen reader compatible
- [ ] Contraste couleurs WCAG AA

#### Estimation
⏱️ 3-4 heures

---

### ✅ Tâche 1.7 : Documentation Phase 1

#### Fichiers à Créer/Mettre à Jour
- [ ] Ajouter commentaires inline dans CSS
- [ ] Ajouter JSDoc dans JavaScript
- [ ] Mettre à jour `DAP_OVERVIEW.md` (statut Phase 1 ✅)
- [ ] Créer screenshots tour guidé
- [ ] Documenter LocalStorage keys utilisés

#### Estimation
⏱️ 1-2 heures

---

### 📊 Récapitulatif Phase 1

| Tâche | Durée | Status |
|-------|-------|--------|
| 1.1 Créer CSS | 4-6h | ✅ |
| 1.2 Créer JS | 6-8h | ✅ |
| 1.3 Intégrer CSS | 15min | ✅ |
| 1.4 Intégrer JS | 15min | ✅ |
| 1.5 Intégrer Modal HTML | 30min | ✅ |
| 1.6 Tests | 3-4h | ⏳ Tests manuels requis |
| 1.7 Documentation | 1-2h | ✅ |
| **TOTAL** | **15-22h** | **✅ COMPLÉTÉ (tests manuels requis)** |

---

## 🎉 Phase 1 - Résultat Final

**Date de complétion :** 2026-01-19
**Durée réelle :** 1 jour (vs 2-3 jours estimés)

### Fichiers Créés
1. `assets/css/components/_onboarding-dap.css` (631 lignes)
2. `assets/js/components/onboarding-dap.js` (573 lignes)
3. `DAP_PHASE1_TEST_REPORT.md` (rapport tests détaillé)

### Fichiers Modifiés
1. `assets/css/main.css` (ajout import)
2. `assets/js/main.js` (ajout import + init)
3. `campaign_generation/dashboard_light.html` (ajout modal 88 lignes)
4. `DAP_OVERVIEW.md` (mise à jour statut Phase 1)

### Composants Livrés
- ✅ Welcome Modal (Bootstrap, 4 bénéfices, zéro style inline)
- ✅ Guided Tour (4 étapes : dashboard overview, btn campagne, sidebar, cards)
- ✅ Overlay + Spotlight (animation pulse)
- ✅ Hotspots pulsants
- ✅ Tooltips contextuels (4 positions dynamiques)
- ✅ Progress dots (1/4, 2/4, 3/4, 4/4)
- ✅ Help button FAB (bas gauche, animation wiggle)
- ✅ Toast félicitation (fin de tour)
- ✅ LocalStorage persistence (4 keys)

### Architecture Respectée
- ✅ AssetMapper : zéro style inline, zéro script inline
- ✅ CSS : fichier dédié `_onboarding-dap.css`
- ✅ JS : module ES6 avec export
- ✅ Variables CSS : héritage design system
- ✅ Responsive : desktop/tablet/mobile
- ✅ Thèmes : light, dark-blue, dark-red (variables héritées)
- ✅ Accessibilité : ARIA, focus-visible, prefers-reduced-motion

### Documentation
- ✅ JSDoc complet (JavaScript)
- ✅ Commentaires inline (CSS)
- ✅ Rapport tests `DAP_PHASE1_TEST_REPORT.md`
- ✅ Mise à jour `DAP_OVERVIEW.md`

### Prochaines Étapes
1. **Tests manuels** (26 tests identifiés dans rapport)
2. **Validation stakeholders** (design, messages)
3. **Tests utilisateurs** (5-10 personnes)
4. **Ajustements post-feedback**
5. **Déploiement Phase 1**
6. **Phase 2** : Tooltips Steps 1-3

---

## Phase 2 : Workflow Steps 1-3

**Durée estimée :** 3-4 jours
**Priorité :** 🟡 MOYENNE
**Objectif :** Ajouter tooltips contextuels et mini-tours sur Steps 1-3

---

### ✅ Tâche 2.1 : Step 1 - Tooltips Projet

**Fichier :** `campaign_generation/step1_create_light.html`

#### Tooltips à Ajouter

**1. Tooltip "Analyse IA du site web"**
```html
<!-- À modifier ligne ~241 -->
<label class="form-label">
    Site web (optionnel)
    <button type="button"
            class="btn btn-link p-0 ms-1"
            data-bs-toggle="tooltip"
            data-bs-placement="right"
            data-bs-custom-class="onboarding-tooltip"
            title="L'IA analysera votre site pour extraire automatiquement : identité de marque, palette de couleurs, typographie, proposition de valeur et mots-clés Google Ads.">
        <i class="bi bi-info-circle text-primary"></i>
    </button>
</label>
```

**2. Mini-tour Step 1 (3 étapes)**
- Étape 1 : Formulaire de base (spotlight)
- Étape 2 : Objectifs SMART (spotlight)
- Étape 3 : Budget & Timeline (spotlight)

#### Configuration JS
```javascript
// Ajouter dans onboarding-dap.js
const STEP1_TOUR_STEPS = [
    {
        id: 'step1-basic-info',
        target: '.form-section:nth-child(1)',
        title: 'Informations de Base',
        content: 'Renseignez votre projet, entreprise et secteur d\'activité.',
    },
    {
        id: 'step1-objectives',
        target: '.form-section:nth-child(2)',
        title: 'Objectifs Marketing',
        content: 'Définissez vos objectifs SMART (Spécifiques, Mesurables, Atteignables).',
    },
    {
        id: 'step1-budget',
        target: '.form-section:nth-child(3)',
        title: 'Budget & Timeline',
        content: 'Indiquez votre budget et les dates de début/fin de campagne.',
    },
];
```

#### Estimation
⏱️ 3-4 heures

---

### ✅ Tâche 2.2 : Step 2 - Tooltips Concurrents

**Fichier :** `campaign_generation/step2_validate_light.html`

#### Tooltips à Ajouter

**1. Tooltip Badges Concurrents**
```html
<!-- Légende badges avec tooltips -->
<div class="d-flex gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-danger">Concurrent Direct</span>
        <button type="button"
                class="btn btn-link p-0"
                data-bs-toggle="tooltip"
                title="Score >85% : chevauchement important de votre offre et marché cible">
            <i class="bi bi-info-circle text-primary"></i>
        </button>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-warning">Concurrent Important</span>
        <button type="button"
                class="btn btn-link p-0"
                data-bs-toggle="tooltip"
                title="Score 75-85% : chevauchement modéré, à surveiller">
            <i class="bi bi-info-circle text-primary"></i>
        </button>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-info">Concurrent Indirect</span>
        <button type="button"
                class="btn btn-link p-0"
                data-bs-toggle="tooltip"
                title="Score <75% : chevauchement faible, marché adjacent">
            <i class="bi bi-info-circle text-primary"></i>
        </button>
    </div>
</div>
```

**2. Mini-tour Step 2 (2 étapes)**
- Étape 1 : Sélection concurrents détectés
- Étape 2 : Ajout manuel concurrent

#### Estimation
⏱️ 2-3 heures

---

### ✅ Tâche 2.3 : Step 3 - Tooltips Personas & Profilia

**Fichier :** `campaign_generation/step3_select_light.html`

#### Tooltips à Ajouter

**1. Tooltip Profilia**
```html
<!-- Dans sidebar Profilia -->
<h5 class="d-flex align-items-center gap-2">
    Enrichissement Profilia
    <button type="button"
            class="btn btn-link p-0"
            data-bs-toggle="tooltip"
            data-bs-html="true"
            title="<strong>Profilia</strong> est une base de données B2B de 48M de profils avec 150+ critères de segmentation. L'enrichissement permet d'estimer le reach potentiel de vos personas sélectionnés.">
        <i class="bi bi-info-circle text-primary"></i>
    </button>
</h5>
```

**2. Tooltip Scores Personas**
```html
<!-- Sur chaque persona card -->
<div class="persona-score">
    92%
    <button type="button"
            class="btn btn-link p-0 ms-1"
            data-bs-toggle="tooltip"
            title="Score de pertinence basé sur l'alignement avec votre projet, concurrents et marché cible">
        <i class="bi bi-info-circle-fill text-white" style="font-size: 0.8rem;"></i>
    </button>
</div>
```

**3. Mini-tour Step 3 (3 étapes)**
- Étape 1 : Sélection personas (min 1)
- Étape 2 : Bouton "Analyser avec Profilia" (optionnel)
- Étape 3 : Validation & passage modal sources

#### Estimation
⏱️ 3-4 heures

---

### ✅ Tâche 2.4 : Initialiser Tooltips Bootstrap

**Fichier :** `assets/js/components/onboarding-dap.js`

#### Code à Ajouter
```javascript
/**
 * Initialiser les tooltips Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover focus',
            boundary: 'window',
            customClass: 'onboarding-tooltip',
        });
    });
}

// Ajouter dans init()
init() {
    // ... code existant

    // Initialiser tooltips Bootstrap
    initTooltips();

    // ... reste du code
}
```

#### CSS Tooltip Custom
```css
/* Dans _onboarding-dap.css */
.tooltip.onboarding-tooltip .tooltip-inner {
    background: var(--bg-card);
    color: var(--text-primary);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-lg);
    padding: var(--spacing-md);
    text-align: left;
    max-width: 300px;
    font-size: var(--font-size-sm);
}

.tooltip.onboarding-tooltip .tooltip-arrow::before {
    border-top-color: var(--border);
}
```

#### Estimation
⏱️ 1-2 heures

---

### ✅ Tâche 2.5 : Tests Phase 2

#### Tests Fonctionnels Steps 1-3
- [ ] Step 1 : Tooltip "Analyse IA" s'affiche au hover
- [ ] Step 1 : Mini-tour fonctionne (3 étapes)
- [ ] Step 2 : Tooltips badges concurrents s'affichent
- [ ] Step 2 : Mini-tour fonctionne (2 étapes)
- [ ] Step 3 : Tooltip Profilia s'affiche
- [ ] Step 3 : Tooltip scores personas s'affiche
- [ ] Step 3 : Mini-tour fonctionne (3 étapes)
- [ ] Tous les tooltips respectent thème actif

#### Estimation
⏱️ 2-3 heures

---

### 📊 Récapitulatif Phase 2

| Tâche | Durée | Status |
|-------|-------|--------|
| 2.1 Step 1 Tooltips | 3-4h | ⏳ |
| 2.2 Step 2 Tooltips | 2-3h | ⏳ |
| 2.3 Step 3 Tooltips | 3-4h | ⏳ |
| 2.4 Init Tooltips Bootstrap | 1-2h | ⏳ |
| 2.5 Tests | 2-3h | ⏳ |
| **TOTAL** | **11-16h** | **⏳** |

---

## Phase 3 : Workflow Steps 4-8

**Durée estimée :** 2-3 jours
**Priorité :** 🟢 BASSE
**Objectif :** Compléter tooltips sur Steps 4-8

---

### ✅ Tâche 3.1 : Step 4 - Tooltips Upload Contacts

#### Tooltips Principaux
- Format fichiers acceptés (CSV, Excel)
- Mapping colonnes automatique
- Validation RGPD

#### Estimation
⏱️ 2 heures

---

### ✅ Tâche 3.2 : Step 5 - Tooltips Stratégie

#### Tooltips Principaux
- Génération stratégie IA
- Validation approche

#### Estimation
⏱️ 2 heures

---

### ✅ Tâche 3.3 : Step 6 - Tooltips Canaux

#### Tooltips Principaux
- Sélection multi-canaux
- Répartition budget (doit = 100%)
- Prévisions ROI par canal

#### Estimation
⏱️ 2 heures

---

### ✅ Tâche 3.4 : Step 7 - Tooltips Assets

#### Tooltips Principaux
- Types d'assets par canal
- Édition/personnalisation
- Tests A/B

#### Estimation
⏱️ 2 heures

---

### ✅ Tâche 3.5 : Step 8 - Tooltips Planning

#### Tooltips Principaux
- Calendrier interactif (FullCalendar)
- Horaires optimaux IA
- Frequency capping

#### Estimation
⏱️ 2 heures

---

### ✅ Tâche 3.6 : Tests Phase 3

#### Tests Fonctionnels Steps 4-8
- [ ] Tous les tooltips s'affichent correctement
- [ ] Aucun conflit avec composants existants
- [ ] Thèmes respectés

#### Estimation
⏱️ 2 heures

---

### 📊 Récapitulatif Phase 3

| Tâche | Durée | Status |
|-------|-------|--------|
| 3.1 Step 4 | 2h | ⏳ |
| 3.2 Step 5 | 2h | ⏳ |
| 3.3 Step 6 | 2h | ⏳ |
| 3.4 Step 7 | 2h | ⏳ |
| 3.5 Step 8 | 2h | ⏳ |
| 3.6 Tests | 2h | ⏳ |
| **TOTAL** | **12h** | **⏳** |

---

## Phase 4 : Analytics & Optimisation

**Durée estimée :** Ongoing
**Priorité :** 🔵 CONTINUE

---

### ✅ Tâche 4.1 : Intégration Analytics

#### Events à Tracker
```javascript
// Onboarding events
'onboarding_modal_shown'
'onboarding_tour_started'
'onboarding_tour_completed'
'onboarding_tour_skipped'
'onboarding_step_viewed' (avec step_id)
'onboarding_help_button_clicked'

// Tooltip events
'tooltip_shown' (avec tooltip_id)
'tooltip_clicked' (si lien dans tooltip)
```

#### Implémentation Google Analytics / Mixpanel
```javascript
// Dans onboarding-dap.js
function trackEvent(eventName, eventData = {}) {
    // Google Analytics 4
    if (window.gtag) {
        gtag('event', eventName, eventData);
    }

    // Mixpanel
    if (window.mixpanel) {
        mixpanel.track(eventName, eventData);
    }

    // dataLayer (GTM)
    if (window.dataLayer) {
        window.dataLayer.push({
            event: eventName,
            ...eventData,
        });
    }
}
```

#### Estimation
⏱️ 3-4 heures

---

### ✅ Tâche 4.2 : A/B Testing

#### Tests à Réaliser
- **Test A** : Message modal bienvenue (version actuelle)
- **Test B** : Message modal bienvenue (plus court)
- **Test C** : Pas de modal, seulement help button

#### Métriques
- Tour completion rate
- Step 1 → Step 8 completion rate
- Time to first campaign created

#### Estimation
⏱️ Ongoing (2-3 semaines)

---

### ✅ Tâche 4.3 : Optimisation basée Feedbacks

#### Sources Feedback
- Analytics métriques
- Tests utilisateurs (5-10 personnes)
- Support tickets
- NPS surveys

#### Actions Correctives
- Ajuster messages tooltips
- Modifier ordre étapes tour
- Ajouter/supprimer étapes
- Améliorer animations

#### Estimation
⏱️ Ongoing

---

### 📊 Récapitulatif Phase 4

| Tâche | Durée | Status |
|-------|-------|--------|
| 4.1 Analytics | 3-4h | ⏳ |
| 4.2 A/B Testing | 2-3 semaines | ⏳ |
| 4.3 Optimisation | Ongoing | ⏳ |

---

## 📅 Timeline Global

```
┌─────────────────────────────────────────────────────────────┐
│ SEMAINE 1 (19-26 Janvier 2026)                              │
├─────────────────────────────────────────────────────────────┤
│ Lun 19 : Phase 1 - Tâches 1.1, 1.2 (CSS + JS)              │
│ Mar 20 : Phase 1 - Tâches 1.2 fin, 1.3, 1.4 (intégrations) │
│ Mer 21 : Phase 1 - Tâche 1.5 (HTML), 1.6 début (tests)     │
│ Jeu 22 : Phase 1 - Tâche 1.6 fin (tests), 1.7 (docs)       │
│ Ven 23 : Phase 1 - Ajustements, polish                      │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ SEMAINE 2 (27 Janvier - 2 Février 2026)                    │
├─────────────────────────────────────────────────────────────┤
│ Lun 27 : Phase 2 - Tâche 2.1 (Step 1 tooltips)             │
│ Mar 28 : Phase 2 - Tâche 2.2, 2.3 (Steps 2-3 tooltips)     │
│ Mer 29 : Phase 2 - Tâche 2.4 (init tooltips Bootstrap)     │
│ Jeu 30 : Phase 2 - Tâche 2.5 (tests)                       │
│ Ven 31 : Tests utilisateurs (5-10 personnes)                │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ SEMAINE 3 (3-9 Février 2026)                               │
├─────────────────────────────────────────────────────────────┤
│ Lun 3  : Phase 3 - Tâches 3.1, 3.2 (Steps 4-5)             │
│ Mar 4  : Phase 3 - Tâches 3.3, 3.4 (Steps 6-7)             │
│ Mer 5  : Phase 3 - Tâche 3.5 (Step 8)                      │
│ Jeu 6  : Phase 3 - Tâche 3.6 (tests)                       │
│ Ven 7  : Phase 4 - Tâche 4.1 (analytics)                   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ SEMAINE 4+ (10 Février+ 2026)                              │
├─────────────────────────────────────────────────────────────┤
│ Lun 10+  : Phase 4 - A/B testing (ongoing)                 │
│ ...      : Phase 4 - Optimisation basée métriques          │
│ ...      : Maintenance, ajouts features                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 Critères de Succès

### Phase 1 (Dashboard)
- ✅ Modal bienvenue s'affiche (première visite)
- ✅ Tour guidé fonctionne (4 étapes)
- ✅ Help button relance tour
- ✅ LocalStorage persistence OK
- ✅ Compatible 3 thèmes
- ✅ Tests cross-browser OK

### Phase 2 (Steps 1-3)
- ✅ Tooltips contextuels fonctionnels
- ✅ Mini-tours par step fonctionnels
- ✅ Aucun conflit avec code existant

### Phase 3 (Steps 4-8)
- ✅ Tous tooltips implémentés
- ✅ Couverture complète workflow

### Phase 4 (Analytics)
- ✅ Analytics intégrés
- ✅ A/B tests lancés
- ✅ Métriques monitored

---

## 📝 Notes Importantes

### LocalStorage Keys
```javascript
'mycfia_onboarding_completed'     // Boolean - Tour complété
'mycfia_onboarding_step'          // Number - Dernière étape vue
'mycfia_onboarding_skipped_at'    // Timestamp - Quand skip
'mycfia_tooltip_dismissed'        // Array - IDs tooltips fermés
```

### Bootstrap Tooltip Config
```javascript
{
    trigger: 'hover focus',
    boundary: 'window',
    customClass: 'onboarding-tooltip',
    html: true,  // Si HTML dans title
    delay: { show: 300, hide: 100 },
}
```

### Animations Performance
- Utiliser `transform` et `opacity` (GPU-accelerated)
- Éviter `width`, `height`, `top`, `left` (reflow coûteux)
- Préférer `requestAnimationFrame` si animations JS

---

**Dernière mise à jour :** 2026-01-19
**Version :** 1.0
**Prochaine révision :** Après Phase 1 complétée