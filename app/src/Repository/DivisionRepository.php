<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Division;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Division>
 */
class DivisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Division::class);
    }

    /**
     * Trouve une division par son ID CFI (idDivision).
     */
    public function findByIdDivision(int $idDivision): ?Division
    {
        return $this->findOneBy(['idDivision' => $idDivision]);
    }

    /**
     * Trouve une division active par son slug.
     */
    public function findActiveBySlug(string $slug): ?Division
    {
        return $this->findOneBy([
            'slug' => $slug,
            'isActive' => true,
        ]);
    }

    /**
     * Trouve toutes les divisions actives.
     *
     * @return Division[]
     */
    public function findAllActive(): array
    {
        return $this->findBy(['isActive' => true], ['nomDivision' => 'ASC']);
    }
}
