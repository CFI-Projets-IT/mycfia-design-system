<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\LigneOperationDto;
use App\Security\UserAuthenticationService;
use App\Service\AiLoggerService;
use App\Service\Api\OperationApiService;
use App\Service\Cfi\CfiApiService;
use App\Service\Cfi\CfiTokenContext;
use App\Service\ToolCallCollector;
use App\Service\ToolResultCollector;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tool IA spécialisé pour récupérer les COMMANDES depuis CFI.
 *
 * Modes d'utilisation :
 * - MODE LISTE : Sans idCampagne → Liste résumée des commandes (économie tokens)
 * - MODE DÉTAIL : Avec idCampagne → Détails complets d'une campagne avec toutes ses lignes d'opérations
 *
 * Responsabilités :
 * - Récupérer TOUTES les opérations sauf factures (type != "mail")
 * - Retour structuré avec table_data pour composant DataTable
 * - Actions suggérées avec liens cliquables
 * - Cache 5 minutes via OperationApiService
 *
 * Structure retournée :
 * - MODE LISTE : Résumé commandes (id, nom, type, date, statut)
 * - MODE DÉTAIL : Campagne complète avec lignes d'opérations détaillées
 *
 * Logging : Canal dédié 'tools' (pas 'chat')
 */
#[AsTool(
    name: 'get_commandes',
    description: 'Récupère les commandes marketing avec 2 modes : liste résumée (défaut) ou détails complets d\'une campagne spécifique. Mode liste = filtrage par période. Mode détail = idCampagne pour obtenir toutes les lignes d\'opérations.'
)]
#[AsTaggedItem(priority: 95)]
final readonly class GetCommandesTool
{
    use AuthenticatedToolTrait;

    public function __construct(
        private OperationApiService $operationApi,
        private CfiApiService $cfiApi,
        private CfiTokenContext $cfiTokenContext,
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
     * Récupérer les commandes avec filtres temporels ou détails d'une campagne spécifique.
     *
     * Modes :
     * - MODE LISTE : dateDebut/dateFin → Liste résumée des commandes
     * - MODE DÉTAIL : idCampagne → Détails complets d'une campagne spécifique avec toutes ses lignes d'opérations
     *
     * @param string|null $dateDebut  Date de début (format YYYY-MM-DD) - utilisé uniquement en MODE LISTE
     * @param string|null $dateFin    Date de fin (format YYYY-MM-DD) - utilisé uniquement en MODE LISTE
     * @param string|null $statut     Statut de l'opération - utilisé uniquement en MODE LISTE
     * @param string|null $type       Type spécifique (sms, email, ou null pour tous) - utilisé uniquement en MODE LISTE
     * @param int|null    $idCampagne ID de la campagne spécifique pour obtenir détails complets. Si fourni, appel direct à /Campagnes/getCampagne
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        ?string $dateDebut = null,
        ?string $dateFin = null,
        ?string $statut = null,
        ?string $type = null,
        ?int $idCampagne = null,
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

            // MODE DÉTAIL : Si idCampagne est fourni, appel direct à /Campagnes/getCampagne
            if (null !== $idCampagne) {
                return $this->getCampagneDetails($idCampagne, $user, $tenant, $startTime);
            }

            // MODE LISTE : Récupérer liste des opérations
            $operations = $this->operationApi->getLignesOperations(
                idDivision: $idDivision,
                type: $type,
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
                    'idCampagne' => $op->idCampagne,
                    'nomCampagne' => $op->nomCampagne,
                    'metadata' => [
                        'source' => 'CFI API /Campagnes/getLignesCampagnes',
                        'dateMAJ' => $op->dateCreation->format('Y-m-d H:i:s'),
                        'link' => "/campagnes/{$op->idCampagne}",
                    ],
                ],
                $operations
            );

            // Générer les actions suggérées pour transmission au frontend
            $suggestedActions = [];
            $processedCampagnes = [];
            foreach ($operations as $operation) {
                // Éviter les doublons de campagnes
                if (! in_array($operation->idCampagne, $processedCampagnes, true)) {
                    $suggestedActions[] = [
                        'type' => 'commande_details',
                        'campagne_id' => $operation->idCampagne,
                        'prompt' => "Donne-moi tous les détails de la campagne {$operation->idCampagne}",
                    ];
                    $processedCampagnes[] = $operation->idCampagne;
                }
            }

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_commandes',
                params: ['dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'type' => $type],
                result: ['count' => count($formattedCommandes)],
                durationMs: $durationMs
            );

            // Log KPI pour monitoring
            $this->logger->info('Tool executed successfully', [
                'tool_name' => 'get_commandes',
                'mode' => 'LISTE',
                'duration_ms' => $durationMs,
                'result_count' => count($formattedCommandes),
                'user_id' => $user->getId(),
                'division_id' => $idDivision,
            ]);

            // Générer les données du tableau pour le composant DataTable
            $tableData = $this->generateTableData($operations, $suggestedActions);

            $result = [
                'success' => true,
                'count' => count($formattedCommandes),
                'commandes' => $formattedCommandes,
                'suggested_actions' => $suggestedActions,
                'table_data' => $tableData,
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Campagnes/getLignesCampagnes',
                    'type_filter' => $type ?? 'all (hors factures)',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                ],
            ];

            // Collecter le résultat pour transmission au frontend
            $this->toolResultCollector->addToolResult('get_commandes', $result);

            return $result;
        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log détaillé pour développeurs (technique)
            $this->logger->error('Tool execution failed', [
                'tool_name' => 'get_commandes',
                'duration_ms' => $durationMs,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Message traduit générique pour utilisateur final (via agent IA)
            $userMessage = $this->translator->trans('operations.error.fetch_failed', [], 'tools');

            return $this->errorResponse($userMessage);
        }
    }

    /**
     * Formatter réponse d'erreur structurée.
     *
     * @return array{success: false, error: string, count: 0, commandes: array<empty, empty>}
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

    /**
     * Récupérer les détails complets d'une campagne spécifique avec toutes ses lignes d'opérations.
     *
     * MODE DÉTAIL : Appel direct à l'endpoint /Campagnes/getCampagne.
     * Retourne les informations complètes avec lignes d'opérations détaillées.
     *
     * @param int   $idCampagne ID de la campagne recherchée
     * @param mixed $user       Utilisateur authentifié
     * @param mixed $tenant     Tenant actuel
     * @param float $startTime  Timestamp début de l'appel
     *
     * @return array<string, mixed>
     */
    private function getCampagneDetails(
        int $idCampagne,
        mixed $user,
        mixed $tenant,
        float $startTime
    ): array {
        // Récupérer le token
        $jeton = $this->cfiTokenContext->getToken();
        if (null === $jeton) {
            $this->logger->error('GetCommandesTool: Token CFI manquant', [
                'idCampagne' => $idCampagne,
            ]);

            return $this->errorResponse('Token CFI manquant ou expiré');
        }

        // Appel direct à /Campagnes/getCampagne
        $response = $this->cfiApi->post('/Campagnes/getCampagne', ['idCampagne' => $idCampagne], $jeton);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        // Vérifier que la réponse n'est pas vide
        if (empty($response)) {
            $this->logger->warning('GetCommandesTool: Campagne non trouvée (réponse vide)', [
                'idCampagne' => $idCampagne,
                'user_id' => $user->getId(),
                'duration_ms' => $durationMs,
            ]);

            return [
                'success' => false,
                'error' => "Campagne #{$idCampagne} non trouvée ou accès refusé",
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Campagnes/getCampagne',
                    'mode' => 'detail',
                    'duration_ms' => $durationMs,
                ],
            ];
        }

        // L'API retourne un tableau avec un élément [{...}]
        // Utiliser reset() pour prendre le premier élément (peu importe le type de clé)
        $campagneData = reset($response);

        // Vérifier que le format est correct
        if (! is_array($campagneData)) {
            $this->logger->warning('GetCommandesTool: Format de réponse invalide', [
                'idCampagne' => $idCampagne,
                'response_keys' => array_keys($response),
                'user_id' => $user->getId(),
                'duration_ms' => $durationMs,
            ]);

            return [
                'success' => false,
                'error' => "Format de réponse invalide pour la campagne #{$idCampagne}",
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Campagnes/getCampagne',
                    'mode' => 'detail',
                    'duration_ms' => $durationMs,
                ],
            ];
        }

        // Log tool call succès
        $this->aiLogger->logToolCall(
            user: $user,
            toolName: 'get_commandes',
            params: ['idCampagne' => $idCampagne],
            result: ['mode' => 'detail', 'nb_lignes' => count($campagneData['lignesOperations'] ?? []), 'found' => true],
            durationMs: $durationMs
        );

        // Log KPI pour monitoring
        $this->logger->info('Tool executed successfully', [
            'tool_name' => 'get_commandes',
            'mode' => 'DÉTAIL',
            'duration_ms' => $durationMs,
            'id_campagne' => $idCampagne,
            'nb_lignes' => count($campagneData['lignesOperations'] ?? []),
            'user_id' => $user->getId(),
            'division_id' => $tenant->getIdCfi(),
        ]);

        // Générer les données du tableau pour les lignes d'opérations (MODE DÉTAIL)
        $tableData = $this->generateDetailTableData($campagneData);

        // Retourner campagne complète avec toutes les lignes
        $result = [
            'success' => true,
            'campagne' => [
                'id' => $campagneData['id'],
                'idDivision' => $campagneData['idDivision'],
                'codeClient' => $campagneData['codeClient'] ?? null,
                'dateCreation' => $campagneData['dateCreation'] ?? null,
                'nom' => $campagneData['nom'] ?? null,
                'nb_lignes' => count($campagneData['lignesOperations'] ?? []),
                'lignesOperations' => $campagneData['lignesOperations'] ?? [],
            ],
            'table_data' => $tableData,
            'metadata' => [
                'source' => 'CFI API',
                'endpoint' => '/Campagnes/getCampagne',
                'mode' => 'detail',
                'division' => $tenant->getNom(),
                'duration_ms' => $durationMs,
            ],
        ];

        // Collecter le résultat pour transmission au frontend
        $this->toolResultCollector->addToolResult('get_commandes', $result);

        return $result;
    }

    /**
     * Générer les données du tableau pour le mode LISTE.
     *
     * @param LigneOperationDto[]       $operations       Liste des opérations
     * @param list<array<string,mixed>> $suggestedActions Actions suggérées
     *
     * @return array{headers: array<string>, rows: array<array<string, string>>, totalRow: array<string, string>, linkColumns: array<string, string>}
     */
    private function generateTableData(array $operations, array $suggestedActions): array
    {
        // En-têtes du tableau
        $headers = ['ID CAMPAGNE', 'NOM CAMPAGNE', 'NOM OPÉRATION', 'TYPE', 'DATE CRÉATION', 'STATUT', 'DESTINATAIRES'];

        // Lignes de données
        $rows = [];
        $totalDestinataires = 0;

        foreach ($operations as $operation) {
            $rows[] = [
                'id_campagne' => (string) $operation->idCampagne,
                'nom_campagne' => $operation->nomCampagne ?? 'N/A',
                'nom_operation' => $operation->nom ?? '',
                'type' => strtoupper($operation->type ?? ''),
                'date_creation' => $operation->dateCreation->format('d/m/Y H:i'),
                'statut' => $operation->statut ?? '',
                'destinataires' => number_format($operation->nbDestinataires, 0, ',', ' '),
            ];

            $totalDestinataires += $operation->nbDestinataires;
        }

        // Ligne Total
        $totalRow = [
            'label' => 'Total',
            'nom_campagne' => '',
            'nom_operation' => '',
            'type' => '',
            'date_creation' => '',
            'statut' => '',
            'destinataires' => number_format($totalDestinataires, 0, ',', ' '),
        ];

        // Configuration des colonnes cliquables
        $linkColumns = [
            'id_campagne' => 'Donne-moi tous les détails de la campagne {id_campagne}',
        ];

        return [
            'headers' => $headers,
            'rows' => $rows,
            'totalRow' => $totalRow,
            'linkColumns' => $linkColumns,
        ];
    }

    /**
     * Générer les données du tableau pour une campagne en détail (lignes d'opérations).
     *
     * @param array<string, mixed> $campagne Campagne complète avec ses lignes
     *
     * @return array{headers: array<string>, rows: array<array<string, string>>, totalRow: array<string, string>, linkColumns: array<string, string>}
     */
    private function generateDetailTableData(array $campagne): array
    {
        // Headers du tableau (colonnes des lignes d'opérations)
        $headers = ['ID OPÉRATION', 'NOM', 'TYPE', 'DATE CRÉATION', 'DESTINATAIRES', 'ENVOYÉS', 'PRIX TOTAL'];

        // Rows : lignes d'opérations
        $rows = [];
        $totalDestinataires = 0;
        $totalEnvoyes = 0;
        $totalPrix = 0.0;

        foreach ($campagne['lignesOperations'] ?? [] as $ligne) {
            $nbDestinataires = $ligne['nbAdresse'] ?? 0;
            $prixTotal = $ligne['prixTotal'] ?? 0.0;

            $rows[] = [
                'id' => (string) $ligne['id'],
                'nom' => $ligne['nom'] ?? 'N/A',
                'type' => strtoupper($ligne['idTypeOperation'] ?? ''),
                'date_creation' => isset($ligne['dateCreation']) ? (new \DateTime($ligne['dateCreation']))->format('d/m/Y H:i') : 'N/A',
                'destinataires' => number_format($nbDestinataires, 0, ',', ' '),
                'envoyes' => number_format($nbDestinataires, 0, ',', ' '),
                'prix_total' => number_format($prixTotal, 2, ',', ' ').' €',
            ];

            $totalDestinataires += $nbDestinataires;
            $totalEnvoyes += $nbDestinataires;
            $totalPrix += $prixTotal;
        }

        // Total row
        $totalRow = [
            'label' => 'Total',
            'nom' => '',
            'type' => '',
            'date_creation' => '',
            'destinataires' => number_format($totalDestinataires, 0, ',', ' '),
            'envoyes' => number_format($totalEnvoyes, 0, ',', ' '),
            'prix_total' => number_format($totalPrix, 2, ',', ' ').' €',
        ];

        // Pas de linkColumns pour les lignes d'opérations (pas cliquables)
        $linkColumns = [];

        return [
            'headers' => $headers,
            'rows' => $rows,
            'totalRow' => $totalRow,
            'linkColumns' => $linkColumns,
        ];
    }
}
