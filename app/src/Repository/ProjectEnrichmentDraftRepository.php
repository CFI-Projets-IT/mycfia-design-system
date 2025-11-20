<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ProjectEnrichmentDraft;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectEnrichmentDraft>
 */
class ProjectEnrichmentDraftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectEnrichmentDraft::class);
    }

    /**
     * Récupère le draft d'enrichissement par task ID.
     */
    public function findOneByTaskId(string $taskId): ?ProjectEnrichmentDraft
    {
        return $this->findOneBy(['taskId' => $taskId]);
    }

    /**
     * Supprime tous les drafts validés ou rejetés de plus de 30 jours.
     */
    public function deleteOldDrafts(): int
    {
        $thirtyDaysAgo = new \DateTimeImmutable('-30 days');

        return (int) $this->createQueryBuilder('ped')
            ->delete()
            ->where('ped.status IN (:statuses)')
            ->andWhere('ped.createdAt < :thirtyDaysAgo')
            ->setParameter('statuses', ['validated', 'rejected'])
            ->setParameter('thirtyDaysAgo', $thirtyDaysAgo)
            ->getQuery()
            ->execute();
    }
}
