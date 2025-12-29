<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets Facebook Post.
 *
 * Formate les données des posts Facebook (text, headline, link_description)
 * pour affichage dans les templates.
 */
final readonly class FacebookPostAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        return 'facebook_post' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset Facebook Post #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'facebook_post',
            'icon' => 'facebook',
            'label' => 'Facebook',
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
                $formatted[] = ['text' => $variation];
            } elseif (is_array($variation)) {
                // Si la variation est un array complet, extraire le contenu
                $formatted[] = $this->extractMainContent($variation);
            }
        }

        return $formatted;
    }

    /**
     * Extrait et formate le contenu principal d'un post Facebook.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Texte du post (priorité : post_text > caption > content > text > body)
        if (isset($data['post_text']) && is_string($data['post_text'])) {
            $content['text'] = $data['post_text'];
        } elseif (isset($data['caption']) && is_string($data['caption'])) {
            $content['text'] = $data['caption'];
        } elseif (isset($data['content']) && is_string($data['content'])) {
            $content['text'] = $data['content'];
        } elseif (isset($data['text']) && is_string($data['text'])) {
            $content['text'] = $data['text'];
        } elseif (isset($data['body']) && is_string($data['body'])) {
            $content['text'] = $data['body'];
        }

        // Titre/Headline
        if (isset($data['title']) && is_string($data['title'])) {
            $content['headline'] = $data['title'];
        } elseif (isset($data['headline']) && is_string($data['headline'])) {
            $content['headline'] = $data['headline'];
        }

        // Call-to-action
        if (isset($data['cta']) && is_string($data['cta'])) {
            $content['cta'] = $data['cta'];
        }

        // Description du lien
        if (isset($data['link_description']) && is_string($data['link_description'])) {
            $content['link_description'] = $data['link_description'];
        }

        // Hashtags (optionnel)
        if (isset($data['hashtags']) && is_array($data['hashtags'])) {
            $content['hashtags'] = array_values(array_filter($data['hashtags'], 'is_string'));
        }

        // Image générée par IA (optionnel) - Nouveau format avec stockage filesystem
        if (isset($data['image_path']) && is_string($data['image_path'])) {
            $content['image_path'] = $data['image_path'];
            $content['image_url'] = $data['image_path'];
        }

        // Description de l'image
        if (isset($data['image_description']) && is_string($data['image_description'])) {
            $content['image_description'] = $data['image_description'];
        }

        // Metadata de l'image
        if (isset($data['image']) && is_array($data['image'])) {
            $content['image_metadata'] = $data['image'];
        }

        return $content;
    }
}
