<?php

declare(strict_types=1);

namespace App\Service\Cfi;

use App\DTO\Cfi\GetUtilisateurByClefDto;
use App\DTO\Cfi\UtilisateurGorilliasDto;
use App\Exception\CfiApiException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service d'authentification CFI.
 *
 * Gere l'authentification des utilisateurs via l'API CFI
 * en utilisant le token Gorillias pour obtenir un token CFI (jeton)
 */
class CfiAuthService
{
    public function __construct(
        private readonly CfiApiService $cfiApiService,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        private readonly string $clefApi,
    ) {
    }

    /**
     * Authentifie un utilisateur via l'API CFI.
     *
     * @param string $jetonUtilisateur Token Gorillias de l'utilisateur
     *
     * @return UtilisateurGorilliasDto Donnees utilisateur + token CFI (jeton)
     *
     * @throws CfiApiException           Si l'authentification echoue
     * @throws \InvalidArgumentException Si le token Gorillias est invalide
     */
    public function authenticate(string $jetonUtilisateur): UtilisateurGorilliasDto
    {
        // 1. Creer le DTO de requete
        $requestDto = new GetUtilisateurByClefDto();
        $requestDto->jetonUtilisateur = $jetonUtilisateur;
        $requestDto->clefApi = $this->clefApi;

        // 2. Valider le DTO
        $errors = $this->validator->validate($requestDto);
        if (count($errors) > 0) {
            $firstError = $errors[0];
            $message = $this->translator->trans(
                (string) $firstError->getMessage(),
                [],
                'validators'
            );

            $this->logger->error('CFI Auth Validation Error', [
                'jeton' => substr($jetonUtilisateur, 0, 8).'...',
                'error' => $message,
            ]);

            throw new \InvalidArgumentException($message);
        }

        // 3. Appeler l'API CFI
        try {
            $this->logger->info('CFI Authentication Request', [
                'jeton' => substr($jetonUtilisateur, 0, 8).'...',
                'endpoint' => '/Utilisateurs/getUtilisateurGorillias',
            ]);

            $response = $this->cfiApiService->post(
                '/Utilisateurs/getUtilisateurGorillias',
                [
                    'jetonUtilisateur' => $jetonUtilisateur,
                    'clefApi' => $this->clefApi,
                ]
            );

            // 4. Mapper la reponse vers le DTO
            $utilisateur = UtilisateurGorilliasDto::fromArray($response);

            $this->logger->info('CFI Authentication Success', [
                'user_id' => $utilisateur->id,
                'division' => $utilisateur->nomDivision,
                'email' => $utilisateur->email,
            ]);

            return $utilisateur;
        } catch (CfiApiException $e) {
            // Erreur API CFI - logger et relancer
            $this->logger->error('CFI Authentication Failed', [
                'jeton' => substr($jetonUtilisateur, 0, 8).'...',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Traduire le message d'erreur si c'est une erreur 401 (Unauthorized)
            if (401 === $e->getCode()) {
                throw new CfiApiException($this->translator->trans('cfi.auth.error.invalid_token', [], 'messages'), $e->getCode(), $e);
            }

            // Relancer l'exception originale pour les autres codes
            throw $e;
        }
    }

    /**
     * Verifie si un token Gorillias est au bon format (UUID).
     */
    public function isValidTokenFormat(string $token): bool
    {
        return 1 === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $token);
    }
}
