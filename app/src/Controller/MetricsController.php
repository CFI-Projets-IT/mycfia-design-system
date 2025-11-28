<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Monitoring\PrometheusMetricsService;
use Prometheus\RenderTextFormat;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller exposant l'endpoint /metrics pour Prometheus.
 *
 * Endpoint : GET /metrics
 * Format : Prometheus text format
 */
final class MetricsController extends AbstractController
{
    #[Route('/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(PrometheusMetricsService $prometheusService): Response
    {
        $registry = $prometheusService->getRegistry();
        $renderer = new RenderTextFormat();

        $metricFamilySamples = $registry->getMetricFamilySamples();
        $result = $renderer->render($metricFamilySamples);

        return new Response(
            $result,
            Response::HTTP_OK,
            ['Content-Type' => RenderTextFormat::MIME_TYPE]
        );
    }
}
