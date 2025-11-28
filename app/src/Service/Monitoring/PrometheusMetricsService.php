<?php

declare(strict_types=1);

namespace App\Service\Monitoring;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service central de gestion des métriques Prometheus.
 *
 * Responsabilités :
 * - Initialiser le CollectorRegistry avec stockage Redis
 * - Fournir des méthodes helpers pour métriques courantes
 * - Gérer les labels cohérents (service, method, etc.)
 *
 * Métriques disponibles :
 * - HTTP (requests, duration)
 * - API CFI (duration, errors, cache)
 * - IA (requests, tokens, errors)
 * - Cache Symfony
 */
final class PrometheusMetricsService
{
    private CollectorRegistry $registry;

    public function __construct(
        #[Autowire('%prometheus.storage.redis.host%')]
        string $redisHost,
        #[Autowire('%prometheus.storage.redis.port%')]
        int $redisPort,
        #[Autowire('%prometheus.storage.redis.database%')]
        int $redisDatabase,
    ) {
        // Configuration Redis pour stockage persistant des métriques
        Redis::setDefaultOptions([
            'host' => $redisHost,
            'port' => $redisPort,
            'database' => $redisDatabase,
            'timeout' => 0.1,
            'read_timeout' => 10,
            'persistent_connections' => true,
        ]);

        $this->registry = new CollectorRegistry(new Redis());
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }

    // ========================================
    // Métriques HTTP
    // ========================================

    /**
     * Incrémente le compteur de requêtes HTTP.
     *
     * @param string $method     Méthode HTTP (GET, POST, etc.)
     * @param string $route      Nom de la route Symfony
     * @param int    $statusCode Code HTTP (200, 404, 500, etc.)
     */
    public function incrementHttpRequestCounter(string $method, string $route, int $statusCode): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'app',
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'route', 'status']
        );

        $counter->inc([$method, $route, (string) $statusCode]);
    }

    /**
     * Enregistre la durée d'une requête HTTP.
     *
     * @param string $method          Méthode HTTP
     * @param string $route           Nom de la route
     * @param float  $durationSeconds Durée en secondes
     */
    public function observeHttpRequestDuration(string $method, string $route, float $durationSeconds): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            'app',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route'],
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10]
        );

        $histogram->observe($durationSeconds, [$method, $route]);
    }

    // ========================================
    // Métriques API CFI
    // ========================================

    /**
     * Enregistre la durée d'un appel API CFI.
     *
     * @param string $service    Service API (facturation, operations, stocks, etats)
     * @param string $method     Méthode API appelée
     * @param float  $durationMs Durée en millisecondes
     */
    public function observeApiCallDuration(string $service, string $method, float $durationMs): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            'cfi',
            'api_call_duration_milliseconds',
            'CFI API call duration in milliseconds',
            ['service', 'method'],
            [10, 50, 100, 250, 500, 1000, 2500, 5000, 10000]
        );

        $histogram->observe($durationMs, [$service, $method]);
    }

    /**
     * Incrémente le compteur d'erreurs API CFI.
     *
     * @param string $service   Service API
     * @param string $errorType Type d'erreur (timeout, http_error, invalid_response)
     */
    public function incrementApiErrorCounter(string $service, string $errorType): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'cfi',
            'api_errors_total',
            'Total CFI API errors',
            ['service', 'error_type']
        );

        $counter->inc([$service, $errorType]);
    }

    // ========================================
    // Métriques Cache
    // ========================================

    /**
     * Incrémente le compteur de cache (HIT ou MISS).
     *
     * @param string $status Status du cache (HIT, MISS)
     */
    public function incrementCacheCounter(string $status): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'app',
            'cache_operations_total',
            'Total cache operations',
            ['status']
        );

        $counter->inc([$status]);
    }

    // ========================================
    // Métriques IA
    // ========================================

    /**
     * Incrémente le compteur de requêtes IA.
     *
     * @param string $model    Modèle IA utilisé (gpt-4, mistral-large, etc.)
     * @param string $tenantId ID du tenant (pour multi-tenant)
     */
    public function incrementAiRequestCounter(string $model, string $tenantId): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'ai',
            'requests_total',
            'Total AI requests',
            ['model', 'tenant_id']
        );

        $counter->inc([$model, $tenantId]);
    }

    /**
     * Observe le nombre de tokens utilisés.
     *
     * @param string $model  Modèle IA
     * @param int    $tokens Nombre de tokens
     */
    public function observeAiTokensUsed(string $model, int $tokens): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            'ai',
            'tokens_used',
            'AI tokens used per request',
            ['model'],
            [10, 50, 100, 500, 1000, 2000, 4000, 8000, 16000, 32000]
        );

        $histogram->observe($tokens, [$model]);
    }

    /**
     * Incrémente le compteur d'erreurs IA.
     *
     * @param string $errorType Type d'erreur (rate_limit, timeout, invalid_response)
     */
    public function incrementAiErrorCounter(string $errorType): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'ai',
            'errors_total',
            'Total AI errors',
            ['type']
        );

        $counter->inc([$errorType]);
    }

    // ========================================
    // Métriques Business
    // ========================================

    /**
     * Définit le nombre d'utilisateurs actifs.
     *
     * @param int $count Nombre d'utilisateurs actifs
     */
    public function setActiveUsers(int $count): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            'app',
            'active_users',
            'Currently active users'
        );

        $gauge->set($count);
    }
}
