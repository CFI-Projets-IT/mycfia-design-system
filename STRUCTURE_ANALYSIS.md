# Analyse Structure Mockups Existants
**Date** : 2025-12-30
**Objectif** : Documenter la structure des mockups campaign_generation pour répliquer dans Upload & Mapping

---

## 📁 Structure des fichiers

### Organisation des mockups
```
/campaign_generation/
  ├─ analytics_light.html
  ├─ analytics_dark-blue.html
  ├─ analytics_dark-red.html
  ├─ campaign_show_light.html
  ├─ campaign_show_dark-blue.html
  ├─ campaign_show_dark-red.html
  ├─ dashboard_light.html
  ├─ dashboard_dark-blue.html
  ├─ dashboard_dark-red.html
  ├─ step1_create_light.html
  ├─ step1_create_dark-blue.html
  ├─ step1_create_dark-red.html
  └─ ... (16 pages × 3 thèmes = 48 fichiers)
```

**Convention de nommage** : `{page}_{theme}.html`
- `{page}` : Nom de la page/état (step1_create, dashboard, analytics, etc.)
- `{theme}` : light / dark-blue / dark-red

---

## 🏗️ Structure HTML standard

### 1. Head (lignes 1-20)

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Titre de la page | myCFiA</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Design System myCFiA -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/themes/light.css" id="theme-stylesheet">
    <!-- OU dark-blue.css OU dark-red.css selon le thème -->
</head>
```

**Points clés** :
- ✅ Bootstrap 5.3.8 depuis CDN
- ✅ Bootstrap Icons depuis CDN
- ✅ Google Fonts Poppins (400, 600, 700, 800)
- ✅ main.css TOUJOURS importé
- ✅ Thème spécifique importé ensuite avec id="theme-stylesheet"
- ✅ Chemin relatif `../assets/css/` (car mockups dans `/campaign_generation/`)

---

### 2. Body > App Container (ligne 21-22)

```html
<body>
    <div class="app">
```

**Structure** : Conteneur principal `.app` pour layout flex

---

### 3. Sidebar (lignes 23-103)

```html
<aside class="sidebar d-flex flex-column">
    <!-- Header -->
    <div class="sidebar-header">
        <button class="btn btn-link text-white-50 p-0" data-sidebar-toggle>
            <i class="bi bi-list fs-4"></i>
        </button>
        <button class="btn btn-link text-white-50 p-0" data-sidebar-toggle>
            <i class="bi bi-arrow-left-circle fs-5"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-grow-1 overflow-auto px-3">
        <!-- Marketing -->
        <div class="nav-section mb-4">
            <div class="nav-section-title-pill mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-megaphone"></i>
                    <span>Marketing</span>
                </div>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="nav-section-content">
                <a href="step1_create_light.html" class="sidebar-link active">
                    <i class="bi bi-plus-circle"></i>
                    <span>Nouvelle campagne</span>
                </a>
                <a href="dashboard_light.html" class="sidebar-link">
                    <i class="bi bi-grid"></i>
                    <span>Mes campagnes</span>
                </a>
                <a href="analytics_light.html" class="sidebar-link">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </div>
        </div>

        <!-- Favoris -->
        <div class="nav-section mb-4">...</div>

        <!-- Historique -->
        <div class="nav-section mb-4">...</div>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="../settings_index/settings_index_light.html" class="sidebar-link">
            <i class="bi bi-gear"></i>
            <span>Paramètres</span>
        </a>
        <a href="../profile/profile_light.html" class="sidebar-link">
            <i class="bi bi-person-circle"></i>
            <span>Mon compte</span>
        </a>
        <a href="#" class="sidebar-link">
            <i class="bi bi-box-arrow-right"></i>
            <span>Quitter</span>
        </a>
    </div>
</aside>
```

**Points clés** :
- ✅ Toujours présente sur toutes les pages
- ✅ Sections de navigation avec `.nav-section`, `.nav-section-title-pill`, `.nav-section-content`
- ✅ Liens avec classe `.sidebar-link` (`.active` pour page courante)
- ✅ **IMPORTANT** : Les href changent selon le thème (light.html, dark-blue.html, dark-red.html)
- ✅ Footer avec Paramètres, Mon compte, Quitter

---

### 4. Main Content (lignes 105-454)

```html
<main class="main">
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <!-- Logo -->
            <a href="../index.html" class="step1-header-logo-link">
                <img src="../images/logo_picto.svg" alt="CFI" class="header-logo-picto">
                <img src="../images/logo.svg" alt="myCFiA" class="header-logo">
            </a>

            <!-- Actions -->
            <div class="step1-header-buttons">
                <button class="btn btn-glass header-btn step1-header-btn-primary">
                    <i class="bi bi-building"></i>
                    <span class="header-btn-text">Ma Division</span>
                </button>
                <button class="btn btn-glass-primary header-btn">
                    <i class="bi bi-sun-fill"></i>
                    <span class="header-btn-text">Clair</span>
                </button>
                <a href="#" class="btn btn-glass header-btn step1-header-link">
                    <i class="bi bi-arrow-left-circle"></i>
                    <span class="header-btn-text">Retour CFI</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Content -->
    <div class="content step1-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="step1-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="dashboard_light.html" class="step1-breadcrumb-link">Campagnes Marketing</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Nouvelle campagne</li>
            </ol>
        </nav>

        <!-- Stepper (si workflow multi-étapes) -->
        <div class="campaign-stepper">
            <div class="stepper-container">
                <div class="stepper-line">
                    <div class="stepper-progress step1-stepper-progress"></div>
                </div>

                <div class="stepper-step active">
                    <div class="stepper-circle">1</div>
                    <div class="stepper-label">Projet</div>
                </div>

                <div class="stepper-step">
                    <div class="stepper-circle">2</div>
                    <div class="stepper-label">Personas</div>
                </div>

                <!-- ... autres steps ... -->
            </div>
        </div>

        <!-- Contenu principal (formulaire, cartes, tableaux, etc.) -->
        <form id="campaignForm">
            <!-- Sections avec .form-section -->
            <div class="form-section form-section-delay-1">
                <div class="form-section-header">
                    <div class="form-section-icon">
                        <i class="bi bi-info-circle-fill"></i>
                    </div>
                    <h2 class="form-section-title">Titre de la section</h2>
                </div>

                <!-- Champs du formulaire -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">
                            Label <span class="required">*</span>
                        </label>
                        <input type="text" class="form-control" placeholder="..." required>
                    </div>
                </div>
            </div>

            <!-- Speed Dial FAB (si actions multiples) -->
            <div class="speed-dial-container">
                <div class="speed-dial-action">
                    <span class="speed-dial-label">Annuler</span>
                    <a href="dashboard_light.html" class="speed-dial-btn speed-dial-cancel">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

                <div class="speed-dial-action">
                    <span class="speed-dial-label">Action principale</span>
                    <a href="step1_loading_light.html" class="speed-dial-btn speed-dial-primary">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </a>
                </div>

                <div class="speed-dial-btn speed-dial-main">
                    <i class="bi bi-three-dots-vertical"></i>
                </div>
            </div>
        </form>
    </div>
</main>
```

**Points clés** :
- ✅ Header avec logo (lien vers ../index.html) + boutons actions
- ✅ **Bouton thème** affiche le thème actuel :
  - light.html → "Clair" + `.bi-sun-fill`
  - dark-blue.html → "Sombre Bleu" + `.bi-moon-stars-fill`
  - dark-red.html → "Sombre Rouge" + `.bi-moon-fill`
- ✅ Breadcrumb avec liens adaptés au thème
- ✅ Stepper pour workflow multi-étapes (`.campaign-stepper`)
- ✅ Sections de formulaire avec `.form-section` + icône + titre
- ✅ Speed Dial FAB pour actions contextuelles (`.speed-dial-container`)

---

### 5. FAB Retour (lignes 457-461)

```html
<!-- FAB Retour -->
<a href="../index.html" class="fab-back">
    <i class="bi bi-arrow-left"></i>
    <span class="fab-tooltip">Retour à l'index</span>
</a>
```

**Points clés** :
- ✅ Toujours présent sur toutes les pages
- ✅ Lien vers `../index.html` (page d'accueil des previews)
- ✅ Icône flèche gauche + tooltip

---

### 6. Scripts (lignes 463-468)

```html
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Design System JS -->
<script src="../assets/js/main.js" type="module"></script>
</body>
</html>
```

**Points clés** :
- ✅ Bootstrap JS depuis CDN
- ✅ main.js en ES6 module (`type="module"`)
- ✅ Chemin relatif `../assets/js/main.js`

---

## 🎨 Gestion des Assets CSS

### Structure main.css

```css
/* ==========================================================================
   TOKENS (variables CSS globales)
   ========================================================================== */
@import 'tokens/_colors.css';
@import 'tokens/_spacing.css';
@import 'tokens/_typography.css';
@import 'tokens/_effects.css';
@import 'tokens/_animations.css';

/* ==========================================================================
   LAYOUT (structure de l'application)
   ========================================================================== */
@import 'layout/_app-layout.css';
@import 'layout/_sidebar.css';
@import 'layout/_header.css';
@import 'layout/_content.css';

/* ==========================================================================
   COMPONENTS (composants réutilisables)
   ========================================================================== */
@import 'components/_buttons.css';
@import 'components/_cards.css';
@import 'components/_fab.css';
@import 'components/_forms.css';
@import 'components/_theme-preview.css';
@import 'components/_profile.css';
@import 'components/_index.css';
@import 'components/_campaign-stepper.css';
@import 'components/_campaign-forms.css';
@import 'components/_campaign-loader.css';
@import 'components/_analytics.css';
@import 'components/_step1-create.css';
@import 'components/_step1-review.css';
@import 'components/_step2-select.css';
@import 'components/_step3-validate.css';
@import 'components/_step4-recap.css';
@import 'components/_step4-result.css';
@import 'components/_step5-config.css';
@import 'components/_step5-loading.css';
@import 'components/_step5-validate.css';
```

**Organisation** :
1. **Tokens** : Variables CSS globales (couleurs, espacements, typo, effets, animations)
2. **Layout** : Structure app (app-layout, sidebar, header, content)
3. **Components** : Composants réutilisables par page/fonctionnalité

**Pattern** : Chaque page/état a son fichier CSS dédié si styles spécifiques nécessaires

---

### Création d'un nouveau composant CSS

Pour Upload & Mapping, créer :
```
/assets/css/components/_contact-upload.css
```

Puis l'ajouter dans main.css :
```css
@import 'components/_contact-upload.css';
```

**Règle** : Utiliser les tokens existants (var(--color-primary), var(--spacing-md), etc.)

---

## 🔧 Gestion des Assets JS

### Structure main.js

```javascript
/**
 * Point d'entrée principal du JavaScript myCFiA Design System
 * Initialise tous les modules et composants
 */

import { initSidebarToggle, restoreSidebarState } from './core/sidebar-toggle.js';
import { initThemeSwitcher } from './core/theme-switcher.js';
import { initThemeCards } from './components/theme-cards.js';
import { initButtonAnimations } from './components/button-animations.js';
import { initAnalyticsCharts } from './components/analytics-charts.js';
import { initAssetSelector } from './components/step1-asset-selector.js';
import { initPersonaSelector } from './components/step2-persona-selector.js';
// ... autres imports

document.addEventListener('DOMContentLoaded', () => {
    console.log('myCFiA Design System - Initialisation');

    try {
        // 1. Initialiser le système de thème
        initThemeSwitcher();

        // 2. Restaurer l'état du sidebar
        restoreSidebarState();

        // 3. Initialiser le toggle de la sidebar
        initSidebarToggle();

        // 4-12. Initialiser les autres composants conditionnellement
        // ...

        console.log('myCFiA Design System - Prêt ✓');
    } catch (error) {
        console.error('Erreur lors de l\'initialisation:', error);
    }
});
```

**Organisation** :
- **core/** : Fonctionnalités globales (sidebar, theme, etc.)
- **components/** : Modules spécifiques par page/composant
- **Initialisation conditionnelle** : Vérification présence éléments DOM avant init

---

### Création d'un nouveau module JS

Pour Upload & Mapping, créer :
```
/assets/js/components/contact-upload-simulator.js
```

Puis l'importer dans main.js :
```javascript
import { initContactUpload } from './components/contact-upload-simulator.js';

// Dans DOMContentLoaded
if (document.querySelector('.contact-upload-container')) {
    console.log('[main] Initialisation du contact upload...');
    initContactUpload();
}
```

**Pattern** : Export fonction init, import conditionnel dans main.js

---

## 🎭 Différences entre thèmes

### Dans le HTML

**Titre** :
```html
<!-- light.html -->
<title>Nouvelle Campagne - Étape 1 | myCFiA</title>

<!-- dark-blue.html -->
<title>Nouvelle Campagne - Étape 1 | myCFiA (Thème Sombre Bleu)</title>

<!-- dark-red.html -->
<title>Nouvelle Campagne - Étape 1 | myCFiA (Thème Sombre Rouge)</title>
```

**Import thème** :
```html
<!-- light.html -->
<link rel="stylesheet" href="../assets/css/themes/light.css" id="theme-stylesheet">

<!-- dark-blue.html -->
<link rel="stylesheet" href="../assets/css/themes/dark-blue.css" id="theme-stylesheet">

<!-- dark-red.html -->
<link rel="stylesheet" href="../assets/css/themes/dark-red.css" id="theme-stylesheet">
```

**Bouton thème dans header** :
```html
<!-- light.html -->
<button class="btn btn-glass-primary header-btn">
    <i class="bi bi-sun-fill"></i>
    <span class="header-btn-text">Clair</span>
</button>

<!-- dark-blue.html -->
<button class="btn btn-glass-primary header-btn">
    <i class="bi bi-moon-stars-fill"></i>
    <span class="header-btn-text">Sombre Bleu</span>
</button>

<!-- dark-red.html -->
<button class="btn btn-glass-primary header-btn">
    <i class="bi bi-moon-fill"></i>
    <span class="header-btn-text">Sombre Rouge</span>
</button>
```

**Liens sidebar et navigation** :
```html
<!-- light.html -->
<a href="dashboard_light.html" class="sidebar-link">...</a>

<!-- dark-blue.html -->
<a href="dashboard_dark-blue.html" class="sidebar-link">...</a>

<!-- dark-red.html -->
<a href="dashboard_dark-red.html" class="sidebar-link">...</a>
```

**IMPORTANT** : Tous les liens internes (sidebar, breadcrumb, speed dial, etc.) doivent pointer vers les fichiers du même thème

---

## 📋 Checklist pour nouveau mockup

### HTML
- [ ] Structure `<head>` complète (Bootstrap, Icons, Fonts, main.css, theme.css)
- [ ] `<div class="app">` comme conteneur principal
- [ ] Sidebar complète avec nav sections + footer
- [ ] Header avec logo + boutons (Ma Division, Thème correct, Retour CFI)
- [ ] Content avec breadcrumb (si navigation) + contenu principal
- [ ] FAB retour vers `../index.html`
- [ ] Scripts (Bootstrap JS + main.js en module)
- [ ] **Tous les liens** adaptés au thème (light, dark-blue, dark-red)
- [ ] **Titre** mentionne le thème (sauf light)
- [ ] **Bouton thème** affiche icône et texte corrects

### CSS
- [ ] Fichier component créé dans `/assets/css/components/`
- [ ] Import ajouté dans `main.css`
- [ ] Utilisation des tokens (var(--...))
- [ ] Classes Bootstrap privilégiées
- [ ] **ZÉRO** `style=""` inline
- [ ] **ZÉRO** `<style>` dans HTML

### JS
- [ ] Module créé dans `/assets/js/components/` (si interactivité nécessaire)
- [ ] Import ajouté dans `main.js`
- [ ] Initialisation conditionnelle (vérification présence DOM)
- [ ] ES6 modules (import/export)
- [ ] **ZÉRO** `<script>` inline dans HTML
- [ ] **ZÉRO** event handlers inline (onclick, etc.)

### Thèmes
- [ ] Fichier créé pour les 3 thèmes (light, dark-blue, dark-red)
- [ ] Différences appliquées (titre, import CSS, bouton thème, liens)
- [ ] Test visuel sur les 3 thèmes

### Responsive & Accessibilité
- [ ] Classes Bootstrap responsive (col-md-6, d-flex, etc.)
- [ ] Labels sur formulaires
- [ ] Attributs ARIA si nécessaire
- [ ] Icônes Bootstrap Icons

---

## 🚀 Prochaine étape

Maintenant que la structure est documentée, passer à la **Phase 2 : Conception** avec cette structure en référence.

**Fichiers à créer** :
```
/campaign_generation/
  ├─ contact_upload_empty_light.html
  ├─ contact_upload_empty_dark-blue.html
  ├─ contact_upload_empty_dark-red.html
  ├─ contact_upload_analyzing_light.html
  ├─ ... (6 états × 3 thèmes = 18 fichiers)

/assets/css/components/
  └─ _contact-upload.css

/assets/js/components/
  └─ contact-upload-simulator.js
```

---

**Dernière mise à jour** : 2025-12-30
