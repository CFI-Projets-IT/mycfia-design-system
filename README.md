# 🚀 Stack Docker Symfony + FrankenPHP

Une configuration Docker moderne et optimisée pour le développement Symfony avec **FrankenPHP**, incluant tous les outils nécessaires pour un environnement de développement complet.

## 📋 Vue d'ensemble

Cette stack propose une solution complète et moderne pour le développement Symfony avec :

- **🦘 FrankenPHP** : Serveur web haute performance (Caddy + PHP 8.3)
- **🐬 MariaDB** : Base de données MySQL/MariaDB
- **🗃️ phpMyAdmin** : Interface de gestion de base de données
- **📧 MailHog** : Capture d'emails pour les tests
- **⚡ Mercure Hub** : Streaming temps réel et WebSocket
- **🛠️ Script de déploiement intelligent** avec auto-configuration des ports

## ✨ Fonctionnalités innovantes

### 🔧 Gestion automatique des ports
- **Détection intelligente** des ports libres disponibles
- **Scan des conteneurs Docker** existants pour éviter les conflits
- **Plages de ports sécurisées** selon les bonnes pratiques

### 👥 Gestion avancée des permissions
- **Auto-détection UID/GID** sur Linux/macOS pour éviter les problèmes de droits
- **Permissions adaptatives** selon l'environnement (dev/prod/test)
- **Configuration sécurisée** en production

### 🏗️ Architecture multi-stage
- **Build multi-environnement** avec optimisations spécifiques
- **Images légères** pour la production (sans Node.js/npm)
- **Configuration adaptée** à chaque contexte d'usage

## 🚀 Démarrage rapide

### 1. Prérequis
```bash
# Vérifier Docker et Docker Compose
docker --version
docker compose version
```

### 2. Configuration initiale
```bash
# Cloner et configurer
cd votre-projet
cp .env.local.example .env.local

# Éditer la configuration
nano .env.local
```

### 3. Lancement avec ports automatiques
```bash
# Démarrage en développement avec auto-configuration
./deploy.sh dev --auto-ports

# Ou démarrage simple
./deploy.sh dev
```

### 4. Accès aux services
Les URLs d'accès sont affichées après le déploiement :
- **📱 Application** : http://localhost:8080 (port auto-détecté)
- **🗃️ phpMyAdmin** : http://localhost:8200
- **📧 MailHog** : http://localhost:8300
- **⚡ Mercure Hub** : http://localhost:3002

**Authentification** : `krystdev` / `dev123`

## 📚 Documentation

### 📖 Guides d'utilisation
- **[📦 Installation complète](docs/INSTALLATION.md)** - Guide d'installation détaillé
- **[⚙️ Configuration](docs/CONFIGURATION.md)** - Personnalisation et variables d'environnement
- **[🛠️ Utilisation quotidienne](docs/USAGE.md)** - Commandes et workflows de développement
- **[🚀 Déploiement](docs/DEPLOYMENT.md)** - Guide de déploiement en production
- **[🔍 Outils de Qualité](docs/QUALITY_TOOLS.md)** - PHPStan et PHP-CS-Fixer

### 🔧 Documentation technique
- **[🏗️ Architecture](docs/ARCHITECTURE.md)** - Structure et composants de la stack
- **[🐳 Services Docker](docs/SERVICES.md)** - Détail de chaque service et configuration
- **[🔐 Sécurité](docs/SECURITY.md)** - Bonnes pratiques et configuration sécurisée
- **[🆘 Dépannage](docs/TROUBLESHOOTING.md)** - Solutions aux problèmes courants

### 📋 Références
- **[📝 Variables d'environnement](docs/ENVIRONMENT_VARIABLES.md)** - Liste complète des variables
- **[🔧 Script de déploiement](docs/DEPLOY_SCRIPT.md)** - Documentation du script deploy.sh
- **[📊 Performances](docs/PERFORMANCE.md)** - Optimisations et monitoring

## 🛠️ Commandes principales

```bash
# Développement
./deploy.sh dev                    # Démarrage développement
./deploy.sh dev --auto-ports       # Avec ports automatiques
./deploy.sh dev --build            # Avec reconstruction

# Gestion des services
./deploy.sh --status               # Statut des services
./deploy.sh --logs                 # Voir les logs en temps réel
./deploy.sh --down                 # Arrêter tous les services

# Production
./deploy.sh prod --build           # Déploiement production
```

## 🏗️ Structure du projet

```
docker_example/
├── 📄 README.md                    # Ce fichier
├── 🚀 deploy.sh                    # Script de déploiement intelligent
├── 🐳 Dockerfile                   # Multi-stage (dev/prod)
├── 📝 docker-compose.yml           # Configuration de base
├── 📝 docker-compose.override.yml  # Overrides développement
├── 📁 docker/                      # Configuration Docker
│   ├── 🔧 entrypoint.sh           # Script d'entrée intelligent
│   ├── ⚙️ Caddyfile.dev           # Configuration Caddy développement
│   ├── 🐘 php.ini.dev             # Configuration PHP développement
│   └── 🐘 php.ini.prod            # Configuration PHP production
├── 📁 app/                         # Code source Symfony
├── 📁 docs/                        # Documentation détaillée
└── 📋 .env.local.example           # Variables d'environnement exemple
```

## 🎯 Cas d'usage

### 👨‍💻 Développeur
```bash
# Démarrage rapide avec ports automatiques
./deploy.sh dev --auto-ports

# Développement avec logs en direct
./deploy.sh dev && ./deploy.sh --logs
```

### 🧪 Tests
```bash
# Environnement de test isolé
./deploy.sh test

# Test avec reconstruction
./deploy.sh test --build
```

### 🚀 Production
```bash
# Déploiement production sécurisé
./deploy.sh prod --build
```

## 🤝 Contribution

1. Consultez la [documentation d'architecture](docs/ARCHITECTURE.md)
2. Respectez les [bonnes pratiques de sécurité](docs/SECURITY.md)
3. Testez vos modifications avec `./deploy.sh test`

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🆘 Support

- **Documentation** : Consultez le dossier `docs/`
- **Issues** : [Guide de dépannage](docs/TROUBLESHOOTING.md)
- **Configuration** : [Variables d'environnement](docs/ENVIRONMENT_VARIABLES.md)

---

**Développé avec ❤️ pour la communauté Symfony**