<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter générique pour assets avec structure standard.
 *
 * Utilisé comme fallback pour les types d'assets sans presenter spécialisé.
 * Affiche tous les champs de manière générique sans formatage spécial.
 */
final readonly class DefaultAssetPresenter implements AssetPresenterInterface
{
    /**
     * Supporte tous les assets qui n'ont pas de presenter dédié.
     * Ce presenter a la priorité la plus basse (exécuté en dernier).
     */
    public function supports(Asset $asset): bool
    {
        // Supporte tout asset (fallback universel)
        return true;
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset #%d (type: %s) a un contenu invalide ou vide.', $asset->getId() ?? 0, $asset->getAssetType()));
        }

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
