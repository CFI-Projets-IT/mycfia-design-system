<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets Bing Ads.
 *
 * Formate les données des campagnes Bing Ads (titles/headlines, descriptions)
 * pour affichage dans les templates.
 */
final readonly class BingAdsAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        return 'bing_ads' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset Bing Ads #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'bing_ads',
            'icon' => 'microsoft',
            'label' => 'Bing Ads',
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
     * Extrait et formate le contenu principal d'un asset Bing Ads.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Titles ou headlines (Bing Ads peut utiliser l'un ou l'autre)
        if (isset($data['titles']) && is_array($data['titles'])) {
            $content['titles'] = array_values(array_filter($data['titles'], 'is_string'));
        } elseif (isset($data['headlines']) && is_array($data['headlines'])) {
            $content['titles'] = array_values(array_filter($data['headlines'], 'is_string'));
        }

        // Descriptions
        if (isset($data['descriptions']) && is_array($data['descriptions'])) {
            $content['descriptions'] = array_values(array_filter($data['descriptions'], 'is_string'));
        }

        // Keywords (optionnel)
        if (isset($data['keywords']) && is_array($data['keywords'])) {
            $content['keywords'] = array_values(array_filter($data['keywords'], 'is_string'));
        }

        // Display URL (URL affichée dans l'annonce)
        if (isset($data['display_url']) && is_string($data['display_url'])) {
            $content['display_url'] = $data['display_url'];
        }

        // Final URL (URL de destination)
        if (isset($data['final_url']) && is_string($data['final_url'])) {
            $content['final_url'] = $data['final_url'];
        }

        // Ad Strength (force de l'annonce : poor, average, good, excellent)
        if (isset($data['ad_strength']) && is_string($data['ad_strength'])) {
            $content['ad_strength'] = $data['ad_strength'];
        }

        // Ad Extensions - Sitelinks
        if (isset($data['ad_extensions']['sitelinks']) && is_array($data['ad_extensions']['sitelinks'])) {
            $content['sitelinks'] = array_values(array_filter($data['ad_extensions']['sitelinks'], 'is_string'));
        } elseif (isset($data['extensions']['sitelinks']) && is_array($data['extensions']['sitelinks'])) {
            $content['sitelinks'] = array_values(array_filter($data['extensions']['sitelinks'], 'is_string'));
        }

        // Ad Extensions - Callouts (atouts)
        if (isset($data['ad_extensions']['callouts']) && is_array($data['ad_extensions']['callouts'])) {
            $content['callouts'] = array_values(array_filter($data['ad_extensions']['callouts'], 'is_string'));
        } elseif (isset($data['extensions']['callouts']) && is_array($data['extensions']['callouts'])) {
            $content['callouts'] = array_values(array_filter($data['extensions']['callouts'], 'is_string'));
        }

        // Call to Action (si présent dans les données)
        if (isset($data['call_to_action']) && is_string($data['call_to_action'])) {
            $content['call_to_action'] = $data['call_to_action'];
        }

        return $content;
    }
}
