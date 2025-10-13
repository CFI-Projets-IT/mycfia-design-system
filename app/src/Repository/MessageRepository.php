<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\Message;
use App\Enum\MessageStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Récupère les statistiques d'une campagne.
     *
     * @param Campaign $campaign Campagne cible
     *
     * @return array{total: int, sent: int, delivered: int, failed: int}
     */
    public function getStatsByCampaign(Campaign $campaign): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id) as total')
            ->addSelect('SUM(CASE WHEN m.status = :sent THEN 1 ELSE 0 END) as sent')
            ->addSelect('SUM(CASE WHEN m.status = :delivered THEN 1 ELSE 0 END) as delivered')
            ->addSelect('SUM(CASE WHEN m.status = :failed THEN 1 ELSE 0 END) as failed')
            ->where('m.campaign = :campaign')
            ->andWhere('m.deletedAt IS NULL')
            ->setParameter('campaign', $campaign)
            ->setParameter('sent', MessageStatus::SENT->value)
            ->setParameter('delivered', MessageStatus::DELIVERED->value)
            ->setParameter('failed', MessageStatus::FAILED->value);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) $result['total'],
            'sent' => (int) $result['sent'],
            'delivered' => (int) $result['delivered'],
            'failed' => (int) $result['failed'],
        ];
    }
}
