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
     * Extrait et formate le contenu principal d'un post Facebook.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Texte du post (peut être 'text' ou 'body')
        if (isset($data['text']) && is_string($data['text'])) {
            $content['text'] = $data['text'];
        } elseif (isset($data['body']) && is_string($data['body'])) {
            $content['text'] = $data['body'];
        }

        // Headline (titre)
        if (isset($data['headline']) && is_string($data['headline'])) {
            $content['headline'] = $data['headline'];
        }

        // Description du lien
        if (isset($data['link_description']) && is_string($data['link_description'])) {
            $content['link_description'] = $data['link_description'];
        }

        // Hashtags (optionnel)
        if (isset($data['hashtags']) && is_array($data['hashtags'])) {
            $content['hashtags'] = array_values(array_filter($data['hashtags'], 'is_string'));
        }

        return $content;
    }
}
