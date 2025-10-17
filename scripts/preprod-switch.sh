#!/bin/bash
# Script de switch de branch pour preprod
# Usage: ./scripts/preprod-switch.sh [branch-name]

set -e

BRANCH=${1:-preprod}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && cd .. && pwd)"
APP_DIR="$SCRIPT_DIR"

echo "🔄 Switch vers branch: $BRANCH"

# Sauvegarder branch actuelle pour rollback
CURRENT_BRANCH=$(git branch --show-current)
echo "📌 Branch actuelle: $CURRENT_BRANCH"

# Vérifier état Git propre
if [[ -n $(git status -s) ]]; then
    echo "⚠️  Modifications non commitées détectées"
    echo "Voulez-vous continuer? (y/N)"
    read -r response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
        echo "❌ Annulé"
        exit 1
    fi
fi

# Fetch et checkout
echo "🔍 Fetch origin..."
git fetch origin

echo "🔄 Checkout $BRANCH..."
if ! git checkout "$BRANCH"; then
    echo "❌ Erreur lors du checkout"
    exit 1
fi

echo "⬇️  Pull dernières modifications..."
if ! git pull origin "$BRANCH"; then
    echo "❌ Erreur lors du pull"
    git checkout "$CURRENT_BRANCH"
    exit 1
fi

# Installer dépendances Composer (si nécessaire)
if [ -f "app/composer.json" ]; then
    echo "📦 Installation dépendances Composer..."
    cd app
    if ! docker compose -f ../docker-compose.yml -f ../docker-compose.preprod.yml exec -T frankenphp composer install --no-dev --optimize-autoloader --no-interaction; then
        echo "⚠️  Erreur Composer (non bloquant)"
    fi
    cd ..
fi

# Clear cache Symfony
echo "🧹 Clear cache Symfony..."
docker compose -f docker-compose.yml -f docker-compose.preprod.yml exec -T frankenphp php app/bin/console cache:clear --env=prod --no-debug

# Restart conteneurs pour appliquer changements
echo "🔄 Restart conteneurs..."
docker compose -f docker-compose.yml -f docker-compose.preprod.yml restart frankenphp messenger_worker

# Attendre que les services soient prêts
echo "⏳ Attente redémarrage services..."
sleep 5

# Test healthcheck
echo "🏥 Test healthcheck..."
if curl -f -s http://127.0.0.1:8081 > /dev/null; then
    echo "✅ Switch vers $BRANCH réussi!"
    echo "🌐 Application accessible sur http://127.0.0.1:8081"
else
    echo "❌ Healthcheck échoué"
    echo "🔙 Rollback vers $CURRENT_BRANCH..."
    git checkout "$CURRENT_BRANCH"
    docker compose -f docker-compose.yml -f docker-compose.preprod.yml restart frankenphp messenger_worker
    exit 1
fi
