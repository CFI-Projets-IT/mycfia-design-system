<?php

declare(strict_types=1);

namespace App\Service\Cfi;

use App\Exception\CfiAccessDeniedException;
use App\Exception\CfiApiException;
use App\Exception\CfiTokenExpiredException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service de base pour communiquer avec l'API CFI.
 *
 * Gere les requetes HTTP POST avec header custom "Jeton",
 * la gestion des erreurs, retry, timeout, correlation ID et logging.
 *
 * Chaque requete genere un correlation ID unique (UUID v4) pour le tracing.
 */
readonly class CfiApiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(service: 'monolog.logger.cfi_api')]
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private string $cfiApiBaseUrl,
        private int $cfiApiTimeout,
    ) {
    }

    /**
     * Effectue une requete POST vers l'API CFI.
     *
     * Genere automatiquement un correlation ID (UUID v4) pour tracer la requete.
     * Gere specifiquement les erreurs 401 (token expire) et 403 (acces refuse).
     *
     * @param string               $endpoint Endpoint relatif (ex: "/Utilisateurs/getUtilisateurGorillias")
     * @param array<string, mixed> $body     Corps de la requete (sera encode en JSON)
     * @param string|null          $jeton    Token CFI optionnel pour header "Jeton"
     *
     * @return array<string, mixed> Reponse decodee depuis JSON
     *
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function post(string $endpoint, array $body, ?string $jeton = null): array
    {
        // Generer correlation ID unique pour tracer cette requete
        $correlationId = Uuid::v4()->toRfc4122();

        $url = $this->cfiApiBaseUrl.$endpoint;

        // Preparation des headers
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Correlation-ID' => $correlationId,
        ];

        // Ajout du header Jeton si fourni
        if (null !== $jeton) {
            $headers['Jeton'] = $jeton;
        }

        // Log de la requete (dev uniquement)
        $this->logger->debug('CFI API Request', [
            'correlation_id' => $correlationId,
            'method' => 'POST',
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        ]);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $headers,
                'json' => $body,
                'timeout' => $this->cfiApiTimeout,
            ]);

            // Verifier le code HTTP
            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                $this->handleHttpError($response, $url, $correlationId);
            }

            // Decoder la reponse JSON
            $data = $response->toArray();

            // Log de la reponse (dev uniquement)
            $this->logger->debug('CFI API Response', [
                'correlation_id' => $correlationId,
                'url' => $url,
                'status_code' => $statusCode,
                'data' => $data,
            ]);

            return $data;
        } catch (ClientExceptionInterface $e) {
            // Gestion specifique des erreurs 4xx selon le code HTTP
            $statusCode = $e->getResponse()->getStatusCode();

            $this->logger->error('CFI API Client Error', [
                'correlation_id' => $correlationId,
                'url' => $url,
                'status_code' => $statusCode,
                'message' => $e->getMessage(),
            ]);

            // 401 : Token expire ou invalide → demander reconnexion
            if (401 === $statusCode) {
                throw new CfiTokenExpiredException($this->translator->trans('cfi.api.error.token_expired', [], 'messages'), $correlationId, $e);
            }

            // 403 : Acces refuse → droits insuffisants
            if (403 === $statusCode) {
                throw new CfiAccessDeniedException($this->translator->trans('cfi.api.error.access_denied', [], 'messages'), $correlationId, $e);
            }

            // Autres erreurs 4xx (400, 404, etc.)
            throw new CfiApiException($this->translator->trans('cfi.api.error.client', ['%status_code%' => $statusCode, '%message%' => $e->getMessage()], 'messages'), $statusCode, $e);
        } catch (ServerExceptionInterface $e) {
            // Erreurs 5xx (500, 502, 503, etc.)
            $this->logger->error('CFI API Server Error', [
                'correlation_id' => $correlationId,
                'url' => $url,
                'status_code' => $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ]);

            throw new CfiApiException($this->translator->trans('cfi.api.error.server', ['%status_code%' => $e->getResponse()->getStatusCode(), '%message%' => $e->getMessage()], 'messages'), $e->getResponse()->getStatusCode(), $e);
        } catch (TransportException $e) {
            // Erreurs reseau (timeout, DNS, connexion, etc.)
            $this->logger->error('CFI API Transport Error', [
                'correlation_id' => $correlationId,
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Gere les erreurs HTTP non-exception (2xx hors 200-299).
     *
     * Gestion specifique des codes 401 et 403.
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function handleHttpError(ResponseInterface $response, string $url, string $correlationId): never
    {
        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);

        $this->logger->error('CFI API HTTP Error', [
            'correlation_id' => $correlationId,
            'url' => $url,
            'status_code' => $statusCode,
            'response_body' => $content,
        ]);

        // 401 : Token expire ou invalide
        if (401 === $statusCode) {
            throw new CfiTokenExpiredException($this->translator->trans('cfi.api.error.token_expired', [], 'messages'), $correlationId);
        }

        // 403 : Acces refuse
        if (403 === $statusCode) {
            throw new CfiAccessDeniedException($this->translator->trans('cfi.api.error.access_denied', [], 'messages'), $correlationId);
        }

        // Autres codes HTTP
        throw new CfiApiException($this->translator->trans('cfi.api.error.http', ['%status_code%' => $statusCode, '%url%' => $url, '%content%' => $content], 'messages'), $statusCode);
    }
}
