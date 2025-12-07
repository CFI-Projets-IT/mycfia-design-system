<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets Instagram Post.
 *
 * Formate les données des posts Instagram (caption, hashtags, image_description)
 * pour affichage dans les templates.
 */
final readonly class InstagramPostAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        return 'instagram_post' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset Instagram Post #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'instagram_post',
            'icon' => 'instagram',
            'label' => 'Instagram',
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

    /**
     * Extrait et formate le contenu principal d'un post Instagram.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Caption (légende)
        if (isset($data['caption']) && is_string($data['caption'])) {
            $content['caption'] = $data['caption'];
        }

        // Hashtags
        if (isset($data['hashtags']) && is_array($data['hashtags'])) {
            $content['hashtags'] = array_values(array_filter($data['hashtags'], 'is_string'));
        }

        // Description de l'image
        if (isset($data['image_description']) && is_string($data['image_description'])) {
            $content['image_description'] = $data['image_description'];
        }

        return $content;
    }
}
