# Configuration des Logs - myCfia

## 📋 Organisation des Logs par Canal

Le projet utilise **Monolog** avec des **canaux (channels)** pour séparer les logs par domaine fonctionnel.

### Structure des Fichiers de Log

```
var/log/
├── dev.log              # Tous les logs génériques (fallback)
├── auth.log             # Authentification CFI uniquement
├── chat.log             # Chat IA et streaming uniquement
├── cfi_api.log          # Appels API CFI uniquement
└── messenger.log        # Messages async Symfony Messenger
```

### Canaux Disponibles

| Canal | Description | Fichier | Services Concernés |
|-------|-------------|---------|-------------------|
| `auth` | Authentification CFI | `auth.log` | CfiAuthenticator, CfiAuthService, UserAuthenticationService |
| `chat` | Chat IA et streaming | `chat.log` | ChatService, ChatController, ChatStreamMessageHandler |
| `cfi_api` | Appels API CFI | `cfi_api.log` | CfiApiService, CfiTenantService, CfiSessionService |
| `messenger` | Messages async | `messenger.log` | Symfony Messenger (système) |
| `app` | Application générale | `dev.log` | Tous les autres services |

## 🔧 Configuration Monolog

**Fichier** : `config/packages/monolog.yaml`

```yaml
monolog:
    channels:
        - deprecation
        - auth        # Authentification CFI
        - chat        # Chat IA et streaming
        - cfi_api     # Appels API CFI
        - messenger   # Messages async

when@dev:
    monolog:
        handlers:
            # Fichier principal (exclut les channels spécifiques)
            main:
                type: stream
                path: "%kernel.logs_dir%/%kernel.environment%.log"
                level: debug
                channels: ["!event", "!auth", "!chat", "!cfi_api", "!messenger"]

            # Logs d'authentification CFI
            auth:
                type: stream
                path: "%kernel.logs_dir%/auth.log"
                level: info
                channels: ["auth"]

            # Logs du chat IA et streaming
            chat:
                type: stream
                path: "%kernel.logs_dir%/chat.log"
                level: debug
                channels: ["chat"]

            # Logs des appels API CFI
            cfi_api:
                type: stream
                path: "%kernel.logs_dir%/cfi_api.log"
                level: debug
                channels: ["cfi_api"]

            # Logs Messenger (messages async)
            messenger:
                type: stream
                path: "%kernel.logs_dir%/messenger.log"
                level: info
                channels: ["messenger"]
```

## 💡 Utilisation dans les Services

### Pattern d'Injection du Logger

Pour utiliser un canal spécifique, utilisez l'attribut `#[Autowire]` :

```php
<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class MonService
{
    public function __construct(
        // Utiliser le canal 'chat' pour ce service
        #[Autowire(service: 'monolog.logger.chat')]
        private readonly LoggerInterface $logger,
    ) {
    }

    public function doSomething(): void
    {
        // Les logs iront dans var/log/chat.log
        $this->logger->info('Message de log', ['context' => 'valeur']);
    }
}
```

### Services Modifiés

✅ **Canal `auth`**
- `CfiAuthenticator` - app/src/Security/CfiAuthenticator.php:63
- `CfiAuthService` - app/src/Service/Cfi/CfiAuthService.php:28
- `CfiUserProvider` - app/src/Security/CfiUserProvider.php:25

✅ **Canal `chat`**
- `ChatStreamMessageHandler` - app/src/MessageHandler/ChatStreamMessageHandler.php:64
- `ChatService` - app/src/Service/ChatService.php:58
- `ChatStreamPublisher` - app/src/Service/ChatStreamPublisher.php:32

✅ **Canal `cfi_api`**
- `CfiApiService` - app/src/Service/Cfi/CfiApiService.php:35
- `CfiTenantService` - app/src/Service/Cfi/CfiTenantService.php:23

## 📊 Consultation des Logs

### Logs en Temps Réel

```bash
# Suivre les logs d'authentification
docker compose exec frankenphp tail -f /var/www/html/var/log/auth.log

# Suivre les logs du chat
docker compose exec frankenphp tail -f /var/www/html/var/log/chat.log

# Suivre les logs API CFI
docker compose exec frankenphp tail -f /var/www/html/var/log/cfi_api.log

# Suivre tous les logs génériques
docker compose exec frankenphp tail -f /var/www/html/var/log/dev.log
```

### Recherche dans les Logs

```bash
# Rechercher les erreurs d'authentification
docker compose exec frankenphp grep "ERROR" /var/www/html/var/log/auth.log

# Rechercher les appels API CFI échoués
docker compose exec frankenphp grep "CFI API Failed" /var/www/html/var/log/cfi_api.log

# Compter les tentatives de connexion aujourd'hui
docker compose exec frankenphp grep "$(date +%Y-%m-%d)" /var/www/html/var/log/auth.log | wc -l
```

### Analyse des Logs d'Authentification

```bash
# Voir uniquement les échecs d'authentification
docker compose exec frankenphp grep "Authentication Failed" /var/www/html/var/log/auth.log

# Voir les tentatives de connexion par utilisateur
docker compose exec frankenphp grep "identifiant" /var/www/html/var/log/auth.log | awk -F'"identifiant":"' '{print $2}' | cut -d'"' -f1 | sort | uniq -c
```

## 🎯 Avantages de cette Organisation

### 1. **Isolation des Logs**
- Chaque domaine métier a son fichier dédié
- Facilite le debugging ciblé
- Réduit le bruit dans les logs

### 2. **Performance**
- Moins de données à parser pour trouver une info spécifique
- Rotation des logs plus efficace par domaine

### 3. **Sécurité**
- Possibilité de restreindre l'accès aux logs sensibles (auth.log)
- Audits d'authentification simplifiés

### 4. **Monitoring**
- Intégration facilitée avec des outils de monitoring (ELK, Grafana)
- Alertes spécifiques par canal possible

## 🔄 Extension Future

Pour ajouter un nouveau canal :

1. **Déclarer le canal** dans `config/packages/monolog.yaml` :
   ```yaml
   monolog:
       channels:
           - mon_nouveau_canal
   ```

2. **Créer le handler** dans la section `when@dev` :
   ```yaml
   mon_nouveau_canal:
       type: stream
       path: "%kernel.logs_dir%/mon_nouveau_canal.log"
       level: debug
       channels: ["mon_nouveau_canal"]
   ```

3. **Exclure du main handler** :
   ```yaml
   main:
       channels: ["!event", "!auth", "!chat", "!cfi_api", "!messenger", "!mon_nouveau_canal"]
   ```

4. **Utiliser dans les services** :
   ```php
   #[Autowire(service: 'monolog.logger.mon_nouveau_canal')]
   private readonly LoggerInterface $logger,
   ```

---

**Dernière mise à jour** : 2025-10-15
**Auteur** : Claude Code - Optimisation des logs par canaux
