<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findByUuid(string $uuid): ?Task
    {
        return $this->findOneBy(['uuid' => Uuid::fromString($uuid)]);
    }

    /**
     * @return list<Task>
     */
    public function findCompletedSince(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.completedAt >= :since')
            ->setParameter('status', 'completed')
            ->setParameter('since', $since)
            ->orderBy('t.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Task>
     */
    public function findFailedSince(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.completedAt >= :since')
            ->setParameter('status', 'failed')
            ->setParameter('since', $since)
            ->orderBy('t.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les tâches bloquées en processing depuis plus de N minutes.
     *
     * @return list<Task>
     */
    public function findStuckTasks(int $minutes = 5): array
    {
        $threshold = new \DateTimeImmutable("-{$minutes} minutes");

        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.startedAt < :threshold')
            ->setParameter('status', 'processing')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * Analytics : Coût moyen par agent.
     */
    public function getAverageCostByAgent(string $agentClass): ?float
    {
        $result = $this->createQueryBuilder('t')
            ->select('AVG(t.cost) as avg_cost')
            ->where('t.agentClass = :agent')
            ->andWhere('t.status = :status')
            ->setParameter('agent', $agentClass)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return null !== $result ? (float) $result : null;
    }

    /**
     * Analytics : Durée moyenne par agent.
     */
    public function getAverageDurationByAgent(string $agentClass): ?float
    {
        $result = $this->createQueryBuilder('t')
            ->select('AVG(t.durationMs) as avg_duration')
            ->where('t.agentClass = :agent')
            ->andWhere('t.status = :status')
            ->setParameter('agent', $agentClass)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return null !== $result ? (float) $result : null;
    }

    /**
     * Analytics : Coût total par agent.
     *
     * @return array<int, array{agentClass: string, total_cost: float, task_count: int}>
     */
    public function getTotalCostByAgent(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.agentClass, SUM(t.cost) as total_cost, COUNT(t.id) as task_count')
            ->where('t.status = :status')
            ->setParameter('status', 'completed')
            ->groupBy('t.agentClass')
            ->orderBy('total_cost', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Analytics : Durée moyenne par type de tâche.
     *
     * @return array<int, array{type: string, avg_duration: float}>
     */
    public function getAverageDurationByType(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.type, AVG(t.durationMs) as avg_duration')
            ->where('t.status = :status')
            ->setParameter('status', 'completed')
            ->groupBy('t.type')
            ->orderBy('avg_duration', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Analytics : Taux d'erreur par agent.
     *
     * @return array<int, array{agentClass: string, failures: int, total: int, error_rate: float}>
     */
    public function getErrorRateByAgent(): array
    {
        $qb = $this->createQueryBuilder('t');

        $results = $qb
            ->select('t.agentClass')
            ->addSelect('COUNT(CASE WHEN t.status = \'failed\' THEN 1 END) as failures')
            ->addSelect('COUNT(t.id) as total')
            ->groupBy('t.agentClass')
            ->getQuery()
            ->getResult();

        // Calculer le taux d'erreur en pourcentage
        return array_map(function ($row) {
            $row['error_rate'] = $row['total'] > 0
                ? round(($row['failures'] / $row['total']) * 100, 2)
                : 0.0;

            return $row;
        }, $results);
    }

    /**
     * Analytics : Tokens totaux consommés par agent.
     *
     * @return array<int, array{agentClass: string, total_tokens: int, task_count: int}>
     */
    public function getTotalTokensByAgent(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.agentClass, SUM(t.tokensTotal) as total_tokens, COUNT(t.id) as task_count')
            ->where('t.status = :status')
            ->setParameter('status', 'completed')
            ->groupBy('t.agentClass')
            ->orderBy('total_tokens', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Purge les tâches complétées plus anciennes que N jours.
     */
    public function purgeOldTasks(int $days = 30): int
    {
        $before = new \DateTimeImmutable("-{$days} days");

        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.completedAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }
}
