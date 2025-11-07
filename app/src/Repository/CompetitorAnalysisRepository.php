<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CompetitorAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les analyses concurrentielles.
 *
 * @extends ServiceEntityRepository<CompetitorAnalysis>
 */
class CompetitorAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompetitorAnalysis::class);
    }

    public function save(CompetitorAnalysis $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CompetitorAnalysis $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
