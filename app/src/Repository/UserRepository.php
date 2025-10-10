<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Trouve un utilisateur par son ID CFI (idCfi).
     */
    public function findByIdCfi(int $idCfi): ?User
    {
        return $this->findOneBy(['idCfi' => $idCfi]);
    }

    /**
     * Trouve un utilisateur par son email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Trouve tous les utilisateurs d'une division.
     *
     * @return User[]
     */
    public function findByDivision(int $divisionId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.division = :divisionId')
            ->setParameter('divisionId', $divisionId)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les utilisateurs récemment connectés.
     *
     * @return User[]
     */
    public function findRecentlyActive(int $days = 30): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('u')
            ->andWhere('u.lastLoginAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('u.lastLoginAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
