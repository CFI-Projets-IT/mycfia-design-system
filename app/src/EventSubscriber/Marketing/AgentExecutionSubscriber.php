<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Service\MercureNotificationPublisher;
use Gorillias\MarketingBundle\Event\AgentExecutionCompletedEvent;
use Gorillias\MarketingBundle\Event\AgentExecutionFailedEvent;
use Gorillias\MarketingBundle\Event\AgentExecutionStartedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber pour les nouveaux evenements AgentExecution* du bundle v3.32.0.
 *
 * Ce subscriber centralise :
 * - Le logging des metriques LLM pour Grafana (via canal llm)
 * - Les notifications utilisateur via Mercure
 * - L'alerting en cas de seuils depasses (duree, tokens, cout)
 *
 * Events ecoutes (bundle marketing-ai v3.32.0+):
 * - AgentExecutionStartedEvent : Debut d'execution d'un agent
 * - AgentExecutionCompletedEvent : Fin avec succes + metriques LLM
 * - AgentExecutionFailedEvent : Echec avec exception
 *
 * @see AgentExecutionStartedEvent
 * @see AgentExecutionCompletedEvent
 * @see AgentExecutionFailedEvent
 */
final readonly class AgentExecutionSubscriber implements EventSubscriberInterface
{
    /** Seuil de duree pour alerting (30 secondes) */
    private const SLOW_EXECUTION_THRESHOLD_MS = 30000;

    /** Seuil de tokens pour alerting (10k tokens) */
    private const HIGH_TOKEN_THRESHOLD = 10000;

    /** Seuil de cout pour alerting ($0.10) */
    private const HIGH_COST_THRESHOLD_USD = 0.10;

    public function __construct(
        private MercureNotificationPublisher $notificationPublisher,
        #[Autowire(service: 'monolog.logger.llm')]
        private LoggerInterface $llmLogger,
        #[Autowire(service: 'monolog.logger.marketing.general')]
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AgentExecutionStartedEvent::class => ['onAgentStarted', 0],
            AgentExecutionCompletedEvent::class => ['onAgentCompleted', 0],
            AgentExecutionFailedEvent::class => ['onAgentFailed', 0],
        ];
    }

    /**
     * Gere le demarrage d'execution d'un agent.
     *
     * Actions :
     * - Log du demarrage
     * - Notification Mercure si userId present
     */
    public function onAgentStarted(AgentExecutionStartedEvent $event): void
    {
        $signature = $event->getSignature();
        $context = $event->context;

        $this->logger->info('Agent execution started', [
            'agent' => $event->getAgentShortName(),
            'method' => $event->methodName,
            'signature' => $signature,
            'user_id' => $context->userId,
            'project_id' => $context->projectId,
            'client_id' => $context->clientId,
        ]);

        // Notification Mercure si userId present
        if (null !== $context->userId) {
            try {
                $this->notificationPublisher->publish(
                    userId: $context->userId,
                    type: 'agent.started',
                    data: [
                        'agent' => $event->getAgentShortName(),
                        'method' => $event->methodName,
                        'projectId' => $context->projectId,
                    ]
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to publish agent.started notification', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Gere la fin d'execution reussie d'un agent.
     *
     * Actions :
     * - Log des metriques LLM pour Grafana
     * - Alerting si seuils depasses
     * - Notification Mercure si userId present
     */
    public function onAgentCompleted(AgentExecutionCompletedEvent $event): void
    {
        $signature = $event->getSignature();
        $context = $event->context;
        $result = $event->result;

        // Log des metriques LLM pour Grafana (format structuré)
        $this->llmLogger->info('Agent LLM Call', [
            'step' => $event->methodName,
            'agent' => $event->getAgentShortName(),
            'signature' => $signature,
            'user_id' => $context->userId,
            'project_id' => $context->projectId,
            'client_id' => $context->clientId,
            'tokens_input' => $result->tokensInput,
            'tokens_output' => $result->tokensOutput,
            'total_tokens' => $result->tokensTotal,
            'duration_ms' => $result->durationMs,
            'cost' => $result->cost,
            'model' => $result->modelUsed,
        ]);

        // Alerting si seuils depasses
        $this->checkThresholdsAndAlert($event);

        // Notification Mercure si userId present
        if (null !== $context->userId) {
            try {
                $this->notificationPublisher->publish(
                    userId: $context->userId,
                    type: 'agent.completed',
                    data: [
                        'agent' => $event->getAgentShortName(),
                        'method' => $event->methodName,
                        'projectId' => $context->projectId,
                        'durationMs' => $result->durationMs,
                        'tokensTotal' => $result->tokensTotal,
                        'cost' => $result->cost,
                    ]
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to publish agent.completed notification', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Agent execution completed', [
            'signature' => $signature,
            'duration_ms' => $result->durationMs,
            'tokens_total' => $result->tokensTotal,
            'cost' => $result->cost,
        ]);
    }

    /**
     * Gere l'echec d'execution d'un agent.
     *
     * Actions :
     * - Log d'erreur avec stack trace
     * - Notification Mercure si userId present
     */
    public function onAgentFailed(AgentExecutionFailedEvent $event): void
    {
        $signature = $event->getSignature();
        $context = $event->context;

        // Log d'erreur avec details
        $this->logger->error('Agent execution failed', [
            'agent' => $event->getAgentShortName(),
            'method' => $event->methodName,
            'signature' => $signature,
            'user_id' => $context->userId,
            'project_id' => $context->projectId,
            'client_id' => $context->clientId,
            'error_type' => $event->getExceptionType(),
            'error_message' => $event->getErrorMessage(),
            'error_location' => $event->getErrorLocation(),
            'is_recoverable' => $event->isRecoverable(),
            'is_llm_error' => $event->isLlmError(),
        ]);

        // Log stack trace separement pour ne pas polluer les metriques
        $this->logger->debug('Agent execution failed - stack trace', [
            'signature' => $signature,
            'stack_trace' => $event->getStackTrace(),
        ]);

        // Notification Mercure si userId present
        if (null !== $context->userId) {
            try {
                $this->notificationPublisher->publish(
                    userId: $context->userId,
                    type: 'agent.failed',
                    data: [
                        'agent' => $event->getAgentShortName(),
                        'method' => $event->methodName,
                        'projectId' => $context->projectId,
                        'error' => $event->getErrorMessage(),
                        'isRecoverable' => $event->isRecoverable(),
                    ]
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to publish agent.failed notification', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Verifie les seuils et alerte si depasses.
     */
    private function checkThresholdsAndAlert(AgentExecutionCompletedEvent $event): void
    {
        $signature = $event->getSignature();
        $result = $event->result;
        $context = $event->context;

        // Alerte execution lente
        if ($event->isSlowExecution(self::SLOW_EXECUTION_THRESHOLD_MS)) {
            $this->logger->warning('Slow agent execution detected', [
                'signature' => $signature,
                'duration_ms' => $result->durationMs,
                'threshold_ms' => self::SLOW_EXECUTION_THRESHOLD_MS,
                'user_id' => $context->userId,
                'project_id' => $context->projectId,
            ]);
        }

        // Alerte usage tokens eleve
        if ($event->isHighTokenUsage(self::HIGH_TOKEN_THRESHOLD)) {
            $this->logger->warning('High token usage detected', [
                'signature' => $signature,
                'tokens_total' => $result->tokensTotal,
                'threshold' => self::HIGH_TOKEN_THRESHOLD,
                'user_id' => $context->userId,
                'project_id' => $context->projectId,
            ]);
        }

        // Alerte cout eleve
        if ($event->isHighCost(self::HIGH_COST_THRESHOLD_USD)) {
            $this->logger->warning('High cost agent execution detected', [
                'signature' => $signature,
                'cost' => $result->cost,
                'threshold_usd' => self::HIGH_COST_THRESHOLD_USD,
                'user_id' => $context->userId,
                'project_id' => $context->projectId,
            ]);
        }
    }
}
