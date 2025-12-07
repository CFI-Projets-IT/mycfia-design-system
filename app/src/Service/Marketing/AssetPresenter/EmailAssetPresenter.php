<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets Email.
 *
 * Formate les données des emails marketing (subject, preview_text, body, CTA)
 * pour affichage dans les templates.
 */
final readonly class EmailAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        return 'mail' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset Email #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'mail',
            'icon' => 'envelope',
            'label' => 'Email',
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
     * Extrait et formate le contenu principal d'un email marketing.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Subject (objet de l'email)
        if (isset($data['subject']) && is_string($data['subject'])) {
            $content['subject'] = $data['subject'];
        }

        // Preview text (texte d'aperçu)
        if (isset($data['preview_text']) && is_string($data['preview_text'])) {
            $content['preview_text'] = $data['preview_text'];
        }

        // Body (corps de l'email)
        if (isset($data['body']) && is_string($data['body'])) {
            $content['body'] = $data['body'];
        }

        // Call to Action
        if (isset($data['call_to_action']) && is_string($data['call_to_action'])) {
            $content['call_to_action'] = $data['call_to_action'];
        }

        return $content;
    }
}
