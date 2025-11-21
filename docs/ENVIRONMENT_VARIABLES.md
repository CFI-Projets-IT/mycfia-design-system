# 📝 Variables d'environnement

Référence complète de toutes les variables d'environnement supportées par la stack Docker Symfony + FrankenPHP.

## 📋 Vue d'ensemble

Les variables d'environnement contrôlent tous les aspects de la stack : configuration Symfony, paramètres Docker, ports, base de données, sécurité et services auxiliaires.

### Hiérarchie des fichiers
```
Priorité décroissante:
1. Variables d'environnement système (export VAR=value)
2. .env.local (développement)
3. .env.prod.local (production)
4. .env.test.local (test)
5. .env (valeurs par défaut - non éditable)
6. docker-compose.yml (fallback)
```

## 🌍 Variables Symfony

### Configuration de base

#### APP_ENV
- **Description** : Environnement d'exécution Symfony
- **Valeurs** : `dev`, `prod`, `test`
- **Défaut** : `dev`
- **Exemple** :
```env
APP_ENV=dev     # Développement avec debug
APP_ENV=prod    # Production optimisée
APP_ENV=test    # Tests automatisés
```

#### APP_DEBUG
- **Description** : Mode debug Symfony
- **Valeurs** : `0` (inactif), `1` (actif)
- **Défaut** : `1` (dev), `0` (prod)
- **Exemple** :
```env
APP_DEBUG=1     # Debug actif : erreurs détaillées, profiler
APP_DEBUG=0     # Debug inactif : erreurs masquées, performances
```

#### APP_SECRET
- **Description** : Clé secrète Symfony pour chiffrement/signatures
- **Format** : Chaîne aléatoire 32+ caractères
- **Sécurité** : ⚠️ CRITIQUE - Unique par environnement
- **Génération** :
```bash
# Générer un secret sécurisé
openssl rand -hex 32
# ou
uuidgen | tr -d '-' | tr '[:upper:]' '[:lower:]'
```
- **Exemple** :
```env
# Développement
APP_SECRET=dev-secret-change-me-in-production

# Production (généré aléatoirement)
APP_SECRET=a1b2c3d4e5f6789012345678901234567890abcdef123456789012345678901234
```

### Base de données

#### DATABASE_URL
- **Description** : URL de connexion Doctrine
- **Format** : `mysql://user:password@host:port/database?options`
- **Auto-générée** : Oui, à partir des variables DB_*
- **Exemple** :
```env
# Auto-générée en développement
DATABASE_URL=mysql://app_user:app_password@mariadb:3306/app_db?serverVersion=mariadb-11&charset=utf8mb4

# Production avec options avancées
DATABASE_URL=mysql://prod_user:secure_pass@db.example.com:3306/prod_db?serverVersion=mariadb-11&charset=utf8mb4&sslmode=require
```

## 🐳 Variables Docker

### Configuration du projet

#### PROJECT_NAME
- **Description** : Nom du projet Docker (conteneurs, volumes, réseau)
- **Format** : Alphanumerique et tirets uniquement
- **Impact** : Nomme tous les conteneurs et volumes
- **Exemple** :
```env
PROJECT_NAME=symfony-app
# Génère :
# symfony-app_frankenphp
# symfony-app_mariadb
# symfony-app_mariadb_data (volume)
```

#### BUILD_TARGET
- **Description** : Cible du build multi-stage Docker
- **Valeurs** : `development`, `production`
- **Défaut** : `development`
- **Exemple** :
```env
BUILD_TARGET=development  # Image avec Node.js, outils dev
BUILD_TARGET=production   # Image légère, optimisée, sécurisée
```

### Gestion des permissions

#### DOCKER_UID
- **Description** : UID pour l'utilisateur www-data dans le conteneur
- **Auto-détection** : Oui (Linux/macOS)
- **Défaut** : `1000`
- **Usage** : Évite les problèmes de permissions sur bind mounts
- **Exemple** :
```env
# Auto-détecté
DOCKER_UID=1000

# Forcé manuellement (optionnel)
DOCKER_UID=1001
```

#### DOCKER_GID
- **Description** : GID pour le groupe www-data dans le conteneur
- **Auto-détection** : Oui (Linux/macOS)
- **Défaut** : `1000`
- **Usage** : Évite les problèmes de permissions sur bind mounts
- **Exemple** :
```env
# Auto-détecté
DOCKER_GID=1000

# Forcé manuellement (optionnel)
DOCKER_GID=1001
```

## 🔌 Variables de ports

### Ports des services

#### HTTP_PORT
- **Description** : Port externe pour l'application Symfony
- **Auto-détection** : Oui (plage 8080-8199)
- **Défaut** : `8080`
- **Exemple** :
```env
HTTP_PORT=8080          # Port par défaut
HTTP_PORT=9080          # Port alternatif
```

#### PHPMYADMIN_PORT
- **Description** : Port externe pour phpMyAdmin
- **Auto-détection** : Oui (plage 8200-8299)
- **Défaut** : `8200`
- **Exemple** :
```env
PHPMYADMIN_PORT=8200    # Interface admin base de données
```

#### MAILHOG_PORT
- **Description** : Port externe pour MailHog
- **Auto-détection** : Oui (plage 8300-8399)
- **Défaut** : `8300`
- **Exemple** :
```env
MAILHOG_PORT=8300       # Interface capture emails
```

#### MERCURE_PORT
- **Description** : Port externe pour Mercure Hub
- **Auto-détection** : Oui (plage 3000-3099)
- **Défaut** : `3002`
- **Exemple** :
```env
MERCURE_PORT=3002       # Hub temps réel
```

### Configuration auto-ports

#### Auto-détection activée
```bash
# Le script détecte automatiquement les ports libres
./deploy.sh dev --auto-ports

# Met à jour .env avec les ports trouvés + synchronisation automatique
HTTP_PORT=8080
PHPMYADMIN_PORT=8201    # 8200 occupé, port suivant utilisé
MAILHOG_PORT=8300
MERCURE_PORT=3002
MERCURE_PUBLIC_URL=http://localhost:8080/.well-known/mercure  # ✅ Synchronisé automatiquement
```

**Notes importantes** :
- Les modifications sont appliquées au fichier `.env` (lu par Docker Compose)
- `MERCURE_PUBLIC_URL` est **automatiquement synchronisé** avec `HTTP_PORT` pour éviter les erreurs CORS
- Cette synchronisation garantit que le chat temps réel fonctionne toujours correctement

## 🗄️ Variables de base de données

### Configuration MariaDB

#### DB_ROOT_PASSWORD
- **Description** : Mot de passe root MariaDB
- **Sécurité** : ⚠️ CRITIQUE - Doit être fort en production
- **Défaut** : `root` (dev seulement)
- **Exemple** :
```env
# Développement
DB_ROOT_PASSWORD=root

# Production
DB_ROOT_PASSWORD=ultra-secure-root-password-64-chars-minimum
```

#### DB_NAME
- **Description** : Nom de la base de données de l'application
- **Format** : Alphanumerique et underscores
- **Exemple** :
```env
DB_NAME=symfony_app     # Développement
DB_NAME=production_db   # Production
```

#### DB_USER
- **Description** : Utilisateur de la base de données pour l'application
- **Permissions** : Accès limité à DB_NAME uniquement
- **Exemple** :
```env
DB_USER=app_user        # Développement
DB_USER=symfony_prod    # Production
```

#### DB_PASSWORD
- **Description** : Mot de passe de l'utilisateur de l'application
- **Sécurité** : ⚠️ CRITIQUE - Générer aléatoirement
- **Génération** :
```bash
# Générer un mot de passe sécurisé
openssl rand -base64 32
```
- **Exemple** :
```env
# Développement
DB_PASSWORD=dev_password

# Production
DB_PASSWORD=XkQ2n8B9m5E7vA3sR6tY1wP4uI0oL9cK
```

#### MARIADB_VERSION
- **Description** : Version de l'image MariaDB
- **Format** : Numéro de version majeure
- **Défaut** : `11`
- **Exemple** :
```env
MARIADB_VERSION=11      # Version stable actuelle
MARIADB_VERSION=10.11   # Version LTS précédente
```

## ⚡ Variables Mercure

### Configuration Hub Mercure

#### MERCURE_JWT_SECRET
- **Description** : Clé secrète JWT pour Mercure Hub
- **Format** : Chaîne secrète longue
- **Usage** : Signature des tokens publisher/subscriber
- **Sécurité** : ⚠️ CRITIQUE - Doit être identique entre publisher et subscriber
- **Exemple** :
```env
# Développement
MERCURE_JWT_SECRET=dev-mercure-secret-key

# Production
MERCURE_JWT_SECRET=ultra-secure-mercure-jwt-secret-production-key
```

#### MERCURE_VERSION
- **Description** : Version de l'image Mercure
- **Défaut** : `v0.16`
- **Exemple** :
```env
MERCURE_VERSION=v0.16   # Version stable
MERCURE_VERSION=latest  # Dernière version (non recommandé prod)
```

#### MERCURE_EXTRA_DIRECTIVES
- **Description** : Directives supplémentaires pour Mercure
- **Format** : Directives séparées par `\n`
- **Défaut** : `"anonymous\ndemo"`
- **Exemple** :
```env
# Développement
MERCURE_EXTRA_DIRECTIVES="anonymous\ndemo"

# Production
MERCURE_EXTRA_DIRECTIVES="cors_origins https://example.com"
```

### URLs Mercure pour Symfony

#### MERCURE_URL
- **Description** : URL interne Mercure pour Symfony (côté serveur)
- **Format** : `http://mercure:3000/.well-known/mercure`
- **Exemple** :
```env
MERCURE_URL=http://mercure:3000/.well-known/mercure
```

#### MERCURE_PUBLIC_URL
- **Description** : URL publique Mercure pour le navigateur (côté client JavaScript)
- **Format** : `http://localhost:PORT/.well-known/mercure`
- **Synchronisation automatique** : ✅ Mise à jour automatiquement par `--auto-ports` pour correspondre à `HTTP_PORT`
- **Importance** : ⚠️ DOIT correspondre au port HTTP pour éviter les erreurs CORS
- **Exemple** :
```env
# Développement (synchronisé avec HTTP_PORT=8080)
MERCURE_PUBLIC_URL=http://localhost:8080/.well-known/mercure

# Production (domaine dédié)
MERCURE_PUBLIC_URL=https://mercure.example.com/.well-known/mercure
```

## 🤖 Variables ChromaDB

### Configuration ChromaDB

#### CHROMA_URL
- **Description** : URL interne ChromaDB pour Symfony (côté serveur)
- **Format** : `http://chroma:PORT`
- **Usage** : Connexion du Gorillias Marketing AI Bundle au service de base de données vectorielle
- **Exemple** :
```env
CHROMA_URL=http://chroma:8000
```

#### CHROMA_PORT
- **Description** : Port externe pour ChromaDB (développement uniquement)
- **Défaut** : `8000`
- **Production** : ⚠️ Non exposé en production (communication interne uniquement)
- **Exemple** :
```env
# Développement : port exposé pour accès direct
CHROMA_PORT=8000

# Production : variable non nécessaire (pas d'exposition)
```

## 🔧 Variables des services de développement

### phpMyAdmin

#### PHPMYADMIN_VERSION
- **Description** : Version de l'image phpMyAdmin
- **Défaut** : `latest`
- **Exemple** :
```env
PHPMYADMIN_VERSION=latest   # Dernière version
PHPMYADMIN_VERSION=5.2     # Version spécifique
```

### MailHog

#### MAILHOG_VERSION
- **Description** : Version de l'image MailHog
- **Défaut** : `latest`
- **Exemple** :
```env
MAILHOG_VERSION=latest      # Dernière version
MAILHOG_VERSION=v1.0.1     # Version spécifique
```

## 🚀 Variables de production

### Domaines et SSL

#### SERVER_NAME
- **Description** : Domaines pour l'application (production)
- **Format** : Domaines séparés par des virgules
- **Usage** : Configuration Caddy et certificats SSL automatiques
- **Exemple** :
```env
SERVER_NAME=example.com,www.example.com
```

#### MERCURE_DOMAIN
- **Description** : Domaine dédié pour Mercure Hub
- **Usage** : SSL automatique et CORS
- **Exemple** :
```env
MERCURE_DOMAIN=mercure.example.com
```

#### ACME_EMAIL
- **Description** : Email pour Let's Encrypt
- **Usage** : Génération automatique certificats SSL
- **Exemple** :
```env
ACME_EMAIL=admin@example.com
```

### Sécurité CORS

#### CORS_ALLOWED_ORIGINS
- **Description** : Origines autorisées pour CORS
- **Format** : URLs séparées par des virgules
- **Exemple** :
```env
CORS_ALLOWED_ORIGINS=https://example.com,https://www.example.com
```

## 📋 Exemples de configuration par environnement

### Développement complet (.env.local)
```env
# === SYMFONY ===
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=dev-secret-change-in-production

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
MERCURE_URL=http://mercure:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:8080/.well-known/mercure

# === CHROMADB ===
CHROMA_URL=http://chroma:8000
CHROMA_PORT=8000

# === DÉVELOPPEMENT ===
PHPMYADMIN_VERSION=latest
MAILHOG_VERSION=latest

# === PERMISSIONS (auto-détectées) ===
# DOCKER_UID=1000
# DOCKER_GID=1000
```

### Production (.env.prod.local)
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
SERVER_NAME=example.com,www.example.com
MERCURE_DOMAIN=mercure.example.com

# === MERCURE ===
MERCURE_JWT_SECRET=ultra-secure-mercure-jwt-secret-64-chars
MERCURE_VERSION=v0.16
MERCURE_URL=http://mercure:3000/.well-known/mercure
MERCURE_PUBLIC_URL=https://example.com/.well-known/mercure

# === CHROMADB ===
CHROMA_URL=http://chroma:8000

# === SÉCURITÉ ===
CORS_ALLOWED_ORIGINS=https://example.com,https://www.example.com
ACME_EMAIL=admin@example.com
```

### Test (.env.test.local)
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
MERCURE_URL=http://mercure:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:9080/.well-known/mercure

# === CHROMADB TEST ===
CHROMA_URL=http://chroma:8000
CHROMA_PORT=8001
```

## ✅ Validation des variables

### Variables obligatoires par environnement

#### Développement minimum
- `APP_SECRET`
- `DB_PASSWORD`
- `MERCURE_JWT_SECRET`

#### Production minimum
- `APP_SECRET` (sécurisé)
- `DB_ROOT_PASSWORD` (sécurisé)
- `DB_PASSWORD` (sécurisé)
- `MERCURE_JWT_SECRET` (sécurisé)
- `SERVER_NAME`
- `ACME_EMAIL`

### Script de validation
```bash
# Validation automatique dans deploy.sh
local required_vars=("APP_SECRET" "DB_PASSWORD")
for var in "${required_vars[@]}"; do
    if ! grep -q "^$var=" "$env_file" 2>/dev/null; then
        log_warn "Variable $var manquante dans $env_file"
    fi
done
```

### Checklist de sécurité
- [ ] Secrets générés aléatoirement (32+ caractères)
- [ ] Mots de passe forts en production
- [ ] Fichiers .env.*.local dans .gitignore
- [ ] Variables sensibles non commitées
- [ ] CORS configuré restrictement en production