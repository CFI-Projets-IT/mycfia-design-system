# Guide d'implémentation DAP (Digital Adoption Platform)

**Version:** 1.0
**Dernière mise à jour:** 2026-01-26
**Statut:** Production-ready

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Principes fondamentaux](#principes-fondamentaux)
3. [Architecture DAP](#architecture-dap)
4. [Structure HTML](#structure-html)
5. [Structure JavaScript](#structure-javascript)
6. [Flow utilisateur](#flow-utilisateur)
7. [Patterns et bonnes pratiques](#patterns-et-bonnes-pratiques)
8. [Checklist d'implémentation](#checklist-dimplémentation)

---

## Vue d'ensemble

Le système DAP (Digital Adoption Platform) de myCFiA est un système d'onboarding guidé qui accompagne l'utilisateur lors de sa première visite sur une page. Il est actuellement implémenté sur 3 pages :

- **Dashboard** (`dashboard_light.html`) : Tour guidé du dashboard avec 5 étapes
- **Step 1 Create** (`step1_create_light.html`) : Tour guidé du formulaire de création avec 5 étapes
- **Step 1 Review** (`step1_review_light.html`) : Tour guidé de validation enrichissement avec 6 étapes

### Objectifs du DAP

1. **Réduire la courbe d'apprentissage** : Guider l'utilisateur à travers les fonctionnalités clés
2. **Améliorer l'adoption** : Montrer la valeur de chaque section de l'interface
3. **Contextualiser l'IA** : Expliquer comment l'IA enrichit l'expérience utilisateur
4. **Augmenter l'engagement** : Créer une expérience interactive dès la première visite

---

## Principes fondamentaux

### 1. Non-intrusif

- Le DAP ne doit **jamais** bloquer l'accès aux fonctionnalités
- L'utilisateur peut **toujours** passer ou fermer le tour guidé
- Le bouton d'aide permet de **relancer le tour** à tout moment
- La modal d'accueil se ferme avec `Echap` ou le bouton de fermeture

### 2. Contextuel

- Chaque étape cible un **élément spécifique** de l'interface
- Le contenu des tooltips est **adapté à la page**
- Les icônes Bootstrap Icons renforcent le **message visuel**
- Le nombre d'étapes est adapté à la **complexité de la page** (3-7 étapes max)

### 3. Progressif

- Le tour commence par une **modal d'accueil** présentant les bénéfices
- Les étapes sont **numérotées** avec des dots de progression
- Le spotlight met en **évidence l'élément ciblé**
- Le message de complétion **encourage l'action suivante**

### 4. Persistant

- L'état est sauvegardé dans **LocalStorage**
- Chaque page a ses propres **clés uniques**
- L'utilisateur ne voit le tour **qu'une seule fois** (sauf si relancé manuellement)
- Les données persistent même après **rechargement de la page**

---

## Architecture DAP

### Composants du système

```
DAP System
├── Modal d'accueil (Bootstrap Modal)
│   ├── Titre avec icône
│   ├── Description lead
│   ├── 4 bénéfices clés (grid 2x2)
│   ├── Alerte info (recommandation)
│   └── Footer avec 2 boutons (Passer / Faire le tour)
│
├── Bouton d'aide inline
│   ├── Position : top-right du breadcrumb
│   ├── Icône : bi-question-lg
│   └── Action : Relance le tour guidé
│
├── Overlay (fond semi-transparent)
│   ├── z-index : 10000
│   ├── Couleur : rgba(0,0,0,0.6)
│   └── Cliquable pour fermer (optionnel)
│
├── Spotlight (découpe autour de l'élément)
│   ├── z-index : 10001
│   ├── Position : calculée dynamiquement
│   ├── Padding : 16px
│   └── Transition : smooth (300ms)
│
└── Tooltip (bulle d'information)
    ├── z-index : 10002
    ├── Structure : Header → Content → Footer
    ├── Positionnement : intelligent (évite débordements)
    ├── Flèche : dynamique selon position
    └── Boutons : "Passer le tour" + "Suivant" (ou "Terminer")
```

### Fichiers requis

#### HTML
- Modal d'accueil (`#onboardingWelcomeModal[PageName]`)
- Bouton d'aide (`#helpButtonInline[PageName]`)

#### JavaScript
- Module ES6 (`onboarding-dap-[page].js`)
- Classe principale (`OnboardingDAP[PageName]`)
- Configuration des étapes (`ONBOARDING_STEPS_[PAGE]`)
- Clés LocalStorage (`STORAGE_KEYS_[PAGE]`)

#### CSS
- Classes DAP existantes dans `main.css`
- Pas de CSS custom nécessaire (sauf cas particulier)

---

## Structure HTML

### 1. Modal d'accueil

**Placement :** Juste avant la fermeture du `</body>`

**Structure obligatoire :**
- Classe : `modal fade`
- ID unique : `onboardingWelcomeModal[PageName]`
- Attributs : `tabindex="-1" aria-hidden="true"`

**Contenu recommandé :**
- **Header** : Titre avec icône `bi-stars` + bouton close
- **Body** :
  - Paragraph lead (description courte)
  - Grid 2x2 avec 4 bénéfices clés (icône + titre + description)
  - Alerte info avec recommandation de faire le tour
- **Footer** :
  - Bouton secondaire "Passer" (dismiss modal)
  - Bouton primary "Faire le tour guidé" (ID : `startOnboardingTour[PageName]`)

### 2. Bouton d'aide

**Placement :** Dans un conteneur flex avec le breadcrumb

**Structure :**
```
<div class="d-flex justify-content-between align-items-center mb-3">
    <nav aria-label="breadcrumb" class="[page]-breadcrumb mb-0">
        <!-- Breadcrumb items -->
    </nav>
    <button class="onboarding-help-button-inline"
            id="helpButtonInline[PageName]"
            type="button"
            aria-label="Aide - Relancer le tour guidé"
            title="Relancer le tour guidé">
        <i class="bi bi-question-lg"></i>
    </button>
</div>
```

**Important :**
- Toujours dans un conteneur `d-flex justify-content-between`
- Le breadcrumb a `mb-0` pour éviter double marge
- L'ID doit être unique par page

---

## Structure JavaScript

### 1. Configuration des étapes

**Constante :** `ONBOARDING_STEPS_[PAGE]`

**Structure d'une étape :**
- `id` (string) : Identifiant unique de l'étape
- `type` (string) : "modal" ou "hotspot"
- `target` (string|null) : Sélecteur CSS de l'élément ciblé (null pour modal)
- `title` (string) : Titre du tooltip (uniquement pour hotspot)
- `content` (string) : Texte explicatif du tooltip (uniquement pour hotspot)
- `icon` (string) : Classe Bootstrap Icon (ex: "bi-palette")

**Bonnes pratiques sélecteurs :**
- Privilégier les **classes spécifiques** aux IDs génériques
- Utiliser `:nth-child()` ou `:nth-of-type()` pour cibler précisément
- Éviter les sélecteurs trop fragiles (ex: `.card:first-child`)
- Tester que l'élément existe avant de configurer l'étape

### 2. Clés LocalStorage

**Constante :** `STORAGE_KEYS_[PAGE]`

**Clés obligatoires :**
- `COMPLETED` : Tour terminé ou non (`mycfia_onboarding_[page]_completed`)
- `STEP` : Étape actuelle (`mycfia_onboarding_[page]_step`)
- `SKIPPED_AT` : Étape où l'utilisateur a skippé (`mycfia_onboarding_[page]_skipped_at`)
- `TOOLTIP_DISMISSED` : Tooltip fermé manuellement (`mycfia_onboarding_[page]_tooltip_dismissed`)

**Naming convention :**
- Préfixe : `mycfia_onboarding_`
- Nom page : `dashboard`, `step1`, `step1_review`, etc.
- Suffixe : `_completed`, `_step`, `_skipped_at`, `_tooltip_dismissed`

### 3. Classe principale

**Nom :** `OnboardingDAP[PageName]`

**Propriétés essentielles :**
- `currentStep` : Index de l'étape actuelle
- `steps` : Référence à la configuration des étapes
- `overlay` : Référence DOM de l'overlay
- `spotlight` : Référence DOM du spotlight
- `tooltip` : Référence DOM du tooltip
- `helpButton` : Référence DOM du bouton d'aide
- `isActive` : Indique si le tour est en cours

**Méthodes essentielles :**

#### Initialisation
- `init()` : Point d'entrée, vérifie LocalStorage et initialise le DOM
- `initModal()` : Configure la modal et ses event listeners
- `initHelpButton()` : Configure le bouton d'aide
- `initDOM()` : Crée overlay, spotlight et tooltip

#### Navigation
- `start()` : Démarre le tour guidé
- `showStep(stepIndex)` : Affiche une étape spécifique
- `nextStep()` : Passe à l'étape suivante
- `skipTour()` : Saute le tour et nettoie le DOM
- `completeTour()` : Termine le tour avec succès

#### Rendu
- `createTooltip(step, currentStepNum, totalSteps)` : Génère le HTML du tooltip
- `positionTooltip(targetElement)` : Calcule la position optimale du tooltip
- `renderProgressDots(current, total)` : Génère les dots de progression

#### Utilitaires
- `showCompletionMessage()` : Affiche un toast de succès
- `cleanup()` : Nettoie le DOM (enlève overlay, spotlight, tooltip)
- `reset()` : Réinitialise l'état complet (DOM + LocalStorage)

---

## Flow utilisateur

### 1. Première visite

```
Chargement page
    ↓
Vérification LocalStorage (completed = false)
    ↓
Affichage modal d'accueil (automatique)
    ↓
┌─────────────────────────────────────┐
│ Utilisateur clique "Faire le tour"  │ → Démarre le tour
│              OU                      │
│   Utilisateur clique "Passer"       │ → Sauvegarde skipped, ferme modal
└─────────────────────────────────────┘
    ↓
Tour guidé (si démarré)
    ↓
Étape 1 (hotspot) → Étape 2 → ... → Étape N
    ↓
À chaque étape :
  - Spotlight sur l'élément
  - Tooltip avec titre/contenu/progression
  - Boutons "Passer le tour" / "Suivant"
    ↓
Dernière étape
    ↓
Message de complétion (toast)
    ↓
Sauvegarde completed = true dans LocalStorage
    ↓
Cleanup DOM
```

### 2. Visites suivantes

```
Chargement page
    ↓
Vérification LocalStorage (completed = true)
    ↓
Aucune modal affichée
    ↓
Bouton d'aide visible
    ↓
Utilisateur peut cliquer pour relancer le tour
```

### 3. Relance manuelle

```
Clic sur bouton d'aide
    ↓
Réinitialisation état (reset LocalStorage)
    ↓
Démarre le tour immédiatement (sans modal)
    ↓
Même flow que première visite
```

---

## Patterns et bonnes pratiques

### 1. Nombre d'étapes optimal

**Dashboard :** 4-6 étapes
- Concentrer sur les actions principales (filtres, création, cartes campagne, menu utilisateur)

**Pages formulaire :** 3-5 étapes
- Une étape par section majeure du formulaire
- Toujours terminer sur le bouton de soumission (CTA)

**Pages validation :** 5-7 étapes
- Une étape par card d'information enrichie
- Terminer sur le Speed Dial FAB pour validation

### 2. Contenu des tooltips

**Titre :** 3-6 mots, action-oriented
- ✅ "Visualisez votre progression"
- ✅ "Lancez l'analyse IA"
- ❌ "Stepper"
- ❌ "Bouton"

**Contenu :** 1-2 phrases courtes, bénéfice-oriented
- Expliquer **POURQUOI** (bénéfice) avant **COMMENT** (action)
- Mentionner l'IA quand pertinent
- Éviter le jargon technique

### 3. Icônes Bootstrap

**Mapping recommandé :**
- Navigation/Stepper : `bi-list-ol`, `bi-signpost`
- Formulaires : `bi-info-circle-fill`, `bi-pencil-square`
- Budget/Finance : `bi-cash-stack`, `bi-wallet2`
- Objectifs : `bi-bullseye`, `bi-trophy`
- IA/Analyse : `bi-lightning-charge-fill`, `bi-stars`, `bi-robot`
- Actions : `bi-play-circle`, `bi-check-circle`, `bi-arrow-right-circle`
- Données : `bi-briefcase`, `bi-palette`, `bi-search`

### 4. Positionnement des tooltips

**Ordre de préférence :**
1. `bottom` : En dessous de l'élément (défaut)
2. `top` : Au-dessus si pas de place en bas
3. `right` : À droite si élément à gauche de l'écran
4. `left` : À gauche si élément à droite de l'écran

**Détection intelligente :**
- Calculer l'espace disponible dans chaque direction
- Éviter les débordements de viewport
- Ajuster la position de la flèche dynamiquement

### 5. Gestion du Speed Dial FAB

**Si la page a un Speed Dial :**
- **Toujours** inclure une étape dédiée (dernière du tour)
- **Auto-ouvrir** le Speed Dial lors de cette étape
- **Auto-fermer** le Speed Dial à la fin du tour
- Expliquer les actions disponibles dans le tooltip

**Gestion de l'état :**
- Tracker si le Speed Dial était déjà ouvert avant le tour
- Restaurer l'état initial après cleanup

### 6. Accessibilité

**Obligations :**
- Attributs ARIA sur tous les boutons interactifs
- Navigation clavier possible (Tab, Enter, Echap)
- Contraste suffisant (WCAG AA minimum)
- Focus visible sur les éléments interactifs
- Modal fermable avec Echap

**Attributs requis :**
- `aria-label` sur les boutons sans texte
- `aria-hidden="true"` sur les éléments décoratifs
- `role="dialog"` sur la modal
- `tabindex="-1"` sur la modal pour focus management

### 7. Performance

**Chargement :**
- Charger le module DAP en **defer** (non-bloquant)
- N'initialiser que si l'élément de déclenchement existe
- Utiliser event delegation pour les boutons dynamiques

**Animation :**
- Transitions CSS (pas JavaScript)
- `transition: all 300ms ease-in-out` pour le spotlight
- `will-change: transform` pour optimisation GPU

**Cleanup :**
- Toujours nettoyer le DOM après le tour
- Retirer les event listeners
- Libérer les références pour garbage collection

---

## Checklist d'implémentation

### Phase 1 : Planification

- [ ] Identifier les 3-7 éléments clés de la page à présenter
- [ ] Définir l'ordre logique des étapes (flow naturel)
- [ ] Rédiger les titres et contenus des tooltips
- [ ] Choisir les icônes Bootstrap Icons appropriées
- [ ] Vérifier que tous les sélecteurs CSS sont uniques et stables

### Phase 2 : HTML

- [ ] Créer la modal d'accueil avec ID unique
- [ ] Ajouter le bouton d'aide dans le breadcrumb (top-right)
- [ ] Vérifier que la structure HTML est cohérente avec les autres pages
- [ ] Tester l'affichage de la modal (Bootstrap JS chargé)

### Phase 3 : JavaScript

- [ ] Créer le fichier `onboarding-dap-[page].js`
- [ ] Définir la configuration des étapes (`ONBOARDING_STEPS_[PAGE]`)
- [ ] Définir les clés LocalStorage (`STORAGE_KEYS_[PAGE]`)
- [ ] Implémenter la classe `OnboardingDAP[PageName]`
- [ ] Copier les méthodes essentielles depuis une implémentation existante :
  - `renderProgressDots()`
  - `positionTooltip()`
  - `createTooltip()`
  - `showCompletionMessage()`
- [ ] Adapter les méthodes spécifiques à la page (ex: Speed Dial)

### Phase 4 : Intégration

- [ ] Importer le module dans `main.js`
- [ ] Ajouter la fonction d'initialisation conditionnelle
- [ ] Vérifier que l'initialisation ne se déclenche que sur la bonne page
- [ ] Tester que les autres pages DAP ne sont pas affectées

### Phase 5 : Tests

#### Tests fonctionnels
- [ ] **Première visite** : Modal s'affiche automatiquement
- [ ] **Clic "Faire le tour"** : Le tour démarre (overlay, spotlight, tooltip)
- [ ] **Navigation étapes** : Clic "Suivant" fonctionne pour toutes les étapes
- [ ] **Progress dots** : Affichage correct (active, completed, pending)
- [ ] **Dernière étape** : Bouton "Terminer" au lieu de "Suivant"
- [ ] **Message complétion** : Toast affiché après dernière étape
- [ ] **LocalStorage** : `completed` = true après tour terminé
- [ ] **Visite suivante** : Modal ne s'affiche plus
- [ ] **Bouton aide** : Relance le tour sans modal
- [ ] **Clic "Passer"** : Tour s'arrête et nettoie le DOM
- [ ] **Touches clavier** : Echap ferme la modal/tour

#### Tests sélecteurs
- [ ] Tous les sélecteurs CSS ciblent le bon élément
- [ ] Aucune erreur console (élément non trouvé)
- [ ] Spotlight positionné correctement autour de chaque élément
- [ ] Tooltips ne débordent jamais du viewport

#### Tests responsive
- [ ] Desktop (> 1200px) : Tout fonctionne correctement
- [ ] Tablet (768-1199px) : Tooltips adaptés
- [ ] Mobile (< 768px) : Tour désactivé ou adapté

#### Tests accessibilité
- [ ] Navigation clavier complète possible
- [ ] Attributs ARIA présents et corrects
- [ ] Contraste couleurs suffisant (WCAG AA)
- [ ] Focus visible sur tous les éléments interactifs
- [ ] Screen reader compatible (tester avec NVDA/VoiceOver)

### Phase 6 : Documentation

- [ ] Ajouter un commentaire JSDoc en tête du fichier JS
- [ ] Documenter les étapes spécifiques à cette page
- [ ] Mettre à jour ce guide si nouveaux patterns découverts
- [ ] Ajouter des screenshots dans `/docs` si nécessaire

### Phase 7 : Commit

- [ ] Créer un commit atomique avec message clair
- [ ] Format : `feat(onboarding): implémenter DAP pour [nom page]`
- [ ] Inclure dans le body du commit :
  - Nombre d'étapes créées
  - Éléments ciblés
  - Particularités de cette page
- [ ] Tester sur un autre poste avant push

---

## Références

### Implémentations existantes

1. **Dashboard** (`dashboard_light.html` + `onboarding-dap.js`)
   - 5 étapes : Filtres, Création, KPI, Cartes campagnes, Menu user
   - Particularité : Cards cliquables, filtres interactifs

2. **Step 1 Create** (`step1_create_light.html` + `onboarding-dap-step1.js`)
   - 5 étapes : Stepper, Infos base, Objectifs, Budget, Speed Dial
   - Particularité : Sections formulaire avec delay CSS

3. **Step 1 Review** (`step1_review_light.html` + `onboarding-dap-step1-review.js`)
   - 6 étapes : Nom campagne, Identité marque, Intelligence business, Keywords, Recommandations, Speed Dial
   - Particularité : Cards d'informations enrichies par IA

### CSS Classes disponibles

Toutes les classes DAP sont définies dans `assets/css/main.css` :
- `.onboarding-overlay`
- `.onboarding-spotlight`
- `.onboarding-tooltip`
- `.onboarding-tooltip-header`
- `.onboarding-tooltip-icon`
- `.onboarding-tooltip-title`
- `.onboarding-tooltip-content`
- `.onboarding-tooltip-footer`
- `.onboarding-progress`
- `.onboarding-progress-dot`
- `.onboarding-btn`
- `.onboarding-help-button-inline`
- `.position-top`, `.position-bottom`, `.position-left`, `.position-right`

### Bootstrap Icons

Documentation complète : https://icons.getbootstrap.com/

Exemples utilisés dans le DAP :
- `bi-stars`, `bi-lightning-charge-fill`, `bi-robot` : IA
- `bi-list-ol`, `bi-signpost` : Navigation
- `bi-bullseye`, `bi-trophy` : Objectifs
- `bi-cash-stack`, `bi-wallet2` : Budget/Finance
- `bi-palette`, `bi-briefcase`, `bi-search` : Informations
- `bi-play-circle`, `bi-arrow-right` : Actions

---

## Conclusion

Ce guide doit servir de référence unique pour toute nouvelle implémentation DAP. En suivant ces principes et patterns, vous garantissez :

✅ **Cohérence** : UX identique sur toutes les pages
✅ **Maintenabilité** : Code structuré et documenté
✅ **Qualité** : Tests complets et accessibilité respectée
✅ **Performance** : Optimisations appliquées dès le départ

**Prochain objectif :** Implémenter le DAP sur les 5 pages restantes du workflow de création de campagne (Step 2 à Step 8).

---

**Document vivant** : Ce guide sera mis à jour au fur et à mesure des nouvelles implémentations et des retours utilisateurs.
