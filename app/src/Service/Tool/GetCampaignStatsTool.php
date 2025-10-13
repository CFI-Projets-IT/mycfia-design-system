<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\Repository\CampaignRepository;
use App\Repository\MessageRepository;
use Symfony\Component\Uid\Uuid;

/**
 * Tool IA pour récupérer les statistiques détaillées d'une campagne marketing.
 *
 * Retourne le nombre total de messages, envoyés, délivrés, échoués et le taux de délivrance.
 *
 * NOTE: L'attribut #[AsTool] sera ajouté une fois Symfony AI Bundle configuré (Étape 6)
 * Pour l'instant, le tool est enregistré manuellement dans services.yaml
 */
final readonly class GetCampaignStatsTool
{
    public function __construct(
        private CampaignRepository $campaignRepository,
        private MessageRepository $messageRepository,
    ) {
    }

    /**
     * Récupère les statistiques d'une campagne.
     *
     * @param string $campaignId UUID de la campagne
     *
     * @return array<string, mixed>
     */
    public function __invoke(string $campaignId): array
    {
        try {
            $uuid = Uuid::fromString($campaignId);
        } catch (\InvalidArgumentException) {
            return ['error' => 'Invalid UUID format'];
        }

        $campaign = $this->campaignRepository->find($uuid);

        if (null === $campaign) {
            return ['error' => 'Campaign not found'];
        }

        $stats = $this->messageRepository->getStatsByCampaign($campaign);

        return [
            'name' => $campaign->getName(),
            'total_messages' => $stats['total'],
            'sent' => $stats['sent'],
            'delivered' => $stats['delivered'],
            'failed' => $stats['failed'],
            'delivery_rate' => $stats['total'] > 0 ? round(($stats['delivered'] / $stats['total']) * 100, 2) : 0.0,
        ];
    }
}
