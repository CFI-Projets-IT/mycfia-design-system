<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Persona;
use App\Entity\Project;
use App\Enum\ProjectStatus;
use App\Message\GeneratePersonasMessage;
use App\Service\MarketingGenerationPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Agent\PersonaGeneratorAgent;
use Gorillias\MarketingBundle\Service\MarketingLoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asynchrone pour la génération de personas marketing par IA.
 *
 * Responsabilités :
 * - Récupérer le projet depuis la base de données
 * - Appeler PersonaGeneratorAgent pour génération IA
 * - Persister les personas générés dans la base
 * - Mettre à jour le statut du projet (DRAFT → PERSONA_GENERATED)
 * - Publier des événements Mercure pour notification temps réel
 * - Gérer les erreurs et logger l'exécution
 *
 * Architecture :
 * - Traite les messages GeneratePersonasMessage de manière asynchrone
 * - Exécute la génération via PersonaGeneratorAgent (Mistral Large)
 * - Permet au contrôleur de retourner immédiatement sans bloquer
 * - Notifie l'utilisateur en temps réel via Mercure
 *
 * Durée estimée : ~30 secondes pour 3 personas
 * Coût estimé : ~$0.005 par génération
 */
#[AsMessageHandler]
final readonly class GeneratePersonasMessageHandler
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private PersonaGeneratorAgent $personaGenerator,
        private MarketingGenerationPublisher $publisher,
        private EntityManagerInterface $entityManager,
        MarketingLoggerFactory $loggerFactory,
    ) {
        $this->logger = $loggerFactory->getGeneralLogger();
    }

    /**
     * Traiter le message de génération de personas de manière asynchrone.
     *
     * @param GeneratePersonasMessage $message Message contenant les paramètres de génération
     */
    public function __invoke(GeneratePersonasMessage $message): void
    {
        $startTime = microtime(true);

        try {
            // 1. Récupérer le projet depuis la base de données
            $project = $this->entityManager->getRepository(Project::class)->find($message->projectId);
            if (null === $project) {
                throw new \RuntimeException(sprintf('Project with ID %d not found', $message->projectId));
            }

            $this->logger->info('GeneratePersonasMessageHandler: Starting persona generation', [
                'project_id' => $message->projectId,
                'user_id' => $message->userId,
                'tenant_id' => $message->tenantId,
                'number_of_personas' => $message->numberOfPersonas,
            ]);

            // 2. Publier événement de démarrage
            $this->publisher->publishStart(
                $message->projectId,
                'personas',
                sprintf('Génération de %d personas en cours...', $message->numberOfPersonas)
            );

            // 3. Extraire sector et target depuis le projet
            $sector = $project->getDescription() ?: $project->getGoalType()->value;
            $target = sprintf(
                'Client cible pour %s avec objectif %s',
                $project->getDescription() ?: 'ce projet',
                $project->getGoalType()->value
            );

            // 4. Générer les personas via PersonaGeneratorAgent (vraie API)
            $generatedCount = 0;
            for ($i = 0; $i < $message->numberOfPersonas; ++$i) {
                $this->publisher->publishProgress(
                    $message->projectId,
                    'personas',
                    sprintf('Génération persona %d/%d...', $i + 1, $message->numberOfPersonas),
                    ['progress' => round((($i + 1) / $message->numberOfPersonas) * 100, 2)]
                );

                // Appel de la vraie API du bundle : generatePersona(sector, target, options)
                /** @var array<string, mixed> $personaData */
                $personaData = $this->personaGenerator->generatePersona(
                    sector: $sector,
                    target: $target,
                    options: [
                        'detailed' => true,
                        'additionalContext' => $message->additionalContext,
                    ]
                );

                // 5. Mapper les données vers l'entité Persona
                $persona = new Persona();
                $persona->setProject($project);

                // Champs typés pour les données importantes
                $demographics = $personaData['demographics'];
                $persona->setName($personaData['name']);
                $persona->setDescription($personaData['description']);
                $persona->setAge(is_numeric($demographics['age'] ?? 0) ? (int) $demographics['age'] : 35);
                $persona->setGender($demographics['gender'] ?? 'N/A');
                $persona->setJob($demographics['job'] ?? $demographics['profession'] ?? 'Non spécifié');

                // Stocker toutes les données complètes en JSON
                $persona->setRawData($personaData);

                // Calculer le score de qualité via l'analyse du bundle (retourne 0-100, converti en 0-1)
                $qualityScore = $this->personaGenerator->analyzePersonaQuality($personaData) / 100;
                $persona->setQualityScore((string) $qualityScore);

                // createdAt géré automatiquement par Gedmo (#[Gedmo\Timestampable(on: 'create')])

                $this->entityManager->persist($persona);
                ++$generatedCount;

                $this->publisher->publishProgress(
                    $message->projectId,
                    'personas',
                    sprintf('Persona "%s" générée avec succès', $personaData['name']),
                    ['persona_name' => $personaData['name']]
                );
            }

            // 6. Mettre à jour le statut du projet
            $project->setStatus(ProjectStatus::PERSONA_GENERATED);
            $this->entityManager->flush();

            // 7. Publier événement de succès
            $duration = (microtime(true) - $startTime) * 1000;

            $this->publisher->publishComplete(
                $message->projectId,
                'personas',
                sprintf('%d personas générés avec succès !', $generatedCount),
                [
                    'personas_count' => $generatedCount,
                    'duration_ms' => round($duration, 2),
                ]
            );

            $this->logger->info('GeneratePersonasMessageHandler: Persona generation completed successfully', [
                'project_id' => $message->projectId,
                'personas_count' => $generatedCount,
                'duration_ms' => round($duration, 2),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('GeneratePersonasMessageHandler: Persona generation failed', [
                'project_id' => $message->projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->publisher->publishError(
                $message->projectId,
                'personas',
                'Échec de la génération des personas. Veuillez réessayer.',
                $e->getMessage()
            );

            // Propager l'erreur pour retry mechanism de Messenger
            throw $e;
        }
    }
}
