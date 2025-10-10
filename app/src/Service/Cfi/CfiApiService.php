<?php

declare(strict_types=1);

namespace App\Service\Cfi;

use App\Exception\CfiApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
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
 * la gestion des erreurs, retry, timeout et logging.
 */
readonly class CfiApiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private string $cfiApiBaseUrl,
        private int $cfiApiTimeout,
    ) {
    }

    /**
     * Effectue une requete POST vers l'API CFI.
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
        $url = $this->cfiApiBaseUrl.$endpoint;

        // Preparation des headers
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        // Ajout du header Jeton si fourni
        if (null !== $jeton) {
            $headers['Jeton'] = $jeton;
        }

        // Log de la requete (dev uniquement)
        $this->logger->debug('CFI API Request', [
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
                $this->handleHttpError($response, $url);
            }

            // Decoder la reponse JSON
            $data = $response->toArray();

            // Log de la reponse (dev uniquement)
            $this->logger->debug('CFI API Response', [
                'url' => $url,
                'status_code' => $statusCode,
                'data' => $data,
            ]);

            return $data;
        } catch (ClientExceptionInterface $e) {
            // Erreurs 4xx (400, 401, 404, etc.)
            $this->logger->error('CFI API Client Error', [
                'url' => $url,
                'status_code' => $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ]);

            throw new CfiApiException($this->translator->trans('cfi.api.error.client', ['%status_code%' => $e->getResponse()->getStatusCode(), '%message%' => $e->getMessage()], 'messages'), $e->getResponse()->getStatusCode(), $e);
        } catch (ServerExceptionInterface $e) {
            // Erreurs 5xx (500, 502, 503, etc.)
            $this->logger->error('CFI API Server Error', [
                'url' => $url,
                'status_code' => $e->getResponse()->getStatusCode(),
                'message' => $e->getMessage(),
            ]);

            throw new CfiApiException($this->translator->trans('cfi.api.error.server', ['%status_code%' => $e->getResponse()->getStatusCode(), '%message%' => $e->getMessage()], 'messages'), $e->getResponse()->getStatusCode(), $e);
        } catch (TransportException $e) {
            // Erreurs reseau (timeout, DNS, connexion, etc.)
            $this->logger->error('CFI API Transport Error', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Gere les erreurs HTTP non-exception (2xx hors 200-299).
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function handleHttpError(ResponseInterface $response, string $url): never
    {
        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);

        $this->logger->error('CFI API HTTP Error', [
            'url' => $url,
            'status_code' => $statusCode,
            'response_body' => $content,
        ]);

        throw new CfiApiException($this->translator->trans('cfi.api.error.http', ['%status_code%' => $statusCode, '%url%' => $url, '%content%' => $content], 'messages'), $statusCode);
    }
}
