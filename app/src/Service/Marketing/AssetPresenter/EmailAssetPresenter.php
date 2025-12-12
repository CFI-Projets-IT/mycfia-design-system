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
        return 'email' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset Email #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'email',
            'icon' => 'envelope',
            'label' => 'Email',
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
                $formatted[] = ['subject' => $variation];
            } elseif (is_array($variation)) {
                // Si la variation est un array complet, extraire le contenu
                $formatted[] = $this->extractMainContent($variation);
            }
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
        // Le bundle génère 'subject_line', mais on supporte aussi 'subject' pour compatibilité
        if (isset($data['subject_line']) && is_string($data['subject_line'])) {
            $content['subject'] = $data['subject_line'];
        } elseif (isset($data['subject']) && is_string($data['subject'])) {
            $content['subject'] = $data['subject'];
        }

        // Preview text (texte d'aperçu)
        // Le bundle génère 'preheader_text', mais on supporte aussi 'preview_text' pour compatibilité
        if (isset($data['preheader_text']) && is_string($data['preheader_text'])) {
            $content['preview_text'] = $data['preheader_text'];
        } elseif (isset($data['preview_text']) && is_string($data['preview_text'])) {
            $content['preview_text'] = $data['preview_text'];
        }

        // Body (corps de l'email)
        // Le bundle génère 'body_html' et 'body_plain', on préfère HTML
        if (isset($data['body_html']) && is_string($data['body_html'])) {
            $content['body'] = $data['body_html'];
        } elseif (isset($data['body_plain']) && is_string($data['body_plain'])) {
            $content['body'] = $data['body_plain'];
        } elseif (isset($data['body']) && is_string($data['body'])) {
            $content['body'] = $data['body'];
        }

        // Call to Action
        // Le bundle génère 'cta_primary', mais on supporte aussi 'call_to_action' pour compatibilité
        if (isset($data['cta_primary']) && is_string($data['cta_primary'])) {
            $content['call_to_action'] = $data['cta_primary'];
        } elseif (isset($data['call_to_action']) && is_string($data['call_to_action'])) {
            $content['call_to_action'] = $data['call_to_action'];
        }

        return $content;
    }
}
