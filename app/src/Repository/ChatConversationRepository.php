<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ChatConversation;
use App\Entity\Division;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les conversations chat.
 *
 * @extends ServiceEntityRepository<ChatConversation>
 */
class ChatConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatConversation::class);
    }

    public function save(ChatConversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChatConversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Recherche les conversations d'un utilisateur avec filtres.
     *
     * @return ChatConversation[]
     */
    public function findByUserAndTenantWithFilters(
        User $user,
        Division $tenant,
        ?string $search = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        ?bool $favoritesOnly = null
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.tenant = :tenant')
            ->setParameter('user', $user)
            ->setParameter('tenant', $tenant)
            ->orderBy('c.updatedAt', 'DESC');

        if ($search) {
            $qb->andWhere('c.title LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($dateDebut) {
            $qb->andWhere('c.createdAt >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('c.createdAt <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        if (true === $favoritesOnly) {
            $qb->andWhere('c.isFavorite = true');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère une conversation avec ses messages (jointure optimisée).
     */
    public function findOneByIdWithMessages(int $id): ?ChatConversation
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.messages', 'm')
            ->addSelect('m')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre de conversations d'un utilisateur.
     */
    public function countByUser(User $user, Division $tenant): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user')
            ->andWhere('c.tenant = :tenant')
            ->setParameter('user', $user)
            ->setParameter('tenant', $tenant)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les conversations favorites d'un utilisateur.
     *
     * @return ChatConversation[]
     */
    public function findFavoritesByUser(User $user, Division $tenant, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.tenant = :tenant')
            ->andWhere('c.isFavorite = true')
            ->setParameter('user', $user)
            ->setParameter('tenant', $tenant)
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les conversations récentes d'un utilisateur.
     *
     * @return ChatConversation[]
     */
    public function findRecentByUser(User $user, Division $tenant, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.tenant = :tenant')
            ->setParameter('user', $user)
            ->setParameter('tenant', $tenant)
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère la dernière conversation d'un utilisateur pour un contexte spécifique.
     *
     * Utilisé pour charger automatiquement la dernière conversation lorsque
     * l'utilisateur accède à un contexte (factures, commandes, stocks, general).
     *
     * @param User   $user     Utilisateur propriétaire
     * @param int    $tenantId ID de la division (idDivision CFI)
     * @param string $context  Contexte du chat
     */
    public function findLatestByUserAndContext(User $user, int $tenantId, string $context): ?ChatConversation
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.tenant', 't')
            ->where('c.user = :user')
            ->andWhere('t.idDivision = :tenantId')
            ->andWhere('c.context = :context')
            ->setParameter('user', $user)
            ->setParameter('tenantId', $tenantId)
            ->setParameter('context', $context)
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
