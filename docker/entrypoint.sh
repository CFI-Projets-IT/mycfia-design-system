#!/bin/bash
# Script d'entrée intelligent pour gestion des droits selon l'environnement
# Compatible avec les conventions Symfony

set -e

# Chargement des variables d'environnement Symfony
APP_ENV=${APP_ENV:-dev}
APP_DEBUG=${APP_DEBUG:-1}

echo "🚀 Démarrage FrankenPHP - Environnement Symfony: $APP_ENV"

# Gestion des droits selon l'environnement
if [ "$APP_ENV" = "dev" ]; then
    echo "📝 Mode développement - Gestion dynamique des droits UID/GID"

    # Récupération de l'UID/GID cible depuis les variables d'environnement ou analyse du volume
    TARGET_UID=${DOCKER_UID:-$(stat -c '%u' /var/www/html 2>/dev/null || echo 1000)}
    TARGET_GID=${DOCKER_GID:-$(stat -c '%g' /var/www/html 2>/dev/null || echo 1000)}

    # Ajustement de l'utilisateur www-data si nécessaire
    if [ "$TARGET_UID" != "0" ] && [ "$(id -u www-data)" != "$TARGET_UID" ]; then
        echo "🔧 Ajustement www-data: UID=$TARGET_UID, GID=$TARGET_GID"
        usermod -u $TARGET_UID www-data 2>/dev/null || echo "⚠️ Ajustement UID ignoré"
        groupmod -g $TARGET_GID www-data 2>/dev/null || echo "⚠️ Ajustement GID ignoré"
    fi

    # Permissions développement (plus permissives pour faciliter l'édition)
    chown -R www-data:www-data /var/www/html 2>/dev/null || true
    find /var/www/html -type d -exec chmod 775 {} \; 2>/dev/null || true
    find /var/www/html -type f -exec chmod 664 {} \; 2>/dev/null || true

    # Créer et configurer les répertoires FrankenPHP/Caddy
    mkdir -p /var/log/caddy /data/caddy /config/caddy
    chown -R www-data:www-data /var/log/caddy /data/caddy /config/caddy
    chmod 775 /var/log/caddy /data/caddy /config/caddy

    # Permissions spéciales Symfony en développement
    if [ -d "/var/www/html/var" ]; then
        chmod -R 775 /var/www/html/var 2>/dev/null || true
    fi

    echo "✅ Permissions développement appliquées"

elif [ "$APP_ENV" = "prod" ]; then
    echo "🔒 Mode production - Configuration sécurisée"

    # Permissions production (restrictives)
    chown -R www-data:www-data /var/www/html 2>/dev/null || true
    find /var/www/html -type d -exec chmod 755 {} \; 2>/dev/null || true
    find /var/www/html -type f -exec chmod 644 {} \; 2>/dev/null || true

    # Permissions spéciales Symfony en production
    if [ -d "/var/www/html/var" ]; then
        chmod -R 775 /var/www/html/var 2>/dev/null || true
    fi
    if [ -d "/var/www/html/public" ]; then
        find /var/www/html/public -name "*.php" -exec chmod 644 {} \; 2>/dev/null || true
    fi

    echo "✅ Permissions production appliquées"

elif [ "$APP_ENV" = "test" ]; then
    echo "🧪 Mode test - Configuration testing"

    # Permissions test (similaires au développement)
    chown -R www-data:www-data /var/www/html 2>/dev/null || true
    find /var/www/html -type d -exec chmod 775 {} \; 2>/dev/null || true
    find /var/www/html -type f -exec chmod 664 {} \; 2>/dev/null || true

    echo "✅ Permissions test appliquées"
fi

# Affichage des informations de débogage en mode dev
if [ "$APP_ENV" = "dev" ] && [ "$APP_DEBUG" = "1" ]; then
    echo "🔍 Informations de débogage:"
    echo "   - APP_ENV: $APP_ENV"
    echo "   - APP_DEBUG: $APP_DEBUG"
    echo "   - UID www-data: $(id -u www-data)"
    echo "   - GID www-data: $(id -g www-data)"
    echo "   - UID cible: $TARGET_UID"
    echo "   - GID cible: $TARGET_GID"
fi

# Vérification de la santé du système
if ! command -v php >/dev/null 2>&1; then
    echo "❌ Erreur: PHP n'est pas disponible"
    exit 1
fi

if ! command -v composer >/dev/null 2>&1; then
    echo "❌ Erreur: Composer n'est pas disponible"
    exit 1
fi

# Démarrage de FrankenPHP avec la configuration appropriée
echo "🎯 Lancement de FrankenPHP"

# En développement, exécuter en tant que www-data pour les bonnes permissions
if [ "$APP_ENV" = "dev" ]; then
    exec gosu www-data frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
else
    exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
fi