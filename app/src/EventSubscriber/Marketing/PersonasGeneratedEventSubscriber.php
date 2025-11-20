<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Entity\Persona;
use App\Entity\Project;
use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Gorillias\MarketingBundle\StructuredOutput\PersonaStructuredOutput;
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
            // Priorité basse (0) pour s'exécuter APRÈS MercurePublisherSubscriber (priorité 10)
            // Important car ce subscriber peut lancer des exceptions qui stopperaient la propagation
            TaskCompletedEvent::class => ['onTaskCompleted', 0],
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
            $personasCreated = $this->createPersonasFromResult($project, $result, $taskId);

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
     * Gère les objets PersonaStructuredOutput (bundle v3.8.4+) :
     * - $result peut être un objet PersonaStructuredOutput unique
     * - $result peut être un tableau contenant plusieurs PersonaStructuredOutput
     * - $result peut être un tableau avec clé 'personas' contenant des PersonaStructuredOutput
     *
     * Structure PersonaStructuredOutput v3.8.4+ :
     * {
     *   name: string,
     *   description: string,
     *   demographics: PersonaDemographics (DTO imbriqué),
     *   behaviors: PersonaBehaviors (DTO imbriqué),
     *   painPoints: array<string>,
     *   goals: array<string>,
     *   selected: bool
     * }
     *
     * @param array<string, mixed>|PersonaStructuredOutput $result
     *
     * @return int Nombre de personas créés
     */
    private function createPersonasFromResult(Project $project, array|PersonaStructuredOutput $result, string $taskId): int
    {
        // Déterminer les personas à traiter selon le format du résultat
        $personasData = $this->normalizePersonasData($result);

        $count = 0;

        foreach ($personasData as $personaData) {
            // Toujours utiliser la version array après normalisation (via toArray())
            // PHPStan : Après normalizePersonasData, $personaData est array<string, mixed>
            $count += $this->createPersonaFromArray($project, $personaData, $taskId);
        }

        return $count;
    }

    /**
     * Normalise les données de personas pour obtenir un tableau itérable d'arrays.
     *
     * Convertit tous les DTOs StructuredOutput en arrays via toArray().
     *
     * @param array<string, mixed>|PersonaStructuredOutput $result
     *
     * @return array<array<string, mixed>>
     */
    private function normalizePersonasData(array|PersonaStructuredOutput $result): array
    {
        // Cas 1 : Objet PersonaStructuredOutput unique
        if ($result instanceof PersonaStructuredOutput) {
            return [$result->toArray()];
        }

        // Cas 2 : Tableau avec clé 'personas' contenant des StructuredOutput ou arrays
        if (isset($result['personas']) && is_array($result['personas'])) {
            return array_map(
                fn ($persona) => $persona instanceof \Gorillias\MarketingBundle\StructuredOutput\PersonaItem
                    ? $persona->toArray()
                    : $persona,
                $result['personas']
            );
        }

        // Cas 3 : Tableau direct de personas (StructuredOutput ou arrays)
        if (! empty($result) && (reset($result) instanceof \Gorillias\MarketingBundle\StructuredOutput\PersonaItem || (is_array(reset($result)) && isset(reset($result)['name'])))) {
            return array_map(
                fn ($persona) => $persona instanceof \Gorillias\MarketingBundle\StructuredOutput\PersonaItem
                    ? $persona->toArray()
                    : $persona,
                $result
            );
        }

        // Cas 4 : Array mono-persona direct
        if (isset($result['name']) && is_string($result['name'])) {
            return [$result];
        }

        // Aucun format reconnu
        return [];
    }

    /**
     * Crée une entité Persona depuis un array (v3.8.4+).
     *
     * @param array<string, mixed> $personaData
     *
     * @return int 1 si persona créé, 0 si rejeté
     */
    private function createPersonaFromArray(Project $project, array $personaData, string $taskId): int
    {
        // Vérifier le champ obligatoire principal
        if (! isset($personaData['name'])) {
            $this->logger->warning('Skipping persona without name', [
                'available_keys' => array_keys($personaData),
                'task_id' => $taskId,
            ]);

            return 0;
        }

        // Rejeter les personas "Persona Default"
        if ('Persona Default' === $personaData['name']) {
            $this->logger->error('Skipping fallback persona - generation failed', [
                'task_id' => $taskId,
            ]);

            return 0;
        }

        // Extraire demographics (objet structuré depuis v3.8.4)
        $demographics = $personaData['demographics'] ?? [];
        $age = $this->extractAge($demographics['age'] ?? null);
        $gender = $demographics['gender'] ?? 'N/A';
        $job = $demographics['profession'] ?? $demographics['job'] ?? 'Non spécifié';

        // Créer l'entité
        $persona = new Persona();
        $persona->setProject($project);
        $persona->setName((string) $personaData['name']);
        $persona->setAge($age);
        $persona->setGender($gender);
        $persona->setJob((string) $job);

        if (isset($personaData['description'])) {
            $persona->setDescription((string) $personaData['description']);
        }

        // Récupérer quality_score depuis le bundle (v3.8.5+)
        // Le bundle retourne 0-100, on le convertit en 0.0-1.0 pour la BDD (DECIMAL(3,2))
        $qualityScore = $personaData['quality_score'] ?? 0.0;
        $persona->setQualityScore((string) ($qualityScore / 100));

        // Stocker rawData
        $persona->setRawData($personaData);

        $this->entityManager->persist($persona);

        $this->logger->debug('Persona entity created', [
            'name' => $persona->getName(),
            'age' => $persona->getAge(),
            'gender' => $persona->getGender(),
            'job' => $persona->getJob(),
            'quality_score' => $persona->getQualityScore(),
        ]);

        return 1;
    }

    /**
     * Extrait l'âge depuis un range (ex: "38-45 ans") ou un nombre.
     */
    private function extractAge(string|int|null $ageData): int
    {
        if (null === $ageData) {
            return 35; // Âge par défaut
        }

        // Si c'est déjà un int, le retourner directement
        if (is_int($ageData)) {
            return $ageData;
        }

        // Si c'est un range (ex: "38-45 ans"), prendre la moyenne
        if (preg_match('/(\d+)-(\d+)/', $ageData, $matches)) {
            return (int) round(((int) $matches[1] + (int) $matches[2]) / 2);
        }

        // Si c'est juste un nombre
        if (preg_match('/\d+/', $ageData, $matches)) {
            return (int) $matches[0];
        }

        return 35; // Âge par défaut si non parsable
    }
}
