<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AiLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Service de logging structuré pour toutes les opérations IA.
 *
 * Permet de journaliser :
 * - Les requêtes utilisateur et réponses IA
 * - Les appels de tools (function-calling)
 * - Les erreurs IA avec contexte complet
 */
readonly class AiLoggerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Logger une requête IA (query utilisateur + réponse assistant).
     *
     * @param User                 $user       Utilisateur ayant fait la requête
     * @param string               $input      Requête utilisateur
     * @param string               $output     Réponse de l'assistant IA
     * @param int                  $durationMs Durée de traitement en millisecondes
     * @param array<string, mixed> $metadata   Métadonnées additionnelles (model, tokens, etc.)
     */
    public function logQuery(
        User $user,
        string $input,
        string $output,
        int $durationMs,
        array $metadata = []
    ): AiLog {
        return $this->createLog(
            user: $user,
            action: 'query',
            input: $input,
            output: $output,
            durationMs: $durationMs,
            metadata: $metadata
        );
    }

    /**
     * Logger un appel de tool (function-calling).
     *
     * @param User                 $user       Utilisateur propriétaire
     * @param string               $toolName   Nom du tool appelé
     * @param array<string, mixed> $params     Paramètres passés au tool
     * @param mixed                $result     Résultat retourné par le tool
     * @param int                  $durationMs Durée d'exécution en millisecondes
     *
     * @throws \JsonException
     */
    public function logToolCall(
        User $user,
        string $toolName,
        array $params,
        mixed $result,
        int $durationMs
    ): AiLog {
        return $this->createLog(
            user: $user,
            action: 'tool_call',
            input: json_encode(['tool' => $toolName, 'params' => $params], JSON_THROW_ON_ERROR),
            output: json_encode($result, JSON_THROW_ON_ERROR),
            durationMs: $durationMs,
            metadata: ['tool_name' => $toolName]
        );
    }

    /**
     * Logger une erreur IA.
     *
     * @param User                 $user      Utilisateur concerné
     * @param string               $action    Action en cours lors de l'erreur
     * @param \Throwable           $exception Exception levée
     * @param array<string, mixed> $context   Contexte additionnel
     */
    public function logError(
        User $user,
        string $action,
        \Throwable $exception,
        array $context = []
    ): AiLog {
        return $this->createLog(
            user: $user,
            action: 'error',
            input: $action,
            output: $exception->getMessage(),
            durationMs: 0,
            metadata: [
                'exception' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'context' => $context,
            ]
        );
    }

    /**
     * Créer et persister un log IA.
     *
     * @param User                 $user       Utilisateur propriétaire
     * @param string               $action     Action loggée (query, tool_call, error)
     * @param string               $input      Input de l'action
     * @param string|null          $output     Output de l'action
     * @param int                  $durationMs Durée d'exécution en ms
     * @param array<string, mixed> $metadata   Métadonnées additionnelles
     */
    private function createLog(
        User $user,
        string $action,
        string $input,
        ?string $output,
        int $durationMs,
        array $metadata = []
    ): AiLog {
        $log = new AiLog();
        $log->setUser($user);
        $log->setAction($action);
        $log->setInput($input);
        $log->setOutput($output);
        $log->setDurationMs($durationMs);
        $log->setCorrelationId(Uuid::v4());
        $log->setMetadata($metadata);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }
}
