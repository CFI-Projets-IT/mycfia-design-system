<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Monitoring\PrometheusMetricsService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event Subscriber pour collecter automatiquement les métriques HTTP.
 *
 * Collecte :
 * - Nombre de requêtes HTTP par méthode/route/status
 * - Durée des requêtes HTTP
 *
 * Priority :
 * - REQUEST : 1024 (très tôt, avant autres listeners)
 * - RESPONSE : -1024 (très tard, après autres listeners)
 */
final class MetricsSubscriber implements EventSubscriberInterface
{
    /**
     * Stockage des temps de début de requête.
     *
     * @var array<int, float>
     */
    private array $startTimes = [];

    public function __construct(
        private readonly PrometheusMetricsService $prometheusService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024],
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }

    /**
     * Démarrer le chronomètre au début de la requête.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = spl_object_id($request);

        $this->startTimes[$requestId] = microtime(true);
    }

    /**
     * Enregistrer les métriques à la fin de la requête.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestId = spl_object_id($request);

        // Calcul de la durée
        $duration = 0.0;
        if (isset($this->startTimes[$requestId])) {
            $duration = microtime(true) - $this->startTimes[$requestId];
            unset($this->startTimes[$requestId]);
        }

        $method = $request->getMethod();
        $route = $request->attributes->getString('_route', 'unknown');
        $statusCode = $response->getStatusCode();

        // Ne pas tracker l'endpoint /metrics lui-même (évite boucle)
        if ('metrics' === $route) {
            return;
        }

        // Enregistrer les métriques
        $this->prometheusService->incrementHttpRequestCounter($method, $route, $statusCode);
        $this->prometheusService->observeHttpRequestDuration($method, $route, $duration);
    }
}
