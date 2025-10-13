<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\User;
use App\Enum\CampaignStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campaign>
 */
class CampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    /**
     * Récupère les campagnes d'un utilisateur selon le statut.
     *
     * @param User                 $user   Utilisateur propriétaire
     * @param CampaignStatus|'all' $status Statut (CampaignStatus enum ou 'all')
     * @param int                  $limit  Nombre maximum de résultats
     *
     * @return Campaign[]
     */
    public function findByUserAndStatus(User $user, CampaignStatus|string $status = 'all', int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ('all' !== $status) {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
