<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets Google Ads.
 *
 * Formate les données des campagnes Google Ads (headlines, descriptions, keywords)
 * pour affichage dans les templates.
 */
final readonly class GoogleAdsAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        return 'google_ads' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset Google Ads #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'google_ads',
            'icon' => 'google',
            'label' => 'Google Ads',
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
     * Extrait et formate le contenu principal d'un asset Google Ads.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Headlines (titres)
        if (isset($data['headlines']) && is_array($data['headlines'])) {
            $content['headlines'] = array_values(array_filter($data['headlines'], 'is_string'));
        }

        // Descriptions
        if (isset($data['descriptions']) && is_array($data['descriptions'])) {
            $content['descriptions'] = array_values(array_filter($data['descriptions'], 'is_string'));
        }

        // Keywords (mots-clés)
        if (isset($data['keywords']) && is_array($data['keywords'])) {
            $content['keywords'] = array_values(array_filter($data['keywords'], 'is_string'));
        }

        // Call to Action
        if (isset($data['call_to_action']) && is_string($data['call_to_action'])) {
            $content['call_to_action'] = $data['call_to_action'];
        }

        // Final URL (optionnel)
        if (isset($data['final_url']) && is_string($data['final_url'])) {
            $content['final_url'] = $data['final_url'];
        }

        return $content;
    }
}
