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
        return 'iab_banner' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset IAB Banner #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'iab_banner',
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

        // Subheadline (sous-titre)
        if (isset($data['subheadline']) && is_string($data['subheadline'])) {
            $content['subheadline'] = $data['subheadline'];
        }

        // Body (texte)
        if (isset($data['body']) && is_string($data['body'])) {
            $content['body'] = $data['body'];
        }

        // Call to Action
        if (isset($data['call_to_action']) && is_string($data['call_to_action'])) {
            $content['call_to_action'] = $data['call_to_action'];
        }

        // Size/format (taille de la bannière)
        if (isset($data['size']) && is_string($data['size'])) {
            $content['size'] = $data['size'];
        }

        return $content;
    }
}
