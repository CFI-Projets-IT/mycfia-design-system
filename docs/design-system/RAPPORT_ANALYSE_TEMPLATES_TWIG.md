# Rapport d'Analyse des Templates Twig - myCFiia

**Date** : 2025-01-16
**Projet** : myCFiia - Plateforme d'automatisation marketing multi-canal avec IA conversationnelle
**Objectif** : Analyse complète de la structure, des patterns et des composants Twig existants

---

## Table des Matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Structure et Héritage des Layouts](#2-structure-et-héritage-des-layouts)
3. [Analyse des Pages de Référence](#3-analyse-des-pages-de-référence)
4. [Composants Twig Réutilisables](#4-composants-twig-réutilisables)
5. [Design System et Variables CSS](#5-design-system-et-variables-css)
6. [Patterns et Bonnes Pratiques](#6-patterns-et-bonnes-pratiques)
7. [Système de Traduction](#7-système-de-traduction)
8. [Composants à Créer](#8-composants-à-créer)
9. [Recommandations](#9-recommandations)
10. [Annexes](#10-annexes)

---

## 1. Vue d'Ensemble

### 1.1 Contexte

Le projet myCFiia utilise une architecture Twig moderne avec :
- **Symfony 7.3** (préparation vers 8.0)
- **Bootstrap 5.3.8** (intégration via AssetMapper)
- **Bootstrap Icons** (intégration via AssetMapper)
- **Twig Components** (Symfony UX)
- **Turbo Frames** (Symfony UX)
- **Design System personnalisé** avec variables CSS

### 1.2 Arborescence des Templates

```
app/templates/
├── base.html.twig                    # Layout racine (minimal)
├── layouts/
│   ├── auth.html.twig               # Layout authentification (hexagones animés)
│   └── home.html.twig               # Layout principal application (sidebar + header)
├── components/                       # 13 composants Twig
│   ├── ChatInput.html.twig
│   ├── ChatMessageAssistant.html.twig
│   ├── ChatMessageUser.html.twig
│   ├── ChatNavTabs.html.twig
│   ├── ChatSuggestedActions.html.twig
│   ├── ConversationSidebar.html.twig
│   ├── DataTable.html.twig
│   ├── DivisionSelector.html.twig
│   ├── ThemeSelector.html.twig
│   ├── chat/
│   │   ├── input.html.twig
│   │   └── message.html.twig
│   ├── sidebar.html.twig
│   └── topbar.html.twig
├── home/
│   └── index.html.twig              # Page d'accueil (quick access cards)
├── chat/
│   ├── index.html.twig              # Interface chat immersive
│   └── sidebar_frame.html.twig     # Frame Turbo pour sidebar dynamique
├── settings/
│   └── index.html.twig              # Page paramètres utilisateur
└── profile/
    └── index.html.twig              # Page profil utilisateur
```

### 1.3 Technologies Utilisées

| Technologie | Version | Usage |
|-------------|---------|-------|
| Symfony | 7.3 | Framework backend |
| Twig | 3.x | Moteur de templates |
| Bootstrap | 5.3.8 | Framework CSS |
| Bootstrap Icons | 1.11.x | Iconographie |
| AssetMapper | Native | Gestion assets front |
| Symfony UX | Latest | Composants + Turbo |

---

## 2. Structure et Héritage des Layouts

### 2.1 Layout Racine : `base.html.twig`

**Fichier** : `app/templates/base.html.twig`

```twig
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{% block title %}myCFiia{% endblock %}</title>
        <link rel="icon" href="{{ asset('images/logo_picto.svg') }}">

        {% block stylesheets %}
            {# Les CSS sont importés via app.js (méthode AssetMapper recommandée) #}
        {% endblock %}

        {% block javascripts %}
            {% block importmap %}{{ importmap('app') }}{% endblock %}
        {% endblock %}
    </head>
    <body>
        {% block body %}{% endblock %}
    </body>
</html>
```

**Caractéristiques** :
- Layout **ultra-minimaliste** (18 lignes)
- Ne charge **aucun CSS explicitement** (gestion via `importmap('app')` dans `app.js`)
- Favicon SVG adaptatif
- Aucune structure HTML imposée (délégation aux layouts enfants)
- Bloc `importmap` séparé pour flexibilité

**Blocks exposés** :
- `title` : Titre de la page
- `stylesheets` : CSS supplémentaires (vide par défaut)
- `javascripts` : Scripts JS
- `importmap` : AssetMapper import map
- `body` : Contenu complet de la page

---

### 2.2 Layout Authentification : `layouts/auth.html.twig`

**Fichier** : `app/templates/layouts/auth.html.twig`

```twig
{% extends 'base.html.twig' %}

{% block body %}
<div class="auth-layout d-flex min-vh-100 align-items-center justify-content-center p-5">
    {# Hexagones animés (10 éléments) #}
    <div class="anidiv">
        <div><img src="{{ asset('images/svgs/hexagon.svg') }}" alt=""></div>
        <!-- 9 autres hexagones -->
    </div>

    <div class="container" style="max-width: 586px;">
        {% block auth_content %}{% endblock %}
    </div>
</div>
{% endblock %}
```

**Caractéristiques** :
- Design **immersif avec hexagones animés** (background décoratif)
- Centrage vertical et horizontal (`min-vh-100` + flexbox)
- Container fixe à **586px** de largeur maximale
- Utilisé uniquement pour : login, register, forgot-password

**Blocks exposés** :
- `auth_content` : Formulaire d'authentification

**CSS associé** : `app/assets/styles/layouts/auth.css` + `app/assets/styles/components/hexagons.css`

**Usage** :
```twig
{% extends 'layouts/auth.html.twig' %}
{% block auth_content %}
    {# Formulaire login #}
{% endblock %}
```

---

### 2.3 Layout Principal : `layouts/home.html.twig`

**Fichier** : `app/templates/layouts/home.html.twig` (245 lignes)

#### Architecture Générale

```
┌─────────────────────────────────────────────────────────┐
│  <body class="theme-{{ app.user.theme }}">             │
│  ┌─────────────────────────────────────────────────┐   │
│  │  .home-layout (Flexbox)                         │   │
│  │  ┌─────────────┬───────────────────────────┐    │   │
│  │  │  SIDEBAR    │  MAIN CONTENT             │    │   │
│  │  │  (280px)    │  ┌─────────────────────┐  │    │   │
│  │  │             │  │ sticky-top-container │  │    │   │
│  │  │  - Header   │  │ - Header (logo+nav) │  │    │   │
│  │  │  - Nav      │  │ - Navigation tabs   │  │    │   │
│  │  │  - Footer   │  └─────────────────────┘  │    │   │
│  │  │             │  ┌─────────────────────┐  │    │   │
│  │  │             │  │ home-content        │  │    │   │
│  │  │             │  │ (scrollable)        │  │    │   │
│  │  │             │  │ - Flash messages    │  │    │   │
│  │  │             │  │ - Content block     │  │    │   │
│  │  │             │  └─────────────────────┘  │    │   │
│  │  │             │  ┌─────────────────────┐  │    │   │
│  │  │             │  │ sticky-bottom       │  │    │   │
│  │  │             │  │ (ChatInput)         │  │    │   │
│  │  │             │  └─────────────────────┘  │    │   │
│  │  └─────────────┴───────────────────────────┘    │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

#### Sections Principales

**1. Sidebar (Desktop + Mobile)**

```twig
{# Desktop : Fixed sidebar (280px) #}
<aside class="home-sidebar d-none d-lg-flex flex-column position-fixed">
    {# Header avec icônes (burger + close) #}
    <div class="sidebar-header">
        <button onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <button onclick="toggleSidebar()">
            <i class="bi bi-arrow-left-circle"></i>
        </button>
    </div>

    {# Navigation dynamique (Turbo Frames) #}
    <nav class="flex-grow-1 overflow-auto">
        {# Section Favoris #}
        <turbo-frame id="sidebar-favorites"
                     src="{{ path('chat_sidebar_frame', {section: 'favorites'}) }}">
            {{ component('ConversationSidebar', {section: 'favorites'}) }}
        </turbo-frame>

        {# Section Historique #}
        <turbo-frame id="sidebar-history"
                     src="{{ path('chat_sidebar_frame', {section: 'history'}) }}">
            {{ component('ConversationSidebar', {section: 'history'}) }}
        </turbo-frame>
    </nav>

    {# Footer : Paramètres, Profil, Déconnexion #}
    <div class="mt-auto pt-4 border-top">
        <a href="{{ path('settings_index') }}">
            <i class="bi bi-gear"></i>
            <span>{{ 'nav.footer.settings'|trans({}, 'navigation') }}</span>
        </a>
        <!-- Profil, Déconnexion -->
    </div>
</aside>

{# Mobile : Offcanvas #}
<aside class="offcanvas offcanvas-start d-lg-none" id="sidebarOffcanvas">
    {# Même structure que desktop, mais sans Turbo Frames #}
</aside>
```

**Points clés** :
- **Desktop** : sidebar fixe 280px, `d-none d-lg-flex`
- **Mobile** : offcanvas Bootstrap, `d-lg-none`
- **Navigation dynamique** : Turbo Frames pour rechargement partiel
- **Thème** : variable CSS `var(--theme-sidebar-bg)`

**2. Main Content**

```twig
<main class="flex-grow-1 d-flex flex-column home-main">
    {# CONTAINER 1 : Sticky top (header + nav) #}
    <div class="sticky-top-container">
        {# Header avec logo + actions #}
        <header class="home-header">
            <div class="d-flex justify-content-between">
                {# Logo cliquable (retour homepage) #}
                <a href="{{ path('home_index') }}">
                    {% set theme = app.user ? app.user.theme : 'light' %}
                    {% if theme == 'light' %}
                        <img src="{{ asset('images/logo_picto.svg') }}">
                    {% else %}
                        <img src="{{ asset('images/assistant-picto.svg') }}">
                    {% endif %}
                    <img src="{{ asset('images/logo.svg') }}" alt="myCFiia">
                </a>

                {# Actions : Division, Theme, Retour CFI #}
                <div class="d-flex align-items-center gap-3">
                    {{ component('DivisionSelector') }}
                    {{ component('ThemeSelector') }}
                    <a href="#">
                        <i class="bi bi-box-arrow-up-right"></i>
                        {{ 'nav.header.back_to_cfi'|trans({}, 'navigation') }}
                    </a>
                </div>
            </div>
        </header>

        {# Navigation contextuelle (block optionnel) #}
        {% block navigation %}{% endblock %}
    </div>

    {# CONTAINER 2 : Contenu scrollable #}
    <div class="home-content{% block content_classes %}{% endblock %}">
        {# Messages flash automatiques #}
        {% for type, messages in app.flashes %}
            {% for message in messages %}
                <div class="alert alert-{{ type == 'error' ? 'danger' : type }} alert-dismissible">
                    {% if type == 'success' %}
                        <i class="bi bi-check-circle-fill me-2"></i>
                    {% elseif type == 'error' or type == 'danger' %}
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {% elseif type == 'warning' %}
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                    {% elseif type == 'info' %}
                        <i class="bi bi-info-circle-fill me-2"></i>
                    {% endif %}
                    {{ message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            {% endfor %}
        {% endfor %}

        {% block content %}{% endblock %}
    </div>

    {# CONTAINER 3 : Sticky bottom (optionnel) #}
    {% block sticky_bottom %}{% endblock %}
</main>
```

**Points clés** :
- **3 containers distincts** : sticky-top, scrollable, sticky-bottom
- **Logo adaptatif** : change selon le thème utilisateur
- **Flash messages** : gestion automatique avec icônes Bootstrap
- **Blocks flexibles** : `navigation`, `content_classes`, `content`, `sticky_bottom`

**3. Modals**

```twig
{# Bloc pour les modals Bootstrap (z-index correct) #}
{% block modals %}{% endblock %}
```

**Blocks exposés** :
- `title` : Titre de la page
- `stylesheets` : CSS supplémentaires
- `navigation` : Navigation contextuelle (tabs chat)
- `content_classes` : Classes CSS ajoutées au container
- `content` : Contenu principal
- `sticky_bottom` : Input fixe en bas (chat)
- `modals` : Modals Bootstrap
- `javascripts` : Scripts JS

**CSS associé** :
- `app/assets/styles/layouts/home-layout.css`
- `app/assets/styles/components/sidebar.css`

---

## 3. Analyse des Pages de Référence

### 3.1 Page d'Accueil : `home/index.html.twig`

**Fichier** : `app/templates/home/index.html.twig` (90 lignes)

#### Structure

```twig
{% extends 'layouts/home.html.twig' %}

{% block title %}{{ 'home.page.title'|trans({}, 'home') }} - myCFiia{% endblock %}

{% block content %}
<div class="container" style="max-width: 1000px;">
    <div class="text-center py-5">
        {# Titre personnalisé avec prénom utilisateur #}
        <div class="d-inline-block text-start mb-5">
            <h1 class="fs-1 fw-semibold mb-2 text-primary">
                {{ 'home.greeting'|trans({}, 'home') }}
                <span class="text-danger fw-bold">{{ firstName }}</span>,
            </h1>
            <p class="fs-4 fw-medium text-primary mb-0">
                {{ 'home.question'|trans({}, 'home') }}
            </p>
        </div>

        {# Quick Access Cards (5 contextes) #}
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            {# Factures #}
            <a href="{{ path('chat_index', {context: 'factures'}) }}"
               class="card text-decoration-none shadow-sm quick-access-card"
               data-turbo="false">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <i class="bi bi-receipt fs-2 text-info flex-shrink-0"></i>
                    <h3 class="fs-6 fw-semibold text-primary mb-0">
                        {{ 'home.quick_access.invoices'|trans({}, 'home') }}
                    </h3>
                </div>
            </a>

            {# Commandes #}
            <a href="{{ path('chat_index', {context: 'commandes'}) }}"
               class="card text-decoration-none shadow-sm quick-access-card"
               data-turbo="false">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <i class="bi bi-list-check fs-2 text-success flex-shrink-0"></i>
                    <h3 class="fs-6 fw-semibold text-primary mb-0">
                        {{ 'home.quick_access.orders'|trans({}, 'home') }}
                    </h3>
                </div>
            </a>

            {# Stocks #}
            <a href="{{ path('chat_index', {context: 'stocks'}) }}"
               class="card text-decoration-none shadow-sm quick-access-card"
               data-turbo="false">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <i class="bi bi-box-seam fs-2 text-warning flex-shrink-0"></i>
                    <h3 class="fs-6 fw-semibold text-primary mb-0">
                        {{ 'home.quick_access.stocks'|trans({}, 'home') }}
                    </h3>
                </div>
            </a>

            {# Autre (général) #}
            <a href="{{ path('chat_index', {context: 'general'}) }}"
               class="card text-decoration-none shadow-sm quick-access-card"
               data-turbo="false">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <i class="bi bi-question-circle fs-2 text-primary flex-shrink-0"></i>
                    <h3 class="fs-6 fw-semibold text-primary mb-0">
                        {{ 'home.quick_access.other'|trans({}, 'home') }}
                    </h3>
                </div>
            </a>

            {# Marketing (modal d'avertissement) #}
            <a href="#"
               class="card text-decoration-none shadow-sm quick-access-card"
               data-bs-toggle="modal"
               data-bs-target="#marketingWarningModal">
                <div class="card-body d-flex align-items-center gap-3 p-3">
                    <i class="bi bi-magic fs-2 text-danger flex-shrink-0"></i>
                    <h3 class="fs-6 fw-semibold text-primary mb-0">
                        {{ 'home.quick_access.marketing'|trans({}, 'home') }}
                    </h3>
                </div>
            </a>
        </div>
    </div>
</div>
{% endblock %}

{% block modals %}
{# Modal d'avertissement MarketingAI #}
<div class="modal fade" id="marketingWarningModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10 border-bottom-0">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                    Attention
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <i class="bi bi-robot fs-1 text-muted"></i>
                </div>
                <p class="mb-3 fs-5">
                    Vous entrez dans l'ancienne interface <strong>"MyAgent"</strong>.
                </p>
                <p class="text-muted small mb-0">
                    Cette interface sera bientôt remplacée.
                </p>
            </div>
            <div class="modal-footer border-top-0 justify-content-center pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Annuler
                </button>
                <a href="{{ path('marketing_project_index') }}" class="btn btn-primary">
                    <i class="bi bi-megaphone me-1"></i>
                    Je souhaite créer une campagne
                </a>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

#### Caractéristiques

**Design** :
- Container centralisé **max-width: 1000px**
- Titre personnalisé avec **prénom utilisateur en rouge**
- 5 cartes d'accès rapide en **flexbox responsive**
- Modal d'avertissement pour l'interface Marketing legacy

**Cards Quick Access** :
- Classe unique : `.quick-access-card`
- Structure : icône (fs-2) + titre (fs-6)
- Couleurs iconographiques : `text-info`, `text-success`, `text-warning`, `text-primary`, `text-danger`
- Désactivation Turbo : `data-turbo="false"`

**Modal Bootstrap** :
- Header coloré : `bg-warning bg-opacity-10`
- Corps centré avec icône robot
- Footer avec 2 boutons (annulation + confirmation)

**CSS associé** : `app/assets/styles/components/quick-access.css`

**Variables passées au controller** :
- `firstName` : Prénom de l'utilisateur connecté

---

### 3.2 Page Chat : `chat/index.html.twig`

**Fichier** : `app/templates/chat/index.html.twig` (128 lignes)

#### Structure

```twig
{% extends 'layouts/home.html.twig' %}

{% block title %}Chat IA - myCFiia{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('styles/components/chat-nav-tabs.css') }}">
    <link rel="stylesheet" href="{{ asset('styles/components/chat-messages.css') }}">
    <link rel="stylesheet" href="{{ asset('styles/components/chat-input.css') }}">
    <link rel="stylesheet" href="{{ asset('styles/components/data-table.css') }}">
    <link rel="stylesheet" href="{{ asset('styles/chat.css') }}">
{% endblock %}

{% block navigation %}
{# Navigation Tabs - Juste après le header #}
{{ component('ChatNavTabs', { context: context }) }}
{% endblock %}

{% block content_classes %} chat-page{% endblock %}

{% block content %}
{# ==================================================================================
   NOUVELLE UI IMMERSIVE CHAT - EN DÉVELOPPEMENT

   ⚠️ IDs JavaScript REQUIS pour chat.js (NE PAS SUPPRIMER) :
   - #chatForm           → Formulaire de soumission
   - #chatInput          → Textarea de saisie
   - #chatMessages       → Conteneur des messages
   - #sendButton         → Bouton d'envoi
   - #charCount          → Compteur de caractères
   - #chatLoading        → Indicateur de chargement
   - #clearChat          → Bouton nouvelle conversation
   - #exportChat         → Bouton export
   - .quick-question     → Boutons suggestions (class)

   📋 Variables Twig disponibles :
   - context             → Type de chat (factures|commandes|stocks|general)
   - conversationId      → UUID de la conversation
   - mercureUrl          → URL du hub Mercure
   - mercureJwt          → Token JWT pour Mercure
   - app.user.fullName   → Nom complet de l'utilisateur
   ================================================================================== #}

{# Zone des messages - Pré-remplie si conversation chargée depuis BDD #}
<div id="chatMessages" class="chat-messages-container">
    {% if loadedConversation is defined and loadedConversation %}
        {# Messages existants chargés depuis la BDD #}
        {% for message in loadedConversation.messages %}
            {% if message.role == 'user' %}
                {# Message utilisateur #}
                <div class="chat-message chat-message-user">
                    <div class="chat-message-content">
                        <div class="chat-message-bubble">
                            {{ message.content|nl2br }}
                        </div>
                    </div>
                    <div class="chat-message-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                </div>
            {% else %}
                {# Message assistant #}
                <div class="chat-message chat-message-assistant">
                    <div class="chat-message-bubble">
                        <img src="{{ asset('images/assistant-picto.svg') }}" alt="IA"
                             class="chat-message-logo">
                        <div class="chat-message-text">
                            {{ message.content|nl2br }}
                        </div>
                    </div>
                </div>

                {# Si le message contient des données de tableau #}
                {% if message.type == 'table' and message.data.table_data is defined %}
                    <div class="chat-message chat-message-assistant mt-3">
                        <div class="chat-message-bubble">
                            <img src="{{ asset('images/assistant-picto.svg') }}" alt="IA"
                                 class="chat-message-logo">
                            <div class="chat-message-text">
                                {{ component('DataTable', {
                                    headers: message.data.table_data.headers,
                                    rows: message.data.table_data.rows,
                                    totalRow: message.data.table_data.totalRow|default(null),
                                    linkColumns: message.data.table_data.linkColumns|default({}),
                                    mode: message.data.table_data.mode|default('LISTE')
                                }) }}
                            </div>
                        </div>
                    </div>
                {% endif %}
            {% endif %}
        {% endfor %}
    {% else %}
        {# Les messages seront insérés ici dynamiquement via JavaScript #}
    {% endif %}
</div>

{# ==================================================================================
   DONNÉES JAVASCRIPT - ABSOLUMENT CRITIQUE - NE JAMAIS SUPPRIMER
   Ce div invisible injecte la configuration dans chat.js via data attributes
   ================================================================================== #}
<div id="chatData"
     data-context="{{ context }}"
     data-conversation-id="{{ conversationId }}"
     data-mercure-url="{{ mercureUrl }}"
     data-mercure-jwt="{{ mercureJwt }}"
     data-message-url="{{ path('chat_message', {context: context}) }}"
     data-stream-url="{{ path('chat_stream', {context: context}) }}"
     data-assistant-logo="{{ asset('images/assistant-picto.svg') }}"
     {% if loadedConversation is defined and loadedConversation %}
     data-loaded-conversation="{{ loadedConversation.id }}"
     data-is-favorite="{{ loadedConversation.isFavorite ? '1' : '0' }}"
     data-favorite-url="{{ path('chat_toggle_favorite', {id: loadedConversation.id}) }}"
     data-delete-url="{{ path('chat_delete_conversation', {id: loadedConversation.id}) }}"
     {% endif %}
     style="display: none;">
</div>
{% endblock %}

{% block sticky_bottom %}
{# Input fixe en bas #}
<div class="sticky-bottom-container">
    {{ component('ChatInput', { context: context }) }}
</div>
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    <script type="module" src="{{ asset('js/chat/chat.js') }}"></script>
{% endblock %}
```

#### Caractéristiques

**Architecture 3 couches** :
1. **Navigation tabs** (block `navigation`)
2. **Messages container** (block `content`)
3. **Input sticky** (block `sticky_bottom`)

**Chargement conversation** :
- **Conversation existante** : messages pré-rendus depuis BDD
- **Nouvelle conversation** : container vide (`#chatMessages`)
- **Messages dynamiques** : ajoutés via JavaScript + Mercure

**Messages** :
- **Utilisateur** : alignés à droite, avatar icône
- **Assistant** : alignés à gauche, logo assistant
- **Tableau** : composant `DataTable` intégré

**Configuration JavaScript** :
- Div invisible `#chatData` avec **12 data-attributes**
- Injection de : context, conversationId, URLs API, JWT Mercure
- Utilisé par `chat.js` pour initialisation

**CSS associé** :
- `styles/chat.css`
- `styles/components/chat-nav-tabs.css`
- `styles/components/chat-messages.css`
- `styles/components/chat-input.css`
- `styles/components/data-table.css`

**JavaScript** : `js/chat/chat.js`

**Variables passées au controller** :
- `context` : factures|commandes|stocks|general
- `conversationId` : UUID
- `mercureUrl` : URL hub Mercure
- `mercureJwt` : Token JWT
- `loadedConversation` : Objet conversation (optionnel)

---

## 4. Composants Twig Réutilisables

### 4.1 Inventaire des Composants

| Composant | Fichier | Usage | Complexité |
|-----------|---------|-------|------------|
| **ChatInput** | `ChatInput.html.twig` | Input chat avec placeholder contextuel | Simple |
| **ChatMessageAssistant** | `ChatMessageAssistant.html.twig` | Message de l'assistant IA | Simple |
| **ChatMessageUser** | `ChatMessageUser.html.twig` | Message utilisateur | Simple |
| **ChatNavTabs** | `ChatNavTabs.html.twig` | Navigation contextes chat | Moyen |
| **ChatSuggestedActions** | `ChatSuggestedActions.html.twig` | Suggestions actions rapides | Simple |
| **ConversationSidebar** | `ConversationSidebar.html.twig` | Liste conversations (Favoris/Historique) | Moyen |
| **DataTable** | `DataTable.html.twig` | Tableau de données avec liens cliquables | Complexe |
| **DivisionSelector** | `DivisionSelector.html.twig` | Sélecteur multi-tenant (désactivé) | Simple |
| **ThemeSelector** | `ThemeSelector.html.twig` | Sélecteur de thème visuel | Simple |

---

### 4.2 Analyse Détaillée des Composants

#### 4.2.1 ChatInput

**Fichier** : `app/templates/components/ChatInput.html.twig`

```twig
{#
  Composant Input Chat Immersif

  Usage :
  {{ component('ChatInput', { context: 'factures' }) }}

  Paramètres :
  - context (string) : Contexte du chat (factures|commandes|stocks|general)
#}

{% set placeholders = {
    'factures': 'Je recherche la/les facture(s) par <strong>année</strong> ou <strong>mois</strong> ?',
    'commandes': 'Je recherche la/les commande(s) par <strong>client</strong> ou <strong>référence</strong> ?',
    'stocks': 'Je recherche les stocks par <strong>référence</strong> ou <strong>niveau</strong> ?',
    'general': 'Posez votre question ici...'
} %}

<div class="chat-input-wrapper">
    <form id="chatForm" class="chat-input-form">
        <div class="chat-input-container">
            <textarea
                id="chatInput"
                class="chat-input-field"
                placeholder="{{ placeholders[context]|raw|striptags }}"
                rows="1"
                maxlength="500"
            ></textarea>
        </div>
        <button
            type="submit"
            id="sendButton"
            class="chat-send-button"
            aria-label="Envoyer le message"
        >
            <i class="bi bi-send-fill"></i>
        </button>
    </form>
</div>
```

**Caractéristiques** :
- **Placeholder contextualisé** : change selon le context
- **Textarea auto-expansible** : `rows="1"` + JavaScript
- **Limite de caractères** : 500 caractères max
- **IDs critiques** : `#chatForm`, `#chatInput`, `#sendButton`

**Props** :
- `context` (string, requis) : factures|commandes|stocks|general

**CSS** : `styles/components/chat-input.css`

**Usage** :
```twig
{{ component('ChatInput', { context: 'factures' }) }}
```

---

#### 4.2.2 ChatMessageAssistant

**Fichier** : `app/templates/components/ChatMessageAssistant.html.twig`

```twig
{#
    Composant ChatMessageAssistant - Message de l'assistant IA

    Props:
    - message: string (le texte du message)
    - timestamp: string (optionnel - horodatage)
#}

{% set message = message ?? 'Message de l\'assistant' %}
{% set timestamp = timestamp ?? null %}

<div class="chat-message chat-message-assistant">
    <div class="chat-message-bubble">
        <img src="{{ asset('images/assistant-picto.svg') }}" alt="IA"
             class="chat-message-logo">
        <div class="chat-message-text">
            {{ message|raw }}
        </div>
    </div>
</div>
```

**Caractéristiques** :
- **Logo assistant** : SVG adaptatif
- **Message HTML** : filtre `|raw` (attention sécurité)
- **Timestamp** : non implémenté (prop conservée pour futur)

**Props** :
- `message` (string, défaut : "Message de l'assistant")
- `timestamp` (string, optionnel)

**CSS** : `styles/components/chat-messages.css`

**Usage** :
```twig
{{ component('ChatMessageAssistant', { message: 'Bonjour !' }) }}
```

---

#### 4.2.3 ChatMessageUser

**Fichier** : `app/templates/components/ChatMessageUser.html.twig`

```twig
{#
    Composant ChatMessageUser - Message utilisateur

    Props:
    - message: string (le texte du message)
    - timestamp: string (optionnel - horodatage)
#}

{% set message = message ?? 'Message utilisateur' %}
{% set timestamp = timestamp ?? null %}

<div class="chat-message chat-message-user">
    <div class="chat-message-content">
        <div class="chat-message-bubble">
            {{ message }}
        </div>
    </div>
    <div class="chat-message-avatar">
        <i class="bi bi-person-fill"></i>
    </div>
</div>
```

**Caractéristiques** :
- **Avatar icône** : Bootstrap Icons `bi-person-fill`
- **Message texte brut** : pas de `|raw` (sécurité)
- **Timestamp** : non implémenté

**Props** :
- `message` (string, défaut : "Message utilisateur")
- `timestamp` (string, optionnel)

**CSS** : `styles/components/chat-messages.css`

**Usage** :
```twig
{{ component('ChatMessageUser', { message: 'Ma question' }) }}
```

---

#### 4.2.4 ChatNavTabs

**Fichier** : `app/templates/components/ChatNavTabs.html.twig`

```twig
{#
  Composant Navigation Tabs Chat

  Usage :
  {{ component('ChatNavTabs', { context: 'factures' }) }}

  Paramètres :
  - context (string) : Contexte actif (factures|commandes|stocks|general)
#}

{% set tabs = [
    { key: 'factures', label: 'Factures', icon: 'bi-receipt' },
    { key: 'commandes', label: 'Commandes', icon: 'bi-list-check' },
    { key: 'stocks', label: 'Stocks', icon: 'bi-box-seam' },
    { key: 'general', label: 'Autre', icon: 'bi-question-circle' }
] %}

<nav class="chat-nav-tabs" style="display: flex; justify-content: space-between;">
    <div class="chat-nav-tabs-container">
        {% for tab in tabs %}
            <a href="{{ path('chat_index', {context: tab.key}) }}"
               class="chat-nav-tab {{ context == tab.key ? 'active' : '' }}"
               {% if context == tab.key %}aria-current="page"{% endif %}>
                <i class="bi {{ tab.icon }}"></i>
                <span>{{ tab.label }}</span>
            </a>
        {% endfor %}
    </div>

    {# Actions : Nouvelle conversation + Favori #}
    <div class="chat-nav-actions d-flex align-items-center gap-2">
        {# Bouton Nouvelle conversation #}
        <a href="{{ path('chat_new_conversation', {context: context}) }}"
           class="btn btn-sm btn-outline-primary"
           data-turbo="false"
           title="Démarrer une nouvelle conversation">
            <i class="bi bi-plus-circle"></i>
            <span class="d-none d-md-inline ms-1">Nouvelle</span>
        </a>

        {# Bouton favori - visible si conversation chargée #}
        <div id="favoriteButtonContainer"></div>
    </div>
</nav>
```

**Caractéristiques** :
- **4 onglets contextuels** : factures, commandes, stocks, général
- **Onglet actif** : classe `.active` selon prop `context`
- **Actions** : nouvelle conversation + favori (container dynamique)
- **Responsive** : icône seule sur mobile, texte masqué

**Props** :
- `context` (string, requis) : factures|commandes|stocks|general

**CSS** : `styles/components/chat-nav-tabs.css`

**Usage** :
```twig
{% block navigation %}
    {{ component('ChatNavTabs', { context: 'factures' }) }}
{% endblock %}
```

---

#### 4.2.5 ConversationSidebar

**Fichier** : `app/templates/components/ConversationSidebar.html.twig`

```twig
{#
 # Composant sidebar pour l'affichage des conversations (Favoris ou Historique).
 #
 # Props:
 # - section (string): 'favorites' ou 'history'
 # - limit (int): Nombre de conversations à afficher (défaut: 5)
 #}

<div class="nav-section">
    {# En-tête de section (collapsible) #}
    <div class="nav-section-title-pill"
         data-bs-toggle="collapse"
         data-bs-target="#{{ section }}Section"
         aria-expanded="true">
        <div class="d-flex align-items-center gap-2">
            <i class="bi {{ this.sectionIcon }}"></i>
            <span>{{ this.sectionTitle }}</span>
        </div>
        <i class="bi bi-chevron-down"></i>
    </div>

    {# Contenu de la section (collapsible) - Toujours ouvert par défaut #}
    <div class="collapse show" id="{{ section }}Section">
        {% if this.isEmpty %}
            {# Message si aucune conversation #}
            <div class="px-3 py-2 text-white-50 small">
                {% if section == 'favorites' %}
                    Aucune conversation favorite
                {% else %}
                    Aucune conversation récente
                {% endif %}
            </div>
        {% else %}
            {# Liste des conversations #}
            <ul class="list-unstyled">
                {% for conversation in this.conversations %}
                    <li class="mb-2 position-relative">
                        <div class="d-flex align-items-center justify-content-between">
                            {# Lien principal vers la conversation #}
                            <a href="{{ path('chat_load_conversation', {
                                context: conversation.context,
                                conversationId: conversation.id
                            }) }}"
                               class="d-flex align-items-center flex-grow-1 px-3 py-2 rounded
                                      text-decoration-none nav-link
                                      {{ section == 'favorites' ? 'text-white' : 'text-white-50 small' }}"
                               title="{{ conversation.title }}"
                               data-turbo="false">

                                {# Titre tronqué (max 140px) #}
                                <span class="text-truncate" style="max-width: 140px;">
                                    {{ conversation.title }}
                                </span>

                                {# Badge contexte (historique uniquement) #}
                                {% if section == 'history' %}
                                    <span class="badge badge-sm ms-auto" style="font-size: 0.65rem;">
                                        {{ conversation.context|capitalize }}
                                    </span>
                                {% endif %}
                            </a>
                        </div>
                    </li>
                {% endfor %}
            </ul>
        {% endif %}
    </div>
</div>
```

**Caractéristiques** :
- **Composant Twig Live** : utilise `this.*` (propriétés dynamiques)
- **Collapsible Bootstrap** : en-tête cliquable
- **Texte tronqué** : `text-truncate` + max-width 140px
- **Badge contexte** : affiché uniquement dans l'historique
- **Bouton supprimer** : commenté (code conservé pour usage futur)

**Props** :
- `section` (string, requis) : 'favorites' ou 'history'
- `limit` (int, défaut : 5) : nombre max de conversations

**Propriétés Twig Component** :
- `this.sectionIcon` : icône selon section
- `this.sectionTitle` : titre traduit
- `this.isEmpty` : booléen (liste vide)
- `this.conversations` : tableau de conversations

**CSS** : `styles/components/sidebar.css`

**Usage** :
```twig
{# Turbo Frame pour rechargement dynamique #}
<turbo-frame id="sidebar-favorites"
             src="{{ path('chat_sidebar_frame', {section: 'favorites'}) }}">
    {{ component('ConversationSidebar', {section: 'favorites'}) }}
</turbo-frame>
```

---

#### 4.2.6 DataTable

**Fichier** : `app/templates/components/DataTable.html.twig`

```twig
{#
 # Composant DataTable - Tableau de données réutilisable
 #
 # Variables disponibles :
 # - headers : array<int, string>
 # - rows : array<int, array<string, mixed>>
 # - totalRow : array<string, mixed>|null
 # - linkColumns : array<string, string>
 # - striped : bool
 # - hover : bool
 # - responsive : bool
 # - showPdfIcon : bool
 # - pdfColumn : string
 #}

<div class="chat-datatable {% if responsive %}table-responsive{% endif %}">
    <table class="table {% if striped %}table-striped{% endif %}
                   {% if hover %}table-hover{% endif %} mb-0">
        <thead>
            <tr>
                {% for header in headers %}
                <th scope="col">{{ header }}</th>
                {% endfor %}
                {% if showPdfIcon %}
                <th scope="col" class="text-center pdf-column"></th>
                {% endif %}
            </tr>
        </thead>
        <tbody>
            {% for row in rows %}
            <tr>
                {% for key, value in row %}
                <td>
                    {# Si cette colonne a un lien cliquable configuré #}
                    {% if linkColumns[key] is defined and value %}
                        {# Générer le prompt en remplaçant {key} par la valeur #}
                        {% set prompt = linkColumns[key]|replace({('{' ~ key ~ '}'): value}) %}
                        <a href="#"
                           class="detail-link text-decoration-none fw-semibold"
                           data-action-prompt="{{ prompt }}"
                           data-entity-id="{{ value }}"
                           title="Cliquer pour voir les détails">
                            {{ value }}
                        </a>
                    {% else %}
                        {{ value }}
                    {% endif %}
                </td>
                {% endfor %}

                {% if showPdfIcon %}
                <td class="text-center pdf-column">
                    {# Icône PDF placeholder #}
                    <span class="pdf-icon-placeholder" title="PDF non disponible">
                        <i class="bi bi-file-pdf text-muted"></i>
                    </span>
                </td>
                {% endif %}
            </tr>
            {% endfor %}
        </tbody>

        {% if totalRow %}
        <tfoot>
            <tr class="table-total fw-bold">
                {% for key, value in totalRow %}
                <td {% if loop.first %}colspan="1"{% endif %}>{{ value }}</td>
                {% endfor %}
                {% if showPdfIcon %}
                <td></td>
                {% endif %}
            </tr>
        </tfoot>
        {% endif %}
    </table>
</div>
```

**Caractéristiques** :
- **Colonnes cliquables** : `linkColumns` pour générer prompts dynamiques
- **Ligne de total** : footer optionnel (tfoot)
- **Icône PDF** : placeholder pour future fonctionnalité
- **Classes Bootstrap** : `table-striped`, `table-hover`, `table-responsive`
- **Prompt dynamique** : remplacement `{key}` par valeur réelle

**Props** :
- `headers` (array<string>, requis) : en-têtes colonnes
- `rows` (array<array>, requis) : données lignes
- `totalRow` (array, optionnel) : ligne de total
- `linkColumns` (array, optionnel) : mapping colonnes cliquables
- `striped` (bool, défaut : false)
- `hover` (bool, défaut : false)
- `responsive` (bool, défaut : false)
- `showPdfIcon` (bool, défaut : false)
- `pdfColumn` (string, optionnel)

**CSS** : `styles/components/data-table.css`

**Usage** :
```twig
{{ component('DataTable', {
    headers: ['N° Facture', 'Client', 'Montant HT', 'Montant TTC'],
    rows: [
        { 'numero': 'F2025001', 'client': 'ACME Corp', 'ht': '1200€', 'ttc': '1440€' },
        { 'numero': 'F2025002', 'client': 'Beta SAS', 'ht': '850€', 'ttc': '1020€' }
    ],
    totalRow: { 'label': 'TOTAL', 'ht': '2050€', 'ttc': '2460€' },
    linkColumns: { 'numero': 'Afficher les détails de la facture {numero}' },
    striped: true,
    hover: true,
    responsive: true
}) }}
```

---

#### 4.2.7 ThemeSelector

**Fichier** : `app/templates/components/ThemeSelector.html.twig`

```twig
<div class="theme-selector dropdown">
    <button
        class="btn btn-link dropdown-toggle theme-selector-btn"
        type="button"
        id="themeSelectorDropdown"
        data-bs-toggle="dropdown"
        aria-expanded="false"
        title="{{ 'nav.theme.change'|trans({}, 'navigation') }}"
    >
        <i class="bi bi-palette"></i>
    </button>

    <ul class="dropdown-menu dropdown-menu-end theme-selector-menu"
        aria-labelledby="themeSelectorDropdown">
        {% for theme in this.themes %}
            <li>
                <a
                    class="dropdown-item theme-option
                           {% if this.currentTheme == theme.value %}active{% endif %}"
                    href="{{ path('theme_switch', {theme: theme.value}) }}"
                >
                    <span class="theme-preview"
                          style="background-color: {{ theme.color }};"></span>
                    <i class="{{ theme.icon }} me-2"></i>
                    <span>{{ theme.label }}</span>
                    {% if this.currentTheme == theme.value %}
                        <i class="bi bi-check-lg ms-auto"></i>
                    {% endif %}
                </a>
            </li>
        {% endfor %}
    </ul>
</div>
```

**Caractéristiques** :
- **Dropdown Bootstrap** : menu aligné à droite
- **Composant Twig Live** : `this.themes`, `this.currentTheme`
- **Thème actif** : classe `.active` + icône check
- **Preview couleur** : pastille colorée inline
- **Rechargement page** : changement de thème via GET

**Props** : Aucune (composant autonome)

**Propriétés Twig Component** :
- `this.themes` : tableau de thèmes disponibles
- `this.currentTheme` : thème actif

**CSS** : `styles/components/theme-selector.css`

**Usage** :
```twig
{{ component('ThemeSelector') }}
```

---

#### 4.2.8 DivisionSelector

**Fichier** : `app/templates/components/DivisionSelector.html.twig`

```twig
{# Composant : Sélecteur de Division (Multi-Tenant) - DÉSACTIVÉ TEMPORAIREMENT #}
<div class="division-selector dropdown" id="division-selector">
    <button
        class="btn btn-link dropdown-toggle division-selector-btn"
        type="button"
        id="divisionDropdown"
        aria-expanded="false"
        title="Sélecteur de division (temporairement désactivé)"
        disabled
        style="opacity: 0.6; cursor: not-allowed;"
    >
        <i class="bi bi-building"></i>
        <span id="current-division-name" class="d-none d-md-inline">
            {{ this.currentDivisionName }}
        </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end division-selector-menu"
        aria-labelledby="divisionDropdown" id="divisions-list">
        <li class="dropdown-header">
            <i class="bi bi-building me-2"></i>Mes Divisions
        </li>
        <li><hr class="dropdown-divider"></li>
        <li id="divisions-loading" class="px-3 py-2 text-muted">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Chargement...
        </li>
    </ul>
</div>
```

**Caractéristiques** :
- **État désactivé** : `disabled` + `opacity: 0.6`
- **Multi-tenant** : préparation pour gestion divisions futures
- **Spinner loading** : indication de chargement
- **Responsive** : texte masqué sur mobile (`d-none d-md-inline`)

**Props** : Aucune

**Propriétés Twig Component** :
- `this.currentDivisionName` : nom de la division active

**CSS** : `styles/components/division-selector.css`

**Usage** :
```twig
{{ component('DivisionSelector') }}
```

---

## 5. Design System et Variables CSS

### 5.1 Architecture CSS

```
app/assets/styles/
├── app.css                          # Point d'entrée principal
├── variables.css                    # Variables globales (140 lignes)
├── fonts.css                        # Chargement webfonts
├── layouts/
│   ├── auth.css                    # Layout authentification
│   └── home-layout.css             # Layout application
├── components/
│   ├── chat-input.css
│   ├── chat-messages.css
│   ├── chat-nav-tabs.css
│   ├── data-table.css
│   ├── division-selector.css
│   ├── forms.css
│   ├── glass-effects.css
│   ├── hexagons.css
│   ├── quick-access.css
│   ├── sidebar.css
│   ├── theme-selector.css
│   └── topbar.css
└── themes/
    ├── variables.css               # Variables communes thèmes
    ├── light.css
    ├── dark-blue.css
    └── dark-red.css
```

---

### 5.2 Variables Globales

**Fichier** : `app/assets/styles/variables.css` (140 lignes)

#### Typographie

```css
:root {
    /* Fonts */
    --font-family-title: 'Poppins', Arial, sans-serif;
    --font-family-content: 'Calibri', Arial, sans-serif;

    /* Poids Poppins */
    --font-weight-regular: 400;
    --font-weight-semibold: 600;
    --font-weight-bold: 700;
    --font-weight-extrabold: 800;

    /* Tailles */
    --font-size-xs: 12px;
    --font-size-sm: 14px;
    --font-size-md: 16px;
    --font-size-lg: 18px;
    --font-size-xl: 24px;
    --font-size-xxl: 32px;
}
```

#### Couleurs Primaires

```css
:root {
    --color-primary: #6366f1;        /* Violet-bleu (bouton) */
    --color-secondary: #3abff0;      /* Bleu clair */
    --color-tertiary: #e6204c;       /* Rouge accent */
    --color-default: #2a1b3d;        /* Violet foncé */
}
```

#### Couleurs Chat

```css
:root {
    --chat-input-text: #183e82;
    --chat-input-bg: #ffffff;
    --message-user-bg: #ffffff;
    --message-assistant-bg: #405d8c;
    --message-user-text: #000000;
    --message-assistant-text: #ffffff;
}
```

#### Background Gradient

```css
:root {
    --background-gradient: linear-gradient(
        40deg,
        rgba(48, 42, 80, 1) 20%,
        rgba(77, 68, 135, 1) 57%,
        rgba(127, 194, 220, 1) 95%
    );
}
```

#### Glass Effects (3 niveaux)

```css
:root {
    /* Subtle : très léger */
    --glass-subtle: linear-gradient(
        119deg,
        rgba(255, 255, 255, 0.075) 0%,
        rgba(255, 255, 255, 0.01) 100%
    );

    /* Light : léger */
    --glass-light: linear-gradient(
        110deg,
        rgba(255, 255, 255, 0.3) 0%,
        rgba(255, 255, 255, 0.025) 100%
    );

    /* Intense : fort */
    --glass-intense: linear-gradient(
        92deg,
        #ffffffe6 0%,
        #ffffff00 100%
    );

    /* Borders */
    --glass-border: 1px solid rgba(255, 255, 255, 0.3);
    --glass-backdrop-blur: 15px;
}
```

#### Espacements

```css
:root {
    --space-xs: 3px;
    --space-sm: 5px;
    --space-md: 10px;
    --space-lg: 15px;
    --space-xl: 20px;
    --space-xxl: 30px;
}
```

#### Border Radius

```css
:root {
    --radius-sm: 5px;
    --radius-md: 10px;
    --radius-lg: 15px;
    --radius-xl: 20px;
    --radius-full: 9999px;
}
```

#### Ombres

```css
:root {
    /* Shadow glass : effet verre */
    --shadow-glass: inset 2px 3px 7px rgba(0, 0, 0, 0.23),
                    5px 6px 7px rgba(0, 0, 0, 0.1);

    /* Shadow message user */
    --shadow-message-user: inset 0px 3px 6px #00000029,
                           3px 5px 8px #00000029;

    /* Shadow hover */
    --shadow-hover: 0 4px 15px rgba(255, 255, 255, 0.1);
}
```

#### Transitions

```css
:root {
    --transition-fast: 150ms ease-in-out;
    --transition-normal: 300ms ease-in-out;
    --transition-slow: 500ms ease-in-out;
}
```

#### Z-Index Layers

```css
:root {
    --z-index-base: 1;
    --z-index-dropdown: 100;
    --z-index-sidebar: 500;
    --z-index-topbar: 600;
    --z-index-modal: 1000;
    --z-index-toast: 1100;
}
```

---

### 5.3 Variables Thèmes

**Fichier** : `app/assets/styles/themes/variables.css`

```css
:root {
    /* Variables surchargées par chaque thème */
    --theme-bg-primary: #ffffff;
    --theme-bg-secondary: #f5f5f7;
    --theme-text-primary: #1a1a1a;
    --theme-text-secondary: #6b6f80;
    --theme-border-color: #e3e4e9;
    --theme-card-bg: #ffffff;
    --theme-sidebar-bg: #1a1a2e;
    --theme-sidebar-text: #ffffff;
    --theme-accent: #705ec8;
}

/* Transitions pour changement de thème fluide */
body {
    transition:
        background-color 0.3s ease,
        color 0.3s ease;
}

.theme-transition * {
    transition:
        background-color 0.3s ease,
        color 0.3s ease,
        border-color 0.3s ease !important;
}
```

**Thèmes disponibles** :
- `light` : Thème clair par défaut
- `dark-blue` : Thème sombre bleu
- `dark-red` : Thème sombre rouge

**Application dynamique** :
```html
<body class="theme-{{ app.user.theme }}">
```

---

## 6. Patterns et Bonnes Pratiques

### 6.1 Patterns Identifiés

#### 6.1.1 Héritage de Layout Multi-Niveaux

```twig
base.html.twig (racine)
  ↓
layouts/auth.html.twig (authentification)
  ↓
auth/login.html.twig (page login)

base.html.twig (racine)
  ↓
layouts/home.html.twig (application)
  ↓
home/index.html.twig (homepage)
chat/index.html.twig (chat)
settings/index.html.twig (paramètres)
```

**Avantages** :
- Séparation claire des responsabilités
- Réutilisation maximale
- Facilité de maintenance

---

#### 6.1.2 Blocks Contextuels

**Layout `home.html.twig`** expose des blocks spécifiques :

```twig
{% block navigation %}{% endblock %}        {# Navigation contextuelle (chat tabs) #}
{% block content_classes %}{% endblock %}   {# Classes CSS ajoutées au container #}
{% block content %}{% endblock %}           {# Contenu principal #}
{% block sticky_bottom %}{% endblock %}     {# Input fixe en bas (chat) #}
{% block modals %}{% endblock %}            {# Modals Bootstrap #}
```

**Usage** :
```twig
{# Page chat #}
{% block navigation %}
    {{ component('ChatNavTabs', { context: 'factures' }) }}
{% endblock %}

{% block content_classes %} chat-page{% endblock %}

{% block sticky_bottom %}
    {{ component('ChatInput', { context: 'factures' }) }}
{% endblock %}
```

---

#### 6.1.3 Composants Twig avec Props

**Appel de composant** :
```twig
{{ component('DataTable', {
    headers: ['N° Facture', 'Client', 'Montant'],
    rows: data,
    striped: true,
    hover: true
}) }}
```

**Définition dans le composant** :
```twig
{# Variables disponibles directement (pas de this.) #}
<table class="table {% if striped %}table-striped{% endif %}">
    <thead>
        {% for header in headers %}
            <th>{{ header }}</th>
        {% endfor %}
    </thead>
    <tbody>
        {% for row in rows %}
            {# ... #}
        {% endfor %}
    </tbody>
</table>
```

---

#### 6.1.4 Turbo Frames pour Rechargement Partiel

**Sidebar dynamique** :
```twig
<turbo-frame id="sidebar-favorites"
             src="{{ path('chat_sidebar_frame', {section: 'favorites'}) }}">
    {# Contenu initial (fallback) #}
    {{ component('ConversationSidebar', {section: 'favorites'}) }}
</turbo-frame>
```

**Controller** :
```php
#[Route('/chat/sidebar/{section}', name: 'chat_sidebar_frame')]
public function sidebarFrame(string $section): Response
{
    return $this->render('chat/sidebar_frame.html.twig', [
        'section' => $section,
    ]);
}
```

**Avantages** :
- Rechargement partiel sans full page reload
- Meilleure performance
- UX fluide

---

#### 6.1.5 Configuration JavaScript via Data Attributes

**Injection de configuration** :
```twig
<div id="chatData"
     data-context="{{ context }}"
     data-conversation-id="{{ conversationId }}"
     data-mercure-url="{{ mercureUrl }}"
     data-mercure-jwt="{{ mercureJwt }}"
     data-message-url="{{ path('chat_message', {context: context}) }}"
     data-stream-url="{{ path('chat_stream', {context: context}) }}"
     style="display: none;">
</div>
```

**Lecture en JavaScript** :
```javascript
const chatData = document.getElementById('chatData');
const config = {
    context: chatData.dataset.context,
    conversationId: chatData.dataset.conversationId,
    mercureUrl: chatData.dataset.mercureUrl,
    mercureJwt: chatData.dataset.mercureJwt,
    messageUrl: chatData.dataset.messageUrl,
    streamUrl: chatData.dataset.streamUrl
};
```

**Avantages** :
- Pas de variable globale JavaScript
- Configuration centralisée côté serveur
- Facile à déboguer

---

#### 6.1.6 Flash Messages Automatiques

**Layout `home.html.twig`** :
```twig
{% for type, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ type == 'error' ? 'danger' : type }} alert-dismissible">
            {% if type == 'success' %}
                <i class="bi bi-check-circle-fill me-2"></i>
            {% elseif type == 'error' or type == 'danger' %}
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {% elseif type == 'warning' %}
                <i class="bi bi-exclamation-circle-fill me-2"></i>
            {% elseif type == 'info' %}
                <i class="bi bi-info-circle-fill me-2"></i>
            {% endif %}
            {{ message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    {% endfor %}
{% endfor %}
```

**Controller** :
```php
$this->addFlash('success', 'Conversation enregistrée avec succès');
$this->addFlash('error', 'Impossible de charger la conversation');
```

**Avantages** :
- Gestion centralisée dans le layout
- Icônes automatiques selon le type
- Dismissible par défaut

---

#### 6.1.7 Traductions i18n

**Usage** :
```twig
{{ 'home.greeting'|trans({}, 'home') }}
{{ 'nav.footer.settings'|trans({}, 'navigation') }}
{{ 'settings.field.fullname.label'|trans({}, 'settings') }}
```

**Fichiers de traduction** :
```
translations/
├── home.fr.yaml
├── navigation.fr.yaml
└── settings.fr.yaml
```

**Exemple `home.fr.yaml`** :
```yaml
home:
    greeting: "Bonjour"
    question: "Que puis-je faire pour vous ?"
    quick_access:
        invoices: "Factures"
        orders: "Commandes"
        stocks: "Stocks"
        other: "Autre"
        marketing: "Marketing"
```

---

### 6.2 Bonnes Pratiques Appliquées

#### 6.2.1 Séparation des Responsabilités

✅ **Layout racine minimal** : délégation aux layouts enfants
✅ **Layouts spécialisés** : auth vs application
✅ **Composants atomiques** : un composant = une responsabilité
✅ **CSS modulaire** : un fichier par composant

---

#### 6.2.2 Réutilisabilité

✅ **Composants paramétrables** : props flexibles
✅ **Variables CSS** : design tokens centralisés
✅ **Blocks Twig** : personnalisation par page
✅ **Traductions** : séparation contenu/présentation

---

#### 6.2.3 Performance

✅ **AssetMapper** : chargement optimisé CSS/JS
✅ **Turbo Frames** : rechargement partiel
✅ **Lazy loading** : images, frames
✅ **CSS natif** : pas de framework lourd (Bootstrap minimal)

---

#### 6.2.4 Accessibilité

✅ **Attributs ARIA** : `aria-label`, `aria-expanded`, `aria-current`
✅ **Rôles sémantiques** : `<nav>`, `<main>`, `<aside>`
✅ **Focus keyboard** : navigation clavier
✅ **Contrast** : respect WCAG 2.1 (variables CSS)

---

#### 6.2.5 Maintenabilité

✅ **Documentation inline** : commentaires explicatifs
✅ **Naming cohérent** : BEM-like (`.chat-message-bubble`)
✅ **Structure claire** : arborescence logique
✅ **Versioning** : commentaires de désactivation (DivisionSelector)

---

## 7. Système de Traduction

### 7.1 Organisation

```
translations/
├── home.fr.yaml              # Traductions page d'accueil
├── navigation.fr.yaml        # Traductions navigation/menu
├── settings.fr.yaml          # Traductions paramètres
└── [autres domaines]
```

### 7.2 Domaines Identifiés

| Domaine | Fichier | Usage |
|---------|---------|-------|
| `home` | `home.fr.yaml` | Page d'accueil, quick access |
| `navigation` | `navigation.fr.yaml` | Sidebar, header, footer nav |
| `settings` | `settings.fr.yaml` | Page paramètres utilisateur |

### 7.3 Exemples d'Usage

**Page d'accueil** :
```twig
{{ 'home.page.title'|trans({}, 'home') }}
{{ 'home.greeting'|trans({}, 'home') }}
{{ 'home.quick_access.invoices'|trans({}, 'home') }}
```

**Navigation** :
```twig
{{ 'nav.footer.settings'|trans({}, 'navigation') }}
{{ 'nav.section.favorites'|trans({}, 'navigation') }}
{{ 'nav.theme.change'|trans({}, 'navigation') }}
```

**Paramètres** :
```twig
{{ 'settings.page.title'|trans({}, 'settings') }}
{{ 'settings.field.fullname.label'|trans({}, 'settings') }}
{{ 'settings.field.theme.light'|trans({}, 'settings') }}
```

### 7.4 Pattern de Nommage

```
[domaine].[section].[élément]
```

**Exemples** :
- `home.page.title`
- `nav.footer.settings`
- `settings.field.fullname.label`
- `settings.field.theme.light`

---

## 8. Composants à Créer

### 8.1 QuickAccessCard (Priorité Haute)

**Fichier** : `app/templates/components/QuickAccessCard.html.twig`

**Objectif** : Factoriser les cartes d'accès rapide de la homepage

**Props** :
- `icon` (string, requis) : classe Bootstrap Icons (ex: `bi-receipt`)
- `iconColor` (string, requis) : classe couleur (ex: `text-info`)
- `title` (string, requis) : titre de la carte
- `href` (string, requis) : URL de destination
- `dataTurbo` (string, défaut : "false") : désactivation Turbo
- `dataModal` (string, optionnel) : ID du modal à ouvrir

**Structure proposée** :
```twig
{#
  Composant QuickAccessCard - Carte d'accès rapide homepage

  Usage :
  {{ component('QuickAccessCard', {
      icon: 'bi-receipt',
      iconColor: 'text-info',
      title: 'Factures',
      href: path('chat_index', {context: 'factures'})
  }) }}

  Paramètres :
  - icon (string) : Classe Bootstrap Icons
  - iconColor (string) : Classe couleur (text-info, text-success, etc.)
  - title (string) : Titre de la carte
  - href (string) : URL de destination
  - dataTurbo (string) : Désactivation Turbo (défaut: "false")
  - dataModal (string, optionnel) : ID du modal à ouvrir
#}

{% set dataTurbo = dataTurbo|default('false') %}

{% if dataModal is defined %}
    <a href="#"
       class="card text-decoration-none shadow-sm quick-access-card"
       data-bs-toggle="modal"
       data-bs-target="#{{ dataModal }}">
{% else %}
    <a href="{{ href }}"
       class="card text-decoration-none shadow-sm quick-access-card"
       data-turbo="{{ dataTurbo }}">
{% endif %}
        <div class="card-body d-flex align-items-center gap-3 p-3">
            <i class="bi {{ icon }} fs-2 {{ iconColor }} flex-shrink-0"></i>
            <h3 class="fs-6 fw-semibold text-primary mb-0">{{ title }}</h3>
        </div>
    </a>
```

**Usage dans `home/index.html.twig`** :
```twig
<div class="d-flex justify-content-center gap-3 flex-wrap">
    {{ component('QuickAccessCard', {
        icon: 'bi-receipt',
        iconColor: 'text-info',
        title: 'home.quick_access.invoices'|trans({}, 'home'),
        href: path('chat_index', {context: 'factures'})
    }) }}

    {{ component('QuickAccessCard', {
        icon: 'bi-list-check',
        iconColor: 'text-success',
        title: 'home.quick_access.orders'|trans({}, 'home'),
        href: path('chat_index', {context: 'commandes'})
    }) }}

    {{ component('QuickAccessCard', {
        icon: 'bi-magic',
        iconColor: 'text-danger',
        title: 'home.quick_access.marketing'|trans({}, 'home'),
        href: '#',
        dataModal: 'marketingWarningModal'
    }) }}
</div>
```

---

### 8.2 AlertWithHelp (Priorité Moyenne)

**Fichier** : `app/templates/components/AlertWithHelp.html.twig`

**Objectif** : Alert Bootstrap avec icône et bouton d'aide contextuel

**Props** :
- `type` (string, défaut : "info") : success|warning|danger|info
- `message` (string, requis) : texte du message
- `helpUrl` (string, optionnel) : URL page d'aide
- `dismissible` (bool, défaut : true)

**Structure proposée** :
```twig
{#
  Composant AlertWithHelp - Alert avec aide contextuelle

  Usage :
  {{ component('AlertWithHelp', {
      type: 'warning',
      message: 'Votre configuration nécessite une mise à jour.',
      helpUrl: path('help_config')
  }) }}
#}

{% set type = type|default('info') %}
{% set dismissible = dismissible|default(true) %}

<div class="alert alert-{{ type }} {{ dismissible ? 'alert-dismissible' : '' }} fade show" role="alert">
    {# Icône selon le type #}
    {% if type == 'success' %}
        <i class="bi bi-check-circle-fill me-2"></i>
    {% elseif type == 'danger' or type == 'error' %}
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
    {% elseif type == 'warning' %}
        <i class="bi bi-exclamation-circle-fill me-2"></i>
    {% elseif type == 'info' %}
        <i class="bi bi-info-circle-fill me-2"></i>
    {% endif %}

    {# Message #}
    {{ message }}

    {# Lien d'aide optionnel #}
    {% if helpUrl is defined %}
        <a href="{{ helpUrl }}" class="alert-link ms-2" target="_blank">
            <i class="bi bi-question-circle"></i> En savoir plus
        </a>
    {% endif %}

    {# Bouton fermeture #}
    {% if dismissible %}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    {% endif %}
</div>
```

---

### 8.3 InputGroup (Priorité Moyenne)

**Fichier** : `app/templates/components/InputGroup.html.twig`

**Objectif** : Groupe input avec icône et validation Bootstrap

**Props** :
- `id` (string, requis)
- `name` (string, requis)
- `type` (string, défaut : "text")
- `label` (string, optionnel)
- `placeholder` (string, optionnel)
- `icon` (string, optionnel) : classe Bootstrap Icons
- `value` (string, optionnel)
- `required` (bool, défaut : false)
- `readonly` (bool, défaut : false)
- `helpText` (string, optionnel)
- `error` (string, optionnel)

**Structure proposée** :
```twig
{#
  Composant InputGroup - Input avec icône et validation

  Usage :
  {{ component('InputGroup', {
      id: 'email',
      name: 'email',
      type: 'email',
      label: 'Adresse email',
      icon: 'bi-envelope',
      placeholder: 'vous@exemple.com',
      required: true
  }) }}
#}

{% set type = type|default('text') %}
{% set required = required|default(false) %}
{% set readonly = readonly|default(false) %}

<div class="mb-3">
    {# Label #}
    {% if label is defined %}
        <label for="{{ id }}" class="form-label">
            {{ label }}
            {% if required %}
                <span class="text-danger">*</span>
            {% endif %}
        </label>
    {% endif %}

    {# Input group avec icône #}
    <div class="input-group {{ error is defined ? 'has-validation' : '' }}">
        {% if icon is defined %}
            <span class="input-group-text">
                <i class="bi {{ icon }}"></i>
            </span>
        {% endif %}

        <input
            type="{{ type }}"
            class="form-control {{ error is defined ? 'is-invalid' : '' }}"
            id="{{ id }}"
            name="{{ name }}"
            {% if placeholder is defined %}placeholder="{{ placeholder }}"{% endif %}
            {% if value is defined %}value="{{ value }}"{% endif %}
            {% if required %}required{% endif %}
            {% if readonly %}readonly{% endif %}
        >

        {# Message d'erreur #}
        {% if error is defined %}
            <div class="invalid-feedback">
                {{ error }}
            </div>
        {% endif %}
    </div>

    {# Texte d'aide #}
    {% if helpText is defined %}
        <div class="form-text">{{ helpText }}</div>
    {% endif %}
</div>
```

---

### 8.4 Modal (Priorité Basse)

**Fichier** : `app/templates/components/Modal.html.twig`

**Objectif** : Modal Bootstrap réutilisable

**Props** :
- `id` (string, requis)
- `title` (string, requis)
- `headerClass` (string, optionnel)
- `size` (string, défaut : "") : sm|lg|xl
- `centered` (bool, défaut : true)
- `scrollable` (bool, défaut : false)

**Structure proposée** :
```twig
{#
  Composant Modal - Modal Bootstrap

  Usage :
  {% embed 'components/Modal.html.twig' with {
      id: 'confirmModal',
      title: 'Confirmer l\'action',
      headerClass: 'bg-warning'
  } %}
      {% block modal_body %}
          <p>Êtes-vous sûr de vouloir continuer ?</p>
      {% endblock %}
      {% block modal_footer %}
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="button" class="btn btn-primary">Confirmer</button>
      {% endblock %}
  {% endembed %}
#}

{% set size = size|default('') %}
{% set centered = centered|default(true) %}
{% set scrollable = scrollable|default(false) %}

<div class="modal fade" id="{{ id }}" tabindex="-1" aria-labelledby="{{ id }}Label" aria-hidden="true">
    <div class="modal-dialog
                {% if size %}modal-{{ size }}{% endif %}
                {% if centered %}modal-dialog-centered{% endif %}
                {% if scrollable %}modal-dialog-scrollable{% endif %}">
        <div class="modal-content">
            <div class="modal-header {{ headerClass|default('') }}">
                <h5 class="modal-title" id="{{ id }}Label">{{ title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                {% block modal_body %}{% endblock %}
            </div>
            {% block modal_footer %}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            {% endblock %}
        </div>
    </div>
</div>
```

---

### 8.5 FlashMessages (Priorité Basse)

**Fichier** : `app/templates/components/FlashMessages.html.twig`

**Objectif** : Extraire la gestion des flash messages du layout

**Props** : Aucune (utilise `app.flashes`)

**Structure proposée** :
```twig
{#
  Composant FlashMessages - Affichage automatique des flash messages

  Usage :
  {{ component('FlashMessages') }}
#}

{% for type, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ type == 'error' ? 'danger' : type }} alert-dismissible fade show" role="alert">
            {% if type == 'success' %}
                <i class="bi bi-check-circle-fill me-2"></i>
            {% elseif type == 'error' or type == 'danger' %}
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {% elseif type == 'warning' %}
                <i class="bi bi-exclamation-circle-fill me-2"></i>
            {% elseif type == 'info' %}
                <i class="bi bi-info-circle-fill me-2"></i>
            {% endif %}
            {{ message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    {% endfor %}
{% endfor %}
```

**Usage dans `layouts/home.html.twig`** :
```twig
<div class="home-content">
    {{ component('FlashMessages') }}
    {% block content %}{% endblock %}
</div>
```

---

## 9. Recommandations

### 9.1 Architecture

#### Renforcer la Séparation

**Problème identifié** :
La page `home/index.html.twig` contient 5 cartes quasi-identiques en dur.

**Solution** :
Créer le composant `QuickAccessCard` (voir section 8.1).

**Impact** :
- Réduction de 60 lignes de code
- Facilité de maintenance
- Réutilisabilité (futures pages)

---

#### Extraire les Flash Messages

**Problème identifié** :
Le layout `home.html.twig` contient 23 lignes de gestion flash messages.

**Solution** :
Créer le composant `FlashMessages` (voir section 8.5).

**Impact** :
- Layout plus lisible
- Réutilisabilité dans d'autres layouts
- Facilité de personnalisation

---

### 9.2 Performance

#### Lazy Loading Turbo Frames

**Recommandation** :
Ajouter l'attribut `loading="lazy"` aux Turbo Frames de la sidebar.

**Avant** :
```twig
<turbo-frame id="sidebar-history"
             src="{{ path('chat_sidebar_frame', {section: 'history'}) }}">
```

**Après** :
```twig
<turbo-frame id="sidebar-history"
             src="{{ path('chat_sidebar_frame', {section: 'history'}) }}"
             loading="lazy">
```

**Impact** :
- Chargement différé de l'historique
- Amélioration temps de chargement initial

---

#### Optimisation Images

**Recommandation** :
Ajouter `loading="lazy"` aux images non critiques.

**Exemple** :
```twig
<img src="{{ asset('images/assistant-picto.svg') }}"
     alt="IA"
     class="chat-message-logo"
     loading="lazy">
```

---

### 9.3 Accessibilité

#### Ajouter des Labels Cachés

**Problème identifié** :
Certains boutons n'ont que des icônes sans label texte.

**Exemple (DivisionSelector)** :
```twig
<button class="btn btn-link dropdown-toggle"
        type="button"
        aria-label="Sélectionner une division">
    <i class="bi bi-building"></i>
</button>
```

**Recommandation** :
Ajouter `aria-label` sur tous les boutons icône.

---

#### Améliorer les Landmarks

**Recommandation** :
Ajouter des attributs ARIA sur les sections principales.

**Exemple** :
```twig
<aside class="home-sidebar" role="navigation" aria-label="Navigation principale">
    <!-- ... -->
</aside>

<main class="home-main" role="main" aria-label="Contenu principal">
    <!-- ... -->
</main>
```

---

### 9.4 Sécurité

#### Filtrer les Messages Assistant

**Problème identifié** :
`ChatMessageAssistant.html.twig` utilise `{{ message|raw }}`.

**Risque** :
Injection XSS si le contenu provient d'une source non contrôlée.

**Recommandation** :
1. **Si HTML nécessaire** : sanitizer côté backend (HTMLPurifier)
2. **Si texte pur** : remplacer `|raw` par `|nl2br`

**Avant** :
```twig
{{ message|raw }}
```

**Après (option 1 : HTML sanitizé)** :
```twig
{{ message|raw }}  {# Message déjà sanitizé dans le service #}
```

**Après (option 2 : texte simple)** :
```twig
{{ message|nl2br }}
```

---

### 9.5 Maintenabilité

#### Documentation Composants

**Recommandation** :
Ajouter un en-tête documentation sur tous les composants manquants.

**Template** :
```twig
{#
  Composant [NomComposant] - [Description courte]

  Usage :
  {{ component('[NomComposant]', {
      prop1: 'valeur1',
      prop2: 'valeur2'
  }) }}

  Paramètres :
  - prop1 (type, requis/optionnel) : Description
  - prop2 (type, requis/optionnel) : Description

  Exemple :
  {{ component('[NomComposant]', {
      prop1: 'exemple',
      prop2: 42
  }) }}
#}
```

---

#### Tests Visuels Composants

**Recommandation** :
Créer une page de démo des composants (Storybook-like).

**Fichier** : `app/templates/dev/components.html.twig`

**Structure** :
```twig
{% extends 'layouts/home.html.twig' %}

{% block content %}
<div class="container py-5">
    <h1>Composants Twig</h1>

    <section class="mb-5">
        <h2>QuickAccessCard</h2>
        {{ component('QuickAccessCard', {
            icon: 'bi-receipt',
            iconColor: 'text-info',
            title: 'Exemple Factures',
            href: '#'
        }) }}
    </section>

    <section class="mb-5">
        <h2>AlertWithHelp</h2>
        {{ component('AlertWithHelp', {
            type: 'warning',
            message: 'Ceci est un avertissement.',
            helpUrl: '#'
        }) }}
    </section>

    <!-- Autres composants -->
</div>
{% endblock %}
```

**Avantages** :
- Visualisation rapide des composants
- Tests manuels facilités
- Documentation vivante

---

### 9.6 Conventions de Nommage

#### Unifier les Préfixes

**Problème identifié** :
Certains composants utilisent des préfixes différents :
- `ChatInput` : préfixe "Chat"
- `ThemeSelector` : préfixe "Theme"
- `DataTable` : pas de préfixe

**Recommandation** :
Adopter une convention cohérente.

**Proposition** :
- **Composants UI génériques** : pas de préfixe (`Button`, `Modal`, `InputGroup`)
- **Composants métier** : préfixe métier (`ChatInput`, `ChatMessageUser`)

**Renommages potentiels** :
- `ChatMessageAssistant` → `AssistantMessage` (si réutilisé ailleurs)
- `ChatMessageUser` → `UserMessage` (si réutilisé ailleurs)
- `DataTable` → `DataTable` (OK)

---

### 9.7 CSS

#### Variables Thèmes Non Utilisées

**Problème identifié** :
Certaines variables `--theme-*` sont définies mais peu utilisées.

**Exemple** :
```css
--theme-border-color: #e3e4e9;  /* Peu utilisée */
```

**Recommandation** :
Auditer l'usage des variables et supprimer les inutilisées.

**Commande** :
```bash
grep -r "var(--theme-border-color)" app/assets/styles/
```

---

#### Normaliser les Unités

**Problème identifié** :
Mix de `px`, `rem`, classes Bootstrap.

**Exemple** :
```css
font-size: 16px;       /* CSS */
font-size: 1rem;       /* CSS */
font-size: var(--fs-md);  /* Variable */
class="fs-4"           /* Bootstrap */
```

**Recommandation** :
Préférer les **variables CSS** pour la cohérence.

**Avant** :
```twig
<h1 style="font-size: 32px;">Titre</h1>
```

**Après** :
```twig
<h1 style="font-size: var(--font-size-xxl);">Titre</h1>
```

---

## 10. Annexes

### 10.1 Checklist Migration Vers Composants

- [ ] Créer `QuickAccessCard.html.twig`
- [ ] Refactoriser `home/index.html.twig` avec `QuickAccessCard`
- [ ] Créer `FlashMessages.html.twig`
- [ ] Refactoriser `layouts/home.html.twig` avec `FlashMessages`
- [ ] Créer `AlertWithHelp.html.twig`
- [ ] Créer `InputGroup.html.twig`
- [ ] Créer `Modal.html.twig`
- [ ] Refactoriser `home/index.html.twig` modal avec composant `Modal`
- [ ] Tester tous les composants
- [ ] Mettre à jour documentation

---

### 10.2 Liste des IDs JavaScript Critiques

**Chat** (`chat/index.html.twig`) :
- `#chatForm` : Formulaire de soumission
- `#chatInput` : Textarea de saisie
- `#chatMessages` : Conteneur des messages
- `#sendButton` : Bouton d'envoi
- `#chatData` : Configuration JavaScript
- `#favoriteButtonContainer` : Container bouton favori

**Sidebar** (`layouts/home.html.twig`) :
- `#sidebarOffcanvas` : Offcanvas mobile
- `#sidebar-favorites` : Turbo Frame favoris
- `#sidebar-history` : Turbo Frame historique

**Modals** (`home/index.html.twig`) :
- `#marketingWarningModal` : Modal avertissement marketing

---

### 10.3 Classes CSS Critiques

**Chat** :
- `.chat-message` : Conteneur message
- `.chat-message-user` : Message utilisateur
- `.chat-message-assistant` : Message assistant
- `.chat-message-bubble` : Bulle de message
- `.chat-message-text` : Texte message assistant
- `.chat-message-logo` : Logo assistant
- `.chat-message-avatar` : Avatar utilisateur
- `.chat-input-wrapper` : Wrapper input
- `.chat-input-field` : Champ input
- `.chat-send-button` : Bouton envoi
- `.chat-nav-tab` : Onglet navigation
- `.chat-nav-tab.active` : Onglet actif
- `.chat-datatable` : Tableau de données

**Sidebar** :
- `.home-sidebar` : Sidebar desktop
- `.sidebar-header` : Header sidebar
- `.sidebar-link` : Lien sidebar footer
- `.nav-section` : Section navigation
- `.nav-section-title-pill` : Titre section
- `.nav-link` : Lien navigation

**Quick Access** :
- `.quick-access-card` : Carte d'accès rapide

**Layout** :
- `.home-layout` : Container principal
- `.home-main` : Zone principale
- `.home-content` : Zone contenu scrollable
- `.sticky-top-container` : Container sticky top
- `.sticky-bottom-container` : Container sticky bottom

---

### 10.4 Routes Importantes

**Chat** :
- `chat_index` : `/chat/{context}` - Interface chat
- `chat_message` : `/chat/{context}/message` - Envoi message
- `chat_stream` : `/chat/{context}/stream` - Streaming réponse
- `chat_sidebar_frame` : `/chat/sidebar/{section}` - Turbo Frame sidebar
- `chat_load_conversation` : `/chat/{context}/conversation/{conversationId}` - Charger conversation
- `chat_new_conversation` : `/chat/{context}/new` - Nouvelle conversation
- `chat_toggle_favorite` : `/chat/conversation/{id}/favorite` - Toggle favori
- `chat_delete_conversation` : `/chat/conversation/{id}` - Supprimer conversation

**Navigation** :
- `home_index` : `/` - Page d'accueil
- `settings_index` : `/settings` - Paramètres
- `profile_index` : `/profile` - Profil
- `app_logout` : `/logout` - Déconnexion
- `theme_switch` : `/theme/switch/{theme}` - Changement thème

**Marketing** :
- `marketing_project_index` : `/marketing/projects` - Interface marketing

---

### 10.5 Variables Twig Contextuelles

**Page d'accueil** (`home/index.html.twig`) :
- `firstName` : Prénom de l'utilisateur

**Page chat** (`chat/index.html.twig`) :
- `context` : Contexte du chat (factures|commandes|stocks|general)
- `conversationId` : UUID de la conversation
- `mercureUrl` : URL du hub Mercure
- `mercureJwt` : Token JWT pour Mercure
- `loadedConversation` : Objet conversation (optionnel)

**Globales Symfony** :
- `app.user` : Utilisateur connecté
- `app.user.theme` : Thème actif
- `app.user.fullName` : Nom complet
- `app.flashes` : Flash messages

---

### 10.6 Métriques du Projet

**Templates** :
- **Total** : ~15 fichiers Twig
- **Layouts** : 3 (base, auth, home)
- **Pages** : 4 (home, chat, settings, profile)
- **Composants** : 13
- **Lines of Twig** : ~800 lignes

**CSS** :
- **Total** : 23 fichiers CSS
- **Variables** : 140 lignes
- **Thèmes** : 3 (light, dark-blue, dark-red)
- **Lines of CSS** : ~2000 lignes

**Complexité** :
- **Layout le plus complexe** : `layouts/home.html.twig` (245 lignes)
- **Composant le plus complexe** : `DataTable.html.twig` (76 lignes)
- **Page la plus complexe** : `chat/index.html.twig` (128 lignes)

---

## Conclusion

L'analyse des templates Twig du projet myCFiia révèle une architecture **solide et moderne**, avec une **séparation claire des responsabilités** et une **réutilisabilité bien pensée**.

### Points Forts

✅ **Architecture claire** : héritage multi-niveaux, blocks contextuels
✅ **Composants atomiques** : 13 composants bien structurés
✅ **Design System** : 140 variables CSS centralisées
✅ **Performance** : Turbo Frames, lazy loading
✅ **Accessibilité** : ARIA, sémantique HTML
✅ **Traductions** : séparation contenu/présentation

### Points d'Amélioration

⚠️ **Factorisation** : créer `QuickAccessCard`, `FlashMessages`
⚠️ **Sécurité** : sanitizer les messages assistant
⚠️ **Documentation** : ajouter en-têtes sur tous les composants
⚠️ **Tests** : créer page de démo composants

### Prochaines Étapes

1. **Créer les 5 composants manquants** (section 8)
2. **Refactoriser les pages existantes** avec les nouveaux composants
3. **Auditer l'usage des variables CSS**
4. **Ajouter les attributs ARIA manquants**
5. **Créer la page de démo composants**
6. **Documenter les composants**

**Temps estimé** : 3-4 jours de développement

---

**Rapport généré le** : 2025-01-16
**Par** : Analyse automatisée des templates Twig
**Version** : 1.0.0
