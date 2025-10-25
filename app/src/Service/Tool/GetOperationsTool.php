<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\LigneOperationDto;
use App\Security\UserAuthenticationService;
use App\Service\AiLoggerService;
use App\Service\Api\OperationApiService;
use App\Service\ToolCallCollector;
use App\Service\ToolResultCollector;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tool IA pour récupérer les opérations marketing depuis CFI.
 *
 * Permet à l'agent IA de consulter les lignes d'opérations avec filtres :
 * - Type opération (sms, email, mail, all)
 * - Période (dateDebut, dateFin)
 * - Statut
 *
 * Retour structuré avec métadonnées pour "cartes preuve".
 *
 * Logging : Canal dédié 'tools' (pas 'chat')
 */
#[AsTool(
    name: 'get_operations',
    description: 'Récupère les opérations marketing (SMS, Email, Courrier) avec filtres optionnels. Retourne les détails complets avec sources CFI et métadonnées.'
)]
#[AsTaggedItem(priority: 100)]
final readonly class GetOperationsTool
{
    use AuthenticatedToolTrait;

    public function __construct(
        private OperationApiService $operationApi,
        private UserAuthenticationService $authService,
        private AiLoggerService $aiLogger,
        private ToolCallCollector $toolCallCollector,
        private ToolResultCollector $toolResultCollector,
        #[Autowire(service: 'monolog.logger.tools')]
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Récupérer les opérations marketing avec filtres.
     *
     * @param string      $type      Type d'opération : 'sms', 'email', 'mail', 'all' (défaut: 'all')
     * @param string|null $dateDebut Date de début (format YYYY-MM-DD)
     * @param string|null $dateFin   Date de fin (format YYYY-MM-DD)
     * @param string|null $statut    Statut de l'opération
     *
     * @return array{count: int, operations: array, metadata: array}
     */
    public function __invoke(
        string $type = 'all',
        ?string $dateDebut = null,
        ?string $dateFin = null,
        ?string $statut = null,
    ): array {
        $startTime = microtime(true);

        // Enregistrer l'appel du tool
        $this->toolCallCollector->addToolCall('get_operations');

        try {
            // Récupérer utilisateur et tenant via le trait
            $auth = $this->getUserAndTenant($this->authService, $this->translator);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            ['user' => $user, 'tenant' => $tenant] = $auth;
            $idDivision = $tenant->getIdCfi();

            // Appel API CFI via service (avec cache 5min)
            $operations = $this->operationApi->getLignesOperations(
                idDivision: $idDivision,
                type: 'all' === $type ? null : $type,
                dateDebut: $dateDebut,
                dateFin: $dateFin,
                statut: $statut,
            );

            // Formatter données pour l'agent IA
            $formattedOperations = array_map(
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
                        'source' => 'CFI API /Operations/getLignesOperations',
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
                toolName: 'get_operations',
                params: ['type' => $type, 'dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'statut' => $statut],
                result: ['count' => count($formattedOperations)],
                durationMs: $durationMs
            );

            // Générer suggested_actions
            $suggestedActions = [];
            if (count($operations) > 0) {
                $suggestedActions[] = [
                    'label' => 'Voir les statistiques',
                    'icon' => '📊',
                    'prompt' => 'Affiche-moi les statistiques des opérations',
                ];
            }

            // Générer table_data pour le composant DataTable
            $tableData = $this->generateTableData($operations, $suggestedActions);

            // Log KPI pour monitoring
            $this->logger->info('Tool executed successfully', [
                'tool_name' => 'get_operations',
                'mode' => 'LISTE',
                'duration_ms' => $durationMs,
                'nb_operations' => count($operations),
                'user_id' => $user->getId(),
                'division_id' => $tenant->getIdCfi(),
            ]);

            $result = [
                'success' => true,
                'count' => count($formattedOperations),
                'operations' => $formattedOperations,
                'suggested_actions' => $suggestedActions,
                'table_data' => $tableData,
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Operations/getLignesOperations',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                    'mode' => 'LISTE',
                ],
            ];

            // Collecter le résultat pour transmission au frontend
            $this->toolResultCollector->addToolResult('get_operations', $result);

            return $result;
        } catch (\Exception $e) {
            // Log détaillé pour développeurs (technique)
            $this->logger->error('GetOperationsTool: Erreur lors de la récupération des opérations', [
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
            'operations' => [],
        ];
    }

    /**
     * Générer table_data structuré pour le composant DataTable.
     *
     * @param LigneOperationDto[]              $operations       Liste des opérations
     * @param array<int, array<string, mixed>> $suggestedActions Actions suggérées pour liens cliquables
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>, totalRow: array<string, mixed>, linkColumns: array<string, string>}
     */
    private function generateTableData(array $operations, array $suggestedActions): array
    {
        // En-têtes du tableau
        $headers = [
            'ID',
            'NOM OPÉRATION',
            'TYPE',
            'DATE CRÉATION',
            'NB DESTINATAIRES',
            'NB ENVOYÉS',
            'STATUT',
        ];

        // Lignes du tableau
        $rows = [];
        $totalDestinataires = 0;
        $totalEnvoyes = 0;

        foreach ($operations as $op) {
            $rows[] = [
                'id' => (string) $op->id,
                'nom' => $op->nom ?? '',
                'type' => $op->type ?? 'N/A',
                'date_creation' => $op->dateCreation->format('Y-m-d H:i'),
                'nb_destinataires' => (string) ($op->nbDestinataires ?? 0),
                'nb_envoyes' => (string) ($op->nbEnvoyes ?? 0),
                'statut' => $op->statut ?? 'N/A',
            ];

            $totalDestinataires += $op->nbDestinataires ?? 0;
            $totalEnvoyes += $op->nbEnvoyes ?? 0;
        }

        // Ligne de total
        $tauxEnvoi = $totalDestinataires > 0
            ? round(($totalEnvoyes / $totalDestinataires) * 100, 1)
            : 0.0;

        $totalRow = [
            'label' => 'TOTAL',
            'nb_operations' => (string) count($operations),
            'total_destinataires' => (string) $totalDestinataires,
            'total_envoyes' => (string) $totalEnvoyes,
            'taux_envoi' => "{$tauxEnvoi}%",
        ];

        // Colonnes cliquables (pas de mode DÉTAIL pour les opérations génériques)
        $linkColumns = [];

        return [
            'headers' => $headers,
            'rows' => $rows,
            'totalRow' => $totalRow,
            'linkColumns' => $linkColumns,
        ];
    }
}
