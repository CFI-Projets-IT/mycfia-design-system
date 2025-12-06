# TODO Configuration Preprod - À finaliser

## 🚨 IMPORTANT - Configuration Grafana HTTPS

### Problème actuel
- **Grafana accessible uniquement via IP** : http://51.210.159.194:3000
- **Pas de HTTPS** pour Grafana
- **Pas de nom de domaine** pour Grafana

### Solution à mettre en place

#### Étape 1 : Demander au client
**DEMANDER AU CLIENT** de configurer un sous-domaine DNS :
```
grafana.my-cfia.com → Pointe vers 51.210.159.194
```

#### Étape 2 : Ajouter dans Caddyfile.preprod
Une fois le sous-domaine configuré, ajouter ce bloc dans `docker/Caddyfile.preprod` :

```caddy
# Grafana - Monitoring (sous-domaine dédié)
grafana.my-cfia.com {
    reverse_proxy grafana:3000 {
        header_up Host {host}
        header_up X-Forwarded-For {remote}
        header_up X-Forwarded-Proto {scheme}
    }

    log {
        output stdout
        format json
    }
}

# Redirection HTTP → HTTPS pour Grafana
http://grafana.my-cfia.com {
    redir https://{host}{uri} permanent
}
```

#### Étape 3 : Mettre à jour GF_SERVER_ROOT_URL
Dans `docker-compose.monitoring.yml`, modifier la variable d'environnement Grafana :

```yaml
- GF_SERVER_ROOT_URL=https://grafana.my-cfia.com
```

#### Étape 4 : Redéployer
```bash
cd /opt/mycfia-preprod
./deploy.sh preprod --monitoring
```

#### Résultat attendu
- ✅ Accès via : https://grafana.my-cfia.com
- ✅ Certificat SSL Let's Encrypt automatique
- ✅ Redirection HTTP → HTTPS automatique

---

## 🐛 BUG - Symfony apparaît "down" dans Grafana

### Problème
Dans le dashboard Grafana, la carte "Application" montre Symfony comme "down".

### Cause
Le fichier `docker/prometheus/prometheus.yml` ligne 36 essaie de contacter Symfony sur le **mauvais port** :

```yaml
# ❌ INCORRECT (port 82 pour dev local)
- targets: ['frankenphp:82']
```

En preprod, FrankenPHP écoute sur le port **80** (pas 82).

### Solution
Modifier `docker/prometheus/prometheus.yml` ligne 36 :

```yaml
# ✅ CORRECT (port 80 pour preprod)
- targets: ['frankenphp:80']
```

Puis redéployer :
```bash
cd /opt/mycfia-preprod
./deploy.sh preprod --monitoring
```

---

## 📝 Autres configurations possibles (optionnel)

### Portainer
Si vous souhaitez exposer Portainer en HTTPS :

**Sous-domaine client** : `portainer.my-cfia.com → 51.210.159.194`

**Bloc Caddyfile** :
```caddy
portainer.my-cfia.com {
    reverse_proxy localhost:9443 {
        transport http {
            tls_insecure_skip_verify
        }
    }
}
```

---

## 📅 Historique
- **2025-12-06** : Configuration HTTPS multi-domaines terminée (my-cfia.com principal)
- **2025-12-06** : Grafana identifié comme nécessitant sous-domaine HTTPS
- **2025-12-06** : Bug Prometheus port 82 → 80 identifié