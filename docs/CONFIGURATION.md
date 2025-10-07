# ⚙️ Guide de configuration

Configuration détaillée de la stack Docker Symfony + FrankenPHP pour tous les environnements.

## 📁 Fichiers de configuration

### Structure de configuration
```
GoldMind/
├── 📋 .env.local.example          # Template de configuration
├── 📋 .env.local                  # Configuration développement (à créer)
├── 📋 .env.prod.local             # Configuration production (à créer)
├── 📁 docker/
│   ├── ⚙️ Caddyfile.dev          # Configuration Caddy développement
│   ├── ⚙️ Caddyfile.prod         # Configuration Caddy production
│   ├── 🐘 php.ini.dev            # Configuration PHP développement
│   ├── 🐘 php.ini.prod           # Configuration PHP production
│   └── 🔧 entrypoint.sh          # Script d'entrée intelligent
├── 📝 docker-compose.yml          # Configuration Docker de base
├── 📝 docker-compose.override.yml # Overrides développement
└── 📝 docker-compose.prod.yml    # Configuration production
```

## 🌍 Variables d'environnement

### Configuration Symfony

#### Variables principales
```env
# Environnement Symfony (dev, prod, test)
APP_ENV=dev

# Mode debug (1 pour actif, 0 pour inactif)
APP_DEBUG=1

# Clé secrète Symfony (32+ caractères aléatoires)
APP_SECRET=your-32-char-random-secret-key-here

# URL de la base de données (auto-générée en développement)
DATABASE_URL=mysql://user:password@mariadb:3306/database_name?serverVersion=mariadb-11&charset=utf8mb4
```

#### Génération de l'APP_SECRET
```bash
# Méthode 1 : OpenSSL
openssl rand -hex 32

# Méthode 2 : UUID (macOS)
uuidgen | tr -d '-' | tr '[:upper:]' '[:lower:]'

# Méthode 3 : Date + hash
date +%s | sha256sum | head -c 32
```

### Configuration Docker

#### Paramètres de projet
```env
# Nom du projet (utilisé pour nommer les conteneurs et volumes)
PROJECT_NAME=your-project-name

# Cible de build Docker (development, production)
BUILD_TARGET=development

# Version des images Docker
MARIADB_VERSION=11
MERCURE_VERSION=v0.16
PHPMYADMIN_VERSION=latest
MAILHOG_VERSION=latest
```

#### Gestion des droits (Linux/macOS)
```env
# UID/GID automatiques (détectés par le script)
# DOCKER_UID=1000  # Auto-détecté sur Linux/macOS
# DOCKER_GID=1000  # Auto-détecté sur Linux/macOS

# Force l'UID/GID spécifique (optionnel)
DOCKER_UID=1000
DOCKER_GID=1000
```

### Configuration des ports

#### Ports par défaut
```env
# Application principale (FrankenPHP)
HTTP_PORT=8080

# Interface phpMyAdmin
PHPMYADMIN_PORT=8200

# Interface MailHog
MAILHOG_PORT=8300

# Hub Mercure
MERCURE_PORT=3002
```

#### Auto-détection des ports
```bash
# Le script peut détecter automatiquement les ports libres
./deploy.sh dev --auto-ports

# Configuration automatique dans .env.local :
# HTTP_PORT=8080        # Premier port libre dans la plage 8080-8199
# PHPMYADMIN_PORT=8200  # Premier port libre dans la plage 8200-8299
# MAILHOG_PORT=8300     # Premier port libre dans la plage 8300-8399
# MERCURE_PORT=3002     # Premier port libre dans la plage 3000-3099
```

### Configuration de base de données

#### Variables MariaDB
```env
# Mot de passe root MariaDB
DB_ROOT_PASSWORD=your-secure-root-password

# Base de données de l'application
DB_NAME=your_database_name

# Utilisateur de l'application
DB_USER=your_app_user

# Mot de passe de l'utilisateur
DB_PASSWORD=your-secure-app-password

# Version MariaDB
MARIADB_VERSION=11
```

#### Génération de mots de passe sécurisés
```bash
# Mot de passe fort (32 caractères)
openssl rand -base64 32

# Mot de passe avec caractères spéciaux
pwgen -s -y 32 1

# Mot de passe simple mais sécurisé
date +%s | sha256sum | base64 | head -c 24
```

### Configuration Mercure

#### Variables Mercure Hub
```env
# Clé JWT pour Mercure (doit être identique entre publisher et subscriber)
MERCURE_JWT_SECRET=your-mercure-jwt-secret-key

# Version de l'image Mercure
MERCURE_VERSION=v0.16

# Directives supplémentaires pour Mercure
MERCURE_EXTRA_DIRECTIVES="anonymous\ndemo"
```

#### Configuration CORS (développement)
```env
# Origines autorisées pour CORS (développement)
MERCURE_CORS_ORIGINS=http://localhost:8080,http://127.0.0.1:8080
```

## 🔧 Configuration par environnement

### Développement (.env.local)

#### Configuration complète développement
```env
# === SYMFONY ===
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=dev-secret-key-change-in-production

# === BASE DE DONNÉES ===
DB_ROOT_PASSWORD=root
DB_NAME=symfony_dev
DB_USER=dev_user
DB_PASSWORD=dev_password
MARIADB_VERSION=11

# === DOCKER ===
PROJECT_NAME=symfony-dev
BUILD_TARGET=development

# === PORTS ===
HTTP_PORT=8080
PHPMYADMIN_PORT=8200
MAILHOG_PORT=8300
MERCURE_PORT=3002

# === MERCURE ===
MERCURE_JWT_SECRET=dev-mercure-secret-key
MERCURE_VERSION=v0.16

# === DÉVELOPPEMENT ===
PHPMYADMIN_VERSION=latest
MAILHOG_VERSION=latest

# === GESTION DES DROITS (auto-détectés) ===
# DOCKER_UID=1000
# DOCKER_GID=1000
```

### Production (.env.prod.local)

#### Configuration sécurisée production
```env
# === SYMFONY ===
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=ultra-secure-production-secret-64-chars-minimum-length

# === BASE DE DONNÉES ===
DB_ROOT_PASSWORD=ultra-secure-root-password-64-chars
DB_NAME=symfony_prod
DB_USER=symfony_app
DB_PASSWORD=ultra-secure-app-password-64-chars
MARIADB_VERSION=11

# === DOCKER ===
PROJECT_NAME=symfony-prod
BUILD_TARGET=production

# === DOMAINES ===
SERVER_NAME=yourdomain.com,www.yourdomain.com
MERCURE_DOMAIN=mercure.yourdomain.com

# === MERCURE ===
MERCURE_JWT_SECRET=ultra-secure-mercure-jwt-secret-64-chars
MERCURE_VERSION=v0.16

# === SÉCURITÉ ===
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com

# === SSL/TLS ===
ACME_EMAIL=admin@yourdomain.com
```

### Test (.env.test.local)

#### Configuration tests automatisés
```env
# === SYMFONY ===
APP_ENV=test
APP_DEBUG=0
APP_SECRET=test-secret-key

# === BASE DE DONNÉES TEST ===
DB_ROOT_PASSWORD=test_root
DB_NAME=symfony_test
DB_USER=test_user
DB_PASSWORD=test_password
MARIADB_VERSION=11

# === DOCKER ===
PROJECT_NAME=symfony-test
BUILD_TARGET=development

# === PORTS TESTS ===
HTTP_PORT=9080
PHPMYADMIN_PORT=9200
MAILHOG_PORT=9300
MERCURE_PORT=4002

# === MERCURE TEST ===
MERCURE_JWT_SECRET=test-mercure-secret
```

## 🐳 Configuration Docker

### Multi-stage Dockerfile

#### Stage development
```dockerfile
FROM dunglas/frankenphp:1.9-php8.3-bookworm AS development

# Outils de développement
RUN apt-get update && apt-get install -y \
    git zip unzip curl procps net-tools lsof \
    && install-php-extensions pdo_mysql gd intl zip opcache bcmath

# Node.js pour assets Symfony
RUN curl -fsSL https://deb.nodesource.com/setup_current.x | bash - \
    && apt-get install -y nodejs

# Configuration PHP développement (display_errors=On)
COPY ./docker/php.ini.dev /usr/local/etc/php/php.ini
```

#### Stage production
```dockerfile
FROM base AS production

# Configuration PHP production (display_errors=Off)
COPY ./docker/php.ini.prod /usr/local/etc/php/php.ini

# Utilisateur non-privilégié
USER www-data

# Pas de Node.js en production
```

### Configuration Caddy

#### Développement (Caddyfile.dev)
```caddyfile
{
    # Configuration globale développement
    frankenphp
    admin off
    auto_https off
    local_certs

    log {
        output stdout
        level DEBUG
    }
}

# Application Symfony
http://:82 {
    root * /var/www/html/public
    encode gzip zstd
    php_server
    file_server

    # Authentification développement
    basic_auth / {
        krystdev $2y$10$3F3s/vHKZfXRHOsJ2qXhNOcmOQlRZMKz5PQNW8zVo8Iy9KfU3jLUe
    }

    # Headers anti-cache développement
    header {
        Cache-Control "no-cache, no-store, must-revalidate"
        Pragma "no-cache"
        Expires "0"
    }
}
```

#### Production (Caddyfile.prod)
```caddyfile
{
    # Configuration globale production
    frankenphp
    admin off

    # Email pour Let's Encrypt
    email {$ACME_EMAIL}
}

# Application principale avec HTTPS automatique
{$SERVER_NAME} {
    root * /var/www/html/public
    encode gzip zstd
    php_server
    file_server

    # Headers de sécurité production
    header {
        Strict-Transport-Security "max-age=31536000; includeSubDomains"
        X-Content-Type-Options "nosniff"
        X-Frame-Options "DENY"
        X-XSS-Protection "1; mode=block"
        Referrer-Policy "strict-origin-when-cross-origin"
    }

    # Cache pour assets statiques
    @static path *.css *.js *.png *.jpg *.jpeg *.gif *.ico *.svg *.woff *.woff2
    handle @static {
        header Cache-Control "public, max-age=31536000, immutable"
        file_server
    }
}

# Hub Mercure avec domaine dédié
{$MERCURE_DOMAIN} {
    reverse_proxy mercure:3000

    # CORS production
    @cors header Origin {$CORS_ALLOWED_ORIGINS}
    handle @cors {
        header Access-Control-Allow-Origin "{http.request.header.Origin}"
        header Access-Control-Allow-Credentials "true"
    }
}
```

### Configuration PHP

#### Développement (php.ini.dev)
```ini
; Configuration PHP pour développement
[PHP]
memory_limit = 512M
max_execution_time = 60
max_input_time = 60

; Debug et erreurs
display_errors = On
display_startup_errors = On
log_errors = On
error_reporting = E_ALL

; Développement
opcache.enable = 0
opcache.validate_timestamps = 1

; Upload
upload_max_filesize = 50M
post_max_size = 50M

; Timezone
date.timezone = Europe/Paris
```

#### Production (php.ini.prod)
```ini
; Configuration PHP pour production
[PHP]
memory_limit = 256M
max_execution_time = 30
max_input_time = 30

; Sécurité production
display_errors = Off
display_startup_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

; Optimisations production
opcache.enable = 1
opcache.validate_timestamps = 0
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000

; Upload réduit
upload_max_filesize = 10M
post_max_size = 10M

; Timezone
date.timezone = Europe/Paris

; Sécurité
expose_php = Off
```

## 🔒 Configuration de sécurité

### Authentification développement

#### Utilisateur par défaut
- **Utilisateur** : `krystdev`
- **Mot de passe** : `dev123`
- **Hash bcrypt** : `$2y$10$3F3s/vHKZfXRHOsJ2qXhNOcmOQlRZMKz5PQNW8zVo8Iy9KfU3jLUe`

#### Générer un nouveau hash
```bash
# Générer un hash bcrypt
htpasswd -nbB username password

# Ou avec openssl
echo -n "password" | openssl passwd -apr1 -stdin

# Ou avec Python
python3 -c "import crypt; print(crypt.crypt('password', crypt.mksalt(crypt.METHOD_BLOWFISH)))"
```

### Variables sensibles

#### Secrets à ne jamais committer
```env
# ❌ Ne jamais committer ces valeurs
APP_SECRET=actual-secret-value
DB_ROOT_PASSWORD=actual-password
DB_PASSWORD=actual-password
MERCURE_JWT_SECRET=actual-jwt-secret

# ✅ Utiliser des placeholders dans .env.example
APP_SECRET=your-secret-here
DB_ROOT_PASSWORD=your-password-here
```

#### Fichiers à ignorer (.gitignore)
```gitignore
# Variables d'environnement locales
.env.local
.env.prod.local
.env.test.local

# Données persistantes
/var/lib/docker/volumes/
/data/

# Logs
/logs/
*.log
```

## 🔧 Configuration avancée

### Personnalisation des domaines

#### Hosts locaux (développement)
```bash
# Éditer /etc/hosts (Linux/macOS)
sudo nano /etc/hosts

# Ajouter vos domaines personnalisés
127.0.0.1 myproject.local
127.0.0.1 api.myproject.local
127.0.0.1 admin.myproject.local
```

#### Configuration Caddy domaines
```caddyfile
# Modifier Caddyfile.dev pour domaines personnalisés
myproject.local {
    root * /var/www/html/public
    php_server
    file_server
}

api.myproject.local {
    root * /var/www/html/public
    php_server
    file_server

    # Headers API
    header Access-Control-Allow-Origin "*"
}
```

### Optimisation des performances

#### Cache et volumes
```yaml
# docker-compose.override.yml
services:
  frankenphp:
    volumes:
      # Linux : bind mount classique
      - ./app:/var/www/html

      # macOS : optimisation cached
      - ./app:/var/www/html:cached

      # Windows : optimisation delegated
      - ./app:/var/www/html:delegated
```

#### Configuration OPcache
```ini
; php.ini.prod - Optimisation OPcache
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=0
opcache.fast_shutdown=1
```

### Monitoring et logs

#### Configuration des logs
```yaml
# docker-compose.yml
services:
  frankenphp:
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```

#### Logs Caddy personnalisés
```caddyfile
# Logs structurés JSON
log {
    output file /var/log/caddy/access.log {
        roll_size 10MB
        roll_keep 3
    }
    format json {
        time_format "iso8601"
        level_format "upper"
    }
}
```

## ✅ Validation de la configuration

### Scripts de test
```bash
# Test de la configuration
./deploy.sh dev --auto-ports

# Validation des services
docker compose ps
docker compose logs --tail=20

# Test des endpoints
curl -u krystdev:dev123 http://localhost:8080/health
curl -u krystdev:dev123 http://localhost:8200
curl -u krystdev:dev123 http://localhost:8300
```

### Checklist de configuration

- [ ] Variables d'environnement définies dans `.env.local`
- [ ] Secrets générés avec des valeurs sécurisées
- [ ] Ports configurés sans conflits
- [ ] Permissions UID/GID correctes (Linux/macOS)
- [ ] Services accessibles via les URLs configurées
- [ ] Base de données connectée et accessible
- [ ] Logs configurés et fonctionnels