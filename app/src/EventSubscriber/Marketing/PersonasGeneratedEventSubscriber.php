<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Entity\Persona;
use App\Entity\Project;
use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event.
 *
 * Subscriber pour persister les personas générés par l'IA en base de données.
 *
 * Écoute l'événement TaskCompletedEvent dispatché par le PersonaGeneratorAgent,
 * extrait les personas du résultat, les persiste en base de données et met à jour
 * le statut du projet à PERSONA_GENERATED.
 *
 * Workflow :
 * 1. PersonaController dispatch task via AgentTaskManager avec options['project_id']
 * 2. PersonaGeneratorAgent génère 3-5 personas
 * 3. TaskCompletedEvent est dispatché avec result contenant les personas
 * 4. Ce subscriber :
 *    - Vérifie que c'est bien une génération de personas
 *    - Extrait project_id depuis context
 *    - Mappe chaque persona vers entité Persona
 *    - Persiste en base de données
 *    - Met à jour le statut du projet
 */
final readonly class PersonasGeneratedEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TaskCompletedEvent::class => 'onTaskCompleted',
        ];
    }

    /**
     * Persiste les personas générés quand TaskCompletedEvent est reçu.
     *
     * Filtre sur l'agent PersonaGeneratorAgent pour ne traiter que les générations de personas.
     */
    public function onTaskCompleted(TaskCompletedEvent $event): void
    {
        // Filtrer : seulement si c'est PersonaGeneratorAgent
        if (! str_contains($event->agentName, 'PersonaGeneratorAgent')) {
            return;
        }

        $taskId = $event->taskId;
        $result = $event->result;
        $context = $event->context;

        $this->logger->info('PersonasGeneratedEvent received', [
            'task_id' => $taskId,
            'agent_name' => $event->agentName,
            'result_type' => gettype($result),
            'context_keys' => array_keys($context),
        ]);

        // Vérifier qu'on a un résultat valide
        if (! is_array($result) || empty($result)) {
            $this->logger->warning('Task completed but result is empty', [
                'task_id' => $taskId,
            ]);

            return;
        }

        // Extraire project_id depuis context (passé dans options lors du dispatch)
        $projectId = $context['project_id'] ?? null;

        if (! $projectId) {
            $this->logger->error('project_id not found in context', [
                'task_id' => $taskId,
                'context' => $context,
            ]);

            return;
        }

        // Récupérer le projet depuis la base de données
        $project = $this->projectRepository->find($projectId);

        if (! $project) {
            $this->logger->error('Project not found', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);

            return;
        }

        try {
            // Supprimer les anciens personas si regénération
            $this->deleteExistingPersonas($project);

            // Créer et persister les nouvelles entités Persona
            $personasCreated = $this->createPersonasFromResult($project, $result);

            // Mettre à jour le statut du projet
            $project->setStatus(ProjectStatus::PERSONA_GENERATED);

            // Flush en base de données
            $this->entityManager->flush();

            $this->logger->info('Personas persisted successfully', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'personas_count' => $personasCreated,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to persist personas', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw pour que la tâche soit marquée en erreur
        }
    }

    /**
     * Supprime les personas existants du projet (si regénération).
     */
    private function deleteExistingPersonas(Project $project): void
    {
        $existingPersonas = $project->getPersonas();

        if ($existingPersonas->isEmpty()) {
            return;
        }

        $this->logger->info('Deleting existing personas before regeneration', [
            'project_id' => $project->getId(),
            'existing_count' => $existingPersonas->count(),
        ]);

        foreach ($existingPersonas as $persona) {
            $this->entityManager->remove($persona);
        }

        $this->entityManager->flush(); // Flush pour vider avant création
    }

    /**
     * Crée les entités Persona depuis le résultat de l'agent IA.
     *
     * Le résultat peut être un tableau de personas ou un tableau avec clé 'personas'.
     *
     * Structure attendue d'un persona :
     * [
     *   'name' => 'Sophie Tech Enthusiast',
     *   'age' => 32,
     *   'gender' => 'F',
     *   'job' => 'CTO',
     *   'interests' => 'Innovation, IA, développement durable' (string ou array),
     *   'behaviors' => 'Recherche intensive avant achat' (string ou array),
     *   'motivations' => 'ROI mesurable, sécurité' (string ou array),
     *   'pains' => 'Infrastructure complexe, coûts imprévisibles' (string ou array),
     *   'quality_score' => 0.85 (float)
     * ]
     *
     * @param array<string, mixed> $result
     *
     * @return int Nombre de personas créés
     */
    private function createPersonasFromResult(Project $project, array $result): int
    {
        // Le résultat peut avoir une clé 'personas' ou être directement le tableau de personas
        $personasData = $result['personas'] ?? $result;

        // Si c'est un seul persona (pas de tableau de tableaux), le wrapper dans un tableau
        if (isset($personasData['name']) && is_string($personasData['name'])) {
            $personasData = [$personasData];
        }

        $count = 0;

        foreach ($personasData as $personaData) {
            if (! is_array($personaData)) {
                $this->logger->warning('Skipping invalid persona data (not array)', [
                    'data_type' => gettype($personaData),
                ]);

                continue;
            }

            // Vérifier les champs obligatoires
            if (! isset($personaData['name'], $personaData['age'], $personaData['gender'], $personaData['job'])) {
                $this->logger->warning('Skipping persona with missing required fields', [
                    'available_keys' => array_keys($personaData),
                ]);

                continue;
            }

            $persona = new Persona();
            $persona->setProject($project);
            $persona->setName((string) $personaData['name']);
            $persona->setAge((int) $personaData['age']);
            $persona->setGender((string) $personaData['gender']);
            $persona->setJob((string) $personaData['job']);

            // Champs JSON : convertir en string si array
            $persona->setInterests($this->normalizeJsonField($personaData['interests'] ?? ''));
            $persona->setBehaviors($this->normalizeJsonField($personaData['behaviors'] ?? ''));
            $persona->setMotivations($this->normalizeJsonField($personaData['motivations'] ?? ''));
            $persona->setPains($this->normalizeJsonField($personaData['pains'] ?? ''));

            // Quality score optionnel
            if (isset($personaData['quality_score'])) {
                $persona->setQualityScore((string) $personaData['quality_score']);
            }

            $this->entityManager->persist($persona);
            ++$count;

            $this->logger->debug('Persona entity created', [
                'name' => $persona->getName(),
                'age' => $persona->getAge(),
                'job' => $persona->getJob(),
            ]);
        }

        return $count;
    }

    /**
     * Normalise un champ JSON : convertit array en JSON string, garde string tel quel.
     */
    private function normalizeJsonField(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_string($value)) {
            return $value;
        }

        // Fallback : convertir en string
        return (string) $value;
    }
}
