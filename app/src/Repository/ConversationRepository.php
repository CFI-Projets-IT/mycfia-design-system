<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /**
     * Recherche conversations d'un utilisateur par mots-clés.
     *
     * @param User   $user  Utilisateur propriétaire
     * @param string $query Mots-clés à rechercher dans le titre
     * @param int    $limit Nombre maximum de résultats
     *
     * @return Conversation[]
     */
    public function searchByUser(User $user, string $query, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.title LIKE :query')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
