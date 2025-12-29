<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets LinkedIn Post.
 *
 * Formate les données des posts LinkedIn (hook, body, hashtags, cta, target_audience)
 * pour affichage dans les templates.
 *
 * Compatible avec marketing-ai-bundle v3.39.0+ qui normalise les champs via fallbacks :
 * - cta (au lieu de call_to_action)
 * - target_audience (au lieu de target_personas)
 * - main_insight (nouveau champ)
 *
 * @since v3.39.0 - Adaptation aux changements bundle
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
        /** @var string|array<string, mixed> $variation */
        foreach ($variations as $variation) {
            // Les variations peuvent être des strings (texte brut) ou des arrays (structure complète)
            if (is_string($variation)) {
                // Si c'est une string, créer une structure minimale pour extractMainContent
                $formatted[] = $this->extractMainContent(['post_text' => $variation]);
            } elseif (is_array($variation)) {
                // Si c'est déjà un array, utiliser tel quel
                $formatted[] = $this->extractMainContent($variation);
            }
        }

        return $formatted;
    }

    /**
     * Extrait et formate le contenu principal d'un post LinkedIn.
     *
     * Structure attendue depuis bundle v3.39.0 (avec fallbacks pour rétrocompatibilité) :
     * - post_text (obligatoire) : Texte principal du post
     * - hook (recommandé) : Phrase d'accroche captivante
     * - cta (recommandé) : Call-to-action engageant
     * - hashtags (array) : Liste de hashtags professionnels
     * - target_audience (optionnel) : Audience cible LinkedIn
     * - main_insight (optionnel) : Insight principal du post
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Hook (accroche) - nouveau champ v3.39.0
        if (isset($data['hook']) && is_string($data['hook'])) {
            $content['hook'] = $data['hook'];
        }

        // Body - Le bundle retourne 'post_text' ou 'content'
        if (isset($data['post_text']) && is_string($data['post_text'])) {
            $content['body'] = $data['post_text'];
        } elseif (isset($data['content']) && is_string($data['content'])) {
            $content['body'] = $data['content'];
        } elseif (isset($data['body']) && is_string($data['body'])) {
            $content['body'] = $data['body'];
        }

        // Hashtags
        if (isset($data['hashtags']) && is_array($data['hashtags'])) {
            $content['hashtags'] = array_values(array_filter($data['hashtags'], 'is_string'));
        }

        // Call to Action - Le bundle v3.39.0 normalise vers 'cta' (priorité haute)
        if (isset($data['cta']) && is_string($data['cta'])) {
            $content['call_to_action'] = $data['cta'];
        } elseif (isset($data['call_to_action']) && is_string($data['call_to_action'])) {
            $content['call_to_action'] = $data['call_to_action'];
        }

        // Target Audience - Le bundle v3.39.0 normalise vers 'target_audience' (priorité haute)
        if (isset($data['target_audience']) && is_string($data['target_audience'])) {
            $content['target_audience'] = $data['target_audience'];
        } elseif (isset($data['target_personas']) && is_array($data['target_personas']) && ! empty($data['target_personas'])) {
            // Fallback vers target_personas[0] si target_audience absent
            $content['target_audience'] = $data['target_personas'][0];
        }

        // Main Insight - Nouveau champ v3.39.0 (optionnel)
        if (isset($data['main_insight']) && is_string($data['main_insight'])) {
            $content['main_insight'] = $data['main_insight'];
        } elseif (isset($data['key_insights']) && is_array($data['key_insights']) && ! empty($data['key_insights'])) {
            // Fallback vers key_insights[0] si main_insight absent
            $content['main_insight'] = $data['key_insights'][0];
        }

        // Image générée par IA (optionnel) - Nouveau format avec stockage filesystem
        // L'image est stockée sur /public/uploads/ et on récupère juste l'URL
        if (isset($data['image_path']) && is_string($data['image_path'])) {
            $content['image_path'] = $data['image_path'];
            $content['image_url'] = $data['image_path']; // Alias pour compatibilité
        }

        // Description de l'image (optionnel)
        if (isset($data['image_description']) && is_string($data['image_description'])) {
            $content['image_description'] = $data['image_description'];
        }

        // Metadata de l'image (format, taille, etc.) si disponibles
        if (isset($data['image']) && is_array($data['image'])) {
            // Garder les metadata mais sans le base64 (qui a été retiré)
            $content['image_metadata'] = $data['image'];
        }

        return $content;
    }
}
