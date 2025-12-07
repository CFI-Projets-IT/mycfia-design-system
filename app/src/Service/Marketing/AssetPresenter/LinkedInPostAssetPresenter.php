<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets LinkedIn Post.
 *
 * Formate les données des posts LinkedIn (hook, body, hashtags)
 * pour affichage dans les templates.
 */
final readonly class LinkedInPostAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        return 'linkedin_post' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset LinkedIn Post #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'linkedin_post',
            'icon' => 'linkedin',
            'label' => 'LinkedIn',
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
     * Extrait et formate le contenu principal d'un post LinkedIn.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Hook (accroche)
        if (isset($data['hook']) && is_string($data['hook'])) {
            $content['hook'] = $data['hook'];
        }

        // Body (corps du message)
        if (isset($data['body']) && is_string($data['body'])) {
            $content['body'] = $data['body'];
        }

        // Hashtags
        if (isset($data['hashtags']) && is_array($data['hashtags'])) {
            $content['hashtags'] = array_values(array_filter($data['hashtags'], 'is_string'));
        }

        // Call to Action
        if (isset($data['call_to_action']) && is_string($data['call_to_action'])) {
            $content['call_to_action'] = $data['call_to_action'];
        }

        return $content;
    }
}
