<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Service de génération de JWT pour l'authentification Mercure.
 *
 * Responsabilités :
 * - Générer des JWT publishers (publier des mises à jour)
 * - Générer des JWT subscribers (s'abonner à des topics)
 * - Configuration du signer HMAC-SHA256 avec la clé secrète Mercure
 *
 * Architecture :
 * - Utilise les fonctions natives PHP pour créer des JWT simples
 * - Injecte MERCURE_JWT_SECRET depuis l'environnement
 * - Fournit une API simple pour les contrôleurs
 *
 * Note : Un JWT est composé de 3 parties séparées par des points :
 * header.payload.signature
 */
final readonly class MercureJwtGenerator
{
    public function __construct(
        private string $mercureJwtSecret,
    ) {
    }

    /**
     * Générer un JWT Mercure pour publier des updates (publisher).
     *
     * @param array<int, string> $topics Topics sur lesquels publier (ex: ['chat/123'])
     *
     * @return string JWT token
     */
    public function generatePublisherToken(array $topics): string
    {
        return $this->generateJwt([
            'mercure' => [
                'publish' => $topics,
            ],
        ]);
    }

    /**
     * Générer un JWT Mercure pour s'abonner à des topics (subscriber).
     *
     * @param array<int, string> $topics Topics à écouter (ex: ['chat/123'])
     *
     * @return string JWT token
     */
    public function generateSubscriberToken(array $topics): string
    {
        return $this->generateJwt([
            'mercure' => [
                'subscribe' => $topics,
            ],
        ]);
    }

    /**
     * Générer un JWT simple avec HMAC-SHA256.
     *
     * @param array<string, mixed> $payload Payload du JWT
     *
     * @return string JWT token
     */
    private function generateJwt(array $payload): string
    {
        // Header JWT standard avec algorithme HS256
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        // Encoder header et payload en base64url
        $headerEncoded = $this->base64UrlEncode(json_encode($header, \JSON_THROW_ON_ERROR));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, \JSON_THROW_ON_ERROR));

        // Créer la signature HMAC-SHA256
        $signature = hash_hmac('sha256', $headerEncoded.'.'.$payloadEncoded, $this->mercureJwtSecret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        // Assembler le JWT final
        return $headerEncoded.'.'.$payloadEncoded.'.'.$signatureEncoded;
    }

    /**
     * Encoder en base64url (variante base64 compatible URL).
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
