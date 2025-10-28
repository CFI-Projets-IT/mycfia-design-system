<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ChatConversation;
use App\Entity\ChatMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les messages chat.
 *
 * @extends ServiceEntityRepository<ChatMessage>
 */
class ChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatMessage::class);
    }

    public function save(ChatMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChatMessage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère les messages d'une conversation (ordre chronologique).
     *
     * @return ChatMessage[]
     */
    public function findByConversation(ChatConversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche dans le contenu des messages (full-text).
     *
     * @return ChatMessage[]
     */
    public function searchInContent(ChatConversation $conversation, string $query): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.conversation = :conversation')
            ->andWhere('m.content LIKE :query')
            ->setParameter('conversation', $conversation)
            ->setParameter('query', '%'.$query.'%')
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
