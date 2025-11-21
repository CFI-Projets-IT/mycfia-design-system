# 🚀 Documentation du script de déploiement

Guide complet du script `deploy.sh` - outil intelligent pour la gestion de la stack Docker.

## 📋 Vue d'ensemble

Le script `deploy.sh` est un orchestrateur intelligent qui simplifie le déploiement et la gestion de la stack Docker Symfony + FrankenPHP. Il intègre des fonctionnalités avancées comme l'auto-détection des ports, la gestion des permissions et la configuration multi-environnement.

### Caractéristiques principales

- **🔍 Auto-détection des ports** : Scan intelligent des ports libres
- **👥 Gestion des permissions** : Configuration automatique UID/GID
- **🌍 Multi-environnement** : dev, prod, test avec configurations adaptées
- **🛡️ Validation** : Vérification des prérequis et dependencies
- **📊 Monitoring** : Statut, logs et informations de connexion
- **⚡ Optimisations** : Déploiement intelligent avec parallélisation

## 🎯 Syntaxe et utilisation

### Syntaxe générale
```bash
./deploy.sh [ENVIRONNEMENT] [OPTIONS]
```

### Environnements disponibles

#### Développement
```bash
# Syntaxes équivalentes
./deploy.sh dev
./deploy.sh development
```

#### Production
```bash
# Syntaxes équivalentes
./deploy.sh prod
./deploy.sh production
```

#### Test
```bash
./deploy.sh test
```

### Options principales

#### Options de configuration
```bash
-p, --project-name NAME    # Nom personnalisé du projet
--auto-ports              # Auto-détection des ports libres (dev local)
--full-deploy             # Déploiement complet (Composer, migrations, cache, assets)
                          # Automatique pour preprod/prod, optionnel pour dev
--build                   # Force la reconstruction des images
```

#### Options de gestion
```bash
--down                    # Arrêter tous les services
--logs                    # Afficher les logs en temps réel
--status                  # Statut des services
-h, --help               # Afficher l'aide
```

## 🔧 Fonctionnalités détaillées

### Auto-détection des ports

#### Principe de fonctionnement
```bash
find_free_port() {
    local start_port=$1
    local max_port=${2:-65535}
    local port=$start_port

    while [ $port -le $max_port ]; do
        # Double vérification :
        # 1. Port libre au niveau système (ss -tuln)
        # 2. Port libre dans Docker (docker ps --format)
        if ! ss -tuln | grep -q ":$port " && \
           ! docker ps --format "table {{.Ports}}" | grep -q "0.0.0.0:$port->"; then
            echo $port
            return 0
        fi
        ((port++))
    done
}
```

#### Plages de ports sécurisées
```bash
# Plages allouées selon les bonnes pratiques
HTTP_PORT=$(find_free_port 8080 8199)      # Applications web
PHPMYADMIN_PORT=$(find_free_port 8200 8299) # Outils développement
MAILHOG_PORT=$(find_free_port 8300 8399)    # Services de test
MERCURE_PORT=$(find_free_port 3000 3099)    # Services temps réel
```

#### Configuration automatique
```bash
# Le script met à jour automatiquement .env (fichier lu par Docker Compose)
sed -i "s/^HTTP_PORT=.*/HTTP_PORT=$http_port/" "$env_file"
sed -i "s/^PHPMYADMIN_PORT=.*/PHPMYADMIN_PORT=$phpmyadmin_port/" "$env_file"
sed -i "s|^MERCURE_PUBLIC_URL=.*|MERCURE_PUBLIC_URL=http://localhost:$http_port/.well-known/mercure|" "$env_file"
export MERCURE_PUBLIC_URL="http://localhost:$http_port/.well-known/mercure"
# MERCURE_PUBLIC_URL est automatiquement synchronisé avec HTTP_PORT
```

### Gestion intelligente des environnements

#### Configuration développement
```bash
setup_environment "dev" {
    ENV_FILE=".env"
    COMPOSE_FILES="-f docker-compose.yml -f docker-compose.override.yml"

    # Auto-détection UID/GID (Linux/macOS)
    if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "cygwin" ]]; then
        USER_UID=$(id -u)
        USER_GID=$(id -g)
        export DOCKER_UID=$USER_UID
        export DOCKER_GID=$USER_GID
    fi
}
```

#### Configuration production
```bash
setup_environment "prod" {
    ENV_FILE=".env"
    COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"

    # Validation des variables obligatoires
    local required_vars=("APP_SECRET" "DB_PASSWORD")
    for var in "${required_vars[@]}"; do
        if ! grep -q "^$var=" "$SCRIPT_DIR/.env.prod.local"; then
            log_warn "Variable $var manquante"
        fi
    done
}
```

### Déploiement complet de l'application

#### Fonction deploy_application()

La fonction `deploy_application()` automatise le workflow complet de déploiement pour un environnement production-ready :

```bash
deploy_application() {
    local container="${PROJECT_NAME}_frankenphp"

    log_info "🚀 Déploiement complet de l'application..."

    # 1. Installation des dépendances Composer
    if [ "$APP_ENV" = "prod" ]; then
        # Production : optimisé sans dépendances de dev
        docker exec --user www-data $container \
            composer install --no-dev --optimize-autoloader --no-interaction
    else
        # Dev/Preprod : avec dépendances de dev
        docker exec --user www-data $container \
            composer install --no-interaction
    fi

    # 2. Migrations Doctrine automatiques
    docker exec --user www-data $container \
        php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

    # 3. Nettoyage du cache Symfony
    docker exec --user www-data $container \
        php bin/console cache:clear

    # 4. Préchauffage du cache (prod uniquement)
    if [ "$APP_ENV" = "prod" ]; then
        docker exec --user www-data $container \
            php bin/console cache:warmup
    fi

    # 5. Recompilation des assets
    docker exec --user www-data $container rm -rf public/assets
    docker exec --user www-data $container \
        php bin/console asset-map:compile

    log_success "✅ Application déployée avec succès"
}
```

#### Activation automatique

Le déploiement complet est :
- **Automatique** pour `preprod` et `prod` (même si `APP_ENV=dev` en preprod)
- **Optionnel** pour `dev` local via le flag `--full-deploy`

```bash
# Logique d'activation
if [ "$ENVIRONMENT" = "preprod" ] || [ "$ENVIRONMENT" = "prod" ] || [ "$FULL_DEPLOY" = "true" ]; then
    deploy_application
fi
```

#### Différences par environnement

| Étape | Dev/Preprod | Production |
|-------|-------------|------------|
| Composer | `composer install` | `composer install --no-dev --optimize-autoloader` |
| Migrations | ✅ Automatique | ✅ Automatique |
| Cache clear | ✅ Oui | ✅ Oui |
| Cache warmup | ❌ Non | ✅ Oui |
| Assets | ✅ Recompilation | ✅ Recompilation |

### Système de validation

#### Prérequis système
```bash
check_requirements() {
    # Vérifier Docker
    if ! command -v docker >/dev/null 2>&1; then
        log_error "Docker n'est pas installé"
        exit 1
    fi

    # Vérifier Docker Compose
    if ! docker compose version >/dev/null 2>&1; then
        log_error "Docker Compose n'est pas disponible"
        exit 1
    fi

    # Vérifier les fichiers requis
    local required_files=("Dockerfile" "docker-compose.yml")
    for file in "${required_files[@]}"; do
        if [ ! -f "$SCRIPT_DIR/$file" ]; then
            log_error "Fichier manquant: $file"
            exit 1
        fi
    done
}
```

## 📖 Exemples d'utilisation

### Cas d'usage courants

#### Premier démarrage (développeur)
```bash
# Configuration automatique complète
./deploy.sh dev --auto-ports

# Résultat :
# 🔍 Recherche de ports libres...
# Ports libres trouvés:
#   📱 Application: 8080
#   🗄️ phpMyAdmin: 8200
#   📧 MailHog: 8300
#   ⚡ Mercure: 3002
# ✅ Configuration des ports mise à jour dans .env
# 🚀 Services déployés avec succès
```

#### Déploiement complet (développement)
```bash
# Déploiement avec migrations et recompilation assets
./deploy.sh dev --auto-ports --full-deploy

# Workflow exécuté :
# 1. Démarrage des conteneurs avec auto-détection ports
# 2. Installation Composer (avec dev)
# 3. Migrations Doctrine
# 4. Nettoyage cache Symfony
# 5. Recompilation des assets
```

#### Développement avec reconstruction
```bash
# Après modification du Dockerfile
./deploy.sh dev --build

# Ou combiné avec auto-ports
./deploy.sh dev --auto-ports --build
```

#### Projet personnalisé
```bash
# Nom de projet spécifique
./deploy.sh dev --project-name mon-api-symfony --auto-ports

# Génère des conteneurs nommés :
# mon-api-symfony_frankenphp
# mon-api-symfony_mariadb
# etc.
```

#### Déploiement preprod
```bash
# Déploiement automatique complet (sur le serveur preprod)
./deploy.sh preprod

# Workflow exécuté automatiquement :
# 1. Démarrage des conteneurs (APP_ENV=dev)
# 2. Installation Composer avec dépendances dev
# 3. Migrations Doctrine automatiques
# 4. Nettoyage cache Symfony
# 5. Recompilation des assets
# Note : --full-deploy est activé automatiquement pour preprod
```

#### Déploiement production
```bash
# Validation et déploiement sécurisé
./deploy.sh prod --build

# Le script vérifie :
# - Présence de .env.prod.local
# - Variables de sécurité (APP_SECRET, DB_PASSWORD)
# - Configuration production

# Workflow exécuté automatiquement :
# 1. Rebuild des images
# 2. Démarrage des conteneurs
# 3. Composer install --no-dev --optimize-autoloader
# 4. Migrations Doctrine
# 5. Cache clear + warmup
# 6. Recompilation assets
```

### Gestion quotidienne

#### Monitoring des services
```bash
# Statut rapide
./deploy.sh --status

# Sortie exemple :
# ℹ️ Statut des services:
# NAME                    IMAGE                         STATUS
# symfony-app_frankenphp  GoldMind_frankenphp           Up 2 hours
# symfony-app_mariadb     mariadb:11                    Up 2 hours (healthy)
# symfony-app_phpmyadmin  phpmyadmin/phpmyadmin:latest  Up 2 hours
```

#### Logs en temps réel
```bash
# Tous les services
./deploy.sh --logs

# Logs avec suivi (équivalent à docker compose logs -f)
./deploy.sh --logs
# Ctrl+C pour quitter
```

#### Arrêt propre
```bash
# Arrêt de tous les services
./deploy.sh --down

# Équivalent à :
# docker compose -f docker-compose.yml -f docker-compose.override.yml down
```

## 🔍 Fonctions internes détaillées

### Système de logging

#### Fonctions de log colorées
```bash
log_info() {
    echo "ℹ️  $1"
}

log_success() {
    echo "✅ $1"
}

log_warn() {
    echo "⚠️  $1"
}

log_error() {
    echo "❌ $1" >&2
}
```

#### Logs structurés
```bash
# Exemple de sortie du script
ℹ️  Vérification des prérequis...
✅ Prérequis vérifiés
ℹ️  Configuration environnement DÉVELOPPEMENT
ℹ️  UID/GID détectés: 1000/1000
🔍 Recherche de ports libres...
ℹ️  Ports libres trouvés:
ℹ️    📱 Application: 8080
ℹ️    🗄️ phpMyAdmin: 8200
ℹ️    📧 MailHog: 8300
ℹ️    ⚡ Mercure: 3002
✅ Configuration des ports mise à jour dans .env.local
ℹ️  Déploiement des services...
✅ Services déployés avec succès
✅ 🌐 Services accessibles:
```

### Logique de déploiement

#### Orchestration intelligente
```bash
deploy_services() {
    local build_flag=$1

    log_info "Déploiement des services..."

    local cmd="docker compose $COMPOSE_FILES"

    if [ "$build_flag" = "true" ]; then
        log_info "Reconstruction des images..."
        $cmd build --no-cache
    fi

    # Adaptation selon configuration ports
    if [ "$AUTO_PORTS" = "true" ]; then
        log_info "Recréation des conteneurs pour nouveaux ports..."
        $cmd up -d --force-recreate --wait
    else
        $cmd up -d --wait
    fi

    show_connection_info
}
```

#### Affichage des informations de connexion
```bash
show_connection_info() {
    # Lecture dynamique des ports depuis .env
    local http_port=$(grep "^HTTP_PORT=" "$SCRIPT_DIR/.env" 2>/dev/null | cut -d= -f2 || echo "82")

    echo ""
    log_success "🌐 Services accessibles:"

    if [ "$APP_ENV" = "dev" ]; then
        echo "   📱 Application:    http://localhost:$http_port"
        echo "   🗄️  phpMyAdmin:    http://localhost:$phpmyadmin_port"
        echo "   📧 MailHog:       http://localhost:$mailhog_port"
        echo "   ⚡ Mercure:       http://localhost:$mercure_port"
        echo ""
        echo "   👤 Authentification: krystdev / dev123"
    fi
}
```

## ⚙️ Configuration et personnalisation

### Variables d'environnement du script

#### Variables détectées automatiquement
```bash
# Détection du système d'exploitation
OSTYPE                 # linux-gnu, darwin, msys, cygwin

# Détection des permissions
USER_UID=$(id -u)      # UID utilisateur actuel
USER_GID=$(id -g)      # GID utilisateur actuel

# Détection du répertoire script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
```

#### Variables exportées pour Docker Compose
```bash
# Variables environnement
export APP_ENV         # dev, prod, test
export PROJECT_NAME    # Nom du projet Docker
export DOCKER_UID      # UID pour permissions
export DOCKER_GID      # GID pour permissions

# Variables ports (si auto-détection)
export HTTP_PORT
export PHPMYADMIN_PORT
export MAILHOG_PORT
export MERCURE_PORT
```

### Personnalisation du script

#### Ajout d'un nouvel environnement
```bash
# Dans setup_environment()
"staging")
    log_info "Configuration environnement STAGING"
    ENV_FILE=".env"
    COMPOSE_FILES="-f docker-compose.yml -f docker-compose.staging.yml"
    export APP_ENV="staging"
    ;;
```

#### Ajout de nouvelles validations
```bash
# Dans check_requirements()
# Vérifier Git (exemple)
if ! command -v git >/dev/null 2>&1; then
    log_warn "Git n'est pas installé (recommandé pour le développement)"
fi
```

#### Ajout de nouveaux services à surveiller
```bash
# Dans auto_configure_ports()
local redis_port=$(find_free_port 6379 6399)
sed -i "s/^REDIS_PORT=.*/REDIS_PORT=$redis_port/" "$env_file"
export REDIS_PORT=$redis_port
```

## 🔒 Sécurité et bonnes pratiques

### Validation des entrées

#### Validation des arguments
```bash
# Le script valide tous les arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        dev|development|prod|production|test)
            ENVIRONMENT=$1
            ;;
        *)
            log_error "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
done
```

#### Protection contre les erreurs
```bash
# Mode strict bash
set -e  # Arrêt sur erreur

# Vérification des fichiers critiques
if [ ! -f "$SCRIPT_DIR/$file" ]; then
    log_error "Fichier manquant: $file"
    exit 1
fi
```

### Gestion des secrets

#### Variables sensibles
```bash
# Le script ne log jamais les mots de passe
# Variables sensibles uniquement dans les fichiers .env.*
# Fichiers .env.prod.local obligatoires en production
```

#### Fichiers de configuration sécurisés
```bash
# Vérification production
if [ ! -f "$SCRIPT_DIR/.env.prod.local" ]; then
    log_error "Fichier .env.prod.local requis pour la production"
    exit 1
fi
```

## 🧪 Tests et validation

### Tests intégrés

#### Validation de la configuration
```bash
# Le script teste automatiquement :
# - Présence de Docker et Docker Compose
# - Fichiers requis présents
# - Syntaxe des fichiers de configuration
# - Disponibilité des ports
```

#### Vérification post-déploiement
```bash
# Après déploiement, le script affiche :
# - URLs d'accès avec ports détectés
# - Informations d'authentification
# - Instructions de vérification
```

### Tests manuels du script

#### Test de l'auto-détection
```bash
# Simuler ports occupés
nc -l 8080 &  # Occuper le port 8080
./deploy.sh dev --auto-ports
# Vérifier que le script trouve un autre port
```

#### Test multi-environnement
```bash
# Tester tous les environnements
./deploy.sh dev --project-name test-dev
./deploy.sh test --project-name test-test
./deploy.sh --down
```

## 🔧 Dépannage du script

### Problèmes courants

#### Script non exécutable
```bash
# Solution
chmod +x deploy.sh
```

#### Erreur "command not found"
```bash
# Vérifier le chemin
./deploy.sh dev  # ✅ Avec ./
deploy.sh dev    # ❌ Sans ./
```

#### Variables non exportées
```bash
# Débug des variables
set -x  # Mode debug bash
./deploy.sh dev
set +x  # Désactiver debug
```

### Mode debug

#### Activer le mode verbose
```bash
# Modifier temporairement le script
set -x  # Ajouter au début du script
# Voir toutes les commandes exécutées
```

#### Logs détaillés
```bash
# Rediriger les logs
./deploy.sh dev 2>&1 | tee deploy.log
# Analyser les logs
cat deploy.log
```

Le script `deploy.sh` est conçu pour être robuste, sécurisé et faciliter le développement quotidien avec la stack Docker Symfony + FrankenPHP.