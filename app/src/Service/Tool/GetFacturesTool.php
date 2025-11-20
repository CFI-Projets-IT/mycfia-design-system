<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\FactureDto;
use App\Security\UserAuthenticationService;
use App\Service\AiLoggerService;
use App\Service\Api\FacturationApiService;
use App\Service\ToolCallCollector;
use App\Service\ToolResultCollector;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tool IA spécialisé pour récupérer les FACTURES depuis CFI.
 *
 * Utilise l'endpoint CFI /Facturations/getFacturations pour récupérer
 * les vraies factures avec détails complets (montants HT/TTC, lignes, types).
 *
 * Modes d'utilisation :
 * - MODE LISTE : Sans idFacture → Liste résumée des factures (économie tokens)
 * - MODE DÉTAIL : Avec idFacture → Détails complets d'une facture spécifique avec toutes ses lignes
 *
 * Responsabilités :
 * - Récupérer facturations mensuelles avec filtres temporels
 * - Retour structuré avec métadonnées CFI
 * - Cache 5 minutes via FacturationApiService
 *
 * Structure retournée :
 * - MODE LISTE : Résumé factures (id, montants, nb_lignes)
 * - MODE DÉTAIL : Facture complète avec lignes détaillées (libellé, quantité, montant, TVA)
 *
 * Logging : Canal dédié 'tools' (pas 'chat')
 */
#[AsTool(
    name: 'get_factures',
    description: 'Récupère les factures CFI avec 2 modes : liste résumée (défaut) ou détails complets d\'une facture spécifique. Mode liste = filtrage par période. Mode détail = idFacture pour obtenir toutes les lignes de facturation.'
)]
#[AsTaggedItem(priority: 95)]
final readonly class GetFacturesTool
{
    use AuthenticatedToolTrait;

    public function __construct(
        private FacturationApiService $facturationApi,
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
     * Récupérer les factures avec filtres temporels ou détails d'une facture spécifique.
     *
     * Modes :
     * - MODE LISTE : dateDebut/dateFin → Liste résumée des factures
     * - MODE DÉTAIL : idFacture → Détails complets d'une facture spécifique via endpoint dédié /Facturations/getFacture
     *
     * @param string|null $dateDebut Date de début (format ISO 8601, ex: 2024-10-14T00:00:00Z ou YYYY-MM-DD) - utilisé uniquement en MODE LISTE
     * @param string|null $dateFin   Date de fin (format ISO 8601, ex: 2025-10-14T23:59:59Z ou YYYY-MM-DD) - utilisé uniquement en MODE LISTE
     * @param int|null    $idFacture ID de la facture spécifique pour obtenir détails complets (ex: 12577, 13033). Si fourni, appel direct à /Facturations/getFacture
     *
     * @return array<string, mixed>
     */
    public function __invoke(
        ?string $dateDebut = null,
        ?string $dateFin = null,
        ?int $idFacture = null,
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

            // MODE DÉTAIL : Si idFacture est fourni, appel direct à /Facturations/getFacture
            if (null !== $idFacture) {
                error_log("[GetFacturesTool] AVANT appel getFactureDetails() avec idFacture=$idFacture");
                $result = $this->getFactureDetails($idFacture, $user, $tenant, $startTime);
                error_log('[GetFacturesTool] APRÈS appel getFactureDetails() - has_table_data='.(isset($result['table_data']) ? 'YES' : 'NO'));

                return $result;
            }

            // MODE LISTE : Récupération de la liste des factures avec filtres temporels
            $debut = $dateDebut ? $this->normalizeDate($dateDebut) : null;
            $fin = $dateFin ? $this->normalizeDate($dateFin) : null;

            // Appel API CFI via service (avec cache 5min)
            $facturations = $this->facturationApi->getFacturations(
                idDivision: $idDivision,
                debut: $debut,
                fin: $fin,
            );

            // MODE LISTE : Formatter données pour l'agent IA (résumé sans lignes détaillées)
            $formattedFacturations = array_map(
                fn (FactureDto $facturation) => [
                    'id' => $facturation->id,
                    'moisFacturation' => $facturation->moisFacturation->format('Y-m'),
                    'dateMiseADispo' => $facturation->dateMiseADispo->format('Y-m-d'),
                    'nb_factures' => count($facturation->factures),
                    'montant_total_ht' => $facturation->getMontantTotalHT(),
                    'montant_total_ttc' => $facturation->getMontantTotalTTC(),
                    'factures' => array_map(
                        fn ($facture) => [
                            'id' => $facture->id,
                            'nomCommande' => $facture->nomCommande,
                            'demandeur' => $facture->demandeur,
                            'montantHT' => $facture->montantHT,
                            'montantTTC' => $facture->montantTTC,
                            'nb_lignes' => count($facture->lignes),
                            // Ajouter l'action directement dans chaque facture
                            'action_link' => [
                                'type' => 'invoice_details',
                                'label' => '📄 Voir tous les détails',
                                'prompt' => "Donne-moi tous les détails de la facture {$facture->id}",
                            ],
                        ],
                        $facturation->factures
                    ),
                ],
                $facturations
            );

            // Générer les actions suggérées pour transmission au frontend
            // Chaque facture aura son lien intégré dans la réponse de l'IA
            $suggestedActions = [];
            foreach ($facturations as $facturation) {
                foreach ($facturation->factures as $facture) {
                    $suggestedActions[] = [
                        'type' => 'invoice_details',
                        'invoice_id' => $facture->id,
                        'prompt' => "Donne-moi tous les détails de la facture {$facture->id}",
                    ];
                }
            }

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_factures',
                params: ['dateDebut' => $dateDebut, 'dateFin' => $dateFin],
                result: ['count' => count($formattedFacturations)],
                durationMs: $durationMs
            );

            // Log KPI pour monitoring
            $this->logger->info('Tool executed successfully', [
                'tool_name' => 'get_factures',
                'mode' => 'LISTE', // MODE LISTE : $idFacture est toujours null ici (sinon return ligne 105)
                'duration_ms' => $durationMs,
                'result_count' => count($formattedFacturations),
                'user_id' => $user->getId(),
                'division_id' => $idDivision,
            ]);

            // Générer les données du tableau pour le composant DataTable
            $tableData = $this->generateTableData($facturations, $suggestedActions);

            $result = [
                'success' => true,
                'count' => count($formattedFacturations),
                'facturations' => $formattedFacturations,
                'suggested_actions' => $suggestedActions,
                'table_data' => $tableData, // Ajout : données formatées pour DataTable component
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Facturations/getFacturations',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                ],
            ];

            // Collecter le résultat pour transmission au frontend
            $this->logger->info('[DEBUG] GetFacturesTool: AVANT addToolResult()', [
                'has_table_data_in_result' => true,
                'table_data_headers_count' => count($result['table_data']['headers']),
                'table_data_rows_count' => count($result['table_data']['rows']),
                'collector_count_before' => $this->toolResultCollector->count(),
            ]);

            $this->toolResultCollector->addToolResult('get_factures', $result);

            $this->logger->info('[DEBUG] GetFacturesTool: APRÈS addToolResult()', [
                'collector_count_after' => $this->toolResultCollector->count(),
            ]);

            return $result;
        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log détaillé pour développeurs (technique)
            $this->logger->error('Tool execution failed', [
                'tool_name' => 'get_factures',
                'duration_ms' => $durationMs,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => ['dateDebut' => $dateDebut, 'dateFin' => $dateFin],
            ]);

            // Message traduit générique pour utilisateur final (via agent IA)
            $userMessage = $this->translator->trans('operations.error.fetch_failed', [], 'tools');

            return $this->errorResponse($userMessage);
        }
    }

    /**
     * Formatter réponse d'erreur structurée.
     *
     * @return array{success: false, error: string, count: 0, facturations: array<empty, empty>}
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'count' => 0,
            'facturations' => [],
        ];
    }

    /**
     * Récupérer les détails complets d'une facture spécifique avec toutes ses lignes.
     *
     * MODE DÉTAIL : Appel direct à l'endpoint /Facturations/getFacture (pas de cache).
     * Retourne les informations complètes avec lignes de facturation détaillées.
     *
     * @param int   $idFacture ID de la facture recherchée
     * @param mixed $user      Utilisateur authentifié
     * @param mixed $tenant    Tenant actuel
     * @param float $startTime Timestamp début de l'appel
     *
     * @return array<string, mixed>
     */
    private function getFactureDetails(
        int $idFacture,
        mixed $user,
        mixed $tenant,
        float $startTime
    ): array {
        error_log("[GetFacturesTool::getFactureDetails] DÉBUT avec idFacture=$idFacture");

        // Appel direct à /Facturations/getFacture via FacturationApiService
        $facture = $this->facturationApi->getFacture($idFacture);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        // Facture non trouvée ou pas de droits
        if (null === $facture) {
            $this->logger->warning('GetFacturesTool: Facture non trouvée ou pas de droits', [
                'idFacture' => $idFacture,
                'user_id' => $user->getId(),
                'duration_ms' => $durationMs,
            ]);

            // Log tool call échec
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_factures',
                params: ['idFacture' => $idFacture],
                result: ['mode' => 'detail', 'found' => false],
                durationMs: $durationMs
            );

            return [
                'success' => false,
                'error' => "Facture #{$idFacture} non trouvée ou accès refusé (vérifier droit 'factures_Visu')",
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Facturations/getFacture',
                    'mode' => 'detail',
                    'duration_ms' => $durationMs,
                ],
            ];
        }

        // Log tool call succès
        $this->aiLogger->logToolCall(
            user: $user,
            toolName: 'get_factures',
            params: ['idFacture' => $idFacture],
            result: ['mode' => 'detail', 'nb_lignes' => count($facture->lignes), 'found' => true],
            durationMs: $durationMs
        );

        // Log KPI pour monitoring
        $this->logger->info('Tool executed successfully', [
            'tool_name' => 'get_factures',
            'mode' => 'DÉTAIL',
            'duration_ms' => $durationMs,
            'id_facture' => $idFacture,
            'nb_lignes' => count($facture->lignes),
            'user_id' => $user->getId(),
            'division_id' => $tenant->getIdCfi(),
        ]);

        // Générer les données du tableau pour les lignes de facturation (MODE DÉTAIL)
        $tableData = $this->generateDetailTableData($facture);

        // Retourner facture complète avec toutes les lignes
        $result = [
            'success' => true,
            'facture' => [
                'id' => $facture->id,
                'nomCommande' => $facture->nomCommande,
                'demandeur' => $facture->demandeur,
                'adresse' => $facture->adresse,
                'montantHT' => $facture->montantHT,
                'montantTTC' => $facture->montantTTC,
                'idTypeCout' => $facture->idTypeCout,
                'idTypePaiement' => $facture->idTypePaiement,
                'idDelaiPaiement' => $facture->idDelaiPaiement,
                'nb_lignes' => count($facture->lignes),
                'lignes' => array_map(
                    fn ($ligne) => [
                        'id' => $ligne->id,
                        'libelle' => $ligne->libelle,
                        'quantite' => $ligne->qte,
                        'montantHT' => $ligne->montantHT,
                        'tauxTVA' => $ligne->tauxTVA,
                        'montantTTC' => round($ligne->montantHT * (1 + $ligne->tauxTVA / 100), 2),
                    ],
                    $facture->lignes
                ),
            ],
            'table_data' => $tableData, // Ajout : données formatées pour DataTable (lignes de facturation)
            'metadata' => [
                'source' => 'CFI API',
                'endpoint' => '/Facturations/getFacture',
                'mode' => 'detail',
                'division' => $tenant->getNom(),
                'duration_ms' => $durationMs,
            ],
        ];

        // DEBUG : Logger le résultat complet pour diagnostic
        $this->logger->info('[DEBUG MODE DÉTAIL] Résultat retourné à l\'IA', [
            'has_success_key' => true,
            'success_value' => $result['success'],
            'has_facture_key' => true,
            'facture_id' => $result['facture']['id'],
            'facture_nb_lignes' => $result['facture']['nb_lignes'],
            'result_keys' => array_keys($result),
        ]);

        // Collecter le résultat pour transmission au frontend
        $this->toolResultCollector->addToolResult('get_factures', $result);

        return $result;
    }

    /**
     * Normaliser une date en format ISO 8601 pour l'API CFI.
     *
     * Accepte :
     * - Format ISO 8601 complet : "2024-10-14T15:04:29.547Z"
     * - Format date simple : "2024-10-14"
     *
     * Retourne toujours un format ISO 8601 complet compatible CFI.
     */
    private function normalizeDate(string $date): string
    {
        // Si déjà au format ISO 8601 avec heure, retourner tel quel
        if (str_contains($date, 'T')) {
            return $date;
        }

        // Sinon, convertir YYYY-MM-DD en ISO 8601 avec heure 00:00:00
        try {
            $dateTime = new \DateTime($date);

            return $dateTime->format('Y-m-d\TH:i:s.v\Z');
        } catch (\Exception $e) {
            // Fallback : retourner la date telle quelle
            return $date;
        }
    }

    /**
     * Générer les données formatées pour le composant DataTable.
     *
     * Transforme les facturations CFI en structure compatible avec DataTable component.
     * Conforme à la mockup : colonnes ID, NOM, BON DE COMMANDE, MOIS FACTURATION, MONTANT HT, MONTANT TTC.
     *
     * @param array<int, FactureDto>                                           $facturations     Facturations CFI
     * @param array<int, array{type: string, invoice_id: int, prompt: string}> $suggestedActions Actions suggérées
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>, totalRow: array<string, mixed>, linkColumns: array<string, string>}
     */
    private function generateTableData(array $facturations, array $suggestedActions): array
    {
        // En-têtes du tableau (conforme mockup)
        $headers = ['ID', 'NOM', 'BON DE COMMANDE', 'MOIS FACTURATION', 'MONTANT HT', 'MONTANT TTC'];

        // Lignes de données
        $rows = [];
        $totalHT = 0.0;
        $totalTTC = 0.0;

        foreach ($facturations as $facturation) {
            foreach ($facturation->factures as $facture) {
                $rows[] = [
                    'id' => (string) $facture->id,
                    'nom' => $facture->nomCommande ?? 'N/A',
                    'bon' => $facture->demandeur ?? '',
                    'mois' => $facturation->moisFacturation->format('F Y'),
                    'montant_ht' => number_format($facture->montantHT, 2, ',', ' ').' €',
                    'montant_ttc' => number_format($facture->montantTTC, 2, ',', ' ').' €',
                ];

                $totalHT += $facture->montantHT;
                $totalTTC += $facture->montantTTC;
            }
        }

        // Ligne Total
        $totalRow = [
            'label' => 'Total',
            'nom' => '',
            'bon' => '',
            'mois' => '',
            'montant_ht' => number_format($totalHT, 2, ',', ' ').' €',
            'montant_ttc' => number_format($totalTTC, 2, ',', ' ').' €',
        ];

        // Configuration des colonnes cliquables
        $linkColumns = [
            'id' => 'Donne-moi tous les détails de la facture {id}',
        ];

        return [
            'headers' => $headers,
            'rows' => $rows,
            'totalRow' => $totalRow,
            'linkColumns' => $linkColumns,
            'mode' => 'LISTE', // Indicateur de mode pour le frontend
        ];
    }

    /**
     * Générer les données du tableau pour une facture en détail (lignes de facturation).
     *
     * @param object $facture Facture complète avec ses lignes
     *
     * @return array{headers: array<string>, rows: array<array<string, string>>, totalRow: array<string, string>, linkColumns: array<string, string>}
     */
    private function generateDetailTableData(object $facture): array
    {
        // Headers du tableau (colonnes des lignes de facturation)
        $headers = ['ID LIGNE', 'LIBELLÉ', 'QUANTITÉ', 'MONTANT HT', 'TAUX TVA', 'MONTANT TTC'];

        // Rows : lignes de facturation
        $rows = [];
        $totalHT = 0.0;
        $totalTTC = 0.0;

        foreach ($facture->lignes as $ligne) {
            $montantHT = $ligne->montantHT ?? 0.0;
            $tauxTVA = $ligne->tauxTVA ?? 0.0;
            $montantTTC = round($montantHT * (1 + $tauxTVA / 100), 2);

            $rows[] = [
                'id' => (string) $ligne->id,
                'libelle' => $ligne->libelle ?? 'N/A',
                'quantite' => (string) ($ligne->qte ?? 0),
                'montant_ht' => number_format($montantHT, 2, ',', ' ').' €',
                'taux_tva' => number_format($tauxTVA, 2, ',', ' ').' %',
                'montant_ttc' => number_format($montantTTC, 2, ',', ' ').' €',
            ];

            $totalHT += $montantHT;
            $totalTTC += $montantTTC;
        }

        // Total row
        $totalRow = [
            'label' => 'Total',
            'libelle' => '',
            'quantite' => '',
            'montant_ht' => number_format($totalHT, 2, ',', ' ').' €',
            'taux_tva' => '',
            'montant_ttc' => number_format($totalTTC, 2, ',', ' ').' €',
        ];

        // Pas de linkColumns pour les lignes de facturation (pas cliquables)
        $linkColumns = [];

        return [
            'headers' => $headers,
            'rows' => $rows,
            'totalRow' => $totalRow,
            'linkColumns' => $linkColumns,
            'mode' => 'DÉTAIL', // Indicateur de mode pour le frontend
        ];
    }
}
