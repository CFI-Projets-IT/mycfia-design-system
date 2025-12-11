<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Presenter pour les assets Article SEO.
 *
 * Formate les données des articles SEO (title, meta_description, introduction, sections, keywords)
 * pour affichage dans les templates.
 */
final readonly class ArticleAssetPresenter implements AssetPresenterInterface
{
    public function supports(Asset $asset): bool
    {
        // Le bundle génère le type 'article', pas 'article_seo'
        return 'article' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset Article SEO #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'article',
            'icon' => 'newspaper',
            'label' => 'Article SEO',
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
     * Extrait et formate le contenu principal d'un article SEO.
     *
     * @param array<string, mixed> $data Données brutes de l'asset ou variation
     *
     * @return array<string, mixed> Contenu formaté pour affichage
     */
    private function extractMainContent(array $data): array
    {
        $content = [];

        // Title (titre de l'article)
        if (isset($data['title']) && is_string($data['title'])) {
            $content['title'] = $data['title'];
        } elseif (isset($data['title_seo']) && is_string($data['title_seo'])) {
            $content['title'] = $data['title_seo'];
        }

        // Slug (URL-friendly)
        if (isset($data['slug']) && is_string($data['slug'])) {
            $content['slug'] = $data['slug'];
        }

        // Meta Description
        if (isset($data['meta_description']) && is_string($data['meta_description'])) {
            $content['meta_description'] = $data['meta_description'];
        }

        // Introduction
        if (isset($data['introduction']) && is_string($data['introduction'])) {
            $content['introduction'] = $data['introduction'];
        }

        // Body (corps de l'article)
        if (isset($data['body']) && is_string($data['body'])) {
            $content['body'] = $data['body'];
        }

        // Conclusion
        if (isset($data['conclusion']) && is_string($data['conclusion'])) {
            $content['conclusion'] = $data['conclusion'];
        }

        // Keywords primary (mot-clé principal)
        if (isset($data['keywords_primary']) && is_string($data['keywords_primary'])) {
            $content['keywords_primary'] = $data['keywords_primary'];
        }

        // Keywords secondary (mots-clés secondaires)
        if (isset($data['keywords_secondary']) && is_array($data['keywords_secondary'])) {
            $content['keywords_secondary'] = array_values(array_filter($data['keywords_secondary'], 'is_string'));
        } elseif (isset($data['keywords']) && is_array($data['keywords'])) {
            $content['keywords_secondary'] = array_values(array_filter($data['keywords'], 'is_string'));
        }

        // Headings (titres des sections)
        if (isset($data['headings']) && is_array($data['headings'])) {
            $content['headings'] = array_values(array_filter($data['headings'], 'is_string'));
        }

        // CTA Primary (call-to-action principal)
        if (isset($data['cta_primary']) && is_string($data['cta_primary'])) {
            $content['cta_primary'] = $data['cta_primary'];
        }

        // CTA Secondary (call-to-action secondaire)
        if (isset($data['cta_secondary']) && is_string($data['cta_secondary'])) {
            $content['cta_secondary'] = $data['cta_secondary'];
        }

        // Featured Image Description
        if (isset($data['featured_image_description']) && is_string($data['featured_image_description'])) {
            $content['featured_image_description'] = $data['featured_image_description'];
        }

        // Internal Links Suggested (liens internes suggérés)
        if (isset($data['internal_links_suggested']) && is_array($data['internal_links_suggested'])) {
            $content['internal_links_suggested'] = array_values(array_filter($data['internal_links_suggested'], 'is_string'));
        }

        // Reading Time (temps de lecture en minutes)
        if (isset($data['reading_time_minutes'])) {
            $content['reading_time_minutes'] = $data['reading_time_minutes'];
        }

        // Article Type (type d'article : educational, promotional, etc.)
        if (isset($data['article_type']) && is_string($data['article_type'])) {
            $content['article_type'] = $data['article_type'];
        }

        // Target Audience (audience cible)
        if (isset($data['target_audience']) && is_string($data['target_audience'])) {
            $content['target_audience'] = $data['target_audience'];
        }

        // FAQ (questions fréquentes)
        if (isset($data['faq']) && is_array($data['faq'])) {
            $content['faq'] = $data['faq'];
        }

        return $content;
    }
}
