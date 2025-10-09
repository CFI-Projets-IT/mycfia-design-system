# 🛠️ Guide d'utilisation

Guide pratique pour l'utilisation quotidienne de la stack Docker Symfony + FrankenPHP.

## 🚀 Commandes essentielles

### Script de déploiement principal

#### Démarrage des environnements
```bash
# Développement standard
./deploy.sh dev

# Développement avec auto-détection des ports
./deploy.sh dev --auto-ports

# Développement avec reconstruction des images
./deploy.sh dev --build

# Production
./deploy.sh prod --build

# Test
./deploy.sh test
```

#### Gestion des services
```bash
# Voir le statut des services
./deploy.sh --status

# Voir les logs en temps réel
./deploy.sh --logs

# Arrêter tous les services
./deploy.sh --down
```

#### Options avancées
```bash
# Nom de projet personnalisé
./deploy.sh dev --project-name mon-projet

# Combinaison d'options
./deploy.sh dev --auto-ports --build --project-name symfony-api
```

### Commandes Docker Compose directes

#### Gestion des conteneurs
```bash
# Démarrer les services (équivalent à ./deploy.sh dev)
docker compose up -d

# Démarrer avec reconstruction
docker compose up -d --build

# Arrêter les services
docker compose down

# Arrêter et supprimer les volumes
docker compose down -v

# Redémarrer un service spécifique
docker compose restart frankenphp
```

#### Monitoring et logs
```bash
# Statut détaillé des services
docker compose ps

# Logs d'un service spécifique
docker compose logs frankenphp
docker compose logs -f mariadb

# Logs de tous les services
docker compose logs --tail=100 -f

# Utilisation des ressources
docker stats
```

## 🔧 Commandes de développement

### Gestion du conteneur PHP

#### Accès au shell
```bash
# Shell interactif dans le conteneur FrankenPHP
docker compose exec frankenphp bash

# Commande directe sans shell interactif
docker compose exec frankenphp php --version
```

#### Composer (gestionnaire de dépendances PHP)
```bash
# Installer les dépendances
docker compose exec frankenphp composer install

# Mettre à jour les dépendances
docker compose exec frankenphp composer update

# Ajouter une nouvelle dépendance
docker compose exec frankenphp composer require symfony/mailer

# Ajouter une dépendance de développement
docker compose exec frankenphp composer require --dev phpunit/phpunit

# Optimiser l'autoloader pour production
docker compose exec frankenphp composer dump-autoload --optimize
```

#### Commandes Symfony
```bash
# Créer un nouveau projet Symfony
docker compose exec frankenphp composer create-project symfony/website-skeleton .

# Console Symfony
docker compose exec frankenphp php bin/console list

# Vider le cache
docker compose exec frankenphp php bin/console cache:clear

# Créer une entité Doctrine
docker compose exec frankenphp php bin/console make:entity

# Créer un contrôleur
docker compose exec frankenphp php bin/console make:controller

# Générer les migrations
docker compose exec frankenphp php bin/console make:migration

# Exécuter les migrations
docker compose exec frankenphp php bin/console doctrine:migrations:migrate

# Charger les fixtures
docker compose exec frankenphp php bin/console doctrine:fixtures:load
```

### Gestion de la base de données

#### Commandes Doctrine
```bash
# Créer la base de données
docker compose exec frankenphp php bin/console doctrine:database:create

# Supprimer la base de données
docker compose exec frankenphp php bin/console doctrine:database:drop --force

# Mettre à jour le schéma
docker compose exec frankenphp php bin/console doctrine:schema:update --force

# Valider le schéma
docker compose exec frankenphp php bin/console doctrine:schema:validate
```

#### Accès direct MariaDB
```bash
# Shell MySQL dans le conteneur
docker compose exec mariadb mysql -u root -p

# Exécuter une requête directe
docker compose exec mariadb mysql -u root -proot -e "SHOW DATABASES;"

# Importer un dump SQL
docker compose exec -T mariadb mysql -u root -proot database_name < dump.sql

# Exporter la base de données
docker compose exec mariadb mysqldump -u root -proot database_name > backup.sql
```

### Assets et compilation frontend

#### Node.js et npm (développement)
```bash
# Installer les dépendances npm
docker compose exec frankenphp npm install

# Compiler les assets
docker compose exec frankenphp npm run build

# Mode watch pour développement
docker compose exec frankenphp npm run watch

# Webpack Encore (Symfony)
docker compose exec frankenphp php bin/console assets:install
docker compose exec frankenphp npm run dev
docker compose exec frankenphp npm run prod
```

## 🧪 Tests et qualité

### Tests PHPUnit

#### Exécution des tests
```bash
# Lancer tous les tests
docker compose exec frankenphp php bin/phpunit

# Tests d'une classe spécifique
docker compose exec frankenphp php bin/phpunit tests/Unit/UserTest.php

# Tests avec couverture
docker compose exec frankenphp php bin/phpunit --coverage-html coverage

# Tests fonctionnels
docker compose exec frankenphp php bin/phpunit tests/Controller/
```

#### Tests avec environnement dédié
```bash
# Démarrer l'environnement de test
./deploy.sh test

# Préparer la base de test
docker compose exec frankenphp php bin/console doctrine:database:create --env=test
docker compose exec frankenphp php bin/console doctrine:migrations:migrate --env=test

# Charger les fixtures de test
docker compose exec frankenphp php bin/console doctrine:fixtures:load --env=test
```

### Analyse statique et qualité

#### PHP CS Fixer (style de code)
```bash
# Installer PHP CS Fixer
docker compose exec frankenphp composer require --dev friendsofphp/php-cs-fixer

# Analyser le code
docker compose exec frankenphp vendor/bin/php-cs-fixer fix --dry-run --diff

# Corriger le style
docker compose exec frankenphp vendor/bin/php-cs-fixer fix
```

#### PHPStan (analyse statique)
```bash
# Installer PHPStan
docker compose exec frankenphp composer require --dev phpstan/phpstan

# Analyser le code
docker compose exec frankenphp vendor/bin/phpstan analyse src
```

## 📧 Email et développement

### MailHog pour capture d'emails

#### Configuration Symfony
```yaml
# config/packages/dev/mailer.yaml
framework:
    mailer:
        dsn: 'smtp://mailhog:1025'
```

#### Test d'envoi d'emails
```bash
# Commande Symfony pour tester l'envoi
docker compose exec frankenphp php bin/console messenger:consume async -vv

# Interface MailHog accessible sur
# http://localhost:8300 (ou port configuré)
```

### Mercure Hub pour temps réel

#### Configuration Symfony pour Mercure
```yaml
# config/packages/mercure.yaml
mercure:
    hubs:
        default:
            url: '%env(MERCURE_URL)%'
            jwt:
                secret: '%env(MERCURE_JWT_SECRET)%'
                publish: ['*']
```

#### Variables d'environnement
```env
# .env.local
MERCURE_URL=http://mercure:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3002/.well-known/mercure
MERCURE_JWT_SECRET=your-jwt-secret
```

## 🔍 Debugging et monitoring

### Logs et debugging

#### Voir les logs applicatifs
```bash
# Logs Symfony
docker compose exec frankenphp tail -f var/log/dev.log

# Logs Caddy
docker compose logs -f frankenphp | grep caddy

# Logs base de données
docker compose logs -f mariadb

# Logs en temps réel de tous les services
./deploy.sh --logs
```

#### Xdebug (optionnel)
```bash
# Activer Xdebug en développement
# Modifier docker/php.ini.dev
[XDebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003

# Reconstruire l'image
./deploy.sh dev --build
```

### Monitoring des performances

#### Profiling Symfony
```bash
# Installer le profiler
docker compose exec frankenphp composer require --dev symfony/profiler-pack

# Interface accessible dans l'application via /_profiler
```

#### Monitoring système
```bash
# Utilisation des ressources Docker
docker stats

# Espace disque des volumes
docker system df

# Nettoyer les ressources inutilisées
docker system prune -a
```

## 🔄 Workflows de développement

### Workflow quotidien

#### Démarrage journalier
```bash
# 1. Démarrer l'environnement
./deploy.sh dev --auto-ports

# 2. Vérifier le statut
./deploy.sh --status

# 3. Mettre à jour les dépendances si nécessaire
docker compose exec frankenphp composer install

# 4. Compiler les assets
docker compose exec frankenphp npm run dev
```

#### Développement actif
```bash
# Mode watch pour les assets
docker compose exec frankenphp npm run watch

# Console Symfony en arrière-plan
docker compose exec frankenphp php bin/console messenger:consume async -vv &

# Logs en temps réel
./deploy.sh --logs &
```

#### Fin de journée
```bash
# Arrêter les services
./deploy.sh --down

# Ou laisser tourner pour le lendemain
# (les conteneurs redémarrent automatiquement)
```

### Workflow nouvelles fonctionnalités

#### Nouvelle entité Doctrine
```bash
# 1. Créer l'entité
docker compose exec frankenphp php bin/console make:entity Product

# 2. Générer la migration
docker compose exec frankenphp php bin/console make:migration

# 3. Exécuter la migration
docker compose exec frankenphp php bin/console doctrine:migrations:migrate

# 4. Créer les fixtures (optionnel)
docker compose exec frankenphp php bin/console make:fixtures ProductFixtures

# 5. Charger les fixtures
docker compose exec frankenphp php bin/console doctrine:fixtures:load
```

#### Nouveau contrôleur API
```bash
# 1. Créer le contrôleur
docker compose exec frankenphp php bin/console make:controller Api/ProductController

# 2. Installer API Platform (optionnel)
docker compose exec frankenphp composer require api

# 3. Tester l'endpoint
curl -u krystdev:dev123 http://localhost:8080/api/products
```

### Workflow de mise à jour

#### Mise à jour des dépendances
```bash
# 1. Sauvegarder l'état actuel
./deploy.sh --down
docker compose exec mariadb mysqldump -u root -proot database_name > backup_$(date +%Y%m%d).sql

# 2. Mettre à jour composer.json
docker compose exec frankenphp composer update

# 3. Mettre à jour package.json
docker compose exec frankenphp npm update

# 4. Reconstruire l'environnement
./deploy.sh dev --build

# 5. Tester les fonctionnalités
docker compose exec frankenphp php bin/phpunit
```

#### Mise à jour des images Docker
```bash
# 1. Arrêter les services
./deploy.sh --down

# 2. Mettre à jour les versions dans .env.local
MARIADB_VERSION=11.1
MERCURE_VERSION=v0.17

# 3. Reconstruire avec les nouvelles images
./deploy.sh dev --build

# 4. Vérifier le bon fonctionnement
./deploy.sh --status
```

## 📱 Accès aux interfaces

### URLs par défaut

#### Développement
- **Application Symfony** : http://localhost:8080
- **phpMyAdmin** : http://localhost:8200
- **MailHog** : http://localhost:8300
- **Mercure Hub** : http://localhost:3002

#### Authentification
- **Utilisateur** : `krystdev`
- **Mot de passe** : `dev123`

### Domaines personnalisés

#### Configuration hosts locaux
```bash
# Ajouter dans /etc/hosts (Linux/macOS)
127.0.0.1 myproject.local
127.0.0.1 api.myproject.local
127.0.0.1 admin.myproject.local

# Windows : C:\Windows\System32\drivers\etc\hosts
127.0.0.1 myproject.local
```

#### Accès via domaines
- **Application** : http://myproject.local:8080
- **API** : http://api.myproject.local:8080
- **Admin** : http://admin.myproject.local:8080

## 🆘 Commandes de dépannage

### Problèmes courants

#### Services ne démarrent pas
```bash
# Vérifier les logs d'erreur
docker compose logs

# Reconstruire proprement
./deploy.sh --down
docker system prune -f
./deploy.sh dev --build

# Vérifier les ports
ss -tuln | grep 8080
```

#### Problèmes de permissions
```bash
# Linux/macOS : corriger les permissions
sudo chown -R $USER:$USER app/
docker compose exec frankenphp chown -R www-data:www-data /var/www/html

# Recréer avec les bonnes permissions
./deploy.sh dev --build
```

#### Cache et performances
```bash
# Vider tous les caches
docker compose exec frankenphp php bin/console cache:clear
docker compose exec frankenphp npm run build

# Nettoyer Docker
docker system prune -a
docker volume prune
```

#### Base de données corrompue
```bash
# Recréer la base de données
docker compose exec frankenphp php bin/console doctrine:database:drop --force
docker compose exec frankenphp php bin/console doctrine:database:create
docker compose exec frankenphp php bin/console doctrine:migrations:migrate
```

Pour plus de solutions détaillées, consultez le [guide de dépannage](TROUBLESHOOTING.md).