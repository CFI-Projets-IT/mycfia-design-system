# 🎨 Installation et configuration de Bootstrap

Guide complet pour installer et configurer **Bootstrap 5** et **Bootstrap Icons** avec **AssetMapper** dans le projet GoldMind.

## 📋 Prérequis

- Projet Symfony 7.3 opérationnel
- AssetMapper activé (inclus par défaut avec `--webapp`)
- Environnement Docker démarré (`./deploy.sh dev --auto-ports`)

## 🚀 Installation

### Méthode recommandée : AssetMapper

**AssetMapper** est la méthode native de Symfony pour gérer les assets frontend sans bundler (Webpack, Vite, etc.).

#### Étape 1 : Installer Bootstrap

```bash
# Avec le script helper (recommandé)
./scripts/symfony.sh console importmap:require bootstrap

# Ou manuellement
docker compose exec --user www-data frankenphp php bin/console importmap:require bootstrap
```

**Paquets installés automatiquement** :
- `bootstrap@5.3.8` - JavaScript Bootstrap
- `@popperjs/core@2.11.8` - Dépendance pour les tooltips et popovers
- `bootstrap/dist/css/bootstrap.min.css@5.3.8` - Styles Bootstrap

#### Étape 2 : Installer Bootstrap Icons

```bash
# Avec le script helper (recommandé)
./scripts/symfony.sh console importmap:require bootstrap-icons/font/bootstrap-icons.css

# Ou manuellement
docker compose exec --user www-data frankenphp php bin/console importmap:require bootstrap-icons/font/bootstrap-icons.css
```

**Paquet installé** :
- `bootstrap-icons/font/bootstrap-icons.css@1.13.1` - Icônes Bootstrap

### Vérification de l'installation

```bash
# Afficher l'importmap
./scripts/symfony.sh console debug:asset-map

# Vérifier importmap.php
cat app/importmap.php
```

Vous devriez voir :
```php
return [
    'bootstrap' => ['version' => '5.3.8'],
    '@popperjs/core' => ['version' => '2.11.8'],
    'bootstrap/dist/css/bootstrap.min.css' => ['version' => '5.3.8', 'type' => 'css'],
    'bootstrap-icons/font/bootstrap-icons.css' => ['version' => '1.13.1', 'type' => 'css'],
];
```

## ⚙️ Configuration

### Étape 1 : Importer Bootstrap dans app.js

Le fichier `app/assets/app.js` est configuré pour importer Bootstrap CSS, Icons et JavaScript :

```javascript
import './bootstrap.js';
import './styles/app.css';

// Bootstrap CSS
import 'bootstrap/dist/css/bootstrap.min.css';

// Bootstrap Icons CSS
import 'bootstrap-icons/font/bootstrap-icons.css';

// Bootstrap JavaScript
import * as bootstrap from 'bootstrap';

// Exposer Bootstrap globalement (optionnel, pour usage dans le HTML)
window.bootstrap = bootstrap;

// Initialiser automatiquement les tooltips et popovers
document.addEventListener('DOMContentLoaded', () => {
    // Initialisation des tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Initialisation des popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
});

console.log('Bootstrap loaded!');
```

### ⚠️ Important : Initialisation JavaScript des Tooltips et Popovers

**Tooltips et Popovers** nécessitent une initialisation JavaScript explicite pour des raisons de performance.

Notre configuration **initialise automatiquement** tous les éléments avec :
- `data-bs-toggle="tooltip"` pour les tooltips
- `data-bs-toggle="popover"` pour les popovers

Cette initialisation se fait au chargement du DOM via l'événement `DOMContentLoaded`.

**Pourquoi ?** Bootstrap désactive par défaut ces composants car ils utilisent Popper.js et peuvent impacter les performances. Vous devez donc les activer manuellement.

### Étape 2 : Vérifier le template de base

Le fichier `app/templates/base.html.twig` doit inclure `importmap()` :

```twig
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{% block title %}Welcome!{% endblock %}</title>

        {% block stylesheets %}
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

### Étape 3 : Nettoyer le cache

```bash
./scripts/symfony.sh cache:clear
```

## 🧪 Test de l'installation

### Créer une page de test

```bash
./scripts/symfony.sh make:controller TestBootstrapController
```

Éditer `app/src/Controller/TestBootstrapController.php` :

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestBootstrapController extends AbstractController
{
    #[Route('/bootstrap-test', name: 'app_test_bootstrap')]
    public function index(): Response
    {
        return $this->render('test_bootstrap/index.html.twig');
    }
}
```

Éditer `app/templates/test_bootstrap/index.html.twig` :

```twig
{% extends 'base.html.twig' %}

{% block title %}Test Bootstrap{% endblock %}

{% block body %}
<div class="container my-5">
    <h1 class="display-4">
        <i class="bi bi-check-circle-fill text-success"></i>
        Bootstrap fonctionne !
    </h1>

    <hr class="my-4">

    {# Boutons #}
    <div class="mb-4">
        <h2>Boutons</h2>
        <button type="button" class="btn btn-primary">Primary</button>
        <button type="button" class="btn btn-secondary">Secondary</button>
        <button type="button" class="btn btn-success">Success</button>
        <button type="button" class="btn btn-danger">Danger</button>
        <button type="button" class="btn btn-warning">Warning</button>
        <button type="button" class="btn btn-info">Info</button>
    </div>

    {# Icônes #}
    <div class="mb-4">
        <h2>Icônes Bootstrap</h2>
        <i class="bi bi-heart-fill text-danger" style="font-size: 2rem;"></i>
        <i class="bi bi-star-fill text-warning" style="font-size: 2rem;"></i>
        <i class="bi bi-trophy-fill text-success" style="font-size: 2rem;"></i>
        <i class="bi bi-lightning-fill text-primary" style="font-size: 2rem;"></i>
        <i class="bi bi-moon-stars-fill text-secondary" style="font-size: 2rem;"></i>
    </div>

    {# Alert #}
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Succès!</strong> Bootstrap et Bootstrap Icons sont correctement installés.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    {# Card #}
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-folder-fill"></i> Card Bootstrap
                </div>
                <div class="card-body">
                    <h5 class="card-title">Exemple de Card</h5>
                    <p class="card-text">Bootstrap 5 avec AssetMapper fonctionne parfaitement!</p>
                    <a href="#" class="btn btn-primary">
                        <i class="bi bi-box-arrow-up-right"></i> Action
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-gear-fill"></i> Configuration
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check2-circle text-success"></i> Bootstrap 5.3.8
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check2-circle text-success"></i> Bootstrap Icons 1.13.1
                        </li>
                        <li class="list-group-item">
                            <i class="bi bi-check2-circle text-success"></i> AssetMapper
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {# Modal Test #}
    <div class="mt-4">
        <h2>Modal JavaScript</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="bi bi-box-arrow-up-right"></i> Ouvrir Modal
        </button>

        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <i class="bi bi-chat-dots-fill"></i> Modal Bootstrap
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Le JavaScript Bootstrap fonctionne correctement!
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary">Sauvegarder</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {# Tooltip Test #}
    <div class="mt-4">
        <h2>Tooltips (initialisation automatique)</h2>
        <button type="button" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Tooltip sur le haut">
            <i class="bi bi-info-circle"></i> Haut
        </button>
        <button type="button" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Tooltip à droite">
            <i class="bi bi-arrow-right-circle"></i> Droite
        </button>
        <button type="button" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Tooltip en bas">
            <i class="bi bi-arrow-down-circle"></i> Bas
        </button>
        <button type="button" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Tooltip à gauche">
            <i class="bi bi-arrow-left-circle"></i> Gauche
        </button>
    </div>

    {# Popover Test #}
    <div class="mt-4">
        <h2>Popovers (initialisation automatique)</h2>
        <button type="button" class="btn btn-danger" data-bs-toggle="popover" data-bs-title="Titre du Popover" data-bs-content="Contenu du popover. Les popovers sont initialisés automatiquement !">
            <i class="bi bi-chat-dots-fill"></i> Cliquez-moi
        </button>
        <button type="button" class="btn btn-info" data-bs-toggle="popover" data-bs-placement="top" data-bs-title="Popover en haut" data-bs-content="Le contenu s'affiche au-dessus">
            <i class="bi bi-arrow-up"></i> Popover Haut
        </button>
    </div>
</div>

{# Plus besoin de script inline : l'initialisation est automatique via app.js ! #}
{% endblock %}
```

### Tester dans le navigateur

```bash
# Ouvrir le navigateur
http://localhost:8080/bootstrap-test
```

**Ce que vous devriez voir** :
- ✅ Titre avec icône verte
- ✅ Boutons colorés Bootstrap
- ✅ Icônes Bootstrap variées
- ✅ Alert dismissible fonctionnelle
- ✅ Cards Bootstrap
- ✅ Modal qui s'ouvre au clic
- ✅ Tooltips au survol (4 directions)
- ✅ Popovers au clic (avec titre et contenu)

## 📚 Utilisation

### Composants Bootstrap

```twig
{# Navbar #}
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="bi bi-star-fill"></i> GoldMind
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Accueil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">À propos</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

{# Form #}
<form>
    <div class="mb-3">
        <label for="email" class="form-label">
            <i class="bi bi-envelope"></i> Email
        </label>
        <input type="email" class="form-control" id="email">
    </div>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-send"></i> Envoyer
    </button>
</form>

{# Badge #}
<span class="badge bg-primary">
    <i class="bi bi-bell-fill"></i> 3
</span>

{# Spinner #}
<div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">Chargement...</span>
</div>
```

### Icônes Bootstrap

```twig
{# Icône simple #}
<i class="bi bi-house"></i>

{# Icône avec taille #}
<i class="bi bi-heart-fill" style="font-size: 2rem;"></i>

{# Icône avec couleur #}
<i class="bi bi-check-circle-fill text-success"></i>

{# Liste d'icônes populaires #}
<i class="bi bi-heart-fill"></i>       {# Cœur #}
<i class="bi bi-star-fill"></i>        {# Étoile #}
<i class="bi bi-gear-fill"></i>        {# Engrenage #}
<i class="bi bi-person-fill"></i>      {# Personne #}
<i class="bi bi-envelope-fill"></i>    {# Email #}
<i class="bi bi-cart-fill"></i>        {# Panier #}
<i class="bi bi-search"></i>           {# Recherche #}
<i class="bi bi-trash-fill"></i>       {# Corbeille #}
<i class="bi bi-pencil-fill"></i>      {# Crayon #}
<i class="bi bi-download"></i>         {# Télécharger #}
```

**Catalogue complet** : https://icons.getbootstrap.com/

### Tooltips et Popovers

**Tooltips** (infobulles au survol) :

```twig
{# Tooltip basique #}
<button type="button" class="btn btn-primary"
        data-bs-toggle="tooltip"
        data-bs-title="Texte du tooltip">
    Survolez-moi
</button>

{# Tooltip avec position #}
<button data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="En haut">Haut</button>
<button data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="À droite">Droite</button>
<button data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="En bas">Bas</button>
<button data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="À gauche">Gauche</button>
```

**Popovers** (infobulles au clic avec titre et contenu) :

```twig
{# Popover basique #}
<button type="button" class="btn btn-danger"
        data-bs-toggle="popover"
        data-bs-title="Titre du popover"
        data-bs-content="Contenu détaillé du popover">
    Cliquez-moi
</button>

{# Popover avec position #}
<button data-bs-toggle="popover"
        data-bs-placement="top"
        data-bs-title="Titre"
        data-bs-content="Contenu au-dessus">
    Popover Haut
</button>

{# Popover dismissible (se ferme au clic extérieur) #}
<button type="button" class="btn btn-success"
        data-bs-toggle="popover"
        data-bs-trigger="focus"
        data-bs-title="Popover dismissible"
        data-bs-content="Cliquez ailleurs pour fermer">
    Popover Dismissible
</button>
```

**✨ Initialisation automatique** : Les tooltips et popovers sont automatiquement initialisés via `app.js`. Pas besoin de JavaScript supplémentaire !

### JavaScript Bootstrap

```javascript
// Modal
const modal = new bootstrap.Modal(document.getElementById('myModal'));
modal.show();

// Toast
const toast = new bootstrap.Toast(document.getElementById('myToast'));
toast.show();

// Tooltip manuel (si besoin d'options personnalisées)
const tooltip = new bootstrap.Tooltip(document.getElementById('myButton'), {
    placement: 'top',
    trigger: 'hover'
});

// Popover manuel (si besoin d'options personnalisées)
const popover = new bootstrap.Popover(document.getElementById('myPopover'), {
    html: true,
    content: '<strong>HTML personnalisé</strong>'
});

// Dropdown (déjà fonctionnel avec data-bs-toggle)
```

## 🔧 Configuration avancée

### Personnalisation des styles

Créer `app/assets/styles/custom-bootstrap.css` :

```css
/* Variables Bootstrap personnalisées */
:root {
    --bs-primary: #0066cc;
    --bs-secondary: #6c757d;
    --bs-success: #28a745;
    /* ... autres variables ... */
}

/* Styles personnalisés */
.btn-custom {
    background-color: #ff6600;
    color: white;
}

.btn-custom:hover {
    background-color: #cc5200;
}
```

Importer dans `app.js` :

```javascript
import './styles/custom-bootstrap.css';
```

### Utiliser uniquement certains composants

Pour réduire la taille, importer seulement ce dont vous avez besoin :

```javascript
// Au lieu de :
import * as bootstrap from 'bootstrap';

// Importez seulement :
import { Modal, Toast, Tooltip } from 'bootstrap';

window.Modal = Modal;
window.Toast = Toast;
window.Tooltip = Tooltip;
```

### Thème sombre (Dark Mode)

```html
<html data-bs-theme="dark">
```

Ou dynamiquement :

```javascript
// Toggle dark mode
document.documentElement.setAttribute('data-bs-theme', 'dark');

// Toggle light mode
document.documentElement.setAttribute('data-bs-theme', 'light');
```

## 📋 Versions installées

- **Bootstrap** : 5.3.8 (latest)
- **Bootstrap Icons** : 1.13.1 (latest)
- **Popper.js** : 2.11.8 (dépendance Bootstrap)

## 🔄 Mise à jour

Pour mettre à jour Bootstrap vers la dernière version :

```bash
# Mettre à jour l'importmap
./scripts/symfony.sh console importmap:update
```

Pour une version spécifique :

```bash
# Supprimer l'ancienne version
./scripts/symfony.sh console importmap:remove bootstrap

# Installer la version spécifique
./scripts/symfony.sh console importmap:require bootstrap@5.3.0
```

## 📚 Ressources

### Documentation locale

```bash
# Documentation Bootstrap disponible localement
~/.claude/mcp/context7/vendors/bootstrap-docs/
```

### Documentation officielle

- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [Bootstrap Icons](https://icons.getbootstrap.com/)
- [Bootstrap Examples](https://getbootstrap.com/docs/5.3/examples/)
- [Symfony AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html)

### Ressources utiles

- [Bootstrap Themes](https://themes.getbootstrap.com/)
- [Bootstrap Snippets](https://getbootstrap.com/docs/5.3/examples/)
- [Bootstrap Cheat Sheet](https://bootstrap-cheatsheet.themeselection.com/)

## ⚠️ Remarques importantes

### AssetMapper vs Webpack Encore

**AssetMapper** :
- ✅ Pas de build, développement instantané
- ✅ Pas de Node.js requis en production
- ✅ Simple et rapide
- ❌ Pas de SASS/SCSS
- ❌ Pas de minification avancée

**Webpack Encore** :
- ✅ Support SASS/SCSS
- ✅ Minification avancée
- ✅ Tree shaking
- ❌ Build requis
- ❌ Plus complexe

**Pour GoldMind** : AssetMapper est parfait pour commencer. Migrez vers Encore si vous avez besoin de SASS ou d'optimisations avancées.

### Performance

- Les fichiers sont servis via CDN (jsDelivr) en production
- AssetMapper gère automatiquement le cache et les versions
- Les imports sont lazy-loaded via ES modules

### Compatibilité navigateurs

Bootstrap 5 nécessite :
- Chrome, Firefox, Safari, Edge dernières versions
- Pas de support IE11

## 🎯 Bonnes pratiques

- ✅ Utiliser les classes utilitaires Bootstrap au maximum
- ✅ Préférer les composants Bootstrap aux composants custom
- ✅ Utiliser Bootstrap Icons au lieu d'icônes custom
- ✅ **Tooltips et popovers** : Initialisation automatique via `data-bs-toggle` (déjà configuré dans `app.js`)
- ✅ Utiliser `data-bs-*` attributes pour le JavaScript déclaratif
- ✅ Tester le responsive sur mobile
- ✅ Utiliser le système de grille Bootstrap (row/col)
- ✅ Nettoyer le cache après modification (`cache:clear`)
- ✅ Utiliser `data-bs-title` au lieu de `title` pour les tooltips (meilleure compatibilité)
- ✅ Les popovers nécessitent `data-bs-content` en plus de `data-bs-title`

## 🔧 Dépannage

### Les styles ne s'appliquent pas

```bash
# Vider le cache
./scripts/symfony.sh cache:clear

# Vérifier l'importmap
./scripts/symfony.sh console debug:asset-map

# Vérifier que base.html.twig contient {{ importmap('app') }}
```

### Le JavaScript ne fonctionne pas

```bash
# Vérifier la console navigateur (F12)
# Vérifier que window.bootstrap est défini dans app.js

# Tester dans la console navigateur :
console.log(window.bootstrap);
```

### Les icônes ne s'affichent pas

```bash
# Vérifier que l'import est dans app.js :
import 'bootstrap-icons/font/bootstrap-icons.css';

# Nettoyer le cache
./scripts/symfony.sh cache:clear
```

Bootstrap et Bootstrap Icons sont maintenant prêts à l'emploi dans GoldMind ! 🎨
