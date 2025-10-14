<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\LigneOperationDto;
use App\Security\UserAuthenticationService;
use App\Service\AiLoggerService;
use App\Service\Api\OperationApiService;
use App\Service\ToolCallCollector;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tool IA spécialisé pour récupérer les FACTURES depuis CFI.
 *
 * Version spécialisée de GetOperationsTool focalisée uniquement sur les factures
 * (opérations de type "mail" / "courrier").
 *
 * Responsabilités :
 * - Filtrer automatiquement sur type="mail" (factures papier)
 * - Retour structuré avec métadonnées CFI
 * - Cache 5 minutes via OperationApiService
 *
 * Différence avec GetOperationsTool :
 * - Paramètre 'type' forcé à "mail" (non configurable par l'IA)
 * - Description optimisée pour contexte factures
 */
#[AsTool(
    name: 'get_factures',
    description: 'Récupère les factures (courrier papier) envoyées avec filtres optionnels. Type "mail" automatiquement appliqué. Retourne les détails complets avec sources CFI et métadonnées.'
)]
#[AsTaggedItem(priority: 95)]
final readonly class GetFacturesTool
{
    use AuthenticatedToolTrait;

    public function __construct(
        private OperationApiService $operationApi,
        private UserAuthenticationService $authService,
        private AiLoggerService $aiLogger,
        private ToolCallCollector $toolCallCollector,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Récupérer les factures avec filtres.
     *
     * @param string|null $dateDebut Date de début (format YYYY-MM-DD)
     * @param string|null $dateFin   Date de fin (format YYYY-MM-DD)
     * @param string|null $statut    Statut de l'opération
     *
     * @return array{count: int, factures: array, metadata: array}
     */
    public function __invoke(
        ?string $dateDebut = null,
        ?string $dateFin = null,
        ?string $statut = null,
    ): array {
        $startTime = microtime(true);

        // Enregistrer l'appel du tool
        $this->toolCallCollector->addToolCall('get_factures');

        try {
            // Récupérer utilisateur et tenant via le trait
            $auth = $this->getUserAndTenant($this->authService, $this->translator);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            ['user' => $user, 'tenant' => $tenant] = $auth;
            $idDivision = $tenant->getIdCfi();

            // Appel API CFI via service (avec cache 5min) - TYPE FORCÉ À "mail"
            $operations = $this->operationApi->getLignesOperations(
                idDivision: $idDivision,
                type: 'mail', // ← Forcé : uniquement factures
                dateDebut: $dateDebut,
                dateFin: $dateFin,
                statut: $statut,
            );

            // Formatter données pour l'agent IA
            $formattedFactures = array_map(
                fn (LigneOperationDto $op) => [
                    'id' => $op->id,
                    'nom' => $op->nom,
                    'type' => $op->type,
                    'dateCreation' => $op->dateCreation->format('Y-m-d H:i:s'),
                    'statut' => $op->statut,
                    'nbDestinataires' => $op->nbDestinataires,
                    'nbEnvoyes' => $op->nbEnvoyes,
                    'idEtatOperation' => $op->idEtatOperation,
                    'metadata' => [
                        'source' => 'CFI API /Campagnes/getLignesCampagnes',
                        'dateMAJ' => $op->dateCreation->format('Y-m-d H:i:s'),
                        'link' => "/operations/{$op->id}",
                    ],
                ],
                $operations
            );

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_factures',
                params: ['dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'statut' => $statut],
                result: ['count' => count($formattedFactures)],
                durationMs: $durationMs
            );

            return [
                'success' => true,
                'count' => count($formattedFactures),
                'factures' => $formattedFactures,
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Campagnes/getLignesCampagnes',
                    'type_filter' => 'mail',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                ],
            ];
        } catch (\Exception $e) {
            // Log détaillé pour développeurs (technique)
            $this->logger->error('GetFacturesTool: Erreur lors de la récupération des factures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => ['dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'statut' => $statut],
            ]);

            // Message traduit générique pour utilisateur final (via agent IA)
            $userMessage = $this->translator->trans('operations.error.fetch_failed', [], 'tools');

            return $this->errorResponse($userMessage);
        }
    }

    /**
     * Formatter réponse d'erreur structurée.
     *
     * @return array{success: bool, error: string}
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'count' => 0,
            'factures' => [],
        ];
    }
}
