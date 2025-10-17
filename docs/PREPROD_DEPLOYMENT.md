# Déploiement Preprod - myCfia

Documentation complète pour le déploiement de l'application en environnement de préproduction.

## Vue d'ensemble

### Architecture

L'environnement preprod utilise une approche optimisée avec **Git serveur + Volume Docker** :

```
┌─────────────────────────────────────────────────────────────┐
│                    SERVEUR PREPROD                          │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │          REVERSE PROXY (nginx/Apache)                │  │
│  │  - HTTPS avec certificat SSL                         │  │
│  │  - Domaine : preprod.example.com                     │  │
│  │  - Redirige vers → 127.0.0.1:8081                   │  │
│  └─────────────────────┬────────────────────────────────┘  │
│                        │ HTTP (127.0.0.1:8081)              │
│                        ↓                                     │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         DOCKER CONTAINERS                            │  │
│  │  ┌─────────────────────────────────────────────┐    │  │
│  │  │ FrankenPHP (127.0.0.1:8081)                │    │  │
│  │  │ - Volume read-only: /opt/mycfia-preprod    │    │  │
│  │  │ - HTTP uniquement (pas HTTPS)              │    │  │
│  │  └─────────────────────────────────────────────┘    │  │
│  │  ┌─────────────────────────────────────────────┐    │  │
│  │  │ MariaDB (interne)                          │    │  │
│  │  └─────────────────────────────────────────────┘    │  │
│  │  ┌─────────────────────────────────────────────┐    │  │
│  │  │ Mercure Hub (127.0.0.1:3081)              │    │  │
│  │  └─────────────────────────────────────────────┘    │  │
│  │  ┌─────────────────────────────────────────────┐    │  │
│  │  │ Messenger Worker (async)                   │    │  │
│  │  └─────────────────────────────────────────────┘    │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         CODE SOURCE (Git)                            │  │
│  │  /opt/mycfia-preprod/                               │  │
│  │  ├── .git/              (Historique complet)        │  │
│  │  ├── app/               (Code Symfony)              │  │
│  │  ├── deploy.sh                                      │  │
│  │  └── scripts/preprod-switch.sh (Switch branches)   │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Avantages de cette architecture

- ✅ **Switch de branch instantané** : 10-15 secondes (vs 2-5 minutes avec rebuild)
- ✅ **Rollback automatique** : En cas d'erreur lors du switch
- ✅ **Historique Git disponible** : Debug et analyse de commits
- ✅ **Sécurité renforcée** : Conteneur invisible depuis Internet (127.0.0.1)
- ✅ **Volume read-only** : Code protégé en lecture seule
- ✅ **Flexibilité maximale** : Test rapide de n'importe quelle branch

---

## Setup Initial du Serveur

### Prérequis

- **Serveur Linux** : Ubuntu 22.04+ / Debian 11+ / CentOS 8+
- **Docker** : Version 24.0+
- **Docker Compose** : Version 2.20+
- **Git** : Déjà installé sur le serveur
- **Accès SSH** : Avec droits sudo
- **Ports disponibles** : 8081 (HTTP), 3081 (Mercure)

### Étape 1 : Cloner le dépôt Git

```bash
# Se connecter au serveur
ssh user@serveur-preprod

# Créer le répertoire de destination
sudo mkdir -p /opt/mycfia-preprod
sudo chown $(whoami):$(whoami) /opt/mycfia-preprod

# Cloner le dépôt (adapter l'URL)
git clone https://github.com/votre-org/myCfia.git /opt/mycfia-preprod

# Accéder au répertoire
cd /opt/mycfia-preprod
```

### Étape 2 : Créer la branch preprod

```bash
# Basculer sur la branch preprod (depuis develop)
git checkout -b preprod origin/preprod

# Ou créer localement si elle n'existe pas encore
git checkout -b preprod
```

### Étape 3 : Configurer l'environnement

```bash
# Copier le template de configuration
cp .env.preprod.example .env.preprod.local

# Éditer la configuration
nano .env.preprod.local
```

**Variables critiques à configurer** :

```env
# Chemin vers le code (doit pointer vers le répertoire actuel)
PREPROD_CODE_PATH=/opt/mycfia-preprod

# Nom du projet Docker
PROJECT_NAME=mycfia-preprod

# Générer des secrets sécurisés
APP_SECRET=$(openssl rand -hex 32)
DB_PASSWORD=$(openssl rand -base64 32)
MERCURE_JWT_SECRET=$(openssl rand -base64 32)

# URL publique (via reverse proxy)
MERCURE_PUBLIC_URL=https://preprod.example.com/.well-known/mercure
CORS_ALLOWED_ORIGINS=https://preprod.example.com

# Credentials CFI (API de test)
CFI_API_BASE_URL=https://test.cfitech.io/API
CFI_USERNAME=votre-username
CFI_PASSWORD=votre-password

# Mistral AI
MISTRAL_API_KEY=votre-clé-mistral
```

### Étape 4 : Déployer l'application

```bash
# Première construction et démarrage
./deploy.sh preprod --build

# Vérifier les services
docker compose -f docker-compose.yml -f docker-compose.preprod.yml ps
```

**Sortie attendue** :

```
ℹ️  Configuration environnement PREPROD
✅ Prérequis vérifiés
ℹ️  Déploiement des services...
✅ Services déployés avec succès

🌐 Services accessibles:
   📱 Application:    http://127.0.0.1:8081 (localhost uniquement)
   ⚡ Mercure:       http://127.0.0.1:3081 (localhost uniquement)

   ⚠️  Services accessibles uniquement depuis le serveur
   🌐 Reverse proxy requis pour accès public HTTPS

   💡 Switch de branch rapide : ./scripts/preprod-switch.sh <branch>
```

### Étape 5 : Vérifier le déploiement

```bash
# Test de healthcheck depuis le serveur
curl -I http://127.0.0.1:8081

# Devrait retourner : HTTP/1.1 200 OK

# Vérifier les logs
docker compose -f docker-compose.yml -f docker-compose.preprod.yml logs -f frankenphp
```

---

## Configuration Reverse Proxy

L'application écoute **uniquement sur 127.0.0.1:8081** et nécessite un reverse proxy pour l'accès HTTPS public.

### Option A : nginx

**Fichier : `/etc/nginx/sites-available/preprod-mycfia`**

```nginx
server {
    listen 80;
    server_name preprod.example.com;

    # Redirection HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name preprod.example.com;

    # Certificats SSL
    ssl_certificate /etc/ssl/certs/preprod.example.com.crt;
    ssl_certificate_key /etc/ssl/private/preprod.example.com.key;

    # Configuration SSL moderne
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256';
    ssl_prefer_server_ciphers off;

    # Logs
    access_log /var/log/nginx/preprod-mycfia-access.log;
    error_log /var/log/nginx/preprod-mycfia-error.log;

    # Proxy vers application
    location / {
        proxy_pass http://127.0.0.1:8081;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Port $server_port;

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Mercure Hub (Server-Sent Events)
    location /.well-known/mercure {
        proxy_pass http://127.0.0.1:3081;
        proxy_read_timeout 24h;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_buffering off;
    }

    # Limite de taille de fichiers
    client_max_body_size 20M;
}
```

**Activation** :

```bash
# Créer le lien symbolique
sudo ln -s /etc/nginx/sites-available/preprod-mycfia /etc/nginx/sites-enabled/

# Tester la configuration
sudo nginx -t

# Recharger nginx
sudo systemctl reload nginx
```

### Option B : Apache

**Fichier : `/etc/apache2/sites-available/preprod-mycfia.conf`**

```apache
<VirtualHost *:80>
    ServerName preprod.example.com
    Redirect permanent / https://preprod.example.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName preprod.example.com

    # SSL
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/preprod.example.com.crt
    SSLCertificateKeyFile /etc/ssl/private/preprod.example.com.key

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/preprod-mycfia-error.log
    CustomLog ${APACHE_LOG_DIR}/preprod-mycfia-access.log combined

    # Proxy vers application
    ProxyPreserveHost On
    ProxyPass /.well-known/mercure http://127.0.0.1:3081/.well-known/mercure
    ProxyPassReverse /.well-known/mercure http://127.0.0.1:3081/.well-known/mercure
    ProxyPass / http://127.0.0.1:8081/
    ProxyPassReverse / http://127.0.0.1:8081/

    # Headers
    RequestHeader set X-Forwarded-Proto "https"
    RequestHeader set X-Forwarded-Port "443"
</VirtualHost>
```

**Activation** :

```bash
# Activer les modules requis
sudo a2enmod ssl proxy proxy_http headers

# Activer le site
sudo a2ensite preprod-mycfia.conf

# Tester la configuration
sudo apache2ctl configtest

# Recharger Apache
sudo systemctl reload apache2
```

---

## Workflow de Recette

### Switch vers une feature branch

```bash
# Se connecter au serveur
ssh user@serveur-preprod
cd /opt/mycfia-preprod

# Switch vers la branch à recetter
./scripts/preprod-switch.sh feature/sprint-s1-chat-lecture-v1
```

**Sortie** :

```
🔄 Switch vers branch: feature/sprint-s1-chat-lecture-v1
📌 Branch actuelle: preprod
🔍 Fetch origin...
🔄 Checkout feature/sprint-s1-chat-lecture-v1...
⬇️  Pull dernières modifications...
📦 Installation dépendances Composer...
🧹 Clear cache Symfony...
🔄 Restart conteneurs...
⏳ Attente redémarrage services...
🏥 Test healthcheck...
✅ Switch vers feature/sprint-s1-chat-lecture-v1 réussi!
🌐 Application accessible sur http://127.0.0.1:8081
```

**Durée** : 10-15 secondes

### Retour à preprod

```bash
./scripts/preprod-switch.sh preprod
```

### Rollback automatique

En cas d'erreur lors du switch, le script **rollback automatiquement** vers la branch précédente :

```
🔄 Switch vers branch: feature/broken
📌 Branch actuelle: preprod
🔍 Fetch origin...
🔄 Checkout feature/broken...
⬇️  Pull dernières modifications...
📦 Installation dépendances Composer...
🧹 Clear cache Symfony...
🔄 Restart conteneurs...
⏳ Attente redémarrage services...
🏥 Test healthcheck...
❌ Healthcheck échoué
🔙 Rollback vers preprod...
```

---

## Commandes Utiles

### Gestion des services

```bash
# Voir les logs en temps réel
docker compose -f docker-compose.yml -f docker-compose.preprod.yml logs -f

# Voir les logs d'un service spécifique
docker compose -f docker-compose.yml -f docker-compose.preprod.yml logs -f frankenphp

# Redémarrer un service
docker compose -f docker-compose.yml -f docker-compose.preprod.yml restart frankenphp

# Voir le statut des services
docker compose -f docker-compose.yml -f docker-compose.preprod.yml ps

# Arrêter tous les services
docker compose -f docker-compose.yml -f docker-compose.preprod.yml down

# Redémarrer tous les services
./deploy.sh preprod
```

### Gestion Git

```bash
# Voir l'historique
git log --oneline -10

# Voir les branches disponibles
git branch -a

# Mettre à jour la branch actuelle
git pull origin $(git branch --show-current)

# Voir les modifications non commitées
git status

# Annuler les modifications locales
git reset --hard HEAD
```

### Base de données

```bash
# Accéder à MariaDB
docker compose -f docker-compose.yml -f docker-compose.preprod.yml exec mariadb mariadb -u mycfia_preprod_user -p

# Backup de la base
docker compose -f docker-compose.yml -f docker-compose.preprod.yml exec mariadb mariadb-dump -u mycfia_preprod_user -p mycfia_preprod > backup.sql

# Restore de la base
cat backup.sql | docker compose -f docker-compose.yml -f docker-compose.preprod.yml exec -T mariadb mariadb -u mycfia_preprod_user -p mycfia_preprod
```

### Cache Symfony

```bash
# Clear cache
docker compose -f docker-compose.yml -f docker-compose.preprod.yml exec frankenphp php app/bin/console cache:clear --env=prod

# Warmup cache
docker compose -f docker-compose.yml -f docker-compose.preprod.yml exec frankenphp php app/bin/console cache:warmup --env=prod
```

---

## Troubleshooting

### Problème : Services non accessibles

**Symptômes** : `curl -I http://127.0.0.1:8081` ne répond pas

**Solutions** :

```bash
# Vérifier que les conteneurs sont démarrés
docker compose -f docker-compose.yml -f docker-compose.preprod.yml ps

# Vérifier les logs
docker compose -f docker-compose.yml -f docker-compose.preprod.yml logs frankenphp

# Redémarrer les services
./deploy.sh preprod
```

### Problème : Switch de branch échoue

**Symptômes** : Le script preprod-switch.sh renvoie une erreur

**Solutions** :

```bash
# Vérifier l'état Git
git status

# Annuler les modifications locales si besoin
git reset --hard HEAD
git clean -fd

# Réessayer le switch
./scripts/preprod-switch.sh <branch>
```

### Problème : Erreur Composer

**Symptômes** : "Error Composer (non bloquant)" lors du switch

**Solutions** :

```bash
# Installer les dépendances manuellement
docker compose -f docker-compose.yml -f docker-compose.preprod.yml exec frankenphp composer install --no-dev --optimize-autoloader -d /var/www/html/app
```

### Problème : Erreur de permissions

**Symptômes** : "Permission denied" dans les logs Symfony

**Solutions** :

```bash
# Corriger les permissions du volume var
docker compose -f docker-compose.yml -f docker-compose.preprod.yml exec frankenphp chmod -R 775 /var/www/html/app/var
docker compose -f docker-compose.yml -f docker-compose.preprod.yml restart frankenphp
```

### Problème : Base de données inaccessible

**Symptômes** : "Connection refused" dans les logs

**Solutions** :

```bash
# Vérifier le healthcheck MariaDB
docker compose -f docker-compose.yml -f docker-compose.preprod.yml ps mariadb

# Redémarrer MariaDB
docker compose -f docker-compose.yml -f docker-compose.preprod.yml restart mariadb

# Attendre 30 secondes puis redémarrer l'application
sleep 30
docker compose -f docker-compose.yml -f docker-compose.preprod.yml restart frankenphp messenger_worker
```

### Problème : Mercure ne fonctionne pas

**Symptômes** : Pas de temps réel dans l'application

**Solutions** :

```bash
# Vérifier que Mercure est accessible
curl -I http://127.0.0.1:3081/.well-known/mercure

# Vérifier les logs Mercure
docker compose -f docker-compose.yml -f docker-compose.preprod.yml logs mercure

# Vérifier la configuration CORS dans .env.preprod.local
grep CORS_ALLOWED_ORIGINS .env.preprod.local
```

---

## Sécurité

### Bonnes pratiques

1. ✅ **Secrets** : Générer avec `openssl rand -hex 32` (minimum 32 caractères)
2. ✅ **Fichiers .env.local** : Ne JAMAIS committer (dans .gitignore)
3. ✅ **Ports** : Binding strict sur 127.0.0.1 (pas 0.0.0.0)
4. ✅ **HTTPS** : Obligatoire via reverse proxy (Let's Encrypt recommandé)
5. ✅ **Firewall** : Bloquer les ports 8081 et 3081 depuis l'extérieur
6. ✅ **SSH** : Authentification par clé uniquement, désactiver root
7. ✅ **Backup** : Base de données + volumes Docker quotidiens
8. ✅ **Monitoring** : Logs centralisés et alertes

### Vérification de la sécurité

```bash
# Vérifier que les ports ne sont PAS exposés publiquement
docker compose -f docker-compose.yml -f docker-compose.preprod.yml ps

# Doit afficher : 127.0.0.1:8081->82/tcp (PAS 0.0.0.0:8081)

# Tester depuis l'extérieur (doit échouer)
curl http://IP_SERVEUR_PUBLIC:8081
# Doit retourner : Connection refused

# Tester via HTTPS public (doit fonctionner)
curl https://preprod.example.com
# Doit retourner : HTTP 200 OK
```

---

## Monitoring

### Métriques à surveiller

- **CPU** : `docker stats`
- **Mémoire** : `docker stats`
- **Disk** : `df -h`
- **Logs** : `/var/log/nginx/` ou `/var/log/apache2/`
- **Healthchecks** : `curl http://127.0.0.1:8081`

### Alertes recommandées

- ✅ Service down (healthcheck échoue)
- ✅ CPU > 80% pendant 5 minutes
- ✅ RAM > 90% pendant 5 minutes
- ✅ Disk > 85% utilisé
- ✅ Erreurs 500 > 10 par minute

---

## Maintenance

### Mise à jour de l'application

```bash
# Se connecter au serveur
ssh user@serveur-preprod
cd /opt/mycfia-preprod

# Mettre à jour la branch preprod
git checkout preprod
git pull origin preprod

# Redéployer
./deploy.sh preprod --build
```

### Mise à jour des dépendances

```bash
# Mettre à jour Composer
docker compose -f docker-compose.yml -f docker-compose.preprod.yml exec frankenphp composer update --no-dev --optimize-autoloader -d /var/www/html/app

# Redémarrer
docker compose -f docker-compose.yml -f docker-compose.preprod.yml restart frankenphp messenger_worker
```

### Nettoyage

```bash
# Supprimer les images Docker inutilisées
docker image prune -a

# Supprimer les volumes non utilisés
docker volume prune

# Nettoyer les logs
sudo truncate -s 0 /var/log/nginx/preprod-mycfia-*.log
```

---

**Projet** : myCfia - Plateforme d'automatisation marketing multi-canal
**Environnement** : Préproduction
**Dernière mise à jour** : 2025-10-17
