# Pattern AssetPresenter - Documentation Technique

## Vue d'ensemble

Le pattern **AssetPresenter** sépare la logique de présentation des données d'assets de la logique de rendu Twig. Cette architecture améliore la maintenabilité, la testabilité et respecte le principe de responsabilité unique (SOLID).

## Architecture

### Composants principaux

#### 1. Interface `AssetPresenterInterface`

Contrat commun pour tous les presenters d'assets.

```php
namespace App\Service\Marketing\AssetPresenter\Interface;

interface AssetPresenterInterface
{
    public function supports(Asset $asset): bool;
    public function formatForDisplay(Asset $asset): array;
    public function getVariations(Asset $asset): array;
}
```

**Méthodes** :
- `supports(Asset $asset)` : Détermine si ce presenter supporte un type d'asset donné
- `formatForDisplay(Asset $asset)` : Formate les données pour l'affichage (contenu principal)
- `getVariations(Asset $asset)` : Formate les variations de l'asset

#### 2. Service Locator `AssetPresenterLocator`

Sélectionne automatiquement le bon presenter selon le type d'asset.

```php
final readonly class AssetPresenterLocator
{
    public function __construct(
        private iterable $presenters // Injection via tagged_iterator
    ) {}

    public function getPresenter(Asset $asset): AssetPresenterInterface
    {
        foreach ($this->presenters as $presenter) {
            if ($presenter->supports($asset)) {
                return $presenter;
            }
        }
        throw new \RuntimeException(...);
    }
}
```

#### 3. Presenters Spécialisés

Huit presenters implémentent l'interface pour différents types d'assets :

| Presenter | Type d'Asset | Icône Bootstrap | Champs Principaux |
|-----------|-------------|-----------------|-------------------|
| `GoogleAdsAssetPresenter` | `google_ads` | `google` | headlines, descriptions, keywords, call_to_action |
| `BingAdsAssetPresenter` | `bing_ads` | `bing` | titles/headlines, descriptions |
| `LinkedInPostAssetPresenter` | `linkedin_post` | `linkedin` | hook, body, hashtags |
| `FacebookPostAssetPresenter` | `facebook_post` | `facebook` | text, headline, link_description, hashtags |
| `InstagramPostAssetPresenter` | `instagram_post` | `instagram` | caption, hashtags, image_description |
| `EmailAssetPresenter` | `mail` | `envelope` | subject, preview_text, body, call_to_action |
| `IabAssetPresenter` | `iab_banner` | `badge-ad` | headline, subheadline, body, call_to_action, size |
| `ArticleAssetPresenter` | `article_seo` | `newspaper` | title, meta_description, introduction, sections, keywords |

### Configuration Symfony

#### services.yaml

```yaml
# Asset Presenter Services - Pattern Strategy
App\Service\Marketing\AssetPresenter\:
    resource: '../src/Service/Marketing/AssetPresenter/'
    exclude: '../src/Service/Marketing/AssetPresenter/Interface/'
    tags: ['app.asset_presenter']

App\Service\Marketing\AssetPresenter\AssetPresenterLocator:
    arguments:
        $presenters: !tagged_iterator app.asset_presenter
```

**Explication** :
- Auto-configuration de tous les presenters dans le namespace
- Tag `app.asset_presenter` pour l'itération
- Injection automatique via `!tagged_iterator` dans le Locator

## Utilisation

### Dans le Controller

```php
use App\Service\Marketing\AssetPresenter\AssetPresenterLocator;

class ProjectController extends AbstractController
{
    public function __construct(
        private readonly AssetPresenterLocator $assetPresenterLocator,
    ) {}

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        $this->denyAccessUnlessGranted('view', $project);

        // Format assets via AssetPresenters
        $formattedAssets = [];
        foreach ($project->getAssets() as $asset) {
            $presenter = $this->assetPresenterLocator->getPresenter($asset);
            $formattedAssets[] = [
                'entity' => $asset,
                'formatted' => $presenter->formatForDisplay($asset),
            ];
        }

        return $this->render('marketing/project/show.html.twig', [
            'project' => $project,
            'formatted_assets' => $formattedAssets,
        ]);
    }
}
```

### Dans les Templates Twig

**Template principal** `show.html.twig` :
```twig
{# Modals pour afficher le contenu des assets formatés via AssetPresenters #}
{% include 'marketing/project/_asset_modals.html.twig' %}
```

**Partial** `_asset_modals.html.twig` :
```twig
{% if formatted_assets is defined and formatted_assets|length > 0 %}
    {% for item in formatted_assets %}
        {% set asset = item.entity %}
        {% set formatted = item.formatted %}

        <div class="modal fade" id="assetModal{{ asset.id }}">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5><i class="bi bi-{{ formatted.icon }}"></i> {{ formatted.label }}</h5>
                    </div>
                    <div class="modal-body">
                        {% for key, value in formatted.main_content %}
                            {# Affichage selon le type de valeur #}
                        {% endfor %}
                    </div>
                </div>
            </div>
        </div>
    {% endfor %}
{% endif %}
```

## Avantages

### 1. Séparation des préoccupations
- **Logique métier** : Dans les presenters PHP (facile à tester)
- **Présentation** : Dans les templates Twig (simple et lisible)

### 2. Maintenabilité améliorée
- **Réduction de code** : Template principal réduit de 375 lignes (1750 → 1375 lignes)
- **Centralisation** : Logique de formatage centralisée dans des classes dédiées
- **Évolutivité** : Ajouter un nouveau type d'asset = créer un nouveau presenter

### 3. Testabilité accrue
- **Tests unitaires** : Chaque presenter peut être testé indépendamment
- **Mocking facile** : Interface claire pour les tests

### 4. Respect des principes SOLID
- **S** : Chaque presenter a une responsabilité unique (formater un type d'asset)
- **O** : Ouvert à l'extension (nouveau presenter), fermé à la modification
- **L** : Tous les presenters sont substituables via l'interface
- **I** : Interface minimaliste et spécifique
- **D** : Dépendance sur l'abstraction (`AssetPresenterInterface`), pas sur des implémentations concrètes

## Exemple de création d'un nouveau Presenter

Pour ajouter le support d'un nouveau type d'asset (ex: `twitter_post`) :

### 1. Créer le Presenter

```php
<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

final readonly class TwitterPostAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        return 'twitter_post' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf(
                'Asset Twitter Post #%d a un contenu invalide ou vide.',
                $asset->getId() ?? 0
            ));
        }

        return [
            'type' => 'twitter_post',
            'icon' => 'twitter-x',
            'label' => 'Twitter/X',
            'main_content' => $this->extractMainContent($content),
            'variations' => $this->getVariations($asset),
        ];
    }

    public function getVariations(Asset $asset): array
    {
        $variations = $asset->getVariationsArray();

        if (null === $variations || [] === $variations) {
            return [];
        }

        $formatted = [];
        foreach ($variations as $variation) {
            $formatted[] = $this->extractMainContent($variation);
        }

        return $formatted;
    }

    private function extractMainContent(array $data): array
    {
        $content = [];

        if (isset($data['text']) && is_string($data['text'])) {
            $content['text'] = $data['text'];
        }

        if (isset($data['hashtags']) && is_array($data['hashtags'])) {
            $content['hashtags'] = array_values(array_filter($data['hashtags'], 'is_string'));
        }

        if (isset($data['media_url']) && is_string($data['media_url'])) {
            $content['media_url'] = $data['media_url'];
        }

        return $content;
    }
}
```

### 2. C'est tout !

Grâce à l'autowiring Symfony et au tagged_iterator, le nouveau presenter est automatiquement :
- ✅ Enregistré dans le container
- ✅ Taggé avec `app.asset_presenter`
- ✅ Injecté dans le `AssetPresenterLocator`
- ✅ Disponible pour le formatage d'assets

**Aucune modification** de configuration n'est nécessaire.

## Tests

### Exemple de test unitaire

```php
<?php

namespace App\Tests\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\GoogleAdsAssetPresenter;
use PHPUnit\Framework\TestCase;

class GoogleAdsAssetPresenterTest extends TestCase
{
    private GoogleAdsAssetPresenter $presenter;

    protected function setUp(): void
    {
        $this->presenter = new GoogleAdsAssetPresenter();
    }

    public function testSupportsGoogleAdsAsset(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getAssetType')->willReturn('google_ads');

        $this->assertTrue($this->presenter->supports($asset));
    }

    public function testDoesNotSupportOtherAssetTypes(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getAssetType')->willReturn('facebook_post');

        $this->assertFalse($this->presenter->supports($asset));
    }

    public function testFormatForDisplay(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getContentArray')->willReturn([
            'headlines' => ['Titre 1', 'Titre 2'],
            'descriptions' => ['Description complète'],
            'keywords' => ['seo', 'marketing'],
            'call_to_action' => 'En savoir plus',
        ]);
        $asset->method('getVariationsArray')->willReturn(null);

        $formatted = $this->presenter->formatForDisplay($asset);

        $this->assertEquals('google_ads', $formatted['type']);
        $this->assertEquals('google', $formatted['icon']);
        $this->assertArrayHasKey('main_content', $formatted);
        $this->assertCount(2, $formatted['main_content']['headlines']);
    }
}
```

## Validation Qualité

### PHPStan (Niveau 6)
```bash
docker compose exec --user www-data frankenphp php vendor/bin/phpstan analyse --memory-limit=1G
```

### PHP-CS-Fixer (PSR-12 + Symfony)
```bash
docker compose exec --user www-data frankenphp php vendor/bin/php-cs-fixer fix
```

## Migration depuis l'ancien code

### Avant (Twig pur - 1750 lignes)
```twig
{# 377 lignes de logique conditionnelle complexe #}
{% if asset.assetType == 'google_ads' %}
    {% if content.headlines is defined %}
        {# ... logique d'affichage ... #}
    {% endif %}
{% elseif asset.assetType == 'linkedin_post' %}
    {# ... logique d'affichage ... #}
{% endif %}
```

### Après (Pattern Presenter - 1375 lignes)
```twig
{# 1 ligne d'inclusion #}
{% include 'marketing/project/_asset_modals.html.twig' %}
```

**Réduction** : -375 lignes (-21% du template principal)

## Références

- **Pattern Strategy** : https://refactoring.guru/design-patterns/strategy
- **Symfony Service Container** : https://symfony.com/doc/current/service_container.html
- **Tagged Services** : https://symfony.com/doc/current/service_container/tags.html
- **SOLID Principles** : https://en.wikipedia.org/wiki/SOLID

---

**Date de création** : 2025-12-07
**Auteur** : Context Engineering
**Révision** : 1.0
