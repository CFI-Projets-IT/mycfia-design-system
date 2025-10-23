<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\FactureDto;
use App\Security\UserAuthenticationService;
use App\Service\AiLoggerService;
use App\Service\Api\FacturationApiService;
use App\Service\ToolCallCollector;
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
            // Plus besoin de récupérer toutes les factures et de chercher, on va directement chercher la facture spécifique
            if (null !== $idFacture) {
                return $this->getFactureDetails($idFacture, $user, $tenant, $startTime);
            }

            // MODE LISTE : Récupération de la liste des factures avec filtres temporels
            // Convertir dates YYYY-MM-DD en ISO 8601 si nécessaire
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
                        ],
                        $facturation->factures
                    ),
                ],
                $facturations
            );

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

            return [
                'success' => true,
                'count' => count($formattedFacturations),
                'facturations' => $formattedFacturations,
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Facturations/getFacturations',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                ],
            ];
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

        // Retourner facture complète avec toutes les lignes
        return [
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
            'metadata' => [
                'source' => 'CFI API',
                'endpoint' => '/Facturations/getFacture',
                'mode' => 'detail',
                'division' => $tenant->getNom(),
                'duration_ms' => $durationMs,
            ],
        ];
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
}
