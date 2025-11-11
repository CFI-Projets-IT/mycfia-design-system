# JavaScript Modules - Structure Organisée

## 📂 Organisation par Domaine Fonctionnel

Les modules JavaScript vanilla sont organisés par **domaine fonctionnel** pour cohérence avec les controllers Stimulus.

```
js/
├── chat/                       # Modules liés au chat
│   ├── chat.js
│   └── conversation-sidebar.js
├── ui/                         # Modules interface utilisateur
│   └── division-selector.js
├── components/                 # Composants réutilisables
│   └── data/
│       └── datatable-renderer.js
└── templates/                  # Scripts spécifiques à des templates
    └── marketing/
        └── project_new.js
```

## 🎯 Principes d'Organisation

### 1. Domaines Fonctionnels

| Domaine | Description | Fichiers |
|---------|-------------|----------|
| `chat/` | Système de chat et gestion des conversations | `chat.js`, `conversation-sidebar.js` |
| `ui/` | Composants d'interface utilisateur | `division-selector.js` |
| `components/` | Composants réutilisables par domaine | `data/datatable-renderer.js` |
| `templates/` | Scripts liés à des templates spécifiques | `marketing/project_new.js` |

### 2. Types de Modules

#### Modules Génériques (`chat/`, `ui/`)

Scripts JavaScript vanilla réutilisables dans plusieurs contextes :

**Exemple** : `chat/chat.js`
- Utilisé dans : `templates/chat/index.html.twig`
- Gestion complète du système de chat
- Indépendant, auto-initialisé au chargement

#### Composants (`components/`)

Fonctions et classes réutilisables organisées par domaine :

**Exemple** : `components/data/datatable-renderer.js`
- Rendering de tableaux de données
- Exportable et réutilisable
- Pas d'auto-initialisation (importé où nécessaire)

#### Scripts Templates (`templates/`)

Scripts spécifiques à UN template particulier, organisés par module métier :

**Exemple** : `templates/marketing/project_new.js`
- Utilisé uniquement dans : `templates/marketing/project/new.html.twig`
- Enrichissement IA pour la création de projets
- Gestion EventSource Mercure spécifique

## 🔗 Utilisation dans les Templates

### Import Direct (Modules Génériques)

```twig
{# Chat principal #}
{% block javascripts %}
    {{ parent() }}
    <script type="module" src="{{ asset('js/chat/chat.js') }}"></script>
{% endblock %}

{# Sidebar conversations #}
<script type="module" src="{{ asset('js/chat/conversation-sidebar.js') }}"></script>

{# Sélecteur divisions #}
<script type="module" src="{{ asset('js/ui/division-selector.js') }}"></script>
```

### Import Conditionnel (Scripts Templates)

```twig
{# Marketing - Enrichissement projet #}
{% block javascripts %}
    {{ parent() }}
    <script type="module" src="{{ asset('js/templates/marketing/project_new.js') }}"></script>
{% endblock %}
```

### Import de Composants (Dans d'autres modules JS)

```javascript
// Dans un autre module JavaScript
import { DataTableRenderer } from '../components/data/datatable-renderer.js';

const renderer = new DataTableRenderer(config);
renderer.render(data);
```

## ✨ Avantages de cette Structure

### Cohérence

- ✅ **Alignement** : Structure identique aux controllers Stimulus
- ✅ **Navigation** : Organisation intuitive par domaine
- ✅ **Compréhension** : Fichiers groupés par responsabilité

### Maintenabilité

- ✅ **Clarté** : Séparation nette entre modules génériques, composants et scripts templates
- ✅ **Recherche** : Trouver rapidement un module par domaine
- ✅ **Évolution** : Ajout facile de nouveaux domaines

### Réutilisabilité

- ✅ **Components** : Composants réutilisables clairement séparés
- ✅ **Modules** : Scripts génériques indépendants des templates
- ✅ **Templates** : Scripts spécifiques isolés, pas de pollution globale

## 📝 Règles de Placement

### Nouveau Module Générique

Si le module est réutilisable dans **plusieurs contextes** :

```
js/{domaine}/{nom}.js

Exemples :
- js/chat/notifications.js
- js/ui/modal-manager.js
- js/marketing/analytics.js
```

### Nouveau Composant Réutilisable

Si c'est une fonction/classe exportable :

```
js/components/{domaine}/{nom}.js

Exemples :
- js/components/forms/validator.js
- js/components/ui/tooltip-manager.js
- js/components/data/chart-renderer.js
```

### Nouveau Script Template

Si le script est lié à **UN SEUL template** :

```
js/templates/{module}/{nom}.js

Exemples :
- js/templates/marketing/strategy_new.js
- js/templates/analytics/dashboard.js
- js/templates/admin/settings.js
```

## 🔄 Comparaison Avant/Après

### Avant (Structure Plate)

```
js/
├── chat.js
├── conversation-sidebar.js
├── division-selector.js
├── components/
│   └── datatable-renderer.js
└── templates/
    └── marketing_project_new.js
```

**Problèmes** :
- ❌ Fichiers racine mélangés sans logique
- ❌ Pas de séparation par domaine
- ❌ Difficile de trouver les modules liés

### Après (Structure par Domaine)

```
js/
├── chat/
│   ├── chat.js
│   └── conversation-sidebar.js
├── ui/
│   └── division-selector.js
├── components/
│   └── data/
│       └── datatable-renderer.js
└── templates/
    └── marketing/
        └── project_new.js
```

**Avantages** :
- ✅ Organisation claire par domaine
- ✅ Composants groupés par responsabilité
- ✅ Structure évolutive et maintenable

## 📚 Correspondance avec Controllers

Les modules JavaScript suivent la même organisation que les controllers Stimulus pour une cohérence totale :

| Domaine | Controllers Stimulus | Modules JavaScript |
|---------|----------------------|--------------------|
| **Marketing** | `controllers/marketing/generation_controller.js` | `js/templates/marketing/project_new.js` |
| **Chat** | `controllers/chat/chat_controller.js` | `js/chat/chat.js`, `js/chat/conversation-sidebar.js` |
| **UI** | `controllers/ui/sidebar_controller.js` | `js/ui/division-selector.js` |
| **Data** | `controllers/data/datatable_controller.js` | `js/components/data/datatable-renderer.js` |

## 🚀 Migration et Compilation

### Après Modification de Structure

```bash
# Vider le cache Symfony
docker compose exec --user www-data frankenphp php bin/console cache:clear

# Recompiler les assets
docker compose exec --user www-data frankenphp php bin/console asset-map:compile

# Vérifier les assets chargés
docker compose exec --user www-data frankenphp php bin/console debug:asset-map | grep js/
```

### Vérifier les Imports

```bash
# Vérifier que tous les templates utilisent les nouveaux chemins
grep -r "asset('js/" templates/
```

## 📚 Ressources

- **Documentation Controllers** : `assets/controllers/README.md`
- **AssetMapper** : https://symfony.com/doc/current/frontend/asset_mapper.html
- **Modules ES6** : https://developer.mozilla.org/fr/docs/Web/JavaScript/Guide/Modules

---

**Maintenu par** : Équipe myCfia
**Dernière mise à jour** : 2025-11-08
