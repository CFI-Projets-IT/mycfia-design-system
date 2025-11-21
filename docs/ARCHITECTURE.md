# 🏗️ Architecture de la stack

Documentation technique de l'architecture Docker Symfony + FrankenPHP.

## 📊 Vue d'ensemble de l'architecture

### Diagramme de l'architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                          HOST SYSTEM                                │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │                       Docker Network                            │ │
│  │                        app_network                              │ │
│  │                                                                 │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌────────┐  │ │
│  │  │ FrankenPHP  │  │   MariaDB   │  │   Mercure   │  │ Chroma │  │ │
│  │  │   :82       │  │   :3306     │  │   :3000     │  │ :8000  │  │ │
│  │  │ ┌─────────┐ │  │             │  │             │  │  AI    │  │ │
│  │  │ │ Caddy   │ │  │             │  │             │  │ Vector │  │ │
│  │  │ │ PHP 8.3 │ │  │             │  │             │  │  DB    │  │ │
│  │  │ │ Symfony │ │  │             │  │             │  │        │  │ │
│  │  │ └─────────┘ │  │             │  │             │  │        │  │ │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └────────┘  │ │
│  │         │                 │                 │            │      │ │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │ │
│  │  │ phpMyAdmin  │  │   MailHog   │  │      Messenger Worker   │  │ │
│  │  │   :80       │  │   :8025     │  │      (Async Queue)      │  │ │
│  │  │             │  │             │  │                         │  │ │
│  │  └─────────────┘  └─────────────┘  └─────────────────────────┘  │ │
│  │         │                 │                       │             │ │
│  │  ┌─────────────────────────────────────────────────────────────┐  │ │
│  │  │                        Volumes                              │  │ │
│  │  │  - mariadb_data  (Base de données)                          │  │ │
│  │  │  - mercure_data  (Événements temps réel)                    │  │ │
│  │  │  - chroma_data   (Embeddings IA)                            │  │ │
│  │  └─────────────────────────────────────────────────────────────┘  │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│                                 │                                    │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │                         PORT MAPPING                            │ │
│  │  8080 → frankenphp:82     (Application Symfony)                │ │
│  │  8200 → frankenphp:8082   (phpMyAdmin)                         │ │
│  │  8300 → frankenphp:8027   (MailHog)                            │ │
│  │  3002 → mercure:3000      (Mercure Hub)                        │ │
│  │  8000 → chroma:8000       (ChromaDB - Dev uniquement)          │ │
│  └─────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

## 🐳 Services Docker

### FrankenPHP (Service principal)

#### Caractéristiques
- **Image base** : `dunglas/frankenphp:1.9-php8.3-bookworm`
- **Rôle** : Serveur web + Runtime PHP
- **Port interne** : 82 (application), 8082 (phpMyAdmin), 8027 (MailHog)
- **Architecture** : Multi-stage (development/production)

#### Composants intégrés
```
FrankenPHP Container
├── 🦘 FrankenPHP Core (Go)
│   ├── Caddy Server (Web Server)
│   ├── PHP 8.3 Runtime
│   └── HTTP/2 + HTTP/3 Support
├── 🐘 PHP Extensions
│   ├── pdo_mysql (Base de données)
│   ├── gd (Images)
│   ├── intl (Internationalisation)
│   ├── zip (Archives)
│   ├── opcache (Cache PHP)
│   └── bcmath (Calculs précis)
├── 🛠️ Outils développement (dev seulement)
│   ├── Composer (Gestionnaire deps PHP)
│   ├── Symfony CLI
│   └── Node.js + npm
└── ⚙️ Configuration
    ├── Caddyfile (Web server config)
    ├── php.ini (PHP config)
    └── entrypoint.sh (Startup script)
```

#### Build multi-stage
```dockerfile
# Stage commun
FROM dunglas/frankenphp:1.9-php8.3-bookworm AS base
# + PHP extensions + Composer + Symfony CLI

# Stage développement
FROM base AS development
# + Node.js + npm + outils dev + permissions flexibles

# Stage production
FROM base AS production
# + Configuration sécurisée + utilisateur non-privilégié + pas Node.js
```

### MariaDB (Base de données)

#### Spécifications
- **Image** : `mariadb:11`
- **Port** : 3306
- **Volumes** : `mariadb_data` (persistant)
- **Healthcheck** : Ping via mariadb-admin

#### Configuration
```yaml
environment:
  MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
  MYSQL_DATABASE: ${DB_NAME}
  MYSQL_USER: ${DB_USER}
  MYSQL_PASSWORD: ${DB_PASSWORD}

healthcheck:
  test: ["CMD", "mariadb-admin", "ping", "-h", "localhost"]
  timeout: 5s
  retries: 10
  start_period: 30s
```

### Mercure Hub (Temps réel)

#### Caractéristiques
- **Image** : `dunglas/mercure:v0.16`
- **Protocol** : Server-Sent Events (SSE)
- **Port** : 3000 (interne), 3002 (externe)
- **Authentification** : JWT

#### Configuration
```yaml
environment:
  SERVER_NAME: ':3000'
  MERCURE_PUBLISHER_JWT_KEY: ${MERCURE_JWT_SECRET}
  MERCURE_SUBSCRIBER_JWT_KEY: ${MERCURE_JWT_SECRET}
  MERCURE_EXTRA_DIRECTIVES: "anonymous\ndemo"
```

### ChromaDB (Base de données vectorielle)

#### Caractéristiques
- **Image** : `chromadb/chroma:latest`
- **Port** : 8000 (interne et externe en dev)
- **Usage** : Stockage des embeddings pour le Gorillias Marketing AI Bundle
- **Volumes** : `chroma_data` (persistant)

#### Configuration
```yaml
environment:
  IS_PERSISTENT: TRUE
  ANONYMIZED_TELEMETRY: FALSE

volumes:
  - chroma_data:/chroma/chroma

# Dev : port exposé pour accès direct
# Preprod/Prod : communication interne uniquement (pas d'exposition)
```

#### Healthcheck
```yaml
# ⚠️ Healthcheck retiré : curl n'est pas disponible dans l'image chromadb/chroma
# Vérification manuelle possible via : http://localhost:8000/api/v2/heartbeat
```

### Messenger Worker (Queue asynchrone)

#### Caractéristiques
- **Image** : FrankenPHP (même que l'application principale)
- **Rôle** : Traitement asynchrone des messages (Marketing AI, emails, etc.)
- **Transport** : Doctrine (base de données)
- **Configuration** : `messenger:consume async --time-limit=3600`

#### Startup sequence
```bash
# entrypoint-worker.sh sequence:
1. Configuration UID/GID (identique à FrankenPHP)
2. Application des permissions
3. ⏳ Attente MariaDB (wait_for_mariadb avec 3 niveaux)
   - Niveau 1 : Résolution DNS (getent hosts mariadb)
   - Niveau 2 : Connexion TCP (bash /dev/tcp/mariadb/3306)
   - Niveau 3 : Authentification PDO (credentials Symfony)
4. Démarrage du worker Messenger
```

#### Healthcheck
```yaml
# ⚠️ Healthcheck désactivé : service CLI sans port HTTP
# Le worker FrankenPHP hérite du healthcheck port 2019 (Caddy metrics)
# Inapproprié pour un worker CLI → healthcheck: disable: true
healthcheck:
  disable: true
```

#### Gestion des erreurs au démarrage
- **Race condition résolu** : Le worker attend que MariaDB soit complètement accessible avant de démarrer
- **Validation multi-niveau** : DNS → TCP → Authentification (évite les "getaddrinfo failed")
- **Retry stratégie** : 30 tentatives × 2s = 60s timeout maximum
- **Logging détaillé** : Progression visible dans les logs pour diagnostic

### Services de développement

#### phpMyAdmin
- **Image** : `phpmyadmin/phpmyadmin:latest`
- **Accès** : via reverse proxy FrankenPHP
- **Authentification** : Basic auth + connexion auto MariaDB

#### MailHog
- **Image** : `mailhog/mailhog:latest`
- **Fonction** : Capture d'emails de test
- **Interface** : Web UI pour consulter emails
- **Configuration** : Stockage en mémoire

## 🔧 Configuration réseau

### Network Bridge

#### Configuration Docker
```yaml
networks:
  app_network:
    driver: bridge
```

#### Communication inter-services
```
Services Communication Map:
├── frankenphp → mariadb:3306     (Base de données)
├── frankenphp → mercure:3000     (Reverse proxy Mercure)
├── frankenphp → chroma:8000      (Embeddings IA Marketing)
├── messenger_worker → mariadb:3306 (Queue Doctrine)
├── messenger_worker → chroma:8000  (Traitement IA async)
├── phpmyadmin → mariadb:3306     (Administration DB)
├── mailhog → isolated            (Service indépendant)
└── External → frankenphp:82,8082,8027 (Points d'entrée HTTP)
```

### Gestion des ports

#### Auto-détection intelligente
```bash
# Algorithme de détection dans deploy.sh
find_free_port() {
    local start_port=$1
    local max_port=${2:-65535}

    # Vérifications :
    # 1. Port système (ss -tuln)
    # 2. Conteneurs Docker existants
    # 3. Plages recommandées sécurité
}

# Plages allouées :
# 8080-8199 : Applications web
# 8200-8299 : Outils développement
# 8300-8399 : Services de test
# 3000-3099 : Services temps réel
```

#### Mapping des ports
```yaml
# Configuration automatique dans docker-compose.override.yml
ports:
  - "${HTTP_PORT:-82}:82"           # App principale
  - "${PHPMYADMIN_PORT:-8082}:8082" # phpMyAdmin
  - "${MAILHOG_PORT:-8027}:8027"    # MailHog
  - "${MERCURE_PORT:-3001}:3000"    # Mercure
```

## 📁 Structure des volumes

### Volumes persistants

#### MariaDB
```yaml
mariadb_data:
  name: ${PROJECT_NAME}_mariadb_data
  # Stockage : /var/lib/mysql
  # Persistance : Survit aux redémarrages
```

#### Mercure
```yaml
mercure_data:
  name: ${PROJECT_NAME}_mercure_data
  # Stockage : /data (événements)

mercure_config:
  name: ${PROJECT_NAME}_mercure_config
  # Stockage : /config (configuration)
```

#### ChromaDB
```yaml
chroma_data:
  name: ${PROJECT_NAME}_chroma_data
  # Stockage : /chroma/chroma (embeddings vectoriels)
  # Persistance : Survit aux redémarrages
  # Usage : Gorillias Marketing AI Bundle
```

### Bind mounts (développement)

#### Code source Symfony
```yaml
volumes:
  - ./app:/var/www/html
  # Édition en temps réel
  # Permissions : gérées par entrypoint.sh
```

#### Configuration Caddy
```yaml
volumes:
  - ./docker/Caddyfile.dev:/etc/caddy/Caddyfile
  # Configuration web server
  # Rechargement : automatique
```

## 🔒 Gestion des permissions

### Système de permissions intelligent

#### Détection automatique UID/GID
```bash
# Dans entrypoint.sh
TARGET_UID=${DOCKER_UID:-$(stat -c '%u' /var/www/html 2>/dev/null || echo 1000)}
TARGET_GID=${DOCKER_GID:-$(stat -c '%g' /var/www/html 2>/dev/null || echo 1000)}

# Ajustement dynamique
usermod -u $TARGET_UID www-data 2>/dev/null
groupmod -g $TARGET_GID www-data 2>/dev/null
```

#### Permissions par environnement

##### Développement
```bash
# Permissions permissives pour l'édition
find /var/www/html -type d -exec chmod 775 {} \;
find /var/www/html -type f -exec chmod 664 {} \;
chmod -R 775 /var/www/html/var # Cache Symfony
```

##### Production
```bash
# Permissions restrictives
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;
chmod -R 775 /var/www/html/var # Minimum pour Symfony
```

### Sécurité multi-environnement

#### Variables sensibles
```bash
# Développement : variables en clair pour debug
APP_ENV=dev
APP_DEBUG=1

# Production : variables chiffrées et sécurisées
APP_ENV=prod
APP_DEBUG=0
# Secrets : gérés via .env.prod.local (gitignored)
```

## 🔄 Cycle de vie et démarrage

### Ordre de démarrage

#### Phase 1 : Infrastructure (Base de données)
```yaml
mariadb:
  # Démarre en premier
  healthcheck:
    test: ["CMD", "mariadb-admin", "ping"]
    timeout: 5s
    retries: 10
    start_period: 30s
```

#### Phase 2 : Services indépendants
```yaml
# Démarrage parallèle :
chroma:        # Base vectorielle (pas de dépendances)
mercure:       # Hub temps réel (pas de dépendances)
phpmyadmin:    # Interface DB (dépend de mariadb)
mailhog:       # Capture emails (service isolé)
```

#### Phase 3 : Application principale
```yaml
frankenphp:
  depends_on:
    mariadb:
      condition: service_healthy  # Attendre MariaDB ready
  # entrypoint.sh sequence:
  1. Détection environnement (APP_ENV)
  2. Configuration UID/GID
  3. Application permissions
  4. Vérification santé (PHP, Composer)
  5. Démarrage FrankenPHP
```

#### Phase 4 : Worker asynchrone
```yaml
messenger_worker:
  depends_on:
    mariadb:
      condition: service_healthy  # Attendre MariaDB
    frankenphp:
      condition: service_started  # Attendre application
  # entrypoint-worker.sh sequence:
  1. Configuration UID/GID
  2. wait_for_mariadb() avec validation 3 niveaux
  3. Démarrage messenger:consume
```

### Healthchecks

#### MariaDB
```yaml
healthcheck:
  test: ["CMD", "mariadb-admin", "ping", "-h", "localhost", "-u", "user"]
  timeout: 5s
  retries: 10
  start_period: 30s
```

#### FrankenPHP (implicite)
```bash
# Vérification dans entrypoint.sh
if ! command -v php >/dev/null 2>&1; then
    echo "❌ Erreur: PHP n'est pas disponible"
    exit 1
fi
```

## 🚀 Optimisations d'architecture

### Performance

#### FrankenPHP avantages
- **HTTP/2 natif** : Multiplexage des requêtes
- **HTTP/3 (QUIC)** : Latence réduite
- **Worker mode** : Persistance des objets PHP
- **Compilation Go** : Performance native

#### Optimisations Docker
```dockerfile
# Multi-stage builds : images légères
# Production : 256MB vs Développement : 512MB

# Cache layers optimisé
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader
```

### Scalabilité

#### Horizontal scaling
```yaml
# Préparation pour orchestration
deploy:
  replicas: 3
  resources:
    limits:
      cpus: '0.50'
      memory: 512M
```

#### Load balancing préparation
```caddyfile
# Configuration Caddy multi-instance
upstream backend {
  server frankenphp1:82
  server frankenphp2:82
  server frankenphp3:82
}
```

## 🔧 Points d'extension

### Ajout de services

#### Redis (cache)
```yaml
redis:
  image: redis:alpine
  networks:
    - app_network
  volumes:
    - redis_data:/data
```

#### Elasticsearch (recherche)
```yaml
elasticsearch:
  image: elasticsearch:8.8.0
  environment:
    - discovery.type=single-node
  networks:
    - app_network
```

### Personnalisation Caddy

#### Modules additionnels
```dockerfile
# Rebuild FrankenPHP avec modules custom
FROM dunglas/frankenphp:1.9-php8.3-bookworm AS custom
RUN caddy add-package github.com/caddyserver/auth-portal
```

#### Configuration avancée
```caddyfile
# Exemple : authentification OAuth
{$DOMAIN} {
    auth_portal {
        backends {
            google_oauth2_backend {
                method oauth2
                realm google
            }
        }
    }

    php_server
}
```

## 📊 Monitoring et observabilité

### Métriques intégrées

#### Docker stats
```bash
# Monitoring ressources en temps réel
docker stats
# CPU, Memory, Network I/O, Block I/O
```

#### Logs structurés
```json
{
  "level": "info",
  "ts": "2024-01-01T10:00:00Z",
  "logger": "http.log.access",
  "msg": "handled request",
  "request": {
    "remote_addr": "172.18.0.1",
    "method": "GET",
    "uri": "/api/users"
  }
}
```

### Points de monitoring

#### Healthcheck endpoints
```php
// src/Controller/HealthController.php
#[Route('/health', methods: ['GET'])]
public function health(): JsonResponse
{
    return new JsonResponse([
        'status' => 'ok',
        'timestamp' => time(),
        'services' => [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'mercure' => $this->checkMercure()
        ]
    ]);
}
```

Cette architecture offre une base solide, évolutive et sécurisée pour le développement d'applications Symfony modernes.