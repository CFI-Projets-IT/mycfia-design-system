# Firecrawl Self-Hosted - Guide d'Installation et Utilisation myCfia

**Version** : 1.1.0
**Date** : 2025-12-18
**Bundle** : gorillias/marketing-ai-bundle v3.42.0+

---

## 📋 Table des Matières

1. [Vue d'Ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Déploiement](#déploiement)
6. [Tests et Validation](#tests-et-validation)
7. [Utilisation](#utilisation)
8. [Monitoring](#monitoring)
9. [Troubleshooting](#troubleshooting)
10. [Rollback vers Cloud](#rollback-vers-cloud)

---

## Vue d'Ensemble

Firecrawl self-hosted permet de scraper des pages web **localement** sans passer par le SaaS Firecrawl Cloud, offrant :

### Avantages
- ✅ **Coûts fixes** : Pas de facturation au scrape
- ✅ **Données internes** : Scraping sans appel externe
- ✅ **Contrôle total** : Configuration et scaling maîtrisés
- ✅ **Performance** : Communication interne Docker (app_network)

### Inconvénients
- ❌ **Fire-engine absent** : Formats avancés non disponibles (voir limitation ci-dessous)
- ❌ **Maintenance** : Mise à jour des images Docker nécessaire
- ❌ **Ressources** : +4-8 GB RAM, +2-4 CPU cores

### ⚠️ Limitation Critique : Fire-Engine Non Disponible

**Fire-engine** est le moteur Chrome CDP propriétaire de Firecrawl qui gère :
- Format `branding` (extraction palette couleurs, typographie, logo)
- Format `screenshot` (capture d'écran base64)
- Anti-bot avancé (Cloudflare, Akamai, DataDome)

**Status** : Fire-engine n'est **PAS disponible en self-hosted** (limitation officielle Firecrawl).

#### Formats Supportés en Self-Hosted

| Format | Self-Hosted (Playwright) | Cloud (Fire-engine) |
|--------|-------------------------|---------------------|
| `markdown` | ✅ Supporté | ✅ Supporté |
| `html` | ✅ Supporté | ✅ Supporté |
| `links` | ✅ Supporté | ✅ Supporté |
| `branding` | ❌ **Erreur HTTP 500** | ✅ Supporté |
| `screenshot` | ❌ **Erreur HTTP 500** | ✅ Supporté |

#### Impact sur myCfia

Le bundle **gorillias/marketing-ai-bundle v3.42.0+** nécessite le format `branding` pour :
- **BrandStyleAnalyzerTool** : Extraction palette couleurs client
- **ProjectEnrichmentAgent** : Analyse identité visuelle

**Erreur rencontrée en self-hosted** :
```
Error: Branding extraction requires Chrome CDP (fire-engine).
```

#### Décision Projet

**Status actuel** : ✅ **Firecrawl Cloud activé** (`marketing.yaml`)

Raison : Le format `branding` est essentiel pour l'analyse marketing et nécessite fire-engine.

**Coûts optimisés** avec bundle v3.42.0 :
- Avant : 162 crédits/campagne
- Après : **38 crédits/campagne** (-77%)
- Économie : 128 crédits grâce à la désactivation de `extractor` (redondant)

---

## Architecture

Firecrawl self-hosted se compose de **4 services Docker** intégrés dans le réseau `app_network` de myCfia :

```
┌─────────────────────────────────────────────────────────────┐
│                  myCfia_app_network (bridge)                 │
│                                                              │
│  ┌──────────────┐    ┌────────────────┐    ┌─────────────┐ │
│  │  FrankenPHP  │───▶│  Firecrawl API │───▶│ Firecrawl   │ │
│  │  (myCfia)    │    │  (Port 3002)   │    │ Redis       │ │
│  └──────────────┘    └────────────────┘    └─────────────┘ │
│         │                     │                     ▲       │
│         │                     ▼                     │       │
│         ▼              ┌────────────────┐          │       │
│  ┌──────────────┐     │  Playwright    │──────────┘       │
│  │  ChromaDB    │     │  Service       │                   │
│  │  (Existing)  │     └────────────────┘                   │
│  └──────────────┘              │                           │
│                                 ▼                           │
│                         ┌────────────────┐                 │
│                         │  Firecrawl     │                 │
│                         │  Worker        │                 │
│                         └────────────────┘                 │
└──────────────────────────────────────────────────────────────┘
```

### Services

1. **myCfia_firecrawl_redis** : Cache et queues (Redis 7 Alpine)
2. **myCfia_firecrawl_playwright** : Scraping navigateur (Browserless Chrome)
3. **myCfia_firecrawl_api** : API REST principale (Node.js, port 3002)
4. **myCfia_firecrawl_worker** : Workers traitement async (Node.js)

---

## Installation

### Prérequis

- Docker 20.10+
- Docker Compose 2.0+
- **RAM minimum** : 12 GB (8 GB myCfia + 4 GB Firecrawl)
- **CPU minimum** : 6 cores (4 cores myCfia + 2 cores Firecrawl)

### Fichiers Créés

Les fichiers suivants ont été ajoutés au projet :

- `/docker-compose.firecrawl.yml` : Configuration des 4 services Firecrawl
- `/.env` : Variables FIRECRAWL_PORT, FIRECRAWL_BULL_AUTH_KEY
- `/app/config/packages/marketing.yaml` : Configuration bundle marketing-ai
- `/docs/FIRECRAWL_SELF_HOSTED.md` : Ce fichier

---

## Configuration

### Variables d'Environnement

**Fichier** : `/.env` (versionné)

```bash
# === FIRECRAWL SELF-HOSTED ===
FIRECRAWL_PORT=3002
FIRECRAWL_BULL_AUTH_KEY=changeme_production

# Optionnel : API Key OpenAI pour extraction structurée LLM
# OPENAI_API_KEY=sk-proj-...
```

**Fichier** : `/.env.local` (local, non versionné)

```bash
# Firecrawl Self-Hosted
FIRECRAWL_PORT=3002
FIRECRAWL_BULL_AUTH_KEY=dev_secret_key_12345

# Si extraction structurée nécessaire (optionnel)
# OPENAI_API_KEY=sk-proj-votre-clé-réelle
```

### Configuration Bundle Marketing-AI

**Fichier** : `/app/config/packages/marketing.yaml`

```yaml
marketing:
    firecrawl:
        base_url: 'http://firecrawl-api:3002'  # Service Docker interne
        api_key: null                           # Pas d'auth en self-hosted
```

---

## Déploiement

### 1. Démarrer les Services Principaux (si nécessaire)

```bash
./deploy.sh dev
```

### 2. Démarrer Firecrawl

```bash
docker compose -f docker-compose.firecrawl.yml up -d
```

### 3. Vérifier les Services

```bash
docker compose -f docker-compose.firecrawl.yml ps
```

**Output attendu** :
```
NAME                          STATUS          PORTS
myCfia_firecrawl_redis        Up (healthy)
myCfia_firecrawl_playwright   Up (healthy)
myCfia_firecrawl_api          Up (healthy)    0.0.0.0:3002->3002/tcp
myCfia_firecrawl_worker       Up
```

### 4. Vérifier les Logs

```bash
docker logs myCfia_firecrawl_api --tail 50
```

**Output attendu** : Pas d'erreurs, API démarrée sur port 3002

---

## Tests et Validation

### Test 1 : API Firecrawl Directe

```bash
curl -X POST http://localhost:3002/v1/scrape \
  -H 'Content-Type: application/json' \
  -d '{"url": "https://example.com", "formats": ["markdown"]}'
```

**Output attendu** :
```json
{
  "success": true,
  "data": {
    "markdown": "# Example Domain\n\nThis domain is for use in...",
    "metadata": {...}
  }
}
```

### Test 2 : Configuration Bundle

```bash
docker exec --user www-data myCfia_frankenphp php bin/console cache:clear
docker exec --user www-data myCfia_frankenphp php bin/console debug:container --parameter=marketing.firecrawl.base_url
```

**Output attendu** : `http://firecrawl-api:3002`

```bash
docker exec --user www-data myCfia_frankenphp php bin/console debug:container --parameter=marketing.firecrawl.mode
```

**Output attendu** : `self-hosted`

### Test 3 : Intégration Applicative

**Via l'interface myCfia**, déclencher :
- **Enrichissement projet via URL** (utilise BrandStyleAnalyzerTool + Firecrawl)
- **Analyse concurrents** (utilise CompetitorIntelligenceTool + Firecrawl)

**Vérifier les logs** :
```bash
docker exec --user www-data myCfia_frankenphp tail -f var/log/marketing/tools/brand_style.log
docker logs myCfia_firecrawl_api --tail 50
```

**Validation** : Aucune erreur, données scrapées présentes dans l'application

---

## Utilisation

### Admin Panel Firecrawl

**URL** : `http://localhost:3002/admin/{BULL_AUTH_KEY}`

Remplacez `{BULL_AUTH_KEY}` par la valeur de `FIRECRAWL_BULL_AUTH_KEY` (`.env.local`).

**Fonctionnalités** :
- Monitoring des jobs scraping en cours
- Visualisation des jobs échoués et retry
- Statistiques de performance des workers

### Workflow Marketing-AI Bundle

Les outils suivants utilisent automatiquement Firecrawl self-hosted :

1. **BrandStyleAnalyzerTool** : Scraping URL client pour extraction branding
2. **CompetitorIntelligenceTool** : Scraping concurrents pour analyse positionnement

**Aucun changement** dans l'utilisation de l'application, le basculement est transparent.

---

## Monitoring

### Vérifier la Santé des Services

```bash
docker compose -f docker-compose.firecrawl.yml ps
```

### Consommation Ressources

```bash
docker stats myCfia_firecrawl_api myCfia_firecrawl_worker myCfia_firecrawl_playwright myCfia_firecrawl_redis
```

**Valeurs normales** :
- **CPU** : 5-15% par service (idle), 50-80% (active scraping)
- **RAM** :
  - firecrawl-api : 200-400 MB
  - firecrawl-worker : 200-400 MB
  - firecrawl-playwright : 500-1500 MB
  - firecrawl-redis : 10-50 MB

### Logs en Temps Réel

```bash
# API
docker logs -f myCfia_firecrawl_api

# Worker
docker logs -f myCfia_firecrawl_worker

# Playwright
docker logs -f myCfia_firecrawl_playwright
```

---

## Troubleshooting

### Erreur : Service non accessible

**Symptôme** :
```
Connection refused to http://firecrawl-api:3002
```

**Solution** :
```bash
# 1. Vérifier services Firecrawl
docker compose -f docker-compose.firecrawl.yml ps

# 2. Vérifier réseau Docker
docker network inspect myCfia_app_network | grep firecrawl

# 3. Redémarrer services
docker compose -f docker-compose.firecrawl.yml restart
```

### Erreur : Playwright timeout

**Symptôme** :
```
Playwright scraping failed: Timeout after 60s
```

**Solution** :
```bash
# Augmenter TIMEOUT dans docker-compose.firecrawl.yml
# Ligne 23 : TIMEOUT=120000 (120s)
docker compose -f docker-compose.firecrawl.yml up -d --force-recreate
```

### Erreur : RAM insuffisante

**Symptôme** :
```
Container killed (OOMKilled)
```

**Solution** :
```bash
# Réduire NUM_WORKERS_PER_QUEUE
# docker-compose.firecrawl.yml ligne 40 et 57 : NUM_WORKERS_PER_QUEUE=4
docker compose -f docker-compose.firecrawl.yml up -d --force-recreate
```

### Erreur : Port 3002 occupé

**Symptôme** :
```
Bind for 0.0.0.0:3002 failed: port is already allocated
```

**Solution** :
```bash
# Modifier FIRECRAWL_PORT dans .env.local
# FIRECRAWL_PORT=3003

# Relancer
docker compose -f docker-compose.firecrawl.yml down
docker compose -f docker-compose.firecrawl.yml up -d
```

### Erreur : Anti-bot détection (403 Forbidden)

**Symptôme** :
```
Firecrawl scraping failed: HTTP 403 Forbidden
```

**Cause** : Sites avec protection anti-bot avancée (Cloudflare, Akamai)

**Solutions** :
1. **Proxy externe** : Configurer un service proxy (BrightData, Oxylabs)
2. **Rollback cloud** : Utiliser Firecrawl Cloud avec Fire-engine (voir section suivante)

---

## Rollback vers Cloud

En cas de problème avec le self-hosted, rollback immédiat vers Firecrawl Cloud :

### 1. Modifier la Configuration Bundle

**Fichier** : `/app/config/packages/marketing.yaml`

```yaml
marketing:
    firecrawl:
        base_url: 'https://api.firecrawl.dev'  # ← Cloud
        api_key: '%env(FIRECRAWL_API_KEY)%'    # ← Réactiver
```

### 2. Vérifier la Clé API

**Fichier** : `/.env.local`

```bash
FIRECRAWL_API_KEY=fc-your-cloud-api-key
```

### 3. Vider le Cache

```bash
docker exec --user www-data myCfia_frankenphp php bin/console cache:clear
```

### 4. Tester

Déclencher un workflow via l'interface myCfia et vérifier que le scraping fonctionne.

### 5. Arrêter Firecrawl Self-Hosted (Optionnel)

```bash
docker compose -f docker-compose.firecrawl.yml down
```

---

## Ressources et Support

### Documentation Officielle
- **Firecrawl GitHub** : https://github.com/firecrawl/firecrawl
- **Self-hosting guide** : https://github.com/firecrawl/firecrawl/blob/main/SELF_HOST.md
- **API Reference** : https://docs.firecrawl.dev/api-reference

### Documentation Bundle
- **Bundle guide** : `vendor/gorillias/marketing-ai-bundle/docs/firecrawl-self-hosted.md`
- **CHANGELOG v2.3.0** : `vendor/gorillias/marketing-ai-bundle/CHANGELOG.md`

### Support
- **Discord Firecrawl** : https://discord.gg/gSmWdAkdwd
- **Issues GitHub** : https://github.com/firecrawl/firecrawl/issues

---

## Commandes Utiles

```bash
# Démarrer Firecrawl
docker compose -f docker-compose.firecrawl.yml up -d

# Arrêter Firecrawl
docker compose -f docker-compose.firecrawl.yml down

# Redémarrer un service spécifique
docker compose -f docker-compose.firecrawl.yml restart firecrawl-api

# Voir les logs
docker compose -f docker-compose.firecrawl.yml logs -f

# Vérifier l'état
docker compose -f docker-compose.firecrawl.yml ps

# Statistiques ressources
docker stats myCfia_firecrawl_api myCfia_firecrawl_worker

# Rebuild après modification
docker compose -f docker-compose.firecrawl.yml up -d --build
```

---

## Cas d'Usage Recommandés

### ✅ Utiliser Self-Hosted Pour

**Scraping à volume élevé sans besoin de formats avancés** :
- Analyse concurrentielle (format `markdown` uniquement)
- Extraction de contenu textuel massif
- Monitoring de sites web internes
- Tests et développement locaux

**Commande** :
```bash
./deploy.sh dev --firecrawl
```

### ❌ Ne PAS Utiliser Self-Hosted Pour

**Analyses marketing nécessitant l'identité visuelle** :
- Enrichissement projet avec URL client (format `branding` requis)
- Extraction palette de couleurs automatique
- Analyse concurrentielle visuelle
- Génération de campagnes avec branding

**Recommandation** : Utiliser **Firecrawl Cloud** avec bundle v3.42.0 optimisé (-77% coûts)

### 🔄 Stratégie Hybride Future

Si les coûts Cloud augmentent significativement :
1. **Cloud** : Homepage client uniquement (1 crédit + branding)
2. **Self-Hosted** : 31 concurrents (31 crédits, markdown seul)
3. **Économie potentielle** : ~50% vs 100% Cloud

---

**Auteur** : Équipe Technique myCfia
**Dernière mise à jour** : 2025-12-18
**Version** : 1.1.0
