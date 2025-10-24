# Architecture des Fichiers - Interface Chat IA

**Date**: 2025-10-21
**Contexte**: Listing complet des fichiers constituant l'interface de chat avec leurs rôles et responsabilités

---

## 📁 Vue d'ensemble

L'interface de chat est composée de **4 couches principales** :
1. **Templates Twig** (vues et composants)
2. **Styles CSS** (thèmes et composants)
3. **JavaScript** (logique client et contrôleurs Stimulus)
4. **Backend PHP** (contrôleurs, services, entités)

---

## 🎨 1. Templates Twig (Frontend - Vues)

### Layouts de Base

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/templates/layouts/home.html.twig` | **Layout principal applicatif** | - Structure HTML5 de base (`<!DOCTYPE html>`)<br>- Head avec meta, title, favicon<br>- Body avec classe thème dynamique (`theme-{{ app.user.theme }}`)<br>- **Sidebar fixe desktop** (280px, position fixed, z-index 100)<br>  - Header avec boutons menu/fermer<br>  - Navigation avec sections collapsibles (Favoris, Historique)<br>  - Footer avec Paramètres/Profil/Déconnexion<br>- **Sidebar mobile offcanvas** (Bootstrap offcanvas-start)<br>- **Bouton hamburger mobile** (d-lg-none, position fixed)<br>- **Header principal** (logo adaptatif selon thème, ThemeSelector, lien retour CFI)<br>- **Zone de contenu** (`{% block content %}`)<br>- Import scripts (app.js, sidebar.js) |
| `app/templates/layouts/app.html.twig` | **Layout générique simple** | Layout alternatif pour pages sans sidebar |
| `app/templates/layouts/auth.html.twig` | **Layout authentification** | Layout pour pages login/register (sans navigation) |

### Vue Principale Chat

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/templates/chat/index.html.twig` | **Page principale du chat** | - **Extends** : `layouts/home.html.twig`<br>- **Block title** : "Chat IA - myCFiia"<br>- **Block stylesheets** : Import `styles/chat.css`<br>- **Block content** : Container chat flexbox vertical<br>  - Header chat avec badge contexte<br>  - Zone messages scrollable avec message bienvenue<br>  - Zone saisie avec suggestions quick questions<br>  - Indicateur de chargement<br>- **Data attributes** : conversationId, mercureUrl, JWT<br>- **Block javascripts** : Import `js/chat.js` |

### Composants Twig Réutilisables

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/templates/components/ThemeSelector.html.twig` | **Sélecteur de thème** | - Dropdown Bootstrap avec icône palette<br>- Liste des thèmes disponibles (light, dark-blue, dark-red)<br>- Prévisualisation couleur par thème<br>- Indication thème actif (badge check)<br>- Lien vers route `theme_switch` |
| `app/templates/components/chat/message.html.twig` | **Composant Message** | - Affichage d'un message utilisateur ou assistant<br>- Avatar (personne ou robot)<br>- Header avec auteur et timestamp<br>- Contenu du message (nl2br)<br>- Actions (copier, régénérer) pour messages assistant |
| `app/templates/components/chat/input.html.twig` | **Composant Saisie** | - Zone de texte auto-redimensionnable<br>- Bouton joindre fichier (disabled)<br>- Bouton envoyer avec icône<br>- Hints clavier (Entrée vs Shift+Entrée)<br>- Intégration Stimulus (actions et targets) |

### Templates de Prompts IA (System Prompts)

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/templates/ai/prompts/base.md.twig` | **Base commune** | Prompt système de base pour tous les agents |
| `app/templates/ai/prompts/chat_factures.md.twig` | **Agent Factures** | Prompt spécialisé factures (courrier/email) |
| `app/templates/ai/prompts/chat_commandes.md.twig` | **Agent Commandes** | Prompt spécialisé commandes clients |
| `app/templates/ai/prompts/chat_stocks.md.twig` | **Agent Stocks** | Prompt spécialisé gestion stocks |
| `app/templates/ai/prompts/chat_general.md.twig` | **Agent Général** | Prompt généraliste multi-contexte |
| `app/templates/ai/prompts/chat_operations.md.twig` | **Agent Opérations** | Prompt opérations marketing (SMS/Email/Courrier) |

### Partials de Prompts (Inclusions)

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/templates/ai/prompts/partials/_format.md.twig` | **Formatage réponses** | Conventions de formatage des réponses IA |
| `app/templates/ai/prompts/partials/_security.md.twig` | **Sécurité** | Règles de sécurité et confidentialité |
| `app/templates/ai/prompts/partials/_rules.md.twig` | **Règles métier** | Règles métier et comportementales |

---

## 🎨 2. Styles CSS (Frontend - Apparence)

### Styles Spécifiques Chat

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/assets/styles/chat.css` | **Styles globaux chat** | - Container principal (flexbox vertical)<br>- Header, zone messages, zone saisie<br>- Scroll automatique messages<br>- Layout responsive |
| `app/assets/styles/components/chat.css` | **Composants chat** | - Styles messages utilisateur/assistant<br>- Avatars et badges<br>- Actions sur messages<br>- Boutons quick questions<br>- Indicateur de chargement |

### Styles Globaux et Thèmes

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/assets/styles/app.css` | **Styles globaux** | Reset, typographie, utilities Bootstrap |
| `app/assets/styles/variables.css` | **Variables CSS** | Custom properties pour design system |
| `app/assets/styles/layouts/home-layout.css` | **Layout home** | - Flexbox layout principal (`.home-layout`)<br>- Styles sidebar navigation (sections pill, hover effects)<br>- Sidebar rétractable (mode icônes, `.sidebar-collapsed`)<br>- Responsive (décalage main 280px sur desktop)<br>- Header (logo adaptatif selon thème avec filter CSS)<br>- Content area (flex, padding, responsive) |
| `app/assets/styles/layouts/auth.css` | **Layout auth** | Styles pour pages authentification |
| `app/assets/styles/layouts/app-layout.css` | **Layout app générique** | Styles pour layout générique simple |
| `app/assets/styles/themes/light.css` | **Thème clair** | Palette de couleurs mode clair |
| `app/assets/styles/themes/dark-blue.css` | **Thème sombre bleu** | Palette sombre avec accents bleus |
| `app/assets/styles/themes/dark-red.css` | **Thème sombre rouge** | Palette sombre avec accents rouges |
| `app/assets/styles/themes/variables.css` | **Variables thèmes** | Variables partagées entre thèmes |

### Composants UI Additionnels

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/assets/styles/components/glass-effects.css` | **Effets glassmorphism** | Effets de verre pour modernes interfaces |
| `app/assets/styles/components/hexagons.css` | **Formes hexagones** | Éléments décoratifs hexagonaux |
| `app/assets/styles/components/quick-access.css` | **Accès rapides** | Styles pour menus d'accès rapide |
| `app/assets/styles/components/sidebar.css` | **Sidebar navigation** | Styles pour la sidebar principale |
| `app/assets/styles/components/theme-selector.css` | **Sélecteur thème** | Composant de sélection de thème |
| `app/assets/styles/components/topbar.css` | **Barre supérieure** | Styles pour la topbar de navigation |

---

## ⚡ 3. JavaScript (Frontend - Logique Client)

### Scripts Principaux

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/assets/js/chat.js` | **Logique principale chat** | - Initialisation de l'interface<br>- Gestion de l'envoi de messages<br>- Connexion EventSource Mercure (SSE)<br>- Streaming de réponses en temps réel<br>- Auto-scroll messages<br>- Gestion des quick questions<br>- Compteur de caractères<br>- Export de conversation |

### Contrôleurs Stimulus

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/assets/controllers/chat_controller.js` | **Contrôleur Stimulus Chat** | - Actions Stimulus (sendMessage, handleKeydown, autoResize)<br>- Targets Stimulus (input, sendButton)<br>- Intégration avec composant input<br>- Validation formulaire |
| `app/assets/controllers/theme_controller.js` | **Contrôleur Thème** | Gestion des changements de thème (clair/sombre) |
| `app/assets/controllers/sidebar_controller.js` | **Contrôleur Sidebar** | Comportement de la sidebar (collapse, navigation) |
| `app/assets/controllers/csrf_protection_controller.js` | **Protection CSRF** | Injection automatique des tokens CSRF |
| `app/assets/controllers/datatable_controller.js` | **Datatables** | Initialisation des tables de données interactives |
| `app/assets/controllers/hello_controller.js` | **Exemple Stimulus** | Contrôleur d'exemple pour tests |

### Scripts Utilitaires

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/assets/app.js` | **Point d'entrée principal** | - Import Bootstrap<br>- Initialisation Stimulus<br>- Import controllers<br>- Configuration globale |
| `app/assets/bootstrap.js` | **Initialisation Stimulus** | Configuration de l'application Stimulus |
| `app/assets/sidebar.js` | **Script sidebar** | Logique spécifique sidebar (non-Stimulus) |
| `app/assets/login.js` | **Script login** | Logique page de connexion |

---

## 🔧 4. Backend PHP (Serveur - Logique Métier)

### Contrôleur Principal

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/src/Controller/ChatController.php` | **Contrôleur Chat** | - Route GET `/chat/{context}` : Affichage interface<br>- Route POST `/chat/{context}/message` : Question synchrone<br>- Route POST `/chat/{context}/stream` : Question streaming Mercure<br>- Validation des contextes (factures/commandes/stocks/general)<br>- Gestion des sessions de conversation (UUID)<br>- Génération des JWT Mercure<br>- Authentification utilisateur<br>- Dispatch messages asynchrones (Messenger) |

### Services Métier

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/src/Service/ChatService.php` | **Service orchestrateur** | - Orchestration des requêtes IA<br>- Sélection de l'agent contextuel (via ai.yaml)<br>- Injection des variables Twig dans prompts<br>- Appel Symfony AI Bundle<br>- Gestion de l'historique de conversation<br>- Transformation des réponses en DTO |
| `app/src/Service/AiPromptService.php` | **Service de prompts** | - Rendu des templates Twig de prompts<br>- Injection contexte utilisateur (fullName, context, etc.)<br>- Génération du system prompt dynamique<br>- Support des partials (format, security, rules) |
| `app/src/Service/AiLoggerService.php` | **Service de logs IA** | - Enregistrement requêtes/réponses IA (AiLog)<br>- Métriques (latence, tokens, coût)<br>- Traçabilité complète (user, context, outils utilisés)<br>- Support debug et audit |
| `app/src/Service/ChatStreamPublisher.php` | **Service streaming Mercure** | - Publication de chunks de streaming sur Mercure Hub<br>- Gestion du topic `chat/{conversationId}`<br>- Envoi de messages structurés (SSE)<br>- Gestion des erreurs de streaming |
| `app/src/Service/Ai/MistralMetadataProcessor.php` | **Processeur metadata Mistral** | - Extraction des metadata de réponse Mistral<br>- Parsing des tool calls (appels de fonctions)<br>- Transformation du format propriétaire Mistral |

### Services Utilitaires

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/src/Service/MercureJwtGenerator.php` | **Générateur JWT Mercure** | - Génération de tokens JWT pour Mercure<br>- Tokens subscribe (lecture SSE)<br>- Tokens publish (publication événements) |
| `app/src/Security/UserAuthenticationService.php` | **Service authentification** | - Récupération utilisateur authentifié<br>- Gestion du contexte de sécurité<br>- Validation des tokens |
| `app/src/Service/Cfi/CfiSessionService.php` | **Service session CFI** | - Gestion du token CFI API<br>- Persistance session utilisateur<br>- Refresh token automatique |
| `app/src/Service/Cfi/CfiTenantService.php` | **Service tenant CFI** | - Récupération division active (tenantId)<br>- Contexte multi-tenant CFI |

### Entités & Repositories

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/src/Entity/AiMessage.php` | **Entité Message IA** | - Stockage des messages de conversation<br>- Relation User, context, conversationId<br>- Role (user/assistant), contenu, timestamp |
| `app/src/Entity/AiLog.php` | **Entité Log IA** | - Trace complète requête/réponse IA<br>- Métadonnées (latence, tokens, coût, modèle)<br>- Tools utilisés, erreurs éventuelles |
| `app/src/Repository/AiMessageRepository.php` | **Repository Messages** | - Requêtes BDD pour AiMessage<br>- Récupération historique par conversationId<br>- Recherche contextualisée |
| `app/src/Repository/AiLogRepository.php` | **Repository Logs** | - Requêtes BDD pour AiLog<br>- Statistiques et métriques<br>- Recherche par utilisateur/contexte |

### Enums & DTOs

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/src/Enum/AiMessageRole.php` | **Enum Rôle Message** | - Définition des rôles (USER, ASSISTANT, SYSTEM)<br>- Type-safety pour les messages |
| `app/src/DTO/ChatResponse.php` | **DTO Réponse Chat** | - Objet structuré de réponse IA<br>- Champs : answer, metadata, toolsUsed, durationMs<br>- Transformation depuis réponse Symfony AI |

### Messaging & Handlers

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/src/Message/ChatStreamMessage.php` | **Message Messenger** | - Message asynchrone pour streaming<br>- Transport des données (question, userId, tenantId, context, conversationId, cfiToken)<br>- Dispatch via Symfony Messenger |
| `app/src/MessageHandler/ChatStreamMessageHandler.php` | **Handler Streaming** | - Traitement asynchrone des messages de streaming<br>- Exécution de la requête IA en arrière-plan<br>- Publication des chunks via ChatStreamPublisher<br>- Gestion des erreurs et timeouts |

### Exceptions

| Fichier | Rôle | Responsabilité |
|---------|------|----------------|
| `app/src/Exception/ChatException.php` | **Exception Chat** | - Exception métier spécifique au chat<br>- Messages d'erreur contextualisés<br>- Gestion des erreurs prévisibles |

---

## 📊 Flux de Données - Architecture Complète

### Structure de Page Chat (Héritage Twig)

```
layouts/home.html.twig (Layout principal)
├── <!DOCTYPE html>
├── <head> (meta, title, favicon, importmap app)
├── <body class="theme-{{ app.user.theme }}">
│   └── <div class="home-layout">
│       ├── <aside class="home-sidebar"> (Sidebar fixe desktop 280px)
│       │   ├── Header (menu/fermer)
│       │   ├── Navigation (Favoris, Historique - collapsibles)
│       │   └── Footer (Paramètres, Profil, Déconnexion)
│       ├── <aside class="offcanvas"> (Sidebar mobile)
│       ├── <main class="home-main"> (margin-left: 280px sur desktop)
│       │   ├── <header class="home-header">
│       │   │   ├── Logo adaptatif (picto + texte)
│       │   │   ├── ThemeSelector (dropdown thèmes)
│       │   │   └── Lien retour CFI
│       │   └── <div class="home-content">
│       │       └── {% block content %}
│       │           ↓
│       │           chat/index.html.twig (Vue chat)
│       │           └── <div class="chat-container">
│       │               ├── <div class="chat-header"> (Badge contexte)
│       │               ├── <div class="chat-messages"> (Messages + scroll)
│       │               ├── <div class="chat-input-container"> (Saisie + suggestions)
│       │               └── <div class="chat-loading"> (Indicateur)
│       │           {% endblock %}
│       └── <script> (sidebar.js, app.js)
└── </body>
```

### Flux de Données en Temps Réel

```
┌─────────────────────────────────────────────────────────────────┐
│                         FRONTEND (Client)                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Layout System (Héritage Twig)                                  │
│  ├── layouts/home.html.twig (sidebar + header + {% block %})    │
│  └── chat/index.html.twig (extends home, injecte contenu chat)  │
│                                                                   │
│  Composants                                                      │
│  ├── components/ThemeSelector.html.twig (switch thème)          │
│  ├── components/chat/message.html.twig (affichage messages)     │
│  └── components/chat/input.html.twig (saisie utilisateur)       │
│                                                                   │
│  Styles CSS (Cascade)                                            │
│  ├── styles/layouts/home-layout.css (sidebar + header + main)   │
│  ├── styles/chat.css (container chat)                           │
│  ├── styles/components/chat.css (messages, input, badges)       │
│  └── styles/themes/*.css (variables couleurs par thème)         │
│                                                                   │
│  JavaScript                                                      │
│  ├── sidebar.js (toggle sidebar, mode rétractable)              │
│  ├── js/chat.js (logique principale + EventSource Mercure)      │
│  └── controllers/chat_controller.js (Stimulus actions)          │
│                                                                   │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     │ HTTP POST /chat/{context}/stream
                     │ { question, conversationId }
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│                     BACKEND (Symfony)                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Contrôleur                                                      │
│  ├── ChatController::streamMessage()                            │
│  │   ├── Validation contexte + authentification                │
│  │   ├── Dispatch ChatStreamMessage (Messenger)                │
│  │   └── Retour immédiat { success, messageId }                │
│  │                                                               │
│  ├── ChatStreamMessageHandler (async)                           │
│  │   ├── Appel ChatService::processQuestion()                  │
│  │   └── Publication chunks via ChatStreamPublisher            │
│  │                                                               │
│  Services                                                        │
│  ├── ChatService (orchestration)                                │
│  │   ├── AiPromptService (génération prompts)                  │
│  │   ├── Symfony AI Bundle (appel LLM)                         │
│  │   └── AiLoggerService (traçabilité)                         │
│  │                                                               │
│  └── ChatStreamPublisher (Mercure)                              │
│      └── Publication SSE sur topic chat/{conversationId}        │
│                                                                   │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     │ SSE Events (Mercure Hub)
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│                      MERCURE HUB (Temps Réel)                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Topic: chat/{conversationId}                                   │
│  ├── JWT Subscribe (client authentifié)                         │
│  └── Chunks de réponse IA en streaming                          │
│                                                                   │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     │ EventSource onmessage()
                     ▼
┌─────────────────────────────────────────────────────────────────┐
│                     FRONTEND (Mise à jour UI)                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  chat.js                                                         │
│  ├── Réception chunk SSE                                        │
│  ├── Ajout au message assistant en cours                        │
│  ├── Auto-scroll messages                                       │
│  └── Affichage complet du message                               │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎯 Résumé des Responsabilités par Couche

### 🎨 **Layouts & Vues (Templates Twig)**
- **Rôle** : Structure HTML, héritage de templates, affichage
- **Fichiers clés** :
  - `layouts/home.html.twig` (layout principal avec sidebar + header)
  - `chat/index.html.twig` (vue chat qui extends home)
  - `components/ThemeSelector.html.twig`, `components/chat/*.twig`
- **Responsabilités** :
  - Architecture de page (sidebar 280px, header, content area)
  - Héritage Twig avec blocks (title, stylesheets, content, javascripts)
  - Navigation collapsible (Favoris, Historique)
  - Responsive (offcanvas mobile, sidebar fixe desktop)
  - Composants réutilisables (messages, input, theme selector)
  - Injection data attributes pour JS

### 🎨 **Styles (CSS)**
- **Rôle** : Apparence, layout flexbox, thématisation
- **Fichiers clés** :
  - `styles/layouts/home-layout.css` (sidebar + header + main)
  - `styles/chat.css`, `styles/components/chat.css`
  - `styles/themes/*.css` (light, dark-blue, dark-red)
- **Responsabilités** :
  - Layout flexbox principal (`.home-layout`)
  - Sidebar rétractable (mode icônes, `.sidebar-collapsed`)
  - Responsive (margin-left 280px sur desktop)
  - Logo adaptatif (filter CSS selon thème)
  - Design system, thèmes clair/sombre avec CSS variables

### ⚡ **Scripts (JavaScript)**
- **Rôle** : Interactivité, sidebar toggle, temps réel
- **Fichiers clés** :
  - `sidebar.js` (toggle sidebar rétractable)
  - `js/chat.js` (logique chat + EventSource)
  - `controllers/chat_controller.js` (Stimulus)
- **Responsabilités** :
  - Toggle sidebar (mode normal ↔ mode icônes)
  - Envoi messages, EventSource Mercure, streaming SSE
  - Auto-scroll messages, quick questions
  - Gestion thème (via ThemeSelector)

### 🔧 **Backend (PHP)**
- **Rôle** : Logique métier et orchestration IA
- **Fichiers clés** : `ChatController.php`, `ChatService.php`, `AiPromptService.php`
- **Responsabilités** : Routes API, appel LLM, génération prompts, streaming Mercure, persistance BDD

---

## 📌 Points d'Attention pour UI/UX Mockup

### Fichiers à Modifier en Priorité

#### **1. Structure HTML & Layout**
- `app/templates/layouts/home.html.twig` (sidebar + header global)
- `app/templates/chat/index.html.twig` (contenu chat dans block content)
- `app/templates/components/chat/*.twig` (messages, input)

#### **2. Styles CSS**
- `app/assets/styles/layouts/home-layout.css` (sidebar 280px, header, responsive)
- `app/assets/styles/chat.css` (container chat, layout vertical)
- `app/assets/styles/components/chat.css` (messages, avatars, badges, input)
- `app/assets/styles/themes/*.css` (couleurs, spacing, variables)

#### **3. JavaScript**
- `app/assets/sidebar.js` (toggle sidebar rétractable)
- `app/assets/js/chat.js` (EventSource, auto-scroll, quick questions)

### Éléments UI à Aligner avec Mockup

#### **Layout Global (home.html.twig)**
- **Sidebar** : Width 280px, mode rétractable (60px icônes), sections collapsibles
- **Header** : Logo adaptatif (filter CSS selon thème), ThemeSelector dropdown
- **Responsive** : Offcanvas mobile, bouton hamburger, margin-left auto sur desktop

#### **Zone Chat (chat/index.html.twig)**
- **Header chat** : Badges contexte, avatar assistant, actions (Nouveau/Exporter)
- **Zone messages** : Avatars, timestamps, formatage markdown, actions sur messages
- **Zone saisie** : Textarea auto-resize, boutons, quick questions
- **Indicateur de chargement** : Spinner animé "L'assistant réfléchit..."

#### **Thèmes**
- **Variables CSS** : Cohérence couleurs sidebar, header, chat selon thème actif
- **Logo adaptatif** : filter CSS en mode light pour conversion blanc→bleu
- **Palette** : light.css, dark-blue.css, dark-red.css

### Scripts JavaScript Impactés

- **sidebar.js** : Toggle sidebar (addClass/removeClass `.sidebar-collapsed`)
- **chat.js** : Auto-scroll, quick questions, EventSource, streaming progressif
- **Responsive** : Ajustement heights/widths selon sidebar état (280px ↔ 60px)

---

**Fin du listing**
