# myCFiA Design System

Système de design modulaire pour l'application myCFiA avec architecture CSS/JS, tokens, composants réutilisables et previews interactifs.

## Architecture

### CSS (22 fichiers, 1667 lignes)

```
assets/css/
├── tokens/           # Variables CSS globales
│   ├── _colors.css
│   ├── _spacing.css
│   ├── _typography.css
│   ├── _effects.css
│   └── _animations.css
├── layout/           # Structure de l'application
│   ├── _app-layout.css
│   ├── _sidebar.css
│   ├── _header.css
│   └── _content.css
├── components/       # Composants réutilisables
│   ├── _buttons.css
│   ├── _cards.css
│   ├── _fab.css
│   ├── _forms.css
│   ├── _theme-preview.css
│   ├── _profile.css
│   └── _index.css
├── themes/           # Thèmes
│   ├── light.css
│   ├── dark-blue.css
│   └── dark-red.css
└── main.css          # Point d'entrée
```

### JavaScript (5 modules ES6, 357 lignes)

```
assets/js/
├── core/
│   ├── sidebar-toggle.js
│   └── theme-switcher.js
├── components/
│   ├── theme-cards.js
│   ├── button-animations.js
│   └── index-theme-switcher.js
└── main.js
```

## Thèmes disponibles

- **Clair** : Interface lumineuse et épurée
- **Sombre Bleu** : Ambiance professionnelle et moderne
- **Sombre Rouge** : Design chaleureux et élégant

## Pages de preview

- **Templates** : Structure de base (`_template_*.html`)
- **Paramètres** : Configuration et sélection de thème (`settings_index/*.html`)
- **Profil** : Informations utilisateur, préférences et permissions (`profile/*.html`)

## Utilisation

### Prérequis

Les modules JavaScript ES6 nécessitent un serveur HTTP. Utilisez l'une de ces méthodes :

```bash
# Python 3
python3 -m http.server 8000

# Node.js (http-server)
npx http-server -p 8000

# PHP
php -S localhost:8000
```

### Navigation

Ouvrez `http://localhost:8000/index.html` pour accéder à la page d'index qui liste toutes les previews disponibles.

### Intégration dans un projet

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Design System myCFiA -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/themes/light.css" id="theme-stylesheet">
</head>
<body>
    <!-- Contenu -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="assets/js/main.js"></script>
</body>
</html>
```

## Fonctionnalités

- ✅ Architecture CSS modulaire avec tokens
- ✅ 3 thèmes interchangeables dynamiquement
- ✅ Composants réutilisables (boutons, cartes, formulaires)
- ✅ Navigation sidebar responsive avec état persistant
- ✅ Effet glassmorphism sur les boutons
- ✅ Animations et transitions fluides
- ✅ LocalStorage pour préférences utilisateur
- ✅ Support mobile/tablet/desktop

## Technologies

- **CSS3** avec Custom Properties (variables)
- **JavaScript ES6** modules
- **Bootstrap 5.3.8** pour la grille et composants de base
- **Bootstrap Icons 1.11.3**
- **Font Poppins**

## Documentation

Consultez le dossier `docs/design-system/` pour la documentation complète et les rapports d'analyse.

## Licence

© CFI - Tous droits réservés