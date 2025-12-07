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
        return 'article_seo' === $asset->getAssetType();
    }

    public function formatForDisplay(Asset $asset): array
    {
        $content = $asset->getContentArray();

        if (null === $content) {
            throw new \RuntimeException(sprintf('Asset Article SEO #%d a un contenu invalide ou vide.', $asset->getId() ?? 0));
        }

        return [
            'type' => 'article_seo',
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
        }

        // Meta Description
        if (isset($data['meta_description']) && is_string($data['meta_description'])) {
            $content['meta_description'] = $data['meta_description'];
        }

        // Introduction
        if (isset($data['introduction']) && is_string($data['introduction'])) {
            $content['introduction'] = $data['introduction'];
        }

        // Sections (array d'objets avec heading/title et content/body)
        if (isset($data['sections']) && is_array($data['sections'])) {
            $content['sections'] = $data['sections'];
        }

        // Keywords (mots-clés SEO)
        if (isset($data['keywords']) && is_array($data['keywords'])) {
            $content['keywords'] = array_values(array_filter($data['keywords'], 'is_string'));
        }

        return $content;
    }
}
