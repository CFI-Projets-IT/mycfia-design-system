<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter\Interface;

use App\Entity\Asset;

/**
 * Interface pour les services de présentation d'assets marketing.
 *
 * Définit le contrat pour formater les assets selon leur type
 * (Google Ads, Instagram, Facebook, Email, etc.) pour affichage.
 *
 * Pattern Strategy pour gérer la diversité des types d'assets.
 */
interface AssetPresenterInterface
{
    /**
     * Vérifie si ce presenter supporte le type d'asset donné.
     *
     * Chaque implémentation vérifie le type spécifique qu'elle gère
     * (ex: 'google_ads', 'instagram_post', etc.).
     *
     * @param Asset $asset L'asset à vérifier
     *
     * @return bool True si ce presenter peut gérer cet asset
     */
    public function supports(Asset $asset): bool;

    /**
     * Formate les données de l'asset pour affichage dans un template.
     *
     * Transforme le contenu JSON de l'asset en structure normalisée
     * contenant type, icône, label et contenu principal formaté.
     *
     * @param Asset $asset L'asset à formater
     *
     * @return array{
     *   type: string,
     *   icon: string,
     *   label: string,
     *   main_content: array<string, mixed>,
     *   variations: array<int, array<string, mixed>>
     * } Structure normalisée pour affichage
     *
     * @throws \RuntimeException Si le contenu de l'asset est invalide
     */
    public function formatForDisplay(Asset $asset): array;

    /**
     * Extrait et formate les variations de l'asset.
     *
     * Parse les variations JSON et les normalise pour affichage.
     * Retourne un tableau vide si aucune variation n'existe.
     *
     * @param Asset $asset L'asset dont extraire les variations
     *
     * @return array<int, array<string, mixed>> Variations formatées
     */
    public function getVariations(Asset $asset): array;
}
