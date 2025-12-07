<?php

declare(strict_types=1);

namespace App\Service\Marketing\AssetPresenter;

use App\Entity\Asset;
use App\Service\Marketing\AssetPresenter\Interface\AssetPresenterInterface;

/**
 * Service Locator pour sélectionner le bon AssetPresenter.
 *
 * Parcourt tous les presenters enregistrés et retourne celui qui
 * supporte le type d'asset demandé.
 *
 * Pattern Service Locator pour délégation automatique basée sur le type.
 */
final readonly class AssetPresenterLocator
{
    /**
     * @param iterable<AssetPresenterInterface> $presenters Collection de tous les presenters disponibles
     */
    public function __construct(
        private iterable $presenters
    ) {
    }

    /**
     * Retourne le presenter approprié pour un asset donné.
     *
     * Parcourt tous les presenters enregistrés et retourne le premier
     * qui supporte le type de l'asset via sa méthode supports().
     *
     * @param Asset $asset L'asset pour lequel trouver un presenter
     *
     * @return AssetPresenterInterface Le presenter approprié
     *
     * @throws \RuntimeException Si aucun presenter ne supporte ce type d'asset
     */
    public function getPresenter(Asset $asset): AssetPresenterInterface
    {
        foreach ($this->presenters as $presenter) {
            if ($presenter->supports($asset)) {
                return $presenter;
            }
        }

        throw new \RuntimeException(sprintf('Aucun AssetPresenter trouvé pour le type d\'asset "%s". Types supportés : GoogleAds, BingAds, InstagramPost, FacebookPost, LinkedInPost, Email, IAB, Article.', $asset->getAssetType()));
    }
}
