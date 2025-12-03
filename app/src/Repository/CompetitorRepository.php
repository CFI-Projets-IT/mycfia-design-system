<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Competitor;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Competitor>
 */
class CompetitorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Competitor::class);
    }

    /**
     * Récupère tous les concurrents sélectionnés pour un projet.
     *
     * @return array<Competitor>
     */
    public function findSelectedByProject(Project $project): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.project = :project')
            ->andWhere('c.selected = :selected')
            ->setParameter('project', $project)
            ->setParameter('selected', true)
            ->orderBy('c.alignmentScore', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de concurrents sélectionnés pour un projet.
     */
    public function countSelectedByProject(Project $project): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.project = :project')
            ->andWhere('c.selected = :selected')
            ->setParameter('project', $project)
            ->setParameter('selected', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
