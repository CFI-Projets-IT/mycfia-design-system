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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tool IA spécialisé pour récupérer les COMMANDES depuis CFI.
 *
 * Version spécialisée de GetOperationsTool focalisée sur les commandes
 * (opérations de type SMS, Email, et autres types hors factures).
 *
 * Responsabilités :
 * - Récupérer TOUTES les opérations sauf factures (type != "mail")
 * - Retour structuré avec métadonnées CFI
 * - Cache 5 minutes via OperationApiService
 *
 * Différence avec GetOperationsTool :
 * - Appelle avec type=null pour récupérer tous types
 * - Description optimisée pour contexte commandes
 * - Nom métier "commandes" plutôt que "opérations"
 *
 * Logging : Canal dédié 'tools' (pas 'chat')
 */
#[AsTool(
    name: 'get_commandes',
    description: 'Récupère les commandes marketing (SMS, Email, toutes campagnes) avec filtres optionnels. Retourne les détails complets avec sources CFI et métadonnées.'
)]
#[AsTaggedItem(priority: 95)]
final readonly class GetCommandesTool
{
    use AuthenticatedToolTrait;

    public function __construct(
        private OperationApiService $operationApi,
        private UserAuthenticationService $authService,
        private AiLoggerService $aiLogger,
        private ToolCallCollector $toolCallCollector,
        #[Autowire(service: 'monolog.logger.tools')]
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Récupérer les commandes avec filtres.
     *
     * @param string|null $dateDebut Date de début (format YYYY-MM-DD)
     * @param string|null $dateFin   Date de fin (format YYYY-MM-DD)
     * @param string|null $statut    Statut de l'opération
     * @param string|null $type      Type spécifique (sms, email, ou null pour tous)
     *
     * @return array{count: int, commandes: array, metadata: array}
     */
    public function __invoke(
        ?string $dateDebut = null,
        ?string $dateFin = null,
        ?string $statut = null,
        ?string $type = null,
    ): array {
        $startTime = microtime(true);

        // Enregistrer l'appel du tool
        $this->toolCallCollector->addToolCall('get_commandes');

        try {
            // Récupérer utilisateur et tenant via le trait
            $auth = $this->getUserAndTenant($this->authService, $this->translator);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            ['user' => $user, 'tenant' => $tenant] = $auth;
            $idDivision = $tenant->getIdCfi();

            // Appel API CFI via service (avec cache 5min)
            // Type=null pour récupérer tous types (sms, email, etc.)
            $operations = $this->operationApi->getLignesOperations(
                idDivision: $idDivision,
                type: $type, // Null par défaut = tous types
                dateDebut: $dateDebut,
                dateFin: $dateFin,
                statut: $statut,
            );

            // Filtrer pour exclure les factures (type="mail") si nécessaire
            if (null === $type) {
                $operations = array_filter(
                    $operations,
                    fn (LigneOperationDto $op) => 'mail' !== strtolower($op->type)
                );
            }

            // Formatter données pour l'agent IA
            $formattedCommandes = array_map(
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
                toolName: 'get_commandes',
                params: ['type' => $type, 'dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'statut' => $statut],
                result: ['count' => count($formattedCommandes)],
                durationMs: $durationMs
            );

            return [
                'success' => true,
                'count' => count($formattedCommandes),
                'commandes' => $formattedCommandes,
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Campagnes/getLignesCampagnes',
                    'type_filter' => $type ?? 'all (hors factures)',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                ],
            ];
        } catch (\Exception $e) {
            // Log détaillé pour développeurs (technique)
            $this->logger->error('GetCommandesTool: Erreur lors de la récupération des commandes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => ['type' => $type, 'dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'statut' => $statut],
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
            'commandes' => [],
        ];
    }
}
