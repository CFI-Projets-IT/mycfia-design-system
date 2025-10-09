# 🆘 Guide de dépannage

Solutions aux problèmes courants de la stack Docker Symfony + FrankenPHP.

## 🔍 Diagnostic général

### Commandes de diagnostic rapide

#### Vérification de l'état global
```bash
# Statut des services
./deploy.sh --status

# Logs détaillés
./deploy.sh --logs

# Santé Docker
docker system df
docker system events --since 1h
```

#### Test de connectivité
```bash
# Test des ports
ss -tuln | grep -E ":(8080|8200|8300|3002)"

# Test des services
curl -I http://localhost:8080 2>/dev/null || echo "Service principal inaccessible"
curl -I http://localhost:8200 2>/dev/null || echo "phpMyAdmin inaccessible"
curl -I http://localhost:8300 2>/dev/null || echo "MailHog inaccessible"
curl -I http://localhost:3002 2>/dev/null || echo "Mercure inaccessible"
```

## 🚫 Problèmes de démarrage

### Erreur : "Port already in use"

#### Diagnostic
```bash
# Identifier le processus utilisant le port
sudo lsof -i :8080
sudo ss -tuln | grep :8080

# Vérifier les conteneurs Docker existants
docker ps --format "table {{.Names}}\t{{.Ports}}"
```

#### Solutions
```bash
# Solution 1 : Auto-détection des ports
./deploy.sh dev --auto-ports

# Solution 2 : Arrêter les services conflictuels
sudo systemctl stop apache2  # Si Apache utilise le port
sudo systemctl stop nginx    # Si Nginx utilise le port

# Solution 3 : Changer les ports manuellement
nano .env.local
# Modifier HTTP_PORT=9080

# Solution 4 : Tuer le processus spécifique
sudo kill -9 $(lsof -t -i:8080)
```

### Erreur : "No space left on device"

#### Diagnostic
```bash
# Vérifier l'espace disque
df -h
docker system df

# Identifier les gros volumes
docker volume ls -q | xargs docker volume inspect | grep -A5 -B5 '"Mountpoint"'
```

#### Solutions
```bash
# Nettoyer Docker
docker system prune -a --volumes
docker volume prune

# Nettoyer les images inutilisées
docker image prune -a

# Nettoyer les logs
sudo journalctl --vacuum-time=1d
```

### Erreur : "Permission denied"

#### Diagnostic (Linux/macOS)
```bash
# Vérifier l'UID/GID
id -u && id -g
ls -la app/

# Vérifier les permissions Docker
groups $USER | grep docker
```

#### Solutions
```bash
# Solution 1 : Ajouter l'utilisateur au groupe docker
sudo usermod -aG docker $USER
newgrp docker

# Solution 2 : Corriger les permissions du projet
sudo chown -R $USER:$USER .
chmod +x deploy.sh

# Solution 3 : Forcer l'UID/GID
export DOCKER_UID=$(id -u)
export DOCKER_GID=$(id -g)
./deploy.sh dev --build

# Solution 4 : Reset complet des permissions
./deploy.sh --down
sudo rm -rf app/var/cache app/var/log
./deploy.sh dev --build
```

## 🐳 Problèmes Docker

### Erreur : "Docker daemon not running"

#### Diagnostic
```bash
# Vérifier le statut Docker
sudo systemctl status docker
docker version
```

#### Solutions
```bash
# Linux
sudo systemctl start docker
sudo systemctl enable docker

# macOS
open /Applications/Docker.app

# Windows
# Démarrer Docker Desktop depuis le menu Démarrer
```

### Erreur : "Image build failed"

#### Diagnostic
```bash
# Build avec logs détaillés
docker compose build --no-cache --progress=plain

# Vérifier l'espace disque
docker system df
```

#### Solutions
```bash
# Solution 1 : Nettoyer et rebuilder
./deploy.sh --down
docker system prune -a
./deploy.sh dev --build

# Solution 2 : Build étape par étape
docker build --target base -t test-base .
docker build --target development -t test-dev .

# Solution 3 : Vérifier les fichiers sources
ls -la docker/
cat docker/Dockerfile | head -20
```

### Erreur : "Container exits immediately"

#### Diagnostic
```bash
# Voir les logs de sortie
docker compose logs frankenphp --tail=50

# Tester le conteneur interactivement
docker run -it --rm $(docker compose config --images | grep frankenphp) bash
```

#### Solutions
```bash
# Solution 1 : Vérifier entrypoint.sh
chmod +x docker/entrypoint.sh
cat docker/entrypoint.sh | head -10

# Solution 2 : Tester sans entrypoint
docker run -it --rm --entrypoint="" $(docker compose config --images | grep frankenphp) bash

# Solution 3 : Reconstruire proprement
./deploy.sh --down -v
./deploy.sh dev --build
```

## 🌐 Problèmes réseau et connectivité

### Erreur : "Service unavailable"

#### Diagnostic
```bash
# Vérifier le réseau Docker
docker network ls
docker network inspect $(docker compose config --networks | head -1)

# Tester la connectivité interne
docker compose exec frankenphp ping mariadb
docker compose exec frankenphp curl -I http://mercure:3000
```

#### Solutions
```bash
# Solution 1 : Recréer le réseau
./deploy.sh --down
docker network prune
./deploy.sh dev

# Solution 2 : Vérifier la configuration DNS
docker compose exec frankenphp nslookup mariadb
docker compose exec frankenphp cat /etc/resolv.conf

# Solution 3 : Forcer la recréation des conteneurs
./deploy.sh dev --force-recreate
```

### Erreur : "Authentication failed"

#### Diagnostic
```bash
# Tester l'authentification basic auth
curl -u krystdev:dev123 http://localhost:8080 -v

# Vérifier le hash bcrypt
echo '$2y$10$3F3s/vHKZfXRHOsJ2qXhNOcmOQlRZMKz5PQNW8zVo8Iy9KfU3jLUe' | base64 -d
```

#### Solutions
```bash
# Solution 1 : Regénérer le hash
htpasswd -nbB krystdev dev123

# Solution 2 : Vérifier Caddyfile
cat docker/Caddyfile.dev | grep -A3 basic_auth

# Solution 3 : Désactiver temporairement l'auth
# Commenter les lignes basic_auth dans Caddyfile.dev
./deploy.sh dev --build
```

## 🗄️ Problèmes de base de données

### Erreur : "Connection refused" (MariaDB)

#### Diagnostic
```bash
# Vérifier le statut MariaDB
docker compose logs mariadb --tail=20
docker compose exec mariadb mysqladmin ping -u root -p

# Tester la connectivité
docker compose exec frankenphp ping mariadb
```

#### Solutions
```bash
# Solution 1 : Attendre le healthcheck
docker compose up -d mariadb
docker compose logs -f mariadb
# Attendre "ready for connections"

# Solution 2 : Recréer le volume
./deploy.sh --down -v
./deploy.sh dev

# Solution 3 : Vérifier les variables d'environnement
docker compose exec mariadb env | grep MYSQL
```

### Erreur : "Access denied for user"

#### Diagnostic
```bash
# Vérifier les credentials
cat .env.local | grep DB_

# Tester la connexion
docker compose exec mariadb mysql -u root -p -e "SELECT User, Host FROM mysql.user;"
```

#### Solutions
```bash
# Solution 1 : Reset du mot de passe root
./deploy.sh --down
docker volume rm $(docker volume ls -q | grep mariadb)
./deploy.sh dev

# Solution 2 : Créer l'utilisateur manuellement
docker compose exec mariadb mysql -u root -proot <<EOF
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
EOF

# Solution 3 : Vérifier l'encodage des mots de passe
echo -n "password" | base64  # Pas de caractères spéciaux
```

### Erreur : "Table doesn't exist"

#### Diagnostic
```bash
# Vérifier les migrations
docker compose exec frankenphp php bin/console doctrine:migrations:status

# Vérifier la structure de la base
docker compose exec mariadb mysql -u root -proot -e "USE ${DB_NAME}; SHOW TABLES;"
```

#### Solutions
```bash
# Solution 1 : Exécuter les migrations
docker compose exec frankenphp php bin/console doctrine:database:create
docker compose exec frankenphp php bin/console doctrine:migrations:migrate

# Solution 2 : Régénérer le schéma
docker compose exec frankenphp php bin/console doctrine:schema:update --force

# Solution 3 : Charger les fixtures
docker compose exec frankenphp php bin/console doctrine:fixtures:load
```

## 🐘 Problèmes PHP et Symfony

### Erreur : "Fatal error: Out of memory"

#### Diagnostic
```bash
# Vérifier la configuration PHP
docker compose exec frankenphp php -i | grep memory_limit
docker compose exec frankenphp php -m  # Extensions chargées
```

#### Solutions
```bash
# Solution 1 : Augmenter memory_limit
# Modifier docker/php.ini.dev
memory_limit = 1024M
./deploy.sh dev --build

# Solution 2 : Optimiser Composer
docker compose exec frankenphp composer install --optimize-autoloader --no-dev

# Solution 3 : Vider le cache
docker compose exec frankenphp php bin/console cache:clear
```

### Erreur : "Class not found"

#### Diagnostic
```bash
# Vérifier l'autoloader
docker compose exec frankenphp composer dump-autoload -o

# Vérifier les namespaces
docker compose exec frankenphp composer show -s
```

#### Solutions
```bash
# Solution 1 : Régénérer l'autoloader
docker compose exec frankenphp composer dump-autoload --optimize

# Solution 2 : Vérifier composer.json
cat app/composer.json | jq '.autoload'

# Solution 3 : Réinstaller les dépendances
docker compose exec frankenphp rm -rf vendor/
docker compose exec frankenphp composer install
```

### Erreur : "Environment variable not found"

#### Diagnostic
```bash
# Vérifier les variables d'environnement
docker compose exec frankenphp env | grep APP_
docker compose exec frankenphp php bin/console debug:dotenv
```

#### Solutions
```bash
# Solution 1 : Vérifier les fichiers .env
ls -la .env*
cat .env.local

# Solution 2 : Vérifier la syntaxe
# Pas d'espaces autour du =
APP_SECRET=value  # ✅
APP_SECRET = value  # ❌

# Solution 3 : Redémarrer avec nouvelles variables
./deploy.sh --down
./deploy.sh dev
```

## ⚡ Problèmes de performance

### Problème : Application lente

#### Diagnostic
```bash
# Vérifier les ressources
docker stats
htop

# Profiler Symfony
docker compose exec frankenphp composer require --dev symfony/profiler-pack
# Accéder à /_profiler
```

#### Solutions
```bash
# Solution 1 : Optimiser OPcache
# Modifier docker/php.ini.prod
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000

# Solution 2 : Optimiser les assets
docker compose exec frankenphp npm run build
docker compose exec frankenphp php bin/console assets:install

# Solution 3 : Optimiser Doctrine
docker compose exec frankenphp php bin/console doctrine:query:optimize
```

### Problème : Build lent

#### Diagnostic
```bash
# Analyser les étapes de build
docker build --no-cache --progress=plain . 2>&1 | tee build.log

# Vérifier le cache Docker
docker system df
```

#### Solutions
```bash
# Solution 1 : Optimiser l'ordre Dockerfile
# Copier composer.json avant le code pour cache des deps
COPY composer.json composer.lock ./
RUN composer install
COPY . .

# Solution 2 : Utiliser BuildKit
export DOCKER_BUILDKIT=1
docker build .

# Solution 3 : Optimiser les images de base
# Utiliser des images plus légères si possible
```

## 🔧 Problèmes de configuration

### Problème : Variables d'environnement non prises en compte

#### Diagnostic
```bash
# Vérifier le chargement des fichiers
docker compose config

# Vérifier les priorités
ls -la .env*
```

#### Solutions
```bash
# Solution 1 : Ordre de priorité des fichiers .env
# .env.local > .env > docker-compose.yml

# Solution 2 : Syntaxe correcte
# Utiliser des guillemets pour valeurs avec espaces
DB_PASSWORD="mot de passe avec espaces"

# Solution 3 : Export explicite
export APP_ENV=dev
./deploy.sh dev
```

### Problème : Configuration Caddy non appliquée

#### Diagnostic
```bash
# Vérifier la configuration Caddy
docker compose exec frankenphp caddy fmt --config /etc/caddy/Caddyfile
docker compose logs frankenphp | grep caddy
```

#### Solutions
```bash
# Solution 1 : Valider la syntaxe Caddyfile
caddy fmt docker/Caddyfile.dev

# Solution 2 : Recharger la configuration
docker compose exec frankenphp caddy reload --config /etc/caddy/Caddyfile

# Solution 3 : Reconstruire avec nouvelle config
./deploy.sh dev --build
```

## 🚨 Procédures d'urgence

### Reset complet de l'environnement

```bash
# ATTENTION : Supprime toutes les données !
./deploy.sh --down -v
docker system prune -a --volumes
docker volume prune -f
./deploy.sh dev --build
```

### Sauvegarde avant dépannage

```bash
# Sauvegarder la base de données
docker compose exec mariadb mysqldump -u root -proot --all-databases > backup_$(date +%Y%m%d).sql

# Sauvegarder les volumes
docker run --rm -v $(pwd):/backup -v mariadb_data:/data alpine tar czf /backup/mariadb_backup.tar.gz /data

# Sauvegarder la configuration
tar czf config_backup_$(date +%Y%m%d).tar.gz .env* docker/
```

### Restauration après problème

```bash
# Restaurer la base de données
cat backup_20240101.sql | docker compose exec -T mariadb mysql -u root -proot

# Restaurer un volume
docker run --rm -v $(pwd):/backup -v mariadb_data:/data alpine tar xzf /backup/mariadb_backup.tar.gz -C /
```

## 📞 Obtenir de l'aide

### Logs à collecter pour le support

```bash
# Informations système
uname -a > debug_info.txt
docker version >> debug_info.txt
docker compose version >> debug_info.txt

# Configuration
cat .env.local >> debug_info.txt
docker compose config >> debug_info.txt

# Logs des services
./deploy.sh --logs --tail=100 >> debug_info.txt

# État des conteneurs
docker compose ps >> debug_info.txt
docker stats --no-stream >> debug_info.txt
```

### Ressources utiles

- **Documentation officielle** : [FrankenPHP](https://frankenphp.dev), [Symfony](https://symfony.com/doc)
- **Issues GitHub** : Rechercher dans les issues du projet
- **Stack Overflow** : Tags `docker`, `symfony`, `frankenphp`
- **Discord Symfony** : Communauté active

### Avant de demander de l'aide

1. ✅ Essayer un reset complet
2. ✅ Vérifier les logs détaillés
3. ✅ Rechercher dans cette documentation
4. ✅ Préparer les informations de debug
5. ✅ Décrire les étapes pour reproduire le problème