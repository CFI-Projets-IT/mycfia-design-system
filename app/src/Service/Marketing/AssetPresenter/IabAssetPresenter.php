<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets IAB Banner.
 *
 * Formate les données des bannières publicitaires IAB (headline, subheadline, body, CTA)
 * pour affichage dans les templates.
 */
final readonly class IabAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        // Le bundle génère le type 'iab', pas 'iab_banner'
        return 'iab' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset IAB Banner #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'iab',
            'icon' => 'display',
            'label' => 'Bannière IAB',
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
     * Extrait et formate le contenu principal d'une bannière IAB.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Headline (titre)
        if (isset($data['headline']) && is_string($data['headline'])) {
            $content['headline'] = $data['headline'];
        }

        // Tagline (baseline/sous-titre)
        // Le bundle génère 'tagline', pas 'subheadline'
        if (isset($data['tagline']) && is_string($data['tagline']) && '' !== $data['tagline']) {
            $content['tagline'] = $data['tagline'];
        } elseif (isset($data['subheadline']) && is_string($data['subheadline'])) {
            $content['tagline'] = $data['subheadline'];
        }

        // Body (texte descriptif)
        if (isset($data['body']) && is_string($data['body']) && '' !== $data['body']) {
            $content['body'] = $data['body'];
        } elseif (isset($data['design_description']) && is_string($data['design_description']) && '' !== $data['design_description']) {
            $content['body'] = $data['design_description'];
        }

        // Call to Action
        // Le bundle génère 'cta_text', pas 'call_to_action'
        if (isset($data['cta_text']) && is_string($data['cta_text'])) {
            $content['call_to_action'] = $data['cta_text'];
        } elseif (isset($data['call_to_action']) && is_string($data['call_to_action'])) {
            $content['call_to_action'] = $data['call_to_action'];
        }

        // Format IAB (medium_rectangle, leaderboard, skyscraper, etc.)
        // Le bundle génère 'format', pas 'size'
        if (isset($data['format']) && is_string($data['format'])) {
            $content['format'] = $data['format'];
        } elseif (isset($data['size']) && is_string($data['size'])) {
            $content['format'] = $data['size'];
        }

        // Dimensions (width x height)
        if (isset($data['dimensions']) && is_array($data['dimensions'])) {
            if (isset($data['dimensions']['width'], $data['dimensions']['height'])) {
                $content['dimensions'] = sprintf('%dx%d', $data['dimensions']['width'], $data['dimensions']['height']);
            }
        }

        // Background color
        if (isset($data['background_color']) && is_string($data['background_color'])) {
            $content['background_color'] = $data['background_color'];
        }

        // CTA color
        if (isset($data['cta_color']) && is_string($data['cta_color'])) {
            $content['cta_color'] = $data['cta_color'];
        }

        // Logo placement
        if (isset($data['logo_placement']) && is_string($data['logo_placement'])) {
            $content['logo_placement'] = $data['logo_placement'];
        }

        // Animation type
        if (isset($data['animation']) && is_string($data['animation'])) {
            $content['animation'] = $data['animation'];
        }

        // Estimated weight (poids du fichier en KB)
        if (isset($data['estimated_weight_kb'])) {
            $content['estimated_weight_kb'] = $data['estimated_weight_kb'];
        }

        // Targeting strategy
        if (isset($data['targeting']) && is_string($data['targeting'])) {
            $content['targeting'] = $data['targeting'];
        }

        return $content;
    }
}
