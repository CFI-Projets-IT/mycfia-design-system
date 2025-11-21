#!/bin/bash

# Script de déploiement intelligent pour stack Symfony + FrankenPHP
# Compatible avec les conventions Symfony (APP_ENV=dev/prod)

set -e

# === CONFIGURATION ===
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE=""
COMPOSE_FILES=""
BUILD_ARGS=""

# === FONCTIONS ===

find_free_port() {
    local start_port=$1
    local max_port=${2:-65535}
    local port=$start_port

    while [ $port -le $max_port ]; do
        # Vérifier si le port est utilisé par le système ou Docker
        if ! ss -tuln | grep -q ":$port " && \
           ! docker ps --format "table {{.Ports}}" | grep -q "0.0.0.0:$port->"; then
            echo $port
            return 0
        fi
        ((port++))
    done

    log_error "Aucun port libre trouvé dans la plage $start_port-$max_port"
    return 1
}

auto_configure_ports() {
    local env_file="$1"

    log_info "🔍 Recherche de ports libres..."

    # Plages de ports recommandées pour les applications web (bonnes pratiques sécurité)
    local http_port=$(find_free_port 8080 8199)      # Plage applications web non-privilégiées
    local phpmyadmin_port=$(find_free_port 8200 8299) # Plage outils de développement
    local mailhog_port=$(find_free_port 8300 8399)    # Plage services de test
    local mercure_port=$(find_free_port 3000 3099)    # Plage services temps réel

    if [ -z "$http_port" ] || [ -z "$phpmyadmin_port" ] || [ -z "$mailhog_port" ] || [ -z "$mercure_port" ]; then
        log_error "Impossible de trouver tous les ports nécessaires"
        return 1
    fi

    log_info "Ports libres trouvés:"
    log_info "  📱 Application: $http_port"
    log_info "  🗄️ phpMyAdmin: $phpmyadmin_port"
    log_info "  📧 MailHog: $mailhog_port"
    log_info "  ⚡ Mercure: $mercure_port"

    # Mise à jour du fichier d'environnement
    if [ -f "$env_file" ]; then
        sed -i "s/^HTTP_PORT=.*/HTTP_PORT=$http_port/" "$env_file"
        sed -i "s/^PHPMYADMIN_PORT=.*/PHPMYADMIN_PORT=$phpmyadmin_port/" "$env_file"
        sed -i "s/^MAILHOG_PORT=.*/MAILHOG_PORT=$mailhog_port/" "$env_file"
        sed -i "s/^MERCURE_PORT=.*/MERCURE_PORT=$mercure_port/" "$env_file"

        # Mise à jour des variables dépendantes des ports
        sed -i "s|^MERCURE_PUBLIC_URL=.*|MERCURE_PUBLIC_URL=http://localhost:$http_port/.well-known/mercure|" "$env_file"

        log_success "Configuration des ports mise à jour dans $env_file"
    else
        log_warn "Fichier $env_file non trouvé, création automatique"
        cat >> "$env_file" << EOF
# Ports configurés automatiquement
HTTP_PORT=$http_port
PHPMYADMIN_PORT=$phpmyadmin_port
MAILHOG_PORT=$mailhog_port
MERCURE_PORT=$mercure_port
MERCURE_PUBLIC_URL=http://localhost:$http_port/.well-known/mercure
EOF
    fi

    # Exporter les variables pour Docker Compose
    export HTTP_PORT=$http_port
    export PHPMYADMIN_PORT=$phpmyadmin_port
    export MAILHOG_PORT=$mailhog_port
    export MERCURE_PORT=$mercure_port
    export MERCURE_PUBLIC_URL="http://localhost:$http_port/.well-known/mercure"
}

show_help() {
    cat << EOF
🚀 Script de déploiement Symfony + FrankenPHP + Docker

USAGE:
    ./deploy.sh [ENVIRONNEMENT] [OPTIONS]

ENVIRONNEMENTS:
    dev, development    Environnement de développement local
    prod, production    Environnement de production
    preprod            Environnement de préproduction
    test               Environnement de test

OPTIONS:
    -p, --project-name NAME    Nom du projet (défaut: depuis .env)
    --build                   Force la reconstruction des images
    --auto-ports              Configuration automatique des ports libres (OBLIGATOIRE en dev)
    --down                    Arrêter les services
    --logs                    Afficher les logs
    --status                  Afficher le statut des services
    -h, --help               Afficher cette aide

EXEMPLES:
    ./deploy.sh dev --auto-ports       # Déploiement développement (auto-ports obligatoire)
    ./deploy.sh prod --build           # Production avec rebuild
    ./deploy.sh dev --auto-ports --project-name myapp
    ./deploy.sh --down                 # Arrêter tous les services
    ./deploy.sh --status               # Statut des services
    ./deploy.sh --logs                 # Voir les logs
EOF
}

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

check_requirements() {
    log_info "Vérification des prérequis..."

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

    # Vérifier les fichiers nécessaires
    local required_files=("Dockerfile" "docker-compose.yml")
    for file in "${required_files[@]}"; do
        if [ ! -f "$SCRIPT_DIR/$file" ]; then
            log_error "Fichier manquant: $file"
            exit 1
        fi
    done

    log_success "Prérequis vérifiés"
}

setup_environment() {
    local env=$1

    case $env in
        "dev"|"development")
            log_info "Configuration environnement DÉVELOPPEMENT"

            ENV_FILE=".env"
            COMPOSE_FILES="-f docker-compose.yml -f docker-compose.override.yml"

            # Détecter UID/GID automatiquement sur Linux/Mac
            if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "cygwin" ]]; then
                # Éviter l'erreur UID readonly
                USER_UID=$(id -u)
                USER_GID=$(id -g)
                export DOCKER_UID=$USER_UID
                export DOCKER_GID=$USER_GID
                log_info "UID/GID détectés: $USER_UID/$USER_GID"
            fi

            # Vérifier le fichier .env.local
            if [ ! -f "$SCRIPT_DIR/.env.local" ]; then
                log_warn "Fichier .env.local manquant"
                log_info "Créez-le depuis .env.example pour personnaliser votre configuration"
            fi
            ;;

        "prod"|"production")
            log_info "Configuration environnement PRODUCTION"

            ENV_FILE=".env"
            COMPOSE_FILES="-f docker-compose.yml -f docker-compose.prod.yml"

            # Vérifier les variables de production
            if [ ! -f "$SCRIPT_DIR/.env.prod.local" ]; then
                log_error "Fichier .env.prod.local requis pour la production"
                log_error "Créez ce fichier avec vos variables de production sécurisées"
                exit 1
            fi

            # Variables obligatoires en production
            local required_vars=("APP_SECRET" "DB_PASSWORD")
            for var in "${required_vars[@]}"; do
                if ! grep -q "^$var=" "$SCRIPT_DIR/.env.prod.local" 2>/dev/null; then
                    log_warn "Variable $var manquante dans .env.prod.local"
                fi
            done
            ;;

        "preprod")
            log_info "Configuration environnement PREPROD"

            ENV_FILE=".env"
            COMPOSE_FILES="-f docker-compose.yml -f docker-compose.preprod.yml"

            # Vérifier les variables de preprod
            if [ ! -f "$SCRIPT_DIR/.env.preprod.local" ]; then
                log_error "Fichier .env.preprod.local requis pour preprod"
                log_error "Créez ce fichier depuis .env.preprod.example"
                exit 1
            fi

            # Variables obligatoires preprod
            local required_vars=("PREPROD_CODE_PATH" "APP_SECRET" "DB_PASSWORD" "MERCURE_JWT_SECRET")
            for var in "${required_vars[@]}"; do
                if ! grep -q "^$var=" "$SCRIPT_DIR/.env.preprod.local" 2>/dev/null; then
                    log_error "Variable $var manquante dans .env.preprod.local"
                    exit 1
                fi
            done

            # Vérifier que PREPROD_CODE_PATH existe
            PREPROD_CODE_PATH=$(grep "^PREPROD_CODE_PATH=" "$SCRIPT_DIR/.env.preprod.local" | cut -d= -f2)
            if [ -n "$PREPROD_CODE_PATH" ] && [ ! -d "$PREPROD_CODE_PATH" ]; then
                log_error "Le répertoire $PREPROD_CODE_PATH n'existe pas"
                log_error "Clonez d'abord le dépôt Git : git clone <repo> $PREPROD_CODE_PATH"
                exit 1
            fi

            export APP_ENV=dev
            ;;

        "test")
            log_info "Configuration environnement TEST"
            ENV_FILE=".env"
            COMPOSE_FILES="-f docker-compose.yml"
            export APP_ENV=test
            ;;

        *)
            log_error "Environnement inconnu: $env"
            log_error "Environnements disponibles: dev, prod, preprod, test"
            exit 1
            ;;
    esac

    export APP_ENV=${env#*|}  # Normaliser dev/development -> dev
    case $APP_ENV in
        "development") export APP_ENV="dev";;
        "production") export APP_ENV="prod";;
    esac
}

deploy_services() {
    local build_flag=$1

    log_info "Déploiement des services..."
    log_info "Environnement: $APP_ENV"
    log_info "Fichiers compose: $COMPOSE_FILES"

    # Commande Docker Compose
    local cmd="docker compose $COMPOSE_FILES"

    if [ "$build_flag" = "true" ]; then
        log_info "Reconstruction des images..."
        $cmd build --no-cache
    fi

    # Démarrage des services
    log_info "Démarrage des services..."

    # Forcer la recréation si configuration automatique des ports
    if [ "$AUTO_PORTS" = "true" ]; then
        log_info "Recréation des conteneurs pour appliquer les nouveaux ports..."
        $cmd up -d --force-recreate --wait
    else
        $cmd up -d --wait
    fi

    log_success "Services déployés avec succès"

    # Affichage des informations de connexion
    show_connection_info
}

show_connection_info() {
    local http_port=$(grep "^HTTP_PORT=" "$SCRIPT_DIR/.env" 2>/dev/null | cut -d= -f2 || echo "82")
    local phpmyadmin_port=$(grep "^PHPMYADMIN_PORT=" "$SCRIPT_DIR/.env" 2>/dev/null | cut -d= -f2 || echo "8082")
    local mailhog_port=$(grep "^MAILHOG_PORT=" "$SCRIPT_DIR/.env" 2>/dev/null | cut -d= -f2 || echo "8027")
    local mercure_port=$(grep "^MERCURE_PORT=" "$SCRIPT_DIR/.env" 2>/dev/null | cut -d= -f2 || echo "3001")

    echo ""
    log_success "🌐 Services accessibles:"

    if [ "$APP_ENV" = "dev" ]; then
        echo "   📱 Application:    http://localhost:$http_port"
        echo "   🗄️  phpMyAdmin:    http://localhost:$phpmyadmin_port"
        echo "   📧 MailHog:       http://localhost:$mailhog_port"
        echo "   ⚡ Mercure:       http://localhost:$mercure_port"
        echo ""
        echo "   👤 Authentification: krystdev / dev123"
    elif [ "$APP_ENV" = "prod" ] && [[ "$COMPOSE_FILES" == *"preprod"* ]]; then
        echo "   📱 Application:    http://127.0.0.1:8081 (localhost uniquement)"
        echo "   🗄️  phpMyAdmin:    http://127.0.0.1:8082 (localhost uniquement)"
        echo "   ⚡ Mercure:       http://127.0.0.1:3081 (localhost uniquement)"
        echo ""
        echo "   ⚠️  Services accessibles uniquement depuis le serveur"
        echo "   🌐 Reverse proxy requis pour accès public HTTPS"
        echo ""
        echo "   💡 Switch de branch rapide : ./scripts/preprod-switch.sh <branch>"
    else
        echo "   📱 Application:    https://votre-domaine.com"
        echo "   ⚡ Mercure:       https://mercure.votre-domaine.com"
    fi
    echo ""
}

stop_services() {
    log_info "Arrêt des services..."
    docker compose $COMPOSE_FILES down
    log_success "Services arrêtés"
}

show_status() {
    log_info "Statut des services:"
    docker compose $COMPOSE_FILES ps
}

show_logs() {
    log_info "Logs des services (Ctrl+C pour quitter):"
    docker compose $COMPOSE_FILES logs -f
}

# === PROGRAMME PRINCIPAL ===

cd "$SCRIPT_DIR"

# Analyse des arguments
ENVIRONMENT=""
PROJECT_NAME=""
BUILD_FLAG=false
AUTO_PORTS=false
ACTION=""

while [[ $# -gt 0 ]]; do
    case $1 in
        dev|development|prod|production|preprod|test)
            ENVIRONMENT=$1
            shift
            ;;
        -p|--project-name)
            PROJECT_NAME="$2"
            shift 2
            ;;
        --build)
            BUILD_FLAG=true
            shift
            ;;
        --auto-ports)
            AUTO_PORTS=true
            shift
            ;;
        --down)
            ACTION="down"
            shift
            ;;
        --logs)
            ACTION="logs"
            shift
            ;;
        --status)
            ACTION="status"
            shift
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            log_error "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
done

# Vérifications initiales
check_requirements

# Gestion des actions spéciales
case $ACTION in
    "down")
        # Arrêt avec configuration par défaut
        COMPOSE_FILES="-f docker-compose.yml -f docker-compose.override.yml"
        stop_services
        exit 0
        ;;
    "status")
        COMPOSE_FILES="-f docker-compose.yml -f docker-compose.override.yml"
        show_status
        exit 0
        ;;
    "logs")
        COMPOSE_FILES="-f docker-compose.yml -f docker-compose.override.yml"
        show_logs
        exit 0
        ;;
esac

# Environnement requis pour le déploiement
if [ -z "$ENVIRONMENT" ]; then
    log_error "Environnement requis"
    show_help
    exit 1
fi

# Configuration de l'environnement
setup_environment "$ENVIRONMENT"

# Configuration automatique des ports (recommandée en développement local)
if [ "$APP_ENV" = "dev" ] && [ "$ENVIRONMENT" != "preprod" ]; then
    if [ "$AUTO_PORTS" != "true" ]; then
        log_warn "Ports non configurés automatiquement"
        log_warn "Risque de conflit si ports déjà utilisés"
        echo -n "Continuer sans --auto-ports ? (y/N): "
        read -r response
        if [[ ! "$response" =~ ^[Yy]$ ]]; then
            log_info "Déploiement annulé. Relancez avec : ./deploy.sh dev --auto-ports"
            exit 1
        fi
    fi
    auto_configure_ports "$SCRIPT_DIR/.env"
elif [ "$AUTO_PORTS" = "true" ] && [ "$APP_ENV" != "dev" ]; then
    log_warn "--auto-ports disponible uniquement en développement local"
    log_warn "Cette option sera ignorée pour $ENVIRONMENT"
    AUTO_PORTS=false
fi

# Nom du projet personnalisé
if [ -n "$PROJECT_NAME" ]; then
    export PROJECT_NAME="$PROJECT_NAME"
fi

# Déploiement
deploy_services "$BUILD_FLAG"

log_success "🎉 Déploiement terminé avec succès!"