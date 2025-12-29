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
        $content = $asset->getContentArray();

        if (null === $content) {
            return [];
        }

        // Les variations peuvent être dans content['variations'] ou dans asset->variations
        $variations = $content['variations'] ?? $asset->getVariationsArray() ?? [];

        if ([] === $variations) {
            return [];
        }

        $formatted = [];
        foreach ($variations as $variation) {
            // Si la variation est une string simple, la formater directement
            if (is_string($variation)) {
                $formatted[] = ['caption' => $variation];
            } elseif (is_array($variation)) {
                // Si la variation est un array complet, extraire le contenu
                $formatted[] = $this->extractMainContent($variation);
            }
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

        // Caption (priorité : caption > caption_text > content > text)
        if (isset($data['caption']) && is_string($data['caption'])) {
            $content['caption'] = $data['caption'];
        } elseif (isset($data['caption_text']) && is_string($data['caption_text'])) {
            $content['caption'] = $data['caption_text'];
        } elseif (isset($data['content']) && is_string($data['content'])) {
            $content['caption'] = $data['content'];
        } elseif (isset($data['text']) && is_string($data['text'])) {
            $content['caption'] = $data['text'];
        }

        // Titre
        if (isset($data['title']) && is_string($data['title'])) {
            $content['title'] = $data['title'];
        }

        // Call-to-action
        if (isset($data['cta']) && is_string($data['cta'])) {
            $content['cta'] = $data['cta'];
        }

        // Hashtags
        if (isset($data['hashtags']) && is_array($data['hashtags'])) {
            $content['hashtags'] = array_values(array_filter($data['hashtags'], 'is_string'));
        }

        // Description de l'image
        if (isset($data['image_description']) && is_string($data['image_description'])) {
            $content['image_description'] = $data['image_description'];
        }

        // Image générée par IA (optionnel) - Nouveau format avec stockage filesystem
        // L'image est stockée sur /public/uploads/ et on récupère juste l'URL
        if (isset($data['image_path']) && is_string($data['image_path'])) {
            $content['image_path'] = $data['image_path'];
            $content['image_url'] = $data['image_path']; // Alias pour compatibilité
        }

        // Metadata de l'image (format, taille, etc.) si disponibles
        if (isset($data['image']) && is_array($data['image'])) {
            // Garder les metadata mais sans le base64 (qui a été retiré)
            $content['image_metadata'] = $data['image'];
        }

        return $content;
    }
}
