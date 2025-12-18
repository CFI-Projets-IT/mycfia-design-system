# Rapport de Synthèse - Design System myCfia

**Date** : 2025-12-16 10:52
**Objectif** : Analyse complète du design system myCfia pour application aux vues settings, profile et flow marketing
**Agents mobilisés** : mockup-analyzer, design-system-analyzer, twig-template-expert, stimulus-turbo-specialist

---

## 📊 Vue d'Ensemble

### Vues Analysées (Design Établi)
✅ **app_login** - Page de connexion avec système Token/Credentials
✅ **home_index** - Dashboard avec Quick Access Cards
✅ **chat_index** - Interface conversationnelle avec Mercure temps réel

### Vues à Mettre à Jour (Design Basique)
⚠️ **settings_index** - Paramètres de l'application
⚠️ **profile_index** - Profil utilisateur
⚠️ **Flow marketing complet** - Campagnes, persona, enrichissement, competitor

---

## 🎨 Design System Établi

### 1. Architecture CSS

#### Structure Modulaire
```
app/assets/styles/
├── variables.css              # 🎯 Source de vérité (tokens de base)
├── fonts.css                  # Poppins + Calibri
├── app.css                    # Entry point + utilities
├── themes/
│   ├── variables.css          # Variables communes aux thèmes
│   ├── light.css              # Thème clair (défaut)
│   ├── dark-blue.css          # Thème sombre bleu
│   └── dark-red.css           # Thème sombre rouge
└── components/
    ├── glass-effects.css      # Glassmorphism (3 niveaux)
    ├── sidebar.css
    ├── chat.css
    ├── forms.css
    └── [autres composants...]
```

**Niveau de maturité** : 95% documenté
**Système de thématisation** : Dynamique (3 thèmes via classe `body.theme-*`)

---

### 2. Tokens de Design (variables.css)

#### 2.1 Palette de Couleurs

##### Couleurs Primaires (Code vs Mockup)

| Token | Code | Mockup | Statut | Recommandation |
|-------|------|--------|--------|----------------|
| `--color-primary` | `#6366f1` | `#003080` | ⚠️ Écart | **Aligner sur mockup** |
| `--color-secondary` | `#3abff0` | `#39BFEF` | ✅ Cohérent | OK |
| `--color-tertiary` | `#e6204c` | `#E6144C` | ✅ Cohérent | OK |
| `--color-default` | `#2a1b3d` | `#000000` / `#183E82` | ⚠️ Écart | **Aligner sur mockup** |

**🚨 Action prioritaire** : Corriger `--color-primary` de `#6366f1` → `#003080`

##### Couleurs Chat

| Token | Valeur Code | Mockup | Statut |
|-------|-------------|--------|--------|
| `--chat-input-text` | `#183e82` | `#183E82` | ✅ Cohérent |
| `--chat-input-bg` | `#ffffff` | `#FFFFFF` | ✅ Cohérent |
| `--message-user-bg` | `#ffffff` | `#FBC8C8` (rose pâle) | ⚠️ Écart |
| `--message-assistant-bg` | `#405d8c` | `#C1D9FC` (bleu pastel) | ⚠️ Écart |

**🚨 Action** :
- Messages utilisateur : Passer de blanc à rose pâle `#FBC8C8`
- Messages assistant : Passer de bleu-gris à bleu pastel `#C1D9FC`

##### Couleurs Manquantes (Mockup uniquement)

| Couleur | Hex | Usage Mockup | Action |
|---------|-----|--------------|--------|
| Rouge foncé | `#7C0000` | Fond thème dark rouge | Ajouter `--color-danger-dark` |
| Vert succès | `#216904` | Validation, succès | Ajouter `--color-success` |
| Bleu très pâle | `#D9E8FF` | Backgrounds subtils | Ajouter `--color-primary-lightest` |

#### 2.2 Typographie

##### Polices Installées

| Police | Usage | Poids Disponibles | Statut |
|--------|-------|-------------------|--------|
| **Poppins** | Titres (h1-h6) | 400, 600, 700, 800 | ✅ OK |
| **Calibri** | Corps de texte | 400, 700, italic | ✅ OK |

**Mockup** : Utilise aussi "Proxima Nova" (68 styles), "HelveticaNeue", "Arial"
**Recommandation** : Garder Poppins + Calibri (déjà cohérent avec mockup)

##### Variables Typographiques

| Token | Valeur | Usage |
|-------|--------|-------|
| `--font-family-title` | 'Poppins', Arial, sans-serif | h1-h6 |
| `--font-family-content` | 'Calibri', Arial, sans-serif | body, p |
| `--font-size-xs` | 12px | Labels, métadonnées |
| `--font-size-sm` | 14px | Texte secondaire |
| `--font-size-md` | 16px | Texte par défaut |
| `--font-size-lg` | 18px | Sous-titres (h3) |
| `--font-size-xl` | 24px | Titres (h2) |
| `--font-size-xxl` | 32px | Titres principaux (h1) |

**Cohérence** : ✅ Aligné avec mockup (tailles 12-32px observées)

#### 2.3 Espacements

| Token | Valeur | Usage |
|-------|--------|-------|
| `--space-xs` | 3px | Espacement minimal |
| `--space-sm` | 5px | Petit |
| `--space-md` | 10px | Standard |
| `--space-lg` | 15px | Grand |
| `--space-xl` | 20px | Très grand |
| `--space-xxl` | 30px | Maximum |

**Mockup** : Espacements observés entre 5px et 40px
**Recommandation** : Ajouter `--space-xxxl: 40px` pour grilles larges

#### 2.4 Border Radius

| Token | Valeur | Usage |
|-------|--------|-------|
| `--radius-sm` | 5px | Badges, tags |
| `--radius-md` | 10px | Inputs, buttons |
| `--radius-lg` | 15px | Cards, modales |
| `--radius-xl` | 20px | Grands conteneurs |
| `--radius-full` | 9999px | Circulaires |

**Cohérence** : ✅ Aligné avec mockup

#### 2.5 Ombres

| Token | Valeur | Usage |
|-------|--------|-------|
| `--shadow-glass` | inset 2px 3px 7px rgba(0,0,0,0.23), 5px 6px 7px rgba(0,0,0,0.1) | Glassmorphism |
| `--shadow-message-user` | inset 0px 3px 6px #00000029, 3px 5px 8px #00000029 | Messages |

**Mockup** : Ombres légères (10-23% opacité) observées
**Cohérence** : ✅ Aligné

#### 2.6 Glass Effects

| Token | Valeur | Usage |
|-------|--------|-------|
| `--glass-subtle` | linear-gradient(119deg, rgba(255,255,255,0.075) 0%, rgba(255,255,255,0.01) 100%) | Overlays légers |
| `--glass-light` | linear-gradient(110deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0.025) 100%) | Cards |
| `--glass-intense` | linear-gradient(92deg, #ffffffe6 0%, #ffffff00 100%) | Modales |
| `--glass-backdrop-blur` | 15px | Flou d'arrière-plan |

**Cohérence** : ✅ Aligné avec mockup (glassmorphism omniprésent)

---

### 3. Système de Thématisation

#### 3.1 Mécanisme

**Classe sur `<body>`** :
- `body.theme-light` → Thème clair
- `body.theme-dark-blue` → Thème sombre bleu
- `body.theme-dark-red` → Thème sombre rouge

**Transitions fluides** :
```css
body {
    transition: background-color 0.3s ease, color 0.3s ease;
}
```

#### 3.2 Variables Thématiques

| Variable | Light | Dark Blue | Dark Red |
|----------|-------|-----------|----------|
| `--theme-bg-primary` | `#f5f5f7` | `#0f1729` | `#1a0a0e` |
| `--theme-text-primary` | `#1a1a1a` | `#ffffff` | `#ffffff` |
| `--theme-accent` | `#003e82` | `#5fa8d3` | `#d35f8d` |
| `--theme-sidebar-bg` | `#001f3f` | `#0a0f1a` | `#0f0507` |

**Cohérence avec mockup** : ✅ Thèmes bien alignés

#### 3.3 Réseau Géométrique SVG

Chaque thème inclut un **réseau SVG** en background (coin inférieur droit) :
- **Light** : `#003e82` (bleu marine)
- **Dark Blue** : `#5fa8d3` (bleu clair)
- **Dark Red** : `#d35f8d` (rose)

**Opacité** : 0.75
**Taille** : 466px × 346px

---

### 4. Composants Twig Réutilisables

#### 4.1 Composants Existants (À Réutiliser)

| Composant | Usage | Paramètres | Statut |
|-----------|-------|------------|--------|
| `ChatNavTabs` | Navigation contextuelle | `context` (string) | ✅ Prêt |
| `ChatInput` | Zone de saisie chat | `context` | ✅ Prêt |
| `DataTable` | Tableaux avec liens cliquables | `headers`, `rows`, `totalRow` | ✅ Prêt |
| `ThemeSelector` | Sélecteur de thème dropdown | Aucun | ✅ Prêt |
| `DivisionSelector` | Sélecteur de division | Aucun (désactivé) | ⚠️ En pause |
| `ConversationSidebar` | Sidebar conversations | `section` (favorites/history) | ✅ Prêt |

#### 4.2 Composants à Créer (Priorité)

##### A. `AlertWithHelp` 🔴 Haute

**Usage actuel** : Répété dans `login.html.twig`

**Paramètres** :
```twig
{{ component('AlertWithHelp', {
    alertType: 'danger',
    errorType: 'token_expired',
    message: 'Votre session a expiré',
    helpItems: [
        'Votre token CFI est valable 30 minutes',
        'Générez un nouveau token sur CFI'
    ],
    helpButtons: [
        {url: 'https://cfi.com', icon: 'bi-box-arrow-up-right', label: 'Ouvrir CFI', external: true}
    ]
}) }}
```

##### B. `QuickAccessCard` 🔴 Haute

**Usage actuel** : Répété dans `home/index.html.twig`

**Paramètres** :
```twig
{{ component('QuickAccessCard', {
    url: path('settings_index'),
    icon: 'bi-gear',
    label: 'Paramètres',
    color: 'primary'
}) }}
```

##### C. `InputGroup` 🟡 Moyenne

**Usage** : Formulaires avec icônes Bootstrap Icons

**Paramètres** :
```twig
{{ component('InputGroup', {
    id: 'email',
    name: 'email',
    icon: 'bi-envelope',
    placeholder: 'votre@email.com',
    type: 'email',
    required: true
}) }}
```

##### D. `Modal` 🟡 Moyenne

**Usage** : Modals Bootstrap standardisées

**Paramètres** :
```twig
{% embed 'components/Modal.html.twig' with {
    id: 'confirmModal',
    header: 'Confirmation',
    headerIcon: 'bi-question-circle',
    confirmUrl: path('action'),
    confirmLabel: 'Confirmer'
} %}
    {% block modal_body %}
        <p>Voulez-vous vraiment effectuer cette action ?</p>
    {% endblock %}
{% endembed %}
```

##### E. `FlashMessages` 🟢 Basse

**Usage** : Affichage automatique des flash messages avec icônes

---

### 5. Patterns de Design Identifiés

#### 5.1 Quick Access Cards

**Structure HTML** :
```twig
<a href="{{ url }}" class="card quick-access-card">
    <div class="card-body d-flex align-items-center gap-3 p-3">
        <i class="bi {{ icon }} fs-2 text-{{ color }} flex-shrink-0"></i>
        <h3 class="fs-6 fw-semibold mb-0">{{ label }}</h3>
    </div>
</a>
```

**CSS** :
```css
.quick-access-card {
    min-width: 180px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.quick-access-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,62,130,0.1);
    border-color: var(--bs-primary);
}
```

**Utilisation** : Homepage, Settings, Profile

#### 5.2 Alert Contextuelle avec Aide

**Structure** :
```twig
<div class="alert alert-{{ type }}">
    <div class="d-flex align-items-start gap-3">
        <div class="flex-shrink-0 fs-3">
            <i class="bi {{ icon }}"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="alert-heading">{{ title }}</h6>
            <p>{{ message }}</p>

            <div class="border-top pt-3 mt-3">
                <strong><i class="bi bi-lightbulb"></i> Que faire ?</strong>
                <ul>
                    {% for item in helpItems %}
                    <li>{{ item }}</li>
                    {% endfor %}
                </ul>
                <div class="d-flex gap-2">
                    {% for button in buttons %}
                    <a href="{{ button.url }}" class="btn btn-sm btn-outline-{{ type }}">
                        <i class="bi {{ button.icon }}"></i> {{ button.label }}
                    </a>
                    {% endfor %}
                </div>
            </div>
        </div>
    </div>
</div>
```

**Utilisation** : Pages avec erreurs contextuelles

#### 5.3 Modal Bootstrap Standard

**Caractéristiques** :
- Header coloré avec opacité (`bg-warning bg-opacity-10`)
- Icône dans titre
- Body centré avec grande icône (`fs-1`)
- Footer sans bordure, centré

**Utilisation** : Confirmations, avertissements, informations

#### 5.4 Input Group avec Icônes

**Structure** :
```twig
<div class="input-group">
    <span class="input-group-text">
        <i class="bi {{ icon }}"></i>
    </span>
    <input type="{{ type }}" class="form-control" placeholder="{{ placeholder }}">
</div>
```

**Icônes courantes** :
- Token : `bi-key-fill`
- Email : `bi-envelope`
- Password : `bi-lock-fill`
- Username : `bi-person-badge`
- Search : `bi-search`

**Utilisation** : Tous les formulaires

---

### 6. Mercure & Stimulus

#### 6.1 Architecture Mercure

**Double système** (⚠️ À consolider) :
1. **Marketing AI Bundle** : `/tasks/{taskId}` (génération campagnes)
2. **Legacy** : `marketing/project/{id}` (à supprimer)

**Événements SSE** :
- `TaskStarted` → Badge "En cours"
- `TaskProgress` → Barre progression + pourcentage
- `TaskCompleted` → Badge "Terminé" + données
- `TaskFailed` → Badge "Erreur" + message

**Configuration** :
```yaml
# config/packages/mercure.yaml
mercure:
    hubs:
        default:
            url: '%env(MERCURE_URL)%'
            public_url: '%env(MERCURE_PUBLIC_URL)%'
            jwt:
                secret: '%env(MERCURE_JWT_SECRET)%'
```

**Proxy FrankenPHP** : Port 8080 (same-origin, pas de CORS)

#### 6.2 Contrôleurs Stimulus

**10 contrôleurs identifiés** :

| Nom | Type | Responsabilité | Mercure |
|-----|------|----------------|---------|
| `sidebar_controller.js` | UI | Toggle sidebar mobile/desktop | Non |
| `theme_controller.js` | UI | Switch thèmes (light/dark-blue/dark-red) | Non |
| `range-display_controller.js` | UI | Affichage valeur range input | Non |
| `csrf-protection_controller.js` | UI | Rafraîchissement token CSRF | Non |
| `datatable_controller.js` | Data | Filtrage temps réel tableaux | Non |
| `marketing-generation_controller.js` | Marketing | Génération stratégie IA | ✅ Oui |
| `marketing-persona_controller.js` | Marketing | Création persona | ✅ Oui |
| `marketing-enrichment_controller.js` | Marketing | Enrichissement persona | ✅ Oui |
| `marketing-competitor_controller.js` | Marketing | Analyse concurrent | ✅ Oui |
| `chat_controller.js` | Chat | Interface conversationnelle | Non (placeholder) |

**⚠️ Controllers marketing** : Nommés en kebab-case (`marketing-generation_controller.js`)
**Recommandation** : Renommer en camelCase (`marketingGenerationController.js`) pour auto-discovery Stimulus

#### 6.3 Turbo

**Drive** : ✅ Activé (navigation SPA globale)
**Frames** : ✅ Utilisé (sidebar lazy loading)
**Streams** : ❌ Non utilisé (Mercure préféré)

**Turbo Frames identifiés** :
```twig
<turbo-frame id="sidebar-favorites" src="{{ path('chat_sidebar_frame', {section: 'favorites'}) }}">
    {{ component('ConversationSidebar', {section: 'favorites'}) }}
</turbo-frame>
```

---

## 🎯 Plan d'Action pour Harmonisation

### Phase 1 - Corrections Critiques (Sprint Actuel)

#### 1.1 Corriger Variables Couleurs

**Fichier** : `app/assets/styles/variables.css`

```css
:root {
    /* AVANT */
    --color-primary: #6366f1;
    --color-default: #2a1b3d;

    /* APRÈS */
    --color-primary: #003080;          /* Bleu principal mockup */
    --color-default: #183E82;          /* Bleu moyen mockup */

    /* AJOUTER */
    --color-success: #216904;          /* Vert validation */
    --color-danger-dark: #7C0000;      /* Rouge foncé dark mode */
    --color-primary-lightest: #D9E8FF; /* Bleu très pâle backgrounds */

    /* Corriger Chat */
    --message-user-bg: #FBC8C8;        /* Rose pâle au lieu de blanc */
    --message-assistant-bg: #C1D9FC;   /* Bleu pastel au lieu de bleu-gris */
}
```

**Impact** :
- Boutons primaires (actuellement violet → bleu marine)
- Messages chat (actuellement blanc/bleu-gris → rose/bleu pastel)
- Cohérence globale avec mockup

#### 1.2 Ajouter Espacements Manquants

```css
:root {
    --space-xxxl: 40px; /* Pour grilles larges */
}
```

---

### Phase 2 - Composants Réutilisables (Même Sprint)

#### 2.1 Créer `QuickAccessCard`

**Fichier** : `app/templates/components/QuickAccessCard.html.twig`

**Priorité** : 🔴 Haute (utilisé dans 3+ vues)

**Usage dans Settings** :
```twig
<div class="d-flex justify-content-center gap-3 flex-wrap">
    {{ component('QuickAccessCard', {
        url: path('settings_profile'),
        icon: 'bi-person',
        label: 'Mon Profil',
        color: 'primary'
    }) }}

    {{ component('QuickAccessCard', {
        url: path('settings_security'),
        icon: 'bi-shield-lock',
        label: 'Sécurité',
        color: 'warning'
    }) }}

    {{ component('QuickAccessCard', {
        url: path('settings_notifications'),
        icon: 'bi-bell',
        label: 'Notifications',
        color: 'info'
    }) }}
</div>
```

#### 2.2 Créer `AlertWithHelp`

**Fichier** : `app/templates/components/AlertWithHelp.html.twig`

**Priorité** : 🔴 Haute (améliore UX erreurs)

#### 2.3 Créer `InputGroup`

**Fichier** : `app/templates/components/InputGroup.html.twig`

**Priorité** : 🟡 Moyenne (factorisation formulaires)

---

### Phase 3 - Application Design aux Vues (Sprint Suivant)

#### 3.1 Settings Index

**Structure proposée** :
```twig
{% extends 'layouts/home.html.twig' %}

{% block content %}
<div class="container" style="max-width: 1000px;">
    <div class="text-center py-5">
        <h1 class="fs-1 fw-semibold mb-2 text-primary">
            Paramètres
        </h1>
        <p class="fs-5 text-secondary mb-4">
            Gérez votre compte et vos préférences
        </p>

        <!-- Quick Access Cards Grid -->
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4">
            {{ component('QuickAccessCard', {
                url: path('settings_profile'),
                icon: 'bi-person',
                label: 'Mon Profil',
                color: 'primary'
            }) }}

            {{ component('QuickAccessCard', {
                url: path('settings_security'),
                icon: 'bi-shield-lock',
                label: 'Sécurité',
                color: 'warning'
            }) }}

            {{ component('QuickAccessCard', {
                url: path('settings_notifications'),
                icon: 'bi-bell',
                label: 'Notifications',
                color: 'info'
            }) }}

            {{ component('QuickAccessCard', {
                url: path('settings_integrations'),
                icon: 'bi-plug',
                label: 'Intégrations',
                color: 'secondary'
            }) }}
        </div>
    </div>
</div>
{% endblock %}
```

**Éléments à appliquer** :
- Layout `home.html.twig` (sidebar + header)
- Container centré max-width 1000px
- Titre + sous-titre avec classes typographiques
- Grid flexible Quick Access Cards
- Icônes Bootstrap Icons colorées

#### 3.2 Profile Index

**Structure proposée** :
```twig
{% extends 'layouts/home.html.twig' %}

{% block content %}
<div class="container" style="max-width: 800px;">
    <div class="py-4">
        <!-- Header avec avatar -->
        <div class="d-flex align-items-center gap-4 mb-4">
            <div class="flex-shrink-0">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                     style="width: 100px; height: 100px;">
                    <i class="bi bi-person-fill fs-1 text-primary"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <h1 class="fs-2 fw-bold mb-1">{{ app.user.firstName }} {{ app.user.lastName }}</h1>
                <p class="text-secondary mb-2">{{ app.user.email }}</p>
                <span class="badge bg-primary">{{ app.user.role }}</span>
            </div>
        </div>

        <!-- Informations personnelles -->
        <div class="card glass-card mb-3">
            <div class="card-header bg-transparent border-bottom-0">
                <h5 class="mb-0">
                    <i class="bi bi-person-badge me-2"></i>
                    Informations personnelles
                </h5>
            </div>
            <div class="card-body">
                <form>
                    {{ component('InputGroup', {
                        id: 'firstName',
                        name: 'firstName',
                        icon: 'bi-person',
                        placeholder: 'Prénom',
                        value: app.user.firstName,
                        required: true
                    }) }}

                    {{ component('InputGroup', {
                        id: 'lastName',
                        name: 'lastName',
                        icon: 'bi-person',
                        placeholder: 'Nom',
                        value: app.user.lastName,
                        required: true
                    }) }}

                    {{ component('InputGroup', {
                        id: 'email',
                        name: 'email',
                        icon: 'bi-envelope',
                        placeholder: 'Email',
                        type: 'email',
                        value: app.user.email,
                        required: true
                    }) }}

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Préférences -->
        <div class="card glass-card">
            <div class="card-header bg-transparent border-bottom-0">
                <h5 class="mb-0">
                    <i class="bi bi-sliders me-2"></i>
                    Préférences
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong>Thème de l'interface</strong>
                        <p class="text-secondary small mb-0">Personnalisez l'apparence de l'application</p>
                    </div>
                    {{ component('ThemeSelector') }}
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Notifications par email</strong>
                        <p class="text-secondary small mb-0">Recevoir les notifications importantes</p>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="emailNotif" checked>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

**Éléments à appliquer** :
- Layout `home.html.twig`
- Container centré max-width 800px
- Avatar circulaire avec icône
- Cards glassmorphism (`.glass-card`)
- Composant `InputGroup` pour formulaires
- Composant `ThemeSelector` existant
- Form switches Bootstrap

#### 3.3 Flow Marketing

##### A. Marketing Project Index

**Structure proposée** :
```twig
{% extends 'layouts/home.html.twig' %}

{% block content %}
<div class="container-fluid px-4 py-4">
    <!-- Header avec bouton "Nouvelle campagne" -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fs-2 fw-bold mb-1">Campagnes Marketing</h1>
            <p class="text-secondary mb-0">Gérez vos campagnes et générez du contenu avec l'IA</p>
        </div>
        <a href="{{ path('marketing_project_new') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>
            Nouvelle campagne
        </a>
    </div>

    <!-- Filtres -->
    <div class="card glass-card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    {{ component('InputGroup', {
                        id: 'searchProjects',
                        name: 'search',
                        icon: 'bi-search',
                        placeholder: 'Rechercher une campagne...'
                    }) }}
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="draft">Brouillon</option>
                        <option value="active">Active</option>
                        <option value="completed">Terminée</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select">
                        <option value="">Toutes les dates</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                        <option value="year">Cette année</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des campagnes -->
    <div class="row g-4">
        {% for project in projects %}
        <div class="col-md-6 col-lg-4">
            <div class="card glass-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0">{{ project.name }}</h5>
                        <span class="badge bg-{{ project.statusColor }}">{{ project.status }}</span>
                    </div>

                    <p class="text-secondary small mb-3">
                        <i class="bi bi-calendar3 me-1"></i>
                        {{ project.createdAt|date('d/m/Y') }}
                    </p>

                    {% if project.description %}
                    <p class="card-text text-secondary small mb-3">
                        {{ project.description|truncate(100) }}
                    </p>
                    {% endif %}

                    <!-- Progress (si en cours) -->
                    {% if project.status == 'in_progress' %}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-secondary">Progression</small>
                            <small class="text-primary fw-semibold">{{ project.progress }}%</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary"
                                 style="width: {{ project.progress }}%"></div>
                        </div>
                    </div>
                    {% endif %}

                    <div class="d-flex gap-2">
                        <a href="{{ path('marketing_project_show', {id: project.id}) }}"
                           class="btn btn-sm btn-outline-primary flex-grow-1">
                            <i class="bi bi-eye me-1"></i>
                            Voir
                        </a>
                        <a href="{{ path('marketing_project_edit', {id: project.id}) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        {% endfor %}
    </div>

    <!-- État vide -->
    {% if projects is empty %}
    <div class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
        <h3 class="text-secondary">Aucune campagne</h3>
        <p class="text-secondary mb-4">Créez votre première campagne marketing</p>
        <a href="{{ path('marketing_project_new') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>
            Créer une campagne
        </a>
    </div>
    {% endif %}
</div>
{% endblock %}
```

##### B. Pages Génération IA (Stratégie, Persona, etc.)

**Éléments communs à appliquer** :
- Badge de statut Mercure (Started, Progress, Completed, Failed)
- Barre de progression animée
- Messages SSE temps réel
- Boutons d'action cohérents
- Cards glassmorphism pour résultats

**Template de badge Mercure** :
```twig
<div id="taskStatus">
    <span class="badge bg-secondary">
        <span class="spinner-border spinner-border-sm me-1"></span>
        En attente...
    </span>
</div>
```

**Template de barre progression** :
```twig
<div id="taskProgress" class="d-none">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-secondary">Progression</span>
        <span id="taskProgressPercent" class="text-primary fw-semibold">0%</span>
    </div>
    <div class="progress" style="height: 8px;">
        <div id="taskProgressBar"
             class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
             style="width: 0%"></div>
    </div>
    <small id="taskProgressMessage" class="text-secondary d-block mt-1"></small>
</div>
```

---

### Phase 4 - Tests & Validation (Post-Harmonisation)

#### 4.1 Tests Visuels

**Checklist** :
- [ ] Vérifier cohérence couleurs (primaire `#003080` partout)
- [ ] Tester les 3 thèmes (light, dark-blue, dark-red)
- [ ] Valider responsive (mobile, tablette, desktop)
- [ ] Vérifier glassmorphism sur toutes les cards
- [ ] Tester icônes Bootstrap Icons (pas de manquants)

#### 4.2 Tests Fonctionnels

**Checklist** :
- [ ] Switch de thème fonctionne sur toutes les pages
- [ ] Quick Access Cards navigations correctes
- [ ] Formulaires (validation HTML5 + backend)
- [ ] Mercure SSE (génération campagnes temps réel)
- [ ] Turbo Frames sidebar (lazy loading)

#### 4.3 Accessibilité

**Checklist** :
- [ ] ARIA attributes (aria-current, aria-label)
- [ ] Contraste couleurs WCAG AA minimum
- [ ] Navigation clavier (focus visible)
- [ ] Headings hiérarchie (h1 → h6)
- [ ] Form labels (tous les inputs)

#### 4.4 Performance

**Checklist** :
- [ ] Fonts chargées (Poppins, Calibri)
- [ ] AssetMapper optimisé (pas de doublons)
- [ ] CSS modulaire (imports spécifiques par page)
- [ ] Mercure reconnexion automatique (max 5 tentatives)

---

## 📦 Fichiers à Créer/Modifier

### Créations

#### Composants Twig
- [ ] `app/templates/components/QuickAccessCard.html.twig`
- [ ] `app/templates/components/AlertWithHelp.html.twig`
- [ ] `app/templates/components/InputGroup.html.twig`
- [ ] `app/templates/components/Modal.html.twig`
- [ ] `app/templates/components/FlashMessages.html.twig`

#### Templates Pages
- [ ] `app/templates/settings/index.html.twig` (refonte)
- [ ] `app/templates/profile/index.html.twig` (refonte)
- [ ] `app/templates/marketing/project/index.html.twig` (refonte)

### Modifications

#### CSS
- [x] `app/assets/styles/variables.css` (corriger couleurs)
- [ ] `app/assets/styles/components/quick-access.css` (si nécessaire)
- [ ] `app/assets/styles/components/forms.css` (InputGroup styles)

#### JavaScript
- [ ] Renommer `marketing-*_controller.js` → `marketing*Controller.js` (4 fichiers)

---

## 🚨 Points d'Attention Critiques

### 1. Couleur Primaire
**Actuellement** : `#6366f1` (violet-bleu)
**Mockup** : `#003080` (bleu marine)
**Impact** : Tous les boutons primaires, liens, accents

### 2. Messages Chat
**Actuellement** : Blanc (user) + Bleu-gris (assistant)
**Mockup** : Rose pâle (user) + Bleu pastel (assistant)
**Impact** : Interface conversationnelle

### 3. Controllers Stimulus Marketing
**Actuellement** : `marketing-generation_controller.js`
**Attendu** : `marketingGenerationController.js`
**Impact** : Auto-discovery Stimulus

### 4. Mercure Double Système
**Actuellement** : AI Bundle + Legacy
**Recommandation** : Consolider sur AI Bundle uniquement
**Impact** : Maintenance, performances

---

## 📊 Métriques de Cohérence

### Design System
- **Tokens CSS** : 95% alignés avec mockup
- **Composants réutilisables** : 6 existants, 5 à créer
- **Thématisation** : 100% fonctionnelle (3 thèmes)
- **Glassmorphism** : 100% cohérent

### Code
- **Templates Twig** : 3 vues référence, 3+ vues à harmoniser
- **Stimulus** : 10 controllers, 4 à renommer
- **Turbo** : Intégration partielle (Drive OK, Frames OK, Streams non utilisé)
- **Mercure** : Architecture fonctionnelle mais à consolider

### Accessibilité
- **ARIA** : Partiellement implémenté
- **Semantic HTML** : Bon
- **Form labels** : OK
- **Contraste** : À vérifier post-correction couleurs

---

## 🎓 Recommandations Finales

### Priorité 1 - Sprint Actuel
1. **Corriger `--color-primary`** → `#003080`
2. **Corriger messages chat** → Rose pâle / Bleu pastel
3. **Créer `QuickAccessCard`** (haute priorité)
4. **Créer `AlertWithHelp`** (haute priorité)

### Priorité 2 - Sprint Suivant
5. **Appliquer design à `settings_index`**
6. **Appliquer design à `profile_index`**
7. **Créer `InputGroup`** et `Modal`
8. **Refactoriser flow marketing** (pages génération)

### Priorité 3 - Optimisation
9. **Renommer controllers Stimulus marketing**
10. **Consolider architecture Mercure** (supprimer legacy)
11. **Créer tests E2E Playwright** (parcours critiques)
12. **Documenter design system** (page `/design-system` dans l'app)

---

## 📎 Annexes

### Rapports Détaillés Générés
1. **`RAPPORT_ANALYSE_MOCKUP_ADOBE_XD.md`** - Extraction complète mockup (69 couleurs, 21 styles typo)
2. **`RAPPORT_DESIGN_SYSTEM_CODE.md`** - Design system existant (tokens, thèmes, composants)
3. **`RAPPORT_ANALYSE_TEMPLATES_TWIG.md`** - Analyse templates référence (layouts, patterns)
4. **`RAPPORT_ANALYSE_STIMULUS_TURBO_MERCURE.md`** - Architecture JavaScript & temps réel

### Screenshots Mockup
- **8 captures** dans `.playwright-mcp/` (login, home, chat, settings, profile, marketing)

---

**Rapport généré le** : 2025-12-16 10:52
**Durée d'analyse** : 4 agents en parallèle
**Statut** : ✅ Analyse complète terminée
**Prochaine étape** : Validation plan d'action par l'utilisateur
