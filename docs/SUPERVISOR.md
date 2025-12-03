# Supervisor - Gestion du Worker Messenger

Ce document explique comment Supervisor gère le worker Messenger pour traiter les messages asynchrones (chat, personas, stratégie, assets).

## Architecture

**Supervisor** est un gestionnaire de processus qui :
- Lance automatiquement le worker Messenger au démarrage
- Surveille l'état du processus et le redémarre en cas de crash
- Capture et organise les logs
- Permet de contrôler le worker via des commandes

## Configuration

### Fichiers de configuration

1. **`docker/supervisor/supervisord.conf`** : Configuration principale de Supervisor
2. **`docker/supervisor/messenger-worker.conf`** : Configuration spécifique du worker Messenger

### Paramètres du worker

```ini
[program:messenger-worker]
command=php /var/www/html/bin/console messenger:consume async --time-limit=3600 --memory-limit=128M --sleep=1 -vv
numprocs=1              # Nombre de workers parallèles (augmenter si charge élevée)
autostart=true          # Démarrage automatique
autorestart=unexpected  # Redémarre uniquement si crash inattendu
startsecs=5             # Délai avant considérer le processus stable
startretries=10         # Nombre de tentatives de redémarrage
```

## Commandes Supervisor

### Se connecter au conteneur

```bash
docker exec -it myCfia_messenger_worker bash
```

### Commandes de base

```bash
# Voir l'état de tous les processus
supervisorctl status

# Démarrer le worker
supervisorctl start messenger-worker

# Arrêter le worker
supervisorctl stop messenger-worker

# Redémarrer le worker
supervisorctl restart messenger-worker

# Recharger la configuration (sans redémarrer)
supervisorctl reread
supervisorctl update

# Voir les logs en temps réel
supervisorctl tail -f messenger-worker

# Voir les logs d'erreurs
supervisorctl tail -f messenger-worker stderr
```

### Commandes avancées

```bash
# Arrêter tous les workers
supervisorctl stop all

# Redémarrer tous les workers
supervisorctl restart all

# Afficher les logs des 100 dernières lignes
supervisorctl tail -100 messenger-worker
```

## Logs

### Emplacements des logs

- **Logs worker** : `app/var/log/messenger/worker-00.log`
- **Logs erreurs** : `app/var/log/messenger/worker-00-error.log`
- **Logs Supervisor** : `app/var/log/supervisor/supervisord.log`

### Rotation automatique

Les logs sont automatiquement limités :
- Taille max par fichier : **10 MB**
- Nombre de backups : **3 fichiers**
- Après rotation : `worker-00.log.1`, `worker-00.log.2`, `worker-00.log.3`

### Consulter les logs depuis l'hôte

```bash
# Logs du worker
tail -f app/var/log/messenger/worker-00.log

# Logs d'erreurs
tail -f app/var/log/messenger/worker-00-error.log

# Logs Supervisor
tail -f app/var/log/supervisor/supervisord.log
```

## Monitoring

### Vérifier l'état du worker

```bash
# Depuis l'hôte
docker exec myCfia_messenger_worker supervisorctl status

# Sortie attendue :
# messenger-worker                 RUNNING   pid 123, uptime 0:05:30
```

### États possibles

- **RUNNING** : Worker actif ✅
- **STARTING** : Démarrage en cours ⏳
- **STOPPED** : Arrêté manuellement ⏸️
- **FATAL** : Échec du démarrage après 10 tentatives ❌
- **BACKOFF** : Redémarrage en cours après crash 🔄

### Vérifier la consommation des messages

```bash
# Voir le nombre de messages en queue
docker exec --user www-data myCfia_frankenphp php bin/console messenger:stats

# Sortie attendue :
# Transport   Count
# async       0
# failed      0
```

## Scalabilité

### Augmenter le nombre de workers

**Modifier** `docker/supervisor/messenger-worker.conf` :

```ini
[program:messenger-worker]
numprocs=3  # Passer de 1 à 3 workers
```

**Puis redémarrer** :

```bash
docker compose restart messenger_worker
```

Les workers seront nommés :
- `messenger-worker:messenger-worker_00`
- `messenger-worker:messenger-worker_01`
- `messenger-worker:messenger-worker_02`

### Logs multi-workers

Chaque worker aura ses propres logs :
- `app/var/log/messenger/worker-00.log`
- `app/var/log/messenger/worker-01.log`
- `app/var/log/messenger/worker-02.log`

## Dépannage

### Le worker ne démarre pas

1. **Vérifier les logs Supervisor** :
   ```bash
   docker exec myCfia_messenger_worker supervisorctl tail messenger-worker stderr
   ```

2. **Vérifier la connexion à MariaDB** :
   ```bash
   docker exec myCfia_messenger_worker php /var/www/html/bin/console dbal:run-sql "SELECT 1"
   ```

3. **Vérifier les permissions** :
   ```bash
   docker exec myCfia_messenger_worker ls -la /var/www/html/var/log/messenger
   ```

### Le worker crash en boucle (état BACKOFF)

1. **Augmenter `startsecs`** : Donner plus de temps au worker pour se stabiliser
2. **Augmenter `memory-limit`** : Le worker peut manquer de mémoire
3. **Vérifier les messages failed** :
   ```bash
   docker exec --user www-data myCfia_frankenphp php bin/console messenger:failed:show
   ```

### Messages non consommés

1. **Vérifier que le worker est RUNNING** :
   ```bash
   docker exec myCfia_messenger_worker supervisorctl status
   ```

2. **Vérifier la queue correcte** :
   ```bash
   # Le worker écoute "async", pas "default"
   docker exec myCfia_mariadb mariadb -u mycfia_user -p mycfia_db \
     -e "SELECT queue_name, COUNT(*) FROM messenger_messages GROUP BY queue_name;"
   ```

3. **Forcer la consommation manuelle** :
   ```bash
   docker exec --user www-data myCfia_frankenphp \
     php bin/console messenger:consume async --limit=10
   ```

## Différences avec l'ancienne configuration

### Avant (sans Supervisor)

```bash
# Commande directe dans docker-compose.yml
command: ["php", "/var/www/html/bin/console", "messenger:consume", "async", "--time-limit=3600", "-vv"]
```

**Limitations** :
- ❌ Pas de monitoring interne du processus
- ❌ Redémarrage basique (Docker seulement)
- ❌ Logs non structurés
- ❌ Impossible de scaler facilement

### Maintenant (avec Supervisor)

```bash
# Supervisor gère le worker
command: ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
```

**Avantages** :
- ✅ Monitoring actif du processus
- ✅ Redémarrage intelligent avec backoff
- ✅ Logs structurés et rotation automatique
- ✅ Contrôle via `supervisorctl`
- ✅ Scalabilité facile (multi-workers)
- ✅ Standard de production éprouvé

## Ressources

- [Documentation Supervisor](http://supervisord.org/index.html)
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [Worker Monitoring Best Practices](http://supervisord.org/running.html#supervisorctl-actions)
