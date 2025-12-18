# Index Design System - myCFiA

Documentation complète du design system myCFiA : analyses, spécifications, guides et ressources.

**Dernière mise à jour** : 2025-12-17

---

## 📚 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Rapports d'analyse](#rapports-danalyse)
- [Guides de référence](#guides-de-référence)
- [Ressources](#ressources)
- [Workflows recommandés](#workflows-recommandés)

---

## 🎨 Vue d'ensemble

### Documents principaux

| Document | Description | Priorité |
|----------|-------------|----------|
| [RAPPORT_SYNTHESE_DESIGN_SYSTEM.md](./RAPPORT_SYNTHESE_DESIGN_SYSTEM.md) | **Point d'entrée principal** - Synthèse complète du design system : thèmes, composants, tokens, architecture | ⭐⭐⭐ |
| [RAPPORT_DESIGN_SYSTEM_CODE.md](./RAPPORT_DESIGN_SYSTEM_CODE.md) | Analyse technique du code : structure, patterns, conventions | ⭐⭐⭐ |

---

## 🔍 Rapports d'analyse

Analyses détaillées des différentes couches du design system.

### Analyse visuelle et spécifications

| Document | Description | Date |
|----------|-------------|------|
| [RAPPORT_ANALYSE_MOCKUP_ADOBE_XD.md](./RAPPORT_ANALYSE_MOCKUP_ADOBE_XD.md) | Analyse des mockups Adobe XD : extraction des couleurs, espacements, typographie, composants | 2025-12-16 |
| [RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md](./RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md) | Analyse approfondie des couleurs du réseau géométrique SVG par thème (light, dark-blue, dark-red) | 2025-12-16 |

### Analyse front-end et templates

| Document | Description | Date |
|----------|-------------|------|
| [RAPPORT_ANALYSE_TEMPLATES_TWIG.md](./RAPPORT_ANALYSE_TEMPLATES_TWIG.md) | Analyse complète des templates Twig : layouts, composants, blocks, structure | 2025-12-16 |
| [RAPPORT_ANALYSE_STIMULUS_TURBO_MERCURE.md](./RAPPORT_ANALYSE_STIMULUS_TURBO_MERCURE.md) | Analyse de l'architecture JavaScript : Stimulus controllers, Turbo Frames/Streams, Mercure | 2025-12-16 |

### Configuration framework UI

| Document | Description | Date |
|----------|-------------|------|
| [BOOTSTRAP_SETUP.md](./BOOTSTRAP_SETUP.md) | Configuration et personnalisation Bootstrap 5 : variables SCSS, composants customisés | 2025-12-16 |

---

## 📖 Guides de référence

Guides rapides pour le développement quotidien.

| Document | Description | Usage |
|----------|-------------|-------|
| [COULEURS_RESEAU_SVG_GUIDE_RAPIDE.md](./COULEURS_RESEAU_SVG_GUIDE_RAPIDE.md) | Guide rapide des couleurs du réseau SVG : valeurs hex, opacités, filtres CSS par thème | Référence rapide |

---

## 🎨 Ressources

Fichiers CSS et assets prêts à l'emploi.

| Fichier | Description | Usage |
|---------|-------------|-------|
| [network-svg-theme-colors.css](./network-svg-theme-colors.css) | Variables CSS et filtres pour le réseau géométrique SVG (light, dark-blue, dark-red) | Copier/coller dans projet |

---

## 🔄 Workflows recommandés

### 🚀 Découvrir le design system

**Objectif** : Comprendre l'architecture globale du design system.

```
1. RAPPORT_SYNTHESE_DESIGN_SYSTEM.md
   └─> Vue d'ensemble : thèmes, tokens, composants

2. RAPPORT_DESIGN_SYSTEM_CODE.md
   └─> Structure technique du code

3. RAPPORT_ANALYSE_MOCKUP_ADOBE_XD.md
   └─> Spécifications visuelles de référence
```

---

### 🎨 Implémenter un nouveau thème

**Objectif** : Créer ou modifier un thème (light, dark-blue, dark-red).

```
1. RAPPORT_SYNTHESE_DESIGN_SYSTEM.md
   └─> Section "Système de thèmes"

2. RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md
   └─> Couleurs du réseau géométrique par thème

3. COULEURS_RESEAU_SVG_GUIDE_RAPIDE.md
   └─> Référence rapide des valeurs

4. network-svg-theme-colors.css
   └─> Implémentation CSS prête à l'emploi

5. BOOTSTRAP_SETUP.md
   └─> Variables Bootstrap à personnaliser
```

---

### 🧩 Créer un composant Twig

**Objectif** : Développer un nouveau composant réutilisable.

```
1. RAPPORT_ANALYSE_TEMPLATES_TWIG.md
   └─> Patterns existants, conventions, structure

2. RAPPORT_SYNTHESE_DESIGN_SYSTEM.md
   └─> Section "Composants UI" pour les specs

3. BOOTSTRAP_SETUP.md
   └─> Classes Bootstrap disponibles

4. RAPPORT_ANALYSE_STIMULUS_TURBO_MERCURE.md
   └─> Intégration JavaScript si nécessaire
```

---

### ⚡ Ajouter de l'interactivité (Stimulus)

**Objectif** : Implémenter un controller Stimulus.

```
1. RAPPORT_ANALYSE_STIMULUS_TURBO_MERCURE.md
   └─> Architecture Stimulus : patterns, conventions, exemples

2. RAPPORT_ANALYSE_TEMPLATES_TWIG.md
   └─> Intégration avec templates (data-controller, data-action)

3. RAPPORT_SYNTHESE_DESIGN_SYSTEM.md
   └─> Section "JavaScript et interactivité"
```

---

### 🎯 Reproduire un mockup Adobe XD

**Objectif** : Transformer un design XD en HTML/Twig.

```
1. RAPPORT_ANALYSE_MOCKUP_ADOBE_XD.md
   └─> Extraction des spécifications : couleurs, espacements, typo

2. RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md
   └─> Couleurs du réseau SVG (si présent dans le mockup)

3. RAPPORT_ANALYSE_TEMPLATES_TWIG.md
   └─> Composants existants réutilisables

4. BOOTSTRAP_SETUP.md
   └─> Utilisation des classes Bootstrap

5. network-svg-theme-colors.css
   └─> CSS du réseau géométrique
```

---

### 🔧 Debugger un problème de style

**Objectif** : Résoudre un problème visuel ou de thème.

```
1. RAPPORT_SYNTHESE_DESIGN_SYSTEM.md
   └─> Comprendre l'architecture des styles

2. BOOTSTRAP_SETUP.md
   └─> Variables et overrides Bootstrap

3. RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md
   └─> Problèmes de couleur du réseau SVG

4. COULEURS_RESEAU_SVG_GUIDE_RAPIDE.md
   └─> Valeurs de référence rapide
```

---

## 📊 Organisation des documents

### Par niveau de détail

#### 🔴 Niveau 1 : Vue d'ensemble (démarrage)
- **RAPPORT_SYNTHESE_DESIGN_SYSTEM.md** : Synthèse complète
- **RAPPORT_DESIGN_SYSTEM_CODE.md** : Architecture technique

#### 🟡 Niveau 2 : Analyses spécialisées (approfondissement)
- **RAPPORT_ANALYSE_MOCKUP_ADOBE_XD.md** : Spécifications visuelles
- **RAPPORT_ANALYSE_TEMPLATES_TWIG.md** : Templates et composants
- **RAPPORT_ANALYSE_STIMULUS_TURBO_MERCURE.md** : JavaScript et interactivité
- **RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md** : Couleurs du réseau SVG

#### 🟢 Niveau 3 : Guides pratiques (développement)
- **COULEURS_RESEAU_SVG_GUIDE_RAPIDE.md** : Référence rapide couleurs
- **BOOTSTRAP_SETUP.md** : Configuration Bootstrap
- **network-svg-theme-colors.css** : Ressource CSS prête à l'emploi

---

### Par thématique

#### 🎨 Design et spécifications visuelles
1. RAPPORT_ANALYSE_MOCKUP_ADOBE_XD.md
2. RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md
3. COULEURS_RESEAU_SVG_GUIDE_RAPIDE.md
4. network-svg-theme-colors.css

#### 🧩 Composants et templates
1. RAPPORT_ANALYSE_TEMPLATES_TWIG.md
2. RAPPORT_SYNTHESE_DESIGN_SYSTEM.md (section Composants)

#### ⚡ JavaScript et interactivité
1. RAPPORT_ANALYSE_STIMULUS_TURBO_MERCURE.md
2. RAPPORT_SYNTHESE_DESIGN_SYSTEM.md (section JavaScript)

#### 🎭 Thèmes et styles
1. RAPPORT_SYNTHESE_DESIGN_SYSTEM.md (section Thèmes)
2. BOOTSTRAP_SETUP.md
3. RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md

---

## 🔗 Liens utiles

### Navigation

- **Documentation projet** : [../INDEX.md](../INDEX.md)
- **README projet** : [../../README.md](../../README.md)
- **Mockups HTML** : [../../Mockup/](../../Mockup/)

### Dossiers source

- **Templates Twig** : `app/templates/`
- **Assets (CSS/JS/Images)** : `app/assets/`
- **Controllers Stimulus** : `app/assets/controllers/`
- **Styles SCSS** : `app/assets/styles/`

---

## 📝 Notes importantes

### Dates des analyses
Tous les rapports d'analyse (RAPPORT_*.md) ont été générés le **2025-12-16** et reflètent l'état du code à cette date.

### Maintenance
Cette documentation doit être mise à jour lors de modifications majeures :
- Ajout de nouveaux thèmes
- Création de composants majeurs
- Refonte de l'architecture Stimulus
- Changements dans Bootstrap

### Conventions
- Les valeurs de couleurs sont au format hexadécimal (#RRGGBB)
- Les espacements suivent l'échelle Bootstrap (0.25rem = 4px)
- Les composants Twig utilisent le naming `component_name.html.twig`
- Les controllers Stimulus utilisent le naming `component-name_controller.js`

---

## 🎯 Points d'entrée recommandés

**Je débute sur le projet** → [RAPPORT_SYNTHESE_DESIGN_SYSTEM.md](./RAPPORT_SYNTHESE_DESIGN_SYSTEM.md)

**Je veux créer un composant** → [RAPPORT_ANALYSE_TEMPLATES_TWIG.md](./RAPPORT_ANALYSE_TEMPLATES_TWIG.md)

**Je veux ajouter du JavaScript** → [RAPPORT_ANALYSE_STIMULUS_TURBO_MERCURE.md](./RAPPORT_ANALYSE_STIMULUS_TURBO_MERCURE.md)

**Je veux modifier un thème** → [RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md](./RAPPORT_ANALYSE_COULEURS_RESEAU_SVG.md)

**Je cherche une référence rapide** → [COULEURS_RESEAU_SVG_GUIDE_RAPIDE.md](./COULEURS_RESEAU_SVG_GUIDE_RAPIDE.md)

**J'ai besoin du CSS** → [network-svg-theme-colors.css](./network-svg-theme-colors.css)
