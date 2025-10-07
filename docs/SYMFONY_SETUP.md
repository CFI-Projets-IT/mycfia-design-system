# 🚀 Initialisation d'un projet Symfony

Guide pour initialiser un nouveau projet Symfony dans l'environnement Docker GoldMind.

## 📋 Prérequis

Avant de commencer, assurez-vous que :
- L'environnement Docker est opérationnel (`./deploy.sh dev`)
- Le conteneur `GoldMind_frankenphp` est en cours d'exécution
- Le dossier `app/` est prêt à accueillir le projet

### Vérification de l'environnement

```bash
# Vérifier que les conteneurs sont démarrés
docker compose ps

# Le conteneur GoldMind_frankenphp doit être "healthy"
```

## 🎯 Script Helper (Recommandé)

Pour éviter les problèmes de permissions, utilisez le **script helper** qui exécute automatiquement les commandes avec les bonnes permissions.

### Utilisation du script helper

```bash
# Afficher l'aide
./scripts/symfony.sh help

# Créer un alias pour plus de simplicité
alias sf='./scripts/symfony.sh'

# Ensuite utiliser directement
sf make:entity User
sf console cache:clear
sf composer require symfony/mailer
```

### Avantages du script helper
- ✅ **Permissions correctes** : Toutes les commandes s'exécutent en tant que `www-data` (UID/GID 1000:1000)
- ✅ **Fichiers éditables** : Les fichiers générés sont directement modifiables depuis l'hôte
- ✅ **Pas de chown manuel** : Plus besoin de corriger les permissions après chaque commande
- ✅ **Commandes simplifiées** : Interface unifiée pour Symfony, Composer, NPM

### Commandes disponibles

#### Symfony Console
```bash
sf make:entity User              # Créer une entité
sf make:controller HomeController # Créer un contrôleur
sf make:migration                # Créer une migration
sf doctrine:migrate              # Exécuter les migrations
sf cache:clear                   # Vider le cache
sf debug:router                  # Afficher les routes
```

#### Composer
```bash
sf composer install              # Installer les dépendances
sf composer require symfony/mailer # Ajouter une dépendance
```

#### NPM/Assets
```bash
sf npm install                   # Installer les dépendances JS
sf npm run dev                   # Compiler les assets
sf npm run watch                 # Mode watch
```

#### Utilitaires
```bash
sf bash                          # Shell dans le conteneur
sf logs                          # Logs FrankenPHP
sf ps                            # Statut des conteneurs
```

## 🛠️ Méthodes d'initialisation

### Méthode 1 : Avec le script helper (Recommandée) ⭐

La méthode la plus simple et sans problème de permissions.

```bash
# Initialiser un nouveau projet Symfony complet
./scripts/symfony.sh init

# Créer la base de données
./scripts/symfony.sh init:database
```

**Ce que fait cette commande** :
- Nettoie le dossier `app/` (après confirmation)
- Installe Symfony avec `--webapp --no-git`
- Configure automatiquement `DATABASE_URL` dans `app/.env`
- Applique les bonnes permissions (www-data 1000:1000)
- Fichiers directement éditables depuis l'hôte

### Méthode 2 : Projet Symfony complet (manuel)

Cette méthode crée un projet Symfony avec tous les composants nécessaires pour une application web complète.

```bash
# Accéder au conteneur FrankenPHP en tant que www-data
docker compose exec --user www-data frankenphp bash

# Initialiser Symfony dans le répertoire courant (/var/www/html → app/)
symfony new . --webapp --no-git

# Quitter le conteneur
exit
```

**⚠️ Important** : Notez l'option `--user www-data` pour éviter les problèmes de permissions.

#### Ce qui est installé
- **Symfony Framework** : Framework complet
- **Doctrine ORM** : Gestion de base de données
- **Twig** : Moteur de templates
- **Symfony Security** : Système d'authentification
- **Symfony Form** : Gestion des formulaires
- **Symfony Validator** : Validation des données
- **Webpack Encore** : Gestion des assets (CSS/JS)
- **Symfony Mailer** : Envoi d'emails

### Méthode 3 : Projet Symfony minimal

Pour un microservice ou une API sans interface web :

```bash
# Avec le script helper
./scripts/symfony.sh bash
symfony new . --no-git
exit

# Ou manuellement avec --user www-data
docker compose exec --user www-data frankenphp bash
symfony new . --no-git
exit
```

### Méthode 4 : Version Symfony spécifique

```bash
# Avec le script helper
./scripts/symfony.sh bash
symfony new . --webapp --version=7.3 --no-git
exit

# Ou manuellement avec --user www-data
docker compose exec --user www-data frankenphp bash
symfony new . --webapp --version=7.3 --no-git  # Symfony 7.3
# symfony new . --webapp --version=6.4 --no-git  # Ou Symfony 6.4 LTS
exit
```

## 📂 Structure générée

Après l'initialisation, la structure suivante est créée dans `app/` :

```
app/
├── bin/                    # Scripts exécutables (console)
├── config/                 # Configuration Symfony
├── migrations/             # Migrations de base de données
├── public/                 # Point d'entrée web (index.php)
├── src/                    # Code source de l'application
│   ├── Controller/         # Contrôleurs
│   ├── Entity/             # Entités Doctrine
│   └── Repository/         # Repositories
├── templates/              # Templates Twig
├── tests/                  # Tests unitaires et fonctionnels
├── var/                    # Cache et logs
├── vendor/                 # Dépendances Composer
├── .env                    # Configuration environnement (versionné)
├── composer.json           # Dépendances PHP
└── symfony.lock            # Versions des bundles
```

## ⚙️ Configuration post-installation

### 1. Configurer la base de données

Modifier le fichier `app/.env` :

```bash
# Ouvrir le fichier de configuration
nano app/.env
```

Mettre à jour la ligne `DATABASE_URL` :

```env
# Configuration MariaDB pour GoldMind
DATABASE_URL="mysql://app_user:app_password@mariadb:3306/app_db?serverVersion=mariadb-11&charset=utf8mb4"
```

**Important** : Les valeurs correspondent aux variables définies dans `.env` du projet Docker :
- `app_user` : `${DB_USER}`
- `app_password` : `${DB_PASSWORD}`
- `mariadb` : Nom du service Docker
- `app_db` : `${DB_NAME}`
- `mariadb-11` : Version MariaDB

### 2. Créer la base de données

```bash
# Accéder au conteneur
docker compose exec frankenphp bash

# Créer la base de données
php bin/console doctrine:database:create

# Quitter le conteneur
exit
```

### 3. Installer les assets (si --webapp)

```bash
# Accéder au conteneur
docker compose exec frankenphp bash

# Installer les dépendances JavaScript
npm install

# Compiler les assets (développement)
npm run dev

# Ou watch mode pour recompilation automatique
npm run watch

# Quitter le conteneur
exit
```

### 4. Vérifier les permissions

Les fichiers créés dans le conteneur doivent être éditables depuis l'hôte grâce au système UID/GID automatique :

```bash
# Vérifier les propriétaires des fichiers
ls -la app/

# Tous les fichiers doivent appartenir à votre utilisateur (1000:1000)
```

Si les permissions sont incorrectes :

```bash
# Redémarrer avec les bons UID/GID
UID=$(id -u) GID=$(id -g) docker compose down
UID=$(id -u) GID=$(id -g) ./deploy.sh dev
```

## 🧪 Vérification de l'installation

### Accès à l'application

Ouvrir dans le navigateur :
- **Application Symfony** : http://localhost:82
- **Profiler Symfony** : http://localhost:82/_profiler (en mode dev)

### Page d'accueil attendue

Vous devriez voir :
- ✅ Page d'accueil Symfony avec le message de bienvenue
- ✅ Barre de debug Symfony en bas de page (mode dev)
- ✅ Accès au profiler fonctionnel

### Tests en ligne de commande

```bash
# Accéder au conteneur
docker compose exec frankenphp bash

# Vérifier la version Symfony
php bin/console --version

# Lister les routes
php bin/console debug:router

# Vérifier la configuration
php bin/console about

# Quitter le conteneur
exit
```

## 🔧 Commandes Symfony courantes

### Création d'entités

```bash
# Avec le script helper (recommandé)
./scripts/symfony.sh make:entity

# Ou manuellement
docker compose exec --user www-data frankenphp php bin/console make:entity
```

### Création de contrôleurs

```bash
# Avec le script helper (recommandé)
./scripts/symfony.sh make:controller

# Ou manuellement
docker compose exec --user www-data frankenphp php bin/console make:controller
```

### Migrations de base de données

```bash
# Avec le script helper (recommandé)
./scripts/symfony.sh make:migration
./scripts/symfony.sh doctrine:migrate

# Ou manuellement
docker compose exec --user www-data frankenphp php bin/console make:migration
docker compose exec --user www-data frankenphp php bin/console doctrine:migrations:migrate
```

### Gestion du cache

```bash
# Avec le script helper (recommandé)
./scripts/symfony.sh cache:clear
./scripts/symfony.sh cache:warmup

# Ou manuellement
docker compose exec --user www-data frankenphp php bin/console cache:clear
docker compose exec --user www-data frankenphp php bin/console cache:warmup
```

## ⚠️ Points d'attention

### Dossier app/ non vide

Si le dossier `app/` contient déjà des fichiers :

```bash
# Sauvegarder d'abord
mv app app.backup

# Créer un nouveau dossier vide
mkdir app

# Puis initialiser Symfony
docker compose exec frankenphp symfony new . --webapp --no-git
```

### Erreur "Directory not empty"

Si Symfony refuse de s'installer car le dossier n'est pas vide :

```bash
# Option 1 : Forcer l'installation (risqué)
docker compose exec frankenphp symfony new . --webapp --no-git --force

# Option 2 : Nettoyer le dossier (recommandé)
rm -rf app/*
docker compose exec frankenphp symfony new . --webapp --no-git
```

### Problèmes de permissions

Si vous ne pouvez pas éditer les fichiers générés :

```bash
# Vérifier les UID/GID dans le conteneur
docker compose exec frankenphp id

# Vérifier votre UID/GID hôte
id

# Redémarrer avec les bons UID/GID si différents
UID=$(id -u) GID=$(id -g) docker compose down
UID=$(id -u) GID=$(id -g) ./deploy.sh dev
```

## 📚 Ressources

### Documentation officielle
- [Symfony Documentation 7.3](https://symfony.com/doc/7.3/index.html)
- [Symfony Best Practices](https://symfony.com/doc/current/best_practices.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/index.html)

### Documentation locale (Context7)
```bash
# Accès via Claude Code avec MCP Context7
~/.claude/mcp/context7/vendors/symfony-docs-7.3/
```

### Commandes d'aide Symfony

```bash
# Avec le script helper (recommandé)
./scripts/symfony.sh console list
./scripts/symfony.sh console help make:entity

# Ou manuellement
docker compose exec --user www-data frankenphp php bin/console list
docker compose exec --user www-data frankenphp php bin/console help make:entity
```

## 🔄 Workflow recommandé

### Développement quotidien

1. **Démarrer l'environnement**
   ```bash
   ./deploy.sh dev
   ```

2. **Créer un alias pour le script helper (optionnel)**
   ```bash
   alias sf='./scripts/symfony.sh'
   ```

3. **Créer une entité**
   ```bash
   sf make:entity User
   ```

4. **Générer la migration**
   ```bash
   sf make:migration
   ```

5. **Appliquer la migration**
   ```bash
   sf doctrine:migrate
   ```

6. **Créer un contrôleur**
   ```bash
   sf make:controller UserController
   ```

7. **Tester dans le navigateur**
   - Ouvrir http://localhost:82

8. **Arrêter proprement**
   ```bash
   docker compose down
   ```

### Bonnes pratiques

- ✅ **Utiliser le script helper** : `./scripts/symfony.sh` ou créer un alias `sf`
- ✅ **Toujours ajouter --user www-data** si vous utilisez `docker compose exec` manuellement
- ✅ **Pas de chown manuel** : Le script helper gère les permissions automatiquement
- ✅ Utiliser `--no-git` pour éviter les conflits avec le Git du projet Docker
- ✅ Configurer DATABASE_URL dans `app/.env` après installation (ou utiliser `./scripts/symfony.sh init`)
- ✅ Consulter la documentation Symfony 7.3 locale via Context7
