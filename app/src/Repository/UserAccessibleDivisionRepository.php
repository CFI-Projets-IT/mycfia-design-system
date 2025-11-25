<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Division;
use App\Entity\User;
use App\Entity\UserAccessibleDivision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour gérer les divisions accessibles par utilisateur.
 *
 * @extends ServiceEntityRepository<UserAccessibleDivision>
 */
class UserAccessibleDivisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAccessibleDivision::class);
    }

    /**
     * Récupère toutes les divisions accessibles pour un utilisateur.
     *
     * @return array<int, Division>
     */
    public function findDivisionsByUser(User $user): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $results = $qb
            ->select('d')
            ->from(Division::class, 'd')
            ->innerJoin(UserAccessibleDivision::class, 'uad', 'WITH', 'uad.division = d')
            ->where('uad.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.nomDivision', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * Supprime toutes les divisions accessibles pour un utilisateur.
     *
     * Utilisé lors de la synchronisation pour nettoyer les anciennes relations
     * avant d'insérer les nouvelles depuis l'API CFI.
     */
    public function clearUserDivisions(User $user): void
    {
        $this->createQueryBuilder('uad')
            ->delete()
            ->where('uad.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère la date de dernière synchronisation pour un utilisateur.
     *
     * Retourne null si aucune synchronisation n'a jamais été effectuée.
     */
    public function getLastSyncDate(User $user): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('uad')
            ->select('MAX(uad.syncedAt)')
            ->where('uad.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return \is_string($result) ? new \DateTimeImmutable($result) : null;
    }

    /**
     * Vérifie si un utilisateur a accès à une division spécifique.
     *
     * @param User $user       Utilisateur à vérifier
     * @param int  $idDivision ID CFI de la division
     */
    public function hasAccess(User $user, int $idDivision): bool
    {
        $count = $this->createQueryBuilder('uad')
            ->select('COUNT(uad.id)')
            ->join('uad.division', 'd')
            ->where('uad.user = :user')
            ->andWhere('d.idDivision = :idDivision')
            ->setParameter('user', $user)
            ->setParameter('idDivision', $idDivision)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Compte le nombre de divisions accessibles pour un utilisateur.
     */
    public function countAccessibleDivisions(User $user): int
    {
        return (int) $this->createQueryBuilder('uad')
            ->select('COUNT(uad.id)')
            ->where('uad.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les utilisateurs ayant accès à une division.
     *
     * @return array<int, User>
     */
    public function findUsersByDivision(Division $division): array
    {
        $results = $this->createQueryBuilder('uad')
            ->select('u')
            ->join('uad.user', 'u')
            ->where('uad.division = :division')
            ->setParameter('division', $division)
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }
}
