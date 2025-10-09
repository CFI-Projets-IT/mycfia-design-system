# 🤖 Installation et configuration de Symfony AI Bundle

Guide complet pour installer et configurer **Symfony AI Bundle** dans le projet GoldMind.

## 📋 Prérequis

- Projet Symfony 7.3 opérationnel
- Environnement Docker démarré (`./deploy.sh dev --auto-ports`)
- Composer fonctionnel dans le conteneur
- Accès à une API AI (OpenAI, Anthropic, Gemini, etc.)

## 🚀 Installation

### Étape 1 : Modifier composer.json

Le bundle Symfony AI est actuellement en développement actif, il faut donc autoriser les paquets `dev`.

```bash
# Éditer app/composer.json et changer :
"minimum-stability": "stable"

# en :
"minimum-stability": "dev"
```

### Étape 2 : Installer le bundle

```bash
# Avec le script helper (recommandé)
./scripts/symfony.sh composer require symfony/ai-bundle

# Ou manuellement
docker compose exec --user www-data frankenphp composer require symfony/ai-bundle
```

**Paquets installés** :
- `symfony/ai-bundle` (dev-main) - Bundle principal
- `symfony/ai-platform` (dev-main) - Intégration des plateformes AI
- `symfony/ai-agent` (dev-main) - Système d'agents
- `symfony/ai-store` (dev-main) - Stockage de documents pour RAG
- `symfony/uid` - Gestion des identifiants uniques
- `oskarstark/enum-helper` - Helper pour les énumérations

### Étape 3 : Vérification de l'installation

```bash
# Vérifier que le bundle est enregistré
cat app/config/bundles.php | grep AiBundle
# Résultat attendu : Symfony\AI\AiBundle\AiBundle::class => ['all' => true]

# Lister les commandes AI disponibles
./scripts/symfony.sh console list ai
```

**Commandes disponibles** :
- `ai:agent:call` / `ai:chat` - Appeler un agent
- `ai:store:index` - Indexer des documents dans un store

## ⚙️ Configuration

### Configuration minimale (OpenAI)

Créer le fichier `app/config/packages/ai.yaml` :

```yaml
# config/packages/ai.yaml
ai:
    platform:
        openai:
            api_key: '%env(OPENAI_API_KEY)%'
    agent:
        default:
            model:
                class: 'Symfony\AI\Platform\Bridge\OpenAi\Gpt'
                name: !php/const Symfony\AI\Platform\Bridge\OpenAi\Gpt::GPT_4O_MINI
```

### Configuration des variables d'environnement

Ajouter dans `app/.env.local` :

```env
###> symfony/ai-bundle ###
OPENAI_API_KEY=sk-your-api-key-here
###< symfony/ai-bundle ###
```

### Configuration avancée (Multi-providers)

Pour utiliser plusieurs fournisseurs AI simultanément :

```yaml
# config/packages/ai.yaml
ai:
    platform:
        # OpenAI
        openai:
            api_key: '%env(OPENAI_API_KEY)%'

        # Anthropic Claude
        anthropic:
            api_key: '%env(ANTHROPIC_API_KEY)%'

        # Google Gemini
        gemini:
            api_key: '%env(GEMINI_API_KEY)%'

        # Ollama (local)
        ollama:
            host_url: '%env(OLLAMA_HOST_URL)%' # Ex: http://localhost:11434

        # Azure OpenAI
        azure:
            gpt_deployment:
                base_url: '%env(AZURE_OPENAI_BASEURL)%'
                deployment: '%env(AZURE_OPENAI_GPT)%'
                api_key: '%env(AZURE_OPENAI_KEY)%'
                api_version: '%env(AZURE_GPT_VERSION)%'

    agent:
        # Agent principal avec GPT-4
        default:
            platform: 'ai.platform.openai'
            model:
                class: 'Symfony\AI\Platform\Bridge\OpenAi\Gpt'
                name: !php/const Symfony\AI\Platform\Bridge\OpenAi\Gpt::GPT_4O_MINI
            system_prompt: 'You are a helpful assistant for the GoldMind application.'
            track_token_usage: true

        # Agent de recherche avec Claude
        research:
            platform: 'ai.platform.anthropic'
            model:
                class: 'Symfony\AI\Platform\Bridge\Anthropic\Claude'
                name: !php/const Symfony\AI\Platform\Bridge\Anthropic\Claude::SONNET_37
            tools:
                - 'Symfony\AI\Agent\Toolbox\Tool\Wikipedia'

        # Agent RAG avec recherche de similarité
        rag:
            platform: 'ai.platform.openai'
            structured_output: false
            track_token_usage: true
            model:
                class: 'Symfony\AI\Platform\Bridge\OpenAi\Gpt'
                name: !php/const Symfony\AI\Platform\Bridge\OpenAi\Gpt::GPT_4O_MINI
            system_prompt: 'You can answer questions using the knowledge base.'
            include_tools: true
            tools:
                - 'Symfony\AI\Agent\Toolbox\Tool\SimilaritySearch'
```

Variables d'environnement correspondantes (`app/.env.local`) :

```env
###> symfony/ai-bundle ###
# OpenAI
OPENAI_API_KEY=sk-your-openai-key

# Anthropic Claude
ANTHROPIC_API_KEY=sk-ant-your-anthropic-key

# Google Gemini
GEMINI_API_KEY=your-gemini-key

# Ollama (local)
OLLAMA_HOST_URL=http://localhost:11434

# Azure OpenAI (optionnel)
AZURE_OPENAI_BASEURL=https://your-resource.openai.azure.com
AZURE_OPENAI_GPT=gpt-4o-mini
AZURE_OPENAI_KEY=your-azure-key
AZURE_GPT_VERSION=2024-08-01-preview
###< symfony/ai-bundle ###
```

## 🔧 Utilisation

### Via ligne de commande

```bash
# Appeler l'agent par défaut
./scripts/symfony.sh console ai:chat "Quelle est la capitale de la France ?"

# Appeler un agent spécifique
./scripts/symfony.sh console ai:agent:call research "Tell me about Symfony framework"
```

### Dans un contrôleur

```php
<?php
// src/Controller/AiController.php

namespace App\Controller;

use Symfony\AI\Agent\AgentInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AiController extends AbstractController
{
    #[Route('/ai/chat', name: 'app_ai_chat', methods: ['POST'])]
    public function chat(Request $request, AgentInterface $agent): JsonResponse
    {
        $message = $request->request->get('message');

        $response = $agent->run($message);

        return $this->json([
            'response' => $response->getOutput(),
            'model' => $response->getModel(),
            'usage' => $response->getUsage(),
        ]);
    }
}
```

### Avec injection d'agent spécifique

```php
<?php
// src/Service/AiAssistantService.php

namespace App\Service;

use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

class AiAssistantService
{
    public function __construct(
        #[Target('default')]
        private AgentInterface $defaultAgent,

        #[Target('research')]
        private AgentInterface $researchAgent,
    ) {
    }

    public function askQuestion(string $question): string
    {
        $response = $this->defaultAgent->run($question);
        return $response->getOutput();
    }

    public function research(string $topic): string
    {
        $response = $this->researchAgent->run("Research about: $topic");
        return $response->getOutput();
    }
}
```

## 🛠️ Fonctionnalités avancées

### RAG (Retrieval Augmented Generation)

Indexer des documents pour la recherche sémantique :

```bash
# Indexer un répertoire de documents
./scripts/symfony.sh console ai:store:index /path/to/documents

# Indexer un fichier spécifique
./scripts/symfony.sh console ai:store:index /path/to/file.txt
```

Configuration du store :

```yaml
# config/packages/ai.yaml
ai:
    store:
        default:
            platform: 'ai.platform.openai'
            embedding_model:
                class: 'Symfony\AI\Platform\Bridge\OpenAi\Embedding'
                name: 'text-embedding-3-small'
            storage: 'doctrine' # ou 'mongodb', 'filesystem'
```

### Outils personnalisés (Tools)

Créer un outil pour l'agent :

```php
<?php
// src/Agent/Tool/DatabaseSearch.php

namespace App\Agent\Tool;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool(
    name: 'database_search',
    description: 'Search for users in the database by name or email'
)]
class DatabaseSearch
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(string $query): array
    {
        $users = $this->userRepository->search($query);

        return array_map(fn($user) => [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
        ], $users);
    }
}
```

Enregistrer l'outil dans la configuration :

```yaml
# config/packages/ai.yaml
ai:
    agent:
        default:
            tools:
                - 'App\Agent\Tool\DatabaseSearch'
```

### Streaming (réponses en temps réel)

```php
<?php

use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/ai/stream', name: 'app_ai_stream')]
public function stream(Request $request, AgentInterface $agent): StreamedResponse
{
    $message = $request->request->get('message');

    return new StreamedResponse(function() use ($agent, $message) {
        $stream = $agent->stream($message);

        foreach ($stream as $chunk) {
            echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
            flush();
        }
    });
}
```

## 📚 Ressources

### Documentation locale

```bash
# Documentation Symfony AI disponible localement
~/.claude/mcp/context7/vendors/symfony-ai-docs/
```

Contenu disponible :
- `agent/` - Documentation sur les agents
- `platform/` - Intégration des plateformes AI
- `store/` - Système de stockage pour RAG
- `aiBundle/` - Configuration du bundle

### Documentation officielle

- [Symfony AI Bundle](https://github.com/symfony/ai-bundle)
- [Symfony AI Platform](https://github.com/symfony/ai-platform)
- [Symfony AI Agent](https://github.com/symfony/ai-agent)
- [Symfony AI Store](https://github.com/symfony/ai-store)

### Modèles supportés

#### OpenAI
- GPT-4o, GPT-4o-mini
- GPT-4 Turbo, GPT-3.5 Turbo
- text-embedding-3-small, text-embedding-3-large

#### Anthropic
- Claude 3.7 Sonnet
- Claude 3.5 Sonnet, Claude 3 Opus
- Claude 3 Haiku

#### Google
- Gemini 1.5 Pro, Gemini 1.5 Flash
- Gemini 2.0 Flash

#### Autres
- Azure OpenAI
- Ollama (local)
- Vertex AI
- ElevenLabs (text-to-speech)

## 🧪 Tests

### Tester l'installation

```bash
# Test basique avec l'agent par défaut
./scripts/symfony.sh console ai:chat "Hello, how are you?"

# Vérifier les agents configurés
./scripts/symfony.sh console debug:container --tag=ai.agent
```

### Tests unitaires

```php
<?php
// tests/Service/AiAssistantServiceTest.php

namespace App\Tests\Service;

use App\Service\AiAssistantService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AiAssistantServiceTest extends KernelTestCase
{
    public function testAskQuestion(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $service = $container->get(AiAssistantService::class);
        $response = $service->askQuestion('What is 2+2?');

        $this->assertStringContainsString('4', $response);
    }
}
```

## ⚠️ Remarques importantes

### Coûts API
- ⚠️ Les appels aux API AI sont **payants**
- Surveillez votre usage avec `track_token_usage: true`
- Utilisez des modèles moins chers pour le développement (ex: GPT-4o-mini)
- Envisagez Ollama pour du développement local gratuit

### Sécurité
- ✅ **Ne jamais** committer les clés API dans Git
- ✅ Utiliser `.env.local` pour les clés API
- ✅ En production, utiliser des variables d'environnement système
- ✅ Limiter les outils (tools) accessibles aux agents
- ✅ Valider les entrées utilisateur avant de les passer aux agents

### Performance
- Les réponses streaming sont recommandées pour l'UX
- Mettre en cache les réponses fréquentes
- Limiter la taille du contexte pour réduire les coûts
- Utiliser des modèles adaptés à la tâche (mini pour des tâches simples)

## 🔄 Workflow recommandé

### Développement

1. **Démarrer l'environnement**
   ```bash
   ./deploy.sh dev --auto-ports
   ```

2. **Configurer les clés API**
   ```bash
   # Éditer app/.env.local
   nano app/.env.local
   ```

3. **Tester la configuration**
   ```bash
   ./scripts/symfony.sh console ai:chat "Test message"
   ```

4. **Créer un agent personnalisé**
   ```yaml
   # config/packages/ai.yaml
   ai:
       agent:
           custom:
               model: { class: '...', name: '...' }
               system_prompt: 'Your custom prompt'
   ```

5. **Développer les outils**
   ```php
   // src/Agent/Tool/YourTool.php
   #[AsTool(name: 'tool_name', description: '...')]
   class YourTool { ... }
   ```

### Production

1. **Sécuriser les clés API**
   ```bash
   # Variables d'environnement système (pas de fichier .env.local)
   export OPENAI_API_KEY=sk-...
   ```

2. **Optimiser la configuration**
   ```yaml
   ai:
       agent:
           default:
               track_token_usage: true
               structured_output: true
               fault_tolerant_toolbox: true
   ```

3. **Monitoring**
   - Surveiller l'usage des tokens
   - Logger les erreurs
   - Mettre en place des alertes sur les coûts

## 🎯 Bonnes pratiques

- ✅ Commencer par un agent simple (OpenAI GPT-4o-mini)
- ✅ Tester en local avec Ollama avant d'utiliser des API payantes
- ✅ Utiliser des system prompts clairs et précis
- ✅ Limiter les outils aux besoins réels de l'application
- ✅ Implémenter le streaming pour une meilleure UX
- ✅ Monitorer l'usage et les coûts
- ✅ Valider et nettoyer les entrées utilisateur
- ✅ Gérer les erreurs API gracieusement
- ✅ Utiliser le cache pour les requêtes répétitives
- ✅ Consulter la documentation locale via Context7
