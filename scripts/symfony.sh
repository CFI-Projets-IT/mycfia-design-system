#!/bin/bash
# Script helper pour exécuter les commandes Symfony avec les bonnes permissions
# Résout le problème de "docker compose exec" qui s'exécute en root au lieu de www-data

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Vérifier que Docker Compose est démarré
check_docker() {
    if ! docker compose ps | grep -q "Up"; then
        echo -e "${RED}❌ Erreur: Les conteneurs Docker ne sont pas démarrés${NC}"
        echo -e "${YELLOW}💡 Démarrez-les avec: ./deploy.sh dev${NC}"
        exit 1
    fi
}

# Fonction d'aide
show_help() {
    cat << EOF
${BLUE}🚀 Helper Symfony - Commandes avec permissions correctes${NC}

${GREEN}Usage:${NC}
  ./scripts/symfony.sh <commande> [arguments]

${GREEN}Commandes disponibles:${NC}

  ${YELLOW}Symfony Console:${NC}
    console <args>          - Exécuter une commande Symfony console
    make:entity            - Créer une nouvelle entité
    make:controller        - Créer un nouveau contrôleur
    make:form              - Créer un nouveau formulaire
    make:migration         - Créer une migration
    doctrine:migrate       - Exécuter les migrations
    cache:clear            - Vider le cache
    debug:router           - Afficher les routes

  ${YELLOW}Composer:${NC}
    composer <args>        - Exécuter Composer
    composer install       - Installer les dépendances
    composer require       - Ajouter une dépendance

  ${YELLOW}NPM/Assets:${NC}
    npm <args>             - Exécuter NPM
    npm install            - Installer les dépendances JS
    npm run dev            - Compiler les assets (dev)
    npm run watch          - Compiler les assets (watch mode)
    npm run build          - Compiler les assets (production)

  ${YELLOW}Initialisation:${NC}
    init                   - Initialiser un nouveau projet Symfony
    init:database          - Créer la base de données

  ${YELLOW}Utilitaires:${NC}
    bash                   - Ouvrir un shell dans le conteneur
    logs                   - Afficher les logs FrankenPHP
    ps                     - Afficher le statut des conteneurs

${GREEN}Exemples:${NC}
  ./scripts/symfony.sh make:entity User
  ./scripts/symfony.sh composer require symfony/mailer
  ./scripts/symfony.sh npm run watch
  ./scripts/symfony.sh console debug:router

${GREEN}Alias recommandé:${NC}
  alias sf='./scripts/symfony.sh'

  Ensuite vous pouvez utiliser:
  sf make:entity User
  sf console cache:clear

EOF
}

# Exécuter une commande avec www-data
exec_as_www_data() {
    docker compose exec --user www-data frankenphp "$@"
}

# Exécuter une commande console Symfony
symfony_console() {
    exec_as_www_data php bin/console "$@"
}

# Vérifier les arguments
if [ $# -eq 0 ]; then
    show_help
    exit 0
fi

# Vérifier Docker
check_docker

# Router selon la commande
case "$1" in
    # Aide
    help|--help|-h)
        show_help
        ;;

    # Commandes Symfony Console
    console)
        shift
        symfony_console "$@"
        ;;

    make:*)
        symfony_console "$@"
        ;;

    doctrine:*)
        symfony_console "$@"
        ;;

    cache:*)
        symfony_console "$@"
        ;;

    debug:*)
        symfony_console "$@"
        ;;

    # Composer
    composer)
        shift
        exec_as_www_data composer "$@"
        ;;

    # NPM
    npm)
        shift
        exec_as_www_data npm "$@"
        ;;

    # Initialisation
    init)
        echo -e "${BLUE}🚀 Initialisation d'un nouveau projet Symfony...${NC}"
        echo -e "${YELLOW}⚠️  Cela va effacer le contenu du dossier app/${NC}"
        read -p "Continuer? (y/N) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            echo -e "${BLUE}📦 Nettoyage du dossier app/...${NC}"
            rm -rf app/*
            echo -e "${BLUE}📥 Installation de Symfony...${NC}"
            exec_as_www_data symfony new . --webapp --no-git
            echo -e "${BLUE}🔧 Configuration de la base de données...${NC}"
            sed -i 's|DATABASE_URL=.*|DATABASE_URL="mysql://app_user:app_password@mariadb:3306/app_db?serverVersion=11.0.0-MariaDB\&charset=utf8mb4"|' app/.env
            echo -e "${GREEN}✅ Projet Symfony initialisé avec succès!${NC}"
            echo -e "${YELLOW}💡 Prochaine étape: ./scripts/symfony.sh init:database${NC}"
        else
            echo -e "${YELLOW}❌ Annulé${NC}"
        fi
        ;;

    init:database)
        echo -e "${BLUE}🗄️  Création de la base de données...${NC}"
        symfony_console doctrine:database:create --if-not-exists
        echo -e "${GREEN}✅ Base de données créée avec succès!${NC}"
        ;;

    # Utilitaires
    bash|shell)
        echo -e "${BLUE}🐚 Ouverture d'un shell en tant que www-data...${NC}"
        exec_as_www_data bash
        ;;

    logs)
        docker compose logs -f frankenphp
        ;;

    ps|status)
        docker compose ps
        ;;

    # Commande non reconnue - passer directement à la console Symfony
    *)
        echo -e "${YELLOW}⚠️  Commande non reconnue, tentative d'exécution via console Symfony...${NC}"
        symfony_console "$@"
        ;;
esac
