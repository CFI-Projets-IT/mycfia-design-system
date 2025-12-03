#!/bin/bash
# Script d'entrée pour Messenger Worker
# Réutilise la logique de permissions de entrypoint.sh

set -e

# Chargement des variables d'environnement Symfony
APP_ENV=${APP_ENV:-dev}
APP_DEBUG=${APP_DEBUG:-1}

echo "🚀 Démarrage Messenger Worker - Environnement Symfony: $APP_ENV"

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

    # Créer les répertoires Symfony nécessaires
    mkdir -p /var/www/html/var/log/supervisor 2>/dev/null || true
    mkdir -p /var/www/html/var/cache 2>/dev/null || true
    mkdir -p /var/www/html/var/log 2>/dev/null || true

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

    echo "✅ Permissions production appliquées"
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

# Fonction d'attente pour la disponibilité de MariaDB
wait_for_mariadb() {
    local max_attempts=30
    local attempt=1
    local wait_time=2

    echo "⏳ Attente de MariaDB (max ${max_attempts} tentatives)..."

    while [ $attempt -le $max_attempts ]; do
        # Vérification de la résolution DNS
        if ! getent hosts mariadb > /dev/null 2>&1; then
            echo "   [$attempt/$max_attempts] Résolution DNS 'mariadb' en cours..."
            sleep $wait_time
            attempt=$((attempt + 1))
            continue
        fi

        # Vérification de la connexion TCP
        if timeout 2 bash -c "cat < /dev/null > /dev/tcp/mariadb/3306" 2>/dev/null; then
            # Vérification de l'authentification avec les credentials Symfony
            if php -r "
                \$dsn = getenv('DATABASE_URL') ?: 'mysql://root:root@mariadb:3306/app_db';
                preg_match('/mysql:\\/\\/([^:]+):([^@]+)@([^:]+):(\d+)\\/(.+)/', \$dsn, \$matches);
                if (count(\$matches) === 6) {
                    try {
                        new PDO('mysql:host='.\$matches[3].';port='.\$matches[4], \$matches[1], \$matches[2]);
                        exit(0);
                    } catch (PDOException \$e) {
                        exit(1);
                    }
                }
                exit(1);
            " 2>/dev/null; then
                echo "✅ MariaDB accessible et authentification réussie"
                return 0
            else
                echo "   [$attempt/$max_attempts] MariaDB répond mais authentification échouée..."
            fi
        else
            echo "   [$attempt/$max_attempts] MariaDB pas encore accessible..."
        fi

        sleep $wait_time
        attempt=$((attempt + 1))
    done

    echo "❌ Erreur: Impossible de se connecter à MariaDB après ${max_attempts} tentatives"
    echo "   Vérifiez que le service MariaDB est démarré et que les credentials sont corrects"
    return 1
}

# Attendre que MariaDB soit prêt avant de démarrer le worker
# Évite les erreurs de race condition au démarrage des conteneurs
if ! wait_for_mariadb; then
    exit 1
fi

# Démarrage du worker Messenger
echo "🎯 Lancement du Messenger Worker"

# Décider d'utiliser gosu ou non selon la variable USE_GOSU
# - USE_GOSU=1 (preprod/prod): exécute avec gosu www-data pour la sécurité
# - USE_GOSU=0 ou absent (dev local): exécute directement (volumes bind-mount)
if [ "${USE_GOSU:-0}" = "1" ]; then
    echo "🔒 Exécution isolée avec gosu www-data (preprod/prod)"
    exec gosu www-data "$@"
else
    echo "🔓 Exécution directe avec volume partagé (dev local)"
    exec "$@"
fi
