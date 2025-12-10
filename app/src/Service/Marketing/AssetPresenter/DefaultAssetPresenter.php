<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;
use Psr\Log\LoggerInterface;

/**
 * Presenter générique pour assets avec structure standard.
 *
 * Utilisé comme fallback pour les types d'assets sans presenter spécialisé.
 * Affiche tous les champs de manière générique sans formatage spécial.
 *
 * Logging :
 * - Log INFO quand le fallback est utilisé (pas de presenter spécialisé trouvé)
 * - Log DEBUG avec la structure de l'asset pour faciliter le debugging
 *
 * @since v3.39.0 - Ajout du logging pour traçabilité
 */
final readonly class DefaultAssetPresenter implements AssetPresenterInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Supporte tous les assets qui n'ont pas de presenter dédié.
     * Ce presenter a la priorité la plus basse (exécuté en dernier).
     *
     * Log INFO à chaque utilisation pour identifier les types d'assets
     * qui nécessitent un presenter spécialisé.
     */
    public function supports(Asset $asset): bool
    {
        // Logger quand le fallback est utilisé (utile pour identifier les manques)
        $this->logger->info('DefaultAssetPresenter utilisé (pas de presenter spécialisé)', [
            'asset_id' => $asset->getId(),
            'asset_type' => $asset->getAssetType(),
            'channel' => $asset->getChannel(),
        ]);

        // Supporte tout asset (fallback universel)
        return true;
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset #%d (type: %s) a un contenu invalide ou vide.', $asset->getId() ?? 0, $asset->getAssetType()));
        }

        // Logger la structure de l'asset pour debugging (niveau DEBUG)
        $this->logger->debug('DefaultAssetPresenter: Structure asset', [
            'asset_id' => $asset->getId(),
            'asset_type' => $asset->getAssetType(),
            'content_keys' => array_keys($content),
            'content_size' => \strlen((string) json_encode($content)),
            'has_variations' => null !== $asset->getVariationsArray() && [] !== $asset->getVariationsArray(),
        ]);

        return [
            'type' => $asset->getAssetType(),
            'icon' => $this->guessIcon($asset->getAssetType()),
            'label' => $this->formatLabel($asset->getAssetType()),
            'main_content' => $content,
            'variations' => $this->getVariations($asset),
        ];
    }

    public function getVariations(Asset $asset): array
    {
        $variations = $asset->getVariationsArray();

        if (null === $variations || [] === $variations) {
            return [];
        }

        // Retourne variations telles quelles (pas de formatage spécial)
        return $variations;
    }

    /**
     * Devine l'icône Bootstrap Icons selon le type d'asset.
     *
     * Support des types depuis marketing-ai-bundle v3.39.0 :
     * - Posts sociaux (LinkedIn, Facebook, Instagram)
     * - Publicités (Google Ads, Bing Ads, IAB)
     * - Email marketing
     * - Articles de blog
     * - SMS marketing (nouveau v3.38.0+)
     */
    private function guessIcon(string $assetType): string
    {
        return match (true) {
            str_contains($assetType, 'post') => 'chat-dots',
            str_contains($assetType, 'ad') => 'badge-ad',
            str_contains($assetType, 'mail') || str_contains($assetType, 'email') => 'envelope',
            str_contains($assetType, 'article') || str_contains($assetType, 'blog') => 'newspaper',
            str_contains($assetType, 'video') => 'camera-video',
            str_contains($assetType, 'image') => 'image',
            str_contains($assetType, 'sms') => 'chat-text',  // Nouveau type SMS
            default => 'file-earmark-text',
        };
    }

    /**
     * Formate le label à partir du type d'asset.
     */
    private function formatLabel(string $assetType): string
    {
        // Remplace underscores par espaces et capitalize
        return ucwords(str_replace('_', ' ', $assetType));
    }
}
