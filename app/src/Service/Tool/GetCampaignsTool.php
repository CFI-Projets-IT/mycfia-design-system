<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\Entity\User;
use App\Repository\CampaignRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Tool IA pour récupérer les campagnes marketing de l'utilisateur connecté.
 *
 * Permet de filtrer par statut et limiter le nombre de résultats.
 *
 * NOTE: L'attribut #[AsTool] sera ajouté une fois Symfony AI Bundle configuré (Étape 6)
 * Pour l'instant, le tool est enregistré manuellement dans services.yaml
 */
final readonly class GetCampaignsTool
{
    public function __construct(
        private CampaignRepository $campaignRepository,
        private Security $security,
    ) {
    }

    /**
     * Récupère les campagnes marketing de l'utilisateur.
     *
     * @param string $status Statut des campagnes à récupérer (all, active, draft, completed, paused)
     * @param int    $limit  Nombre maximum de résultats (1-100)
     *
     * @return array<int|string, mixed>
     */
    public function __invoke(
        string $status = 'all',
        int $limit = 10
    ): array {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return ['error' => 'User not authenticated'];
        }

        $campaigns = $this->campaignRepository->findByUserAndStatus($user, $status, $limit);

        return array_map(fn ($campaign) => [
            'id' => $campaign->getId()->toString(),
            'name' => $campaign->getName(),
            'slug' => $campaign->getSlug(),
            'type' => $campaign->getType()->value,
            'status' => $campaign->getStatus()->value,
            'budget' => $campaign->getBudget() ? (float) $campaign->getBudget() : null,
            'startDate' => $campaign->getStartDate()?->format('Y-m-d'),
            'endDate' => $campaign->getEndDate()?->format('Y-m-d'),
        ], $campaigns);
    }
}
