# Design System - myCfia

**Date d'analyse** : 2025-12-16  
**Projet** : myCfia - Plateforme d'automatisation marketing multi-canal  
**Analysé par** : Claude Code (Design System Analyzer)

---

## Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Structure des fichiers](#structure-des-fichiers)
3. [Tokens de Design](#tokens-de-design)
4. [Système de Thématisation](#système-de-thématisation)
5. [Composants](#composants)
6. [Typographie](#typographie)
7. [Incohérences détectées](#incohérences-détectées)
8. [Recommandations](#recommandations)

---

## Vue d'ensemble

Le design system de myCfia est construit avec :
- **CSS Custom Properties** (variables CSS natives)
- **Système de thématisation** à 3 thèmes (light, dark-blue, dark-red)
- **Bootstrap 5** comme base (via AssetMapper Symfony)
- **Glassmorphism** comme identité visuelle principale
- **Polices custom** : Geist Sans (interface) et Geist Mono (code)

### Architecture

```
app/assets/styles/
├── variables.css          # Tokens globaux + thème light par défaut
├── themes/
│   ├── dark-blue.css     # Thème sombre bleu
│   └── dark-red.css      # Thème sombre rouge
└── components/
    ├── glass.css         # Composants glassmorphism
    ├── animations.css    # Animations et transitions
    └── utilities.css     # Classes utilitaires
```

---

## Structure des fichiers

### 1. `variables.css` (Tokens globaux)

Fichier central définissant :
- Tokens de couleurs (primaire, secondaire, succès, danger, etc.)
- Espacements (spacing-xs à spacing-2xl)
- Typographie (font-family, font-size, line-height)
- Radius (rounded-sm à rounded-2xl)
- Ombres (shadow-sm à shadow-2xl)
- Thème light par défaut (`:root`)

### 2. `themes/dark-blue.css` et `themes/dark-red.css`

Variantes de thème appliquées via attribut HTML :
```html
<html data-theme="dark-blue">
<html data-theme="dark-red">
```

Chaque thème surcharge les variables de couleurs tout en conservant les autres tokens (espacements, radius, etc.).

### 3. `components/glass.css`

Composants glassmorphism réutilisables :
- `.glass` : Effet verre de base
- `.glass-card` : Carte avec verre + padding
- `.glass-nav` : Navigation avec verre
- `.glass-sidebar` : Sidebar avec verre
- `.glass-modal` : Modale avec verre
- `.glass-input` : Input avec verre

### 4. `components/animations.css`

Animations réutilisables :
- `.fade-in` : Apparition progressive
- `.slide-in-up`, `.slide-in-down`, `.slide-in-left`, `.slide-in-right`
- `.scale-in` : Zoom d'apparition
- `.rotate-in` : Rotation d'apparition
- `.bounce-in` : Rebond d'apparition

### 5. `components/utilities.css`

Classes utilitaires :
- Espacements personnalisés (`.mt-xs`, `.p-lg`, etc.)
- Radius personnalisés (`.rounded-sm`, `.rounded-xl`, etc.)
- Ombres personnalisées (`.shadow-sm`, `.shadow-2xl`, etc.)

---

## Tokens de Design

### Couleurs

#### Palette primaire (light theme)
| Variable | Valeur | Usage |
|----------|--------|-------|
| `--color-primary` | `#3b82f6` | Boutons, liens, accents principaux |
| `--color-primary-dark` | `#2563eb` | Hover primaire |
| `--color-primary-light` | `#60a5fa` | Backgrounds légers primaire |

#### Palette secondaire
| Variable | Valeur | Usage |
|----------|--------|-------|
| `--color-secondary` | `#64748b` | Éléments secondaires |
| `--color-secondary-dark` | `#475569` | Hover secondaire |
| `--color-secondary-light` | `#94a3b8` | Backgrounds légers secondaire |

#### Palette sémantique
| Variable | Valeur | Usage |
|----------|--------|-------|
| `--color-success` | `#10b981` | Messages de succès, validation |
| `--color-success-dark` | `#059669` | Hover succès |
| `--color-success-light` | `#34d399` | Backgrounds succès |
| `--color-warning` | `#f59e0b` | Alertes, avertissements |
| `--color-warning-dark` | `#d97706` | Hover warning |
| `--color-warning-light` | `#fbbf24` | Backgrounds warning |
| `--color-danger` | `#ef4444` | Erreurs, suppressions |
| `--color-danger-dark` | `#dc2626` | Hover danger |
| `--color-danger-light` | `#f87171` | Backgrounds danger |
| `--color-info` | `#3b82f6` | Informations |
| `--color-info-dark` | `#2563eb` | Hover info |
| `--color-info-light` | `#60a5fa` | Backgrounds info |

#### Palette neutre
| Variable | Valeur | Usage |
|----------|--------|-------|
| `--color-background` | `#ffffff` | Fond principal |
| `--color-surface` | `#f8fafc` | Surfaces (cartes, panneaux) |
| `--color-text` | `#1e293b` | Texte principal |
| `--color-text-muted` | `#64748b` | Texte secondaire |
| `--color-border` | `#e2e8f0` | Bordures |

### Espacements

| Variable | Valeur | Correspondance rem |
|----------|--------|--------------------|
| `--spacing-xs` | `0.25rem` | 4px |
| `--spacing-sm` | `0.5rem` | 8px |
| `--spacing-md` | `1rem` | 16px |
| `--spacing-lg` | `1.5rem` | 24px |
| `--spacing-xl` | `2rem` | 32px |
| `--spacing-2xl` | `3rem` | 48px |

### Typographie

#### Familles de polices
| Variable | Valeur | Usage |
|----------|--------|-------|
| `--font-family-base` | `'Geist Sans', sans-serif` | Interface, texte principal |
| `--font-family-mono` | `'Geist Mono', monospace` | Code, données techniques |

#### Tailles de police
| Variable | Valeur | Usage |
|----------|--------|-------|
| `--font-size-xs` | `0.75rem` | Petits textes (12px) |
| `--font-size-sm` | `0.875rem` | Texte réduit (14px) |
| `--font-size-base` | `1rem` | Texte standard (16px) |
| `--font-size-lg` | `1.125rem` | Texte agrandi (18px) |
| `--font-size-xl` | `1.25rem` | Sous-titres (20px) |
| `--font-size-2xl` | `1.5rem` | Titres (24px) |
| `--font-size-3xl` | `2rem` | Grands titres (32px) |

#### Line-height
| Variable | Valeur | Usage |
|----------|--------|-------|
| `--line-height-tight` | `1.25` | Titres compacts |
| `--line-height-base` | `1.5` | Texte standard |
| `--line-height-relaxed` | `1.75` | Texte aéré |

#### Font-weight
| Variable | Valeur | Usage |
|----------|--------|-------|
| `--font-weight-normal` | `400` | Texte normal |
| `--font-weight-medium` | `500` | Texte accentué |
| `--font-weight-semibold` | `600` | Sous-titres |
| `--font-weight-bold` | `700` | Titres |

### Radius (arrondis)

| Variable | Valeur | Usage |
|----------|--------|-------|
| `--rounded-sm` | `0.25rem` | Petits éléments (badges) |
| `--rounded-md` | `0.375rem` | Éléments standard (boutons) |
| `--rounded-lg` | `0.5rem` | Cartes |
| `--rounded-xl` | `0.75rem` | Grandes cartes |
| `--rounded-2xl` | `1rem` | Modales |
| `--rounded-full` | `9999px` | Cercles parfaits (avatars) |

### Ombres

| Variable | Valeur | Usage |
|----------|--------|-------|
| `--shadow-sm` | `0 1px 2px 0 rgba(0, 0, 0, 0.05)` | Ombres légères |
| `--shadow-md` | `0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)` | Ombres standards (cartes) |
| `--shadow-lg` | `0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)` | Ombres importantes (hover) |
| `--shadow-xl` | `0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)` | Ombres très marquées (modales) |
| `--shadow-2xl` | `0 25px 50px -12px rgba(0, 0, 0, 0.25)` | Ombres dramatiques |

### Glassmorphism

| Variable | Valeur | Usage |
|----------|--------|-------|
| `--glass-bg` | `rgba(255, 255, 255, 0.1)` | Fond verre |
| `--glass-border` | `rgba(255, 255, 255, 0.2)` | Bordure verre |
| `--glass-shadow` | `0 8px 32px 0 rgba(31, 38, 135, 0.37)` | Ombre verre |
| `--glass-blur` | `10px` | Blur backdrop |

---

## Système de Thématisation

### Thème Light (défaut)

Appliqué par défaut via `:root` dans `variables.css`.

**Caractéristiques** :
- Background blanc (`#ffffff`)
- Texte sombre (`#1e293b`)
- Couleur primaire bleue (`#3b82f6`)
- Surface gris très clair (`#f8fafc`)

### Thème Dark Blue

Appliqué via `<html data-theme="dark-blue">`.

**Variables surchargées** :
```css
--color-background: #0f172a;
--color-surface: #1e293b;
--color-text: #f1f5f9;
--color-text-muted: #94a3b8;
--color-border: #334155;
--color-primary: #3b82f6;
--color-primary-dark: #2563eb;
--color-primary-light: #60a5fa;
```

**Caractéristiques** :
- Background bleu très sombre (slate-900)
- Texte clair (slate-100)
- Conserve la couleur primaire bleue
- Surface bleu-gris (slate-800)

### Thème Dark Red

Appliqué via `<html data-theme="dark-red">`.

**Variables surchargées** :
```css
--color-background: #1a0f0f;
--color-surface: #2d1818;
--color-text: #f9f5f5;
--color-text-muted: #b89898;
--color-border: #4d3333;
--color-primary: #ef4444;
--color-primary-dark: #dc2626;
--color-primary-light: #f87171;
```

**Caractéristiques** :
- Background rouge très sombre
- Texte clair avec teinte chaude
- Couleur primaire rouge (remplace le bleu)
- Surface brun-rouge

### Sélecteur de thème

Un composant JavaScript (`ThemeSwitcher`) permet de basculer entre thèmes :
```javascript
// app/assets/controllers/theme_controller.js
document.documentElement.setAttribute('data-theme', theme);
localStorage.setItem('theme', theme);
```

**Stockage** : Préférence sauvegardée dans `localStorage`.

---

## Composants

### Glassmorphism

#### `.glass` (classe de base)
```css
.glass {
    background: var(--glass-bg);
    backdrop-filter: blur(var(--glass-blur));
    -webkit-backdrop-filter: blur(var(--glass-blur));
    border: 1px solid var(--glass-border);
    box-shadow: var(--glass-shadow);
}
```

**Usage** : Appliquer l'effet verre sur n'importe quel élément.

#### `.glass-card`
```css
.glass-card {
    /* Hérite de .glass */
    padding: var(--spacing-lg);
    border-radius: var(--rounded-lg);
}
```

**Usage** : Carte avec effet verre + padding standard.

#### `.glass-nav`
```css
.glass-nav {
    /* Hérite de .glass */
    position: sticky;
    top: 0;
    z-index: 1000;
}
```

**Usage** : Navigation sticky avec effet verre.

#### `.glass-sidebar`
```css
.glass-sidebar {
    /* Hérite de .glass */
    height: 100vh;
    position: fixed;
}
```

**Usage** : Sidebar fixe avec effet verre.

#### `.glass-modal`
```css
.glass-modal {
    /* Hérite de .glass */
    padding: var(--spacing-2xl);
    border-radius: var(--rounded-2xl);
    max-width: 90vw;
}
```

**Usage** : Modale centrée avec effet verre.

#### `.glass-input`
```css
.glass-input {
    /* Hérite de .glass */
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--rounded-md);
    color: var(--color-text);
}
```

**Usage** : Input de formulaire avec effet verre.

### Animations

#### `.fade-in`
```css
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.fade-in {
    animation: fadeIn 0.3s ease-in;
}
```

#### `.slide-in-up`
```css
@keyframes slideInUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
.slide-in-up {
    animation: slideInUp 0.3s ease-out;
}
```

**Variantes** : `.slide-in-down`, `.slide-in-left`, `.slide-in-right`.

#### `.scale-in`
```css
@keyframes scaleIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
.scale-in {
    animation: scaleIn 0.3s ease-out;
}
```

#### `.bounce-in`
```css
@keyframes bounceIn {
    0% {
        transform: scale(0.3);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
.bounce-in {
    animation: bounceIn 0.5s ease-out;
}
```

### Utilitaires d'espacement

Classes générées pour chaque token d'espacement :

**Margin** :
- `.mt-xs`, `.mt-sm`, `.mt-md`, `.mt-lg`, `.mt-xl`, `.mt-2xl`
- `.mb-xs`, `.mb-sm`, `.mb-md`, `.mb-lg`, `.mb-xl`, `.mb-2xl`
- `.ml-xs`, `.ml-sm`, `.ml-md`, `.ml-lg`, `.ml-xl`, `.ml-2xl`
- `.mr-xs`, `.mr-sm`, `.mr-md`, `.mr-lg`, `.mr-xl`, `.mr-2xl`

**Padding** :
- `.pt-xs`, `.pt-sm`, `.pt-md`, `.pt-lg`, `.pt-xl`, `.pt-2xl`
- `.pb-xs`, `.pb-sm`, `.pb-md`, `.pb-lg`, `.pb-xl`, `.pb-2xl`
- `.pl-xs`, `.pl-sm`, `.pl-md`, `.pl-lg`, `.pl-xl`, `.pl-2xl`
- `.pr-xs`, `.pr-sm`, `.pr-md`, `.pr-lg`, `.pr-xl`, `.pr-2xl`

**Gap** :
- `.gap-xs`, `.gap-sm`, `.gap-md`, `.gap-lg`, `.gap-xl`, `.gap-2xl`

### Utilitaires de radius

- `.rounded-sm` : `border-radius: var(--rounded-sm);`
- `.rounded-md` : `border-radius: var(--rounded-md);`
- `.rounded-lg` : `border-radius: var(--rounded-lg);`
- `.rounded-xl` : `border-radius: var(--rounded-xl);`
- `.rounded-2xl` : `border-radius: var(--rounded-2xl);`
- `.rounded-full` : `border-radius: var(--rounded-full);`

### Utilitaires d'ombres

- `.shadow-sm` : `box-shadow: var(--shadow-sm);`
- `.shadow-md` : `box-shadow: var(--shadow-md);`
- `.shadow-lg` : `box-shadow: var(--shadow-lg);`
- `.shadow-xl` : `box-shadow: var(--shadow-xl);`
- `.shadow-2xl` : `box-shadow: var(--shadow-2xl);`

---

## Typographie

### Polices installées

Le projet utilise les polices **Geist** (créées par Vercel) :

#### Geist Sans (interface)

**Fichiers** : `app/assets/fonts/geist-sans/`

**Graisses disponibles** :
- Thin (100)
- ExtraLight (200)
- Light (300)
- Regular (400)
- Medium (500)
- SemiBold (600)
- Bold (700)
- ExtraBold (800)
- Black (900)

**Variantes** : Normal + Italic pour chaque graisse.

#### Geist Mono (code)

**Fichiers** : `app/assets/fonts/geist-mono/`

**Graisses disponibles** :
- Thin (100)
- ExtraLight (200)
- Light (300)
- Regular (400)
- Medium (500)
- SemiBold (600)
- Bold (700)
- ExtraBold (800)
- Black (900)

**Variantes** : Normal + Italic pour chaque graisse.

### Chargement des polices

Les polices sont chargées via `@font-face` dans `app/assets/styles/fonts.css` :

```css
@font-face {
    font-family: 'Geist Sans';
    src: url('../fonts/geist-sans/GeistVF.woff2') format('woff2-variations');
    font-weight: 100 900;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'Geist Mono';
    src: url('../fonts/geist-mono/GeistMonoVF.woff2') format('woff2-variations');
    font-weight: 100 900;
    font-style: normal;
    font-display: swap;
}
```

**Optimisations** :
- Format **WOFF2 variable** (taille optimale + toutes les graisses en 1 fichier)
- `font-display: swap` : affiche le texte immédiatement avec police fallback

### Utilisation dans le code

```css
/* Interface générale */
body {
    font-family: var(--font-family-base);
    font-size: var(--font-size-base);
    line-height: var(--line-height-base);
    font-weight: var(--font-weight-normal);
}

/* Blocs de code */
code, pre, .font-mono {
    font-family: var(--font-family-mono);
}
```

---

## Incohérences détectées

### 1. Variables glassmorphism non utilisées systématiquement

**Localisation** : `app/templates/chat/index.html.twig`

**Problème** :
```css
/* Valeurs hardcodées au lieu de variables */
background: rgba(255, 255, 255, 0.1);
backdrop-filter: blur(10px);
border: 1px solid rgba(255, 255, 255, 0.2);
```

**Solution** :
```css
background: var(--glass-bg);
backdrop-filter: blur(var(--glass-blur));
border: 1px solid var(--glass-border);
box-shadow: var(--glass-shadow);
```

Ou encore mieux, utiliser directement la classe `.glass`.

### 2. Couleurs hardcodées dans les templates

**Exemples trouvés** :
- `#3b82f6` hardcodé dans certains boutons au lieu de `var(--color-primary)`
- `#ffffff` dans des backgrounds au lieu de `var(--color-background)`
- `rgba(...)` hardcodé au lieu des variables glass

**Impact** :
- Casse le système de thématisation
- Valeurs non cohérentes avec le design system
- Maintenance difficile

**Recommandation** : Audit complet des templates pour remplacer toutes les couleurs hardcodées par des variables CSS.

### 3. Espacements Bootstrap vs espacements custom

**Problème** : Coexistence de 2 systèmes d'espacement :
- Bootstrap : `.mt-1`, `.mt-2`, `.mt-3`, `.mt-4`, `.mt-5`
- Custom : `.mt-xs`, `.mt-sm`, `.mt-md`, `.mt-lg`, `.mt-xl`, `.mt-2xl`

**Impact** : Confusion, incohérence visuelle possible.

**Recommandation** : Choisir un système unique ou mapper les valeurs Bootstrap vers les variables custom :
```css
/* Exemple de mapping */
.mt-1 { margin-top: var(--spacing-xs); }
.mt-2 { margin-top: var(--spacing-sm); }
.mt-3 { margin-top: var(--spacing-md); }
.mt-4 { margin-top: var(--spacing-lg); }
.mt-5 { margin-top: var(--spacing-xl); }
```

### 4. Surcharge Bootstrap partielle

**Problème** : Bootstrap est chargé via AssetMapper mais ses variables ne sont pas surchargées via Sass.

**Conséquence** : Les composants Bootstrap (boutons, formulaires, etc.) n'utilisent pas automatiquement les couleurs du design system.

**Solution possible** :
- Migrer vers une installation Sass de Bootstrap pour surcharger `$primary`, `$secondary`, etc.
- OU créer des classes `.btn-custom-primary` basées sur les variables CSS custom

### 5. Mode sombre incomplet

**Problème** : Les thèmes dark-blue et dark-red ne surchargent QUE les couleurs, mais pas :
- Les ombres (pourraient être plus subtiles en mode sombre)
- Les variables glassmorphism (opacity pourrait être différente)

**Recommandation** : Adapter `--glass-bg`, `--glass-border`, `--shadow-*` dans chaque thème pour un rendu optimal.

### 6. Absence de transition sur le changement de thème

**Problème** : Lorsqu'on change de thème, les couleurs changent brutalement.

**Solution** : Ajouter des transitions sur les propriétés variables :
```css
:root,
[data-theme="dark-blue"],
[data-theme="dark-red"] {
    transition: background-color 0.3s ease, color 0.3s ease;
}
```

---

## Recommandations

### Priorité haute

1. **Centraliser toutes les couleurs hardcodées**
   - Faire un audit complet (Grep sur `#[0-9a-f]{3,6}`, `rgba?\\(`)
   - Remplacer par `var(--color-*)`
   - Utiliser les classes `.glass` au lieu de recréer l'effet à chaque fois

2. **Documenter le design system**
   - Créer une page Storybook ou une documentation HTML affichant tous les tokens
   - Exemples visuels de chaque composant glassmorphism
   - Guide d'utilisation pour les développeurs

3. **Unifier le système d'espacement**
   - Choisir entre Bootstrap ou custom (ou créer un mapping explicite)
   - Documenter la décision dans un guide de style

4. **Adapter le mode sombre**
   - Surcharger `--glass-bg`, `--glass-border` pour chaque thème
   - Adapter les ombres (plus subtiles en dark mode)
   - Tester tous les composants sur les 3 thèmes

### Priorité moyenne

5. **Ajouter des transitions sur le changement de thème**
   - Transition douce sur `background-color`, `color`, `border-color`
   - Améliore l'expérience utilisateur

6. **Créer des composants Bootstrap surchargés**
   - `.btn-primary` utilisant `var(--color-primary)`
   - `.form-control` utilisant l'effet glass
   - `.card` utilisant `.glass-card`

7. **Optimiser le chargement des polices**
   - Preload des polices critiques dans `<head>`
   - Subset des polices si toutes les graisses ne sont pas utilisées

8. **Ajouter des tokens manquants**
   - Z-index (z-index-modal, z-index-nav, etc.)
   - Transitions (transition-fast, transition-base, transition-slow)
   - Breakpoints responsive (si différents de Bootstrap)

### Priorité basse

9. **Créer un thème builder**
   - Interface permettant de personnaliser les couleurs en temps réel
   - Export du thème en CSS

10. **Automatiser les tests visuels**
    - Playwright pour tester le rendu des 3 thèmes
    - Comparaison de screenshots

11. **Créer un guide de contribution**
    - Comment ajouter un nouveau token
    - Comment créer un nouveau composant glassmorphism
    - Convention de nommage des classes

---

## Checklist de migration

Pour les développeurs souhaitant appliquer le design system :

### Templates Twig

- [ ] Remplacer les couleurs hardcodées par `var(--color-*)`
- [ ] Utiliser `.glass`, `.glass-card`, etc. au lieu de recréer l'effet
- [ ] Remplacer les espacements hardcodés par `.mt-md`, `.p-lg`, etc.
- [ ] Utiliser `var(--rounded-*)` pour les border-radius
- [ ] Utiliser `var(--shadow-*)` pour les box-shadow

### CSS custom

- [ ] Éviter les valeurs hardcodées (couleurs, espacements, radius)
- [ ] Utiliser les variables CSS définies dans `variables.css`
- [ ] Tester le rendu sur les 3 thèmes (light, dark-blue, dark-red)
- [ ] Ajouter des transitions si nécessaire

### JavaScript

- [ ] Utiliser le `ThemeSwitcher` pour changer de thème
- [ ] Persister la préférence utilisateur dans `localStorage`
- [ ] Respecter la préférence système (`prefers-color-scheme`) si disponible

---

## Conclusion

Le design system de myCfia est **bien structuré** avec :
- Une architecture claire (variables globales + thèmes)
- Un système de thématisation fonctionnel (3 thèmes)
- Des composants réutilisables (glassmorphism, animations)
- Des tokens cohérents (couleurs, espacements, typographie)

**Points forts** :
- Utilisation de CSS Custom Properties (moderne, performant)
- Thématisation dynamique sans rechargement de page
- Composants glassmorphism bien isolés
- Polices custom optimisées (WOFF2 variable)

**Points à améliorer** :
- Couleurs hardcodées dans certains templates
- Coexistence de 2 systèmes d'espacement (Bootstrap + custom)
- Mode sombre incomplet (ombres, glassmorphism)
- Absence de documentation visuelle du design system

**Effort estimé pour amélioration complète** : 2-3 jours
- Jour 1 : Audit et remplacement des couleurs hardcodées
- Jour 2 : Unification espacement + adaptation mode sombre
- Jour 3 : Documentation (Storybook ou page HTML)

---

**Généré le** : 2025-12-16  
**Outil** : Claude Code - Design System Analyzer  
**Version** : 1.0
