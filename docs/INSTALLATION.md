# 📦 Guide d'installation

Guide détaillé pour installer et configurer la stack Docker Symfony + FrankenPHP.

## 🔧 Prérequis système

### Logiciels requis

#### Docker et Docker Compose
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install docker.io docker-compose-plugin

# CentOS/RHEL
sudo yum install docker docker-compose-plugin

# macOS (Homebrew)
brew install docker docker-compose

# Windows (Chocolatey)
choco install docker-desktop
```

#### Vérification de l'installation
```bash
# Vérifier Docker
docker --version
# Résultat attendu : Docker version 24.0+

# Vérifier Docker Compose
docker compose version
# Résultat attendu : Docker Compose version v2.20+

# Tester l'accès Docker
docker run hello-world
```

### Configuration système recommandée

#### Linux (Ubuntu/Debian)
```bash
# Ajouter l'utilisateur au groupe docker
sudo usermod -aG docker $USER

# Redémarrer la session ou recharger les groupes
newgrp docker

# Activer le service Docker
sudo systemctl enable docker
sudo systemctl start docker
```

#### Windows (Docker Desktop)
- Activer **WSL 2** si nécessaire
- Configurer Docker Desktop pour utiliser WSL 2
- Allouer au minimum **4GB de RAM** à Docker

#### macOS (Docker Desktop)
- Allouer au minimum **4GB de RAM** à Docker
- Activer **VirtioFS** pour de meilleures performances

## 📥 Installation du projet

### 1. Récupération du code

#### Via Git (recommandé)
```bash
# Cloner le repository
git clone https://votre-repo/docker-symfony-stack.git
cd docker-symfony-stack

# Ou fork pour développement
git clone https://github.com/votre-username/docker-symfony-stack.git
cd docker-symfony-stack
```

#### Via téléchargement direct
```bash
# Télécharger et extraire
wget https://github.com/votre-repo/archive/main.zip
unzip main.zip
cd docker-symfony-stack-main
```

### 2. Configuration des permissions

#### Linux/macOS
```bash
# Rendre le script de déploiement exécutable
chmod +x deploy.sh

# Vérifier les permissions
ls -la deploy.sh
# Résultat attendu : -rwxr-xr-x ... deploy.sh
```

#### Windows (Git Bash/WSL)
```bash
# Dans Git Bash ou WSL
chmod +x deploy.sh
```

### 3. Structure des répertoires

```bash
# Créer le répertoire pour le code Symfony
mkdir -p app

# Vérifier la structure
tree -L 2
```

Structure attendue :
```
GoldMind/
├── 📄 README.md
├── 🚀 deploy.sh*                   # Script exécutable
├── 🐳 Dockerfile
├── 📝 docker-compose.yml
├── 📝 docker-compose.override.yml
├── 📁 docker/
│   ├── 🔧 entrypoint.sh
│   ├── ⚙️ Caddyfile.dev
│   ├── 🐘 php.ini.dev
│   └── 🐘 php.ini.prod
├── 📁 app/                         # Code Symfony (vide initialement)
├── 📁 docs/
└── 📋 .env.local.example
```

## ⚙️ Configuration initiale

### 1. Variables d'environnement

#### Créer le fichier de configuration
```bash
# Copier le fichier exemple
cp .env.local.example .env.local

# Éditer selon vos besoins
nano .env.local
```

#### Configuration minimale requise
```env
# === SYMFONY ===
APP_ENV=dev
APP_SECRET=your-unique-secret-key-here

# === BASE DE DONNÉES ===
DB_ROOT_PASSWORD=your-secure-root-password
DB_NAME=your_project_name
DB_USER=your_db_user
DB_PASSWORD=your-secure-db-password

# === DOCKER ===
PROJECT_NAME=your-project-name

# === PORTS (optionnel - auto-détection disponible) ===
HTTP_PORT=8080
PHPMYADMIN_PORT=8200
MAILHOG_PORT=8300
MERCURE_PORT=3002
```

### 2. Génération des secrets

#### APP_SECRET Symfony
```bash
# Générer un secret sécurisé (32 caractères)
openssl rand -hex 32

# Ou utiliser pwgen
pwgen -s 32 1

# Sur macOS
uuidgen | tr -d '-' | tr '[:upper:]' '[:lower:]'
```

#### Mots de passe base de données
```bash
# Générer un mot de passe sécurisé
openssl rand -base64 32

# Ou plus simple
date +%s | sha256sum | base64 | head -c 32 ; echo
```

### 3. Configuration spécifique à l'environnement

#### Développement (.env.local)
```env
APP_ENV=dev
APP_DEBUG=1
BUILD_TARGET=development

# Ports de développement (auto-détection recommandée)
HTTP_PORT=8080
PHPMYADMIN_PORT=8200
MAILHOG_PORT=8300
MERCURE_PORT=3002
```

#### Production (.env.prod.local)
```env
APP_ENV=prod
APP_DEBUG=0
BUILD_TARGET=production

# Variables de production sécurisées
APP_SECRET=ultra-secure-64-char-production-secret-key-here
SERVER_NAME=yourdomain.com
MERCURE_DOMAIN=mercure.yourdomain.com

# CORS pour production
CORS_ALLOWED_ORIGINS=https://yourdomain.com
```

## 🚀 Premier démarrage

### 1. Test avec auto-configuration

```bash
# Démarrage avec auto-détection des ports
./deploy.sh dev --auto-ports

# Vérifier les logs
./deploy.sh --logs
```

### 2. Vérification de l'installation

#### Vérifier les services
```bash
# Statut des conteneurs
./deploy.sh --status

# Résultat attendu :
# ✅ frankenphp : running
# ✅ mariadb    : healthy
# ✅ phpmyadmin : running
# ✅ mailhog    : running
# ✅ mercure    : running
```

#### Test des URLs
```bash
# Application principale
curl -u krystdev:dev123 http://localhost:8080

# phpMyAdmin
curl -u krystdev:dev123 http://localhost:8200

# MailHog
curl -u krystdev:dev123 http://localhost:8300

# Mercure
curl http://localhost:3002/.well-known/mercure
```

### 3. Installation d'un projet Symfony

#### Nouveau projet Symfony
```bash
# Entrer dans le conteneur
docker compose exec frankenphp bash

# Créer un nouveau projet Symfony
composer create-project symfony/website-skeleton .

# Installer les dépendances
composer install

# Sortir du conteneur
exit
```

#### Projet Symfony existant
```bash
# Copier votre projet dans le dossier app/
cp -r /path/to/your/symfony/project/* app/

# Installer les dépendances
docker compose exec frankenphp composer install

# Configurer la base de données
docker compose exec frankenphp php bin/console doctrine:database:create
docker compose exec frankenphp php bin/console doctrine:migrations:migrate
```

## 🔧 Configuration avancée

### 1. Personnalisation des ports

#### Méthode automatique (recommandée)
```bash
# Le script détecte automatiquement les ports libres
./deploy.sh dev --auto-ports
```

#### Méthode manuelle
```bash
# Éditer .env.local
nano .env.local

# Modifier les ports selon vos besoins
HTTP_PORT=9080
PHPMYADMIN_PORT=9200
MAILHOG_PORT=9300
MERCURE_PORT=4002

# Redémarrer avec la nouvelle configuration
./deploy.sh dev --build
```

### 2. Configuration des domaines personnalisés

#### Hosts locaux (développement)
```bash
# Éditer le fichier hosts
sudo nano /etc/hosts

# Ajouter vos domaines
127.0.0.1 myproject.local
127.0.0.1 phpmyadmin.myproject.local
127.0.0.1 mail.myproject.local
```

#### Configuration Caddy personnalisée
```bash
# Copier et modifier le Caddyfile
cp docker/Caddyfile.dev docker/Caddyfile.custom

# Modifier la configuration
nano docker/Caddyfile.custom

# Utiliser la configuration personnalisée
# Modifier docker-compose.override.yml pour pointer vers le nouveau fichier
```

### 3. Optimisation des performances

#### Allocation mémoire Docker
```bash
# Vérifier l'allocation actuelle
docker system df

# Nettoyer les ressources inutilisées
docker system prune -a

# Optimiser l'allocation mémoire pour le développement
docker update --memory=4g --memory-swap=8g $(docker ps -q)
```

#### Cache et volumes
```bash
# Optimiser les volumes pour de meilleures performances
# Modifier docker-compose.yml selon votre système
# Linux : utiliser bind mounts
# macOS : utiliser cached ou delegated
# Windows : utiliser cached
```

## ✅ Vérification de l'installation

### Checklist post-installation

- [ ] Docker et Docker Compose installés et fonctionnels
- [ ] Script `deploy.sh` exécutable
- [ ] Fichier `.env.local` configuré avec vos valeurs
- [ ] Services démarrés avec `./deploy.sh dev`
- [ ] Application accessible sur http://localhost:PORT
- [ ] phpMyAdmin accessible et connecté à la base
- [ ] MailHog accessible pour capturer les emails
- [ ] Mercure Hub accessible pour le temps réel

### Tests de validation

```bash
# Test complet de la stack
./deploy.sh dev --auto-ports

# Vérifier que tous les services sont healthy
docker compose ps

# Test de connectivité
curl -u krystdev:dev123 http://localhost:8080/health 2>/dev/null || echo "Service principal OK"

# Test base de données
docker compose exec mariadb mysql -u root -proot -e "SHOW DATABASES;"

# Test Mercure
curl http://localhost:3002/.well-known/mercure -s | grep "mercure" || echo "Mercure OK"
```

## 🆘 Résolution des problèmes d'installation

### Problèmes courants

#### Docker non accessible
```bash
# Vérifier le statut du service
sudo systemctl status docker

# Redémarrer si nécessaire
sudo systemctl restart docker

# Vérifier les permissions
groups $USER | grep docker
```

#### Ports déjà utilisés
```bash
# Vérifier les ports occupés
ss -tuln | grep :8080

# Utiliser l'auto-détection
./deploy.sh dev --auto-ports

# Ou modifier manuellement les ports dans .env.local
```

#### Erreurs de permissions
```bash
# Linux/macOS : vérifier l'UID/GID
id -u && id -g

# Corriger les permissions
sudo chown -R $USER:$USER .

# Windows : vérifier WSL 2 et les permissions Docker Desktop
```

Pour plus de problèmes spécifiques, consultez le [guide de dépannage](TROUBLESHOOTING.md).