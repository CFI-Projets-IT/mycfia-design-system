<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\User;
use App\Repository\UserAccessibleDivisionRepository;
use App\Service\Cfi\CfiTenantService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('DivisionSelector')]
final class DivisionSelectorComponent
{
    public function __construct(
        private readonly Security $security,
        private readonly CfiTenantService $tenantService,
        private readonly UserAccessibleDivisionRepository $divisionRepository
    ) {
    }

    /**
     * Récupère le nom de la division actuellement active.
     *
     * @return string Nom de la division ou texte par défaut si non trouvée
     */
    public function getCurrentDivisionName(): string
    {
        try {
            $user = $this->security->getUser();

            if (! $user instanceof User) {
                return 'Aucune division';
            }

            // Récupérer l'ID du tenant actuel
            $currentTenantId = $this->tenantService->getCurrentTenantOrNull();

            if (null === $currentTenantId) {
                return 'Aucune division';
            }

            // Récupérer toutes les divisions de l'utilisateur
            $divisions = $this->divisionRepository->findDivisionsByUser($user);

            // Trouver la division actuelle
            foreach ($divisions as $division) {
                if ($division->getIdDivision() === $currentTenantId) {
                    return $division->getNomDivision();
                }
            }

            // Division non trouvée dans la liste (cas rare)
            return sprintf('Division #%d', $currentTenantId);
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un texte neutre
            return 'Sélectionner une division';
        }
    }
}
