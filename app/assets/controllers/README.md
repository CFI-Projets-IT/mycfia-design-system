# Controllers Stimulus - Structure Organisée

## 📂 Organisation par Domaine Fonctionnel

Les controllers Stimulus sont organisés par **domaine fonctionnel** pour une meilleure maintenabilité et clarté du code.

```
controllers/
├── marketing/          # Controllers liés au module Marketing
│   └── generation_controller.js
├── chat/               # Controllers liés au module Chat
│   └── chat_controller.js
├── ui/                 # Controllers liés à l'interface utilisateur
│   ├── sidebar_controller.js
│   ├── theme_controller.js
│   └── range_display_controller.js
├── security/           # Controllers liés à la sécurité
│   └── csrf_protection_controller.js
└── data/               # Controllers liés à la gestion de données
    └── datatable_controller.js
```

## 🎯 Principes d'Organisation

### 1. Domaine Fonctionnel

Chaque dossier représente un **domaine fonctionnel** de l'application :

| Domaine | Description | Exemples |
|---------|-------------|----------|
| `marketing/` | Fonctionnalités marketing (personas, stratégies, assets) | `generation_controller.js` |
| `chat/` | Système de chat et conversations | `chat_controller.js` |
| `ui/` | Interface utilisateur générique (sidebar, thème, ranges) | `sidebar_controller.js`, `theme_controller.js` |
| `security/` | Sécurité et authentification | `csrf_protection_controller.js` |
| `data/` | Gestion et affichage de données | `datatable_controller.js` |

### 2. Convention de Nommage

**Nom de fichier** : `{nom}_controller.js`
**Identifiant Stimulus** : `{domaine}-{nom}`

**Exemples** :
- `marketing/generation_controller.js` → `data-controller="marketing-generation"`
- `ui/sidebar_controller.js` → `data-controller="ui-sidebar"`
- `chat/chat_controller.js` → `data-controller="chat-chat"`

### 3. Utilisation dans les Templates

```twig
{# Marketing - Génération de personas/stratégies/assets #}
<div data-controller="marketing-generation"
     data-marketing-generation-project-id-value="{{ project.id }}"
     data-marketing-generation-task-id-value="{{ taskId }}">
</div>

{# UI - Sidebar #}
<div data-controller="ui-sidebar">
    <button data-action="ui-sidebar#toggle">Toggle</button>
</div>

{# Chat #}
<div data-controller="chat-chat"
     data-chat-chat-mercure-url-value="{{ mercure_public_url }}">
</div>
```

## ✨ Avantages de cette Structure

### Maintenabilité
- ✅ **Clarté** : Chaque domaine est isolé dans son propre dossier
- ✅ **Évolution** : Facile d'ajouter de nouveaux controllers dans le bon domaine
- ✅ **Recherche** : Trouver rapidement un controller par domaine

### Scalabilité
- ✅ **Croissance** : Structure extensible sans pollution de la racine
- ✅ **Modularité** : Domaines indépendants et réutilisables
- ✅ **Organisation** : Pas de mélange de responsabilités

### Collaboration
- ✅ **Compréhension** : Structure intuitive pour les nouveaux développeurs
- ✅ **Conventions** : Règles claires pour placer les nouveaux fichiers
- ✅ **Documentation** : Organisation auto-documentée

## 📝 Règles de Placement

### Ajouter un Nouveau Controller

**Étapes** :
1. Identifier le **domaine fonctionnel** (marketing, chat, ui, security, data)
2. Créer le fichier dans le dossier correspondant : `{domaine}/{nom}_controller.js`
3. Utiliser dans les templates avec : `data-controller="{domaine}-{nom}"`

**Créer un nouveau domaine** si nécessaire :
```bash
mkdir assets/controllers/nouveau_domaine
touch assets/controllers/nouveau_domaine/mon_controller.js
```

### Exemples de Nouveaux Domaines Potentiels

- `notifications/` : Gestion des notifications temps réel
- `forms/` : Validation et gestion avancée de formulaires
- `analytics/` : Tracking et analytiques
- `admin/` : Fonctionnalités d'administration

## 🔄 Migration depuis l'Ancienne Structure

**Avant** (structure plate) :
```
controllers/
├── marketing_generation_controller.js
├── chat_controller.js
├── sidebar_controller.js
└── theme_controller.js
```

**Après** (structure par domaine) :
```
controllers/
├── marketing/generation_controller.js
├── chat/chat_controller.js
├── ui/sidebar_controller.js
└── ui/theme_controller.js
```

**Impact** : Aucun changement dans les templates, Stimulus gère automatiquement les sous-dossiers.

## 🚀 Build et Compilation

Après modification de la structure :

```bash
# Recompiler les assets
docker compose exec --user www-data frankenphp php bin/console asset-map:compile

# Vérifier que tous les controllers sont chargés
docker compose exec --user www-data frankenphp php bin/console debug:asset-map
```

## 📚 Ressources

- [Documentation Stimulus](https://stimulus.hotwired.dev/)
- [Symfony UX](https://ux.symfony.com/)
- [AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html)

---

**Maintenu par** : Équipe myCfia
**Dernière mise à jour** : 2025-11-08
