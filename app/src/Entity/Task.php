<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'marketing_task')]
#[ORM\Index(columns: ['status', 'completed_at'], name: 'idx_task_status_completed')]
#[ORM\Index(columns: ['agent_class'], name: 'idx_task_agent_class')]
#[ORM\Index(columns: ['type'], name: 'idx_task_type')]
#[ORM\Index(columns: ['created_at'], name: 'idx_task_created_at')]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $type;

    #[ORM\Column(length: 50)]
    private string $status = 'pending'; // pending, processing, completed, failed

    #[ORM\Column(length: 255)]
    private string $agentClass;

    #[ORM\Column(length: 255)]
    private string $methodName;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $arguments = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $context = [];

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $result = null;

    #[ORM\Column]
    private int $tokensInput = 0;

    #[ORM\Column]
    private int $tokensOutput = 0;

    #[ORM\Column]
    private int $tokensTotal = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 4, columnDefinition: 'DECIMAL(10,4) DEFAULT 0.0000')]
    private string $cost = '0.0000';

    #[ORM\Column]
    private int $durationMs = 0;

    #[ORM\Column(length: 100)]
    private string $modelUsed = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorTrace = null;

    #[ORM\Column]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->startedAt = new \DateTimeImmutable();
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid|string $uuid): static
    {
        $this->uuid = $uuid instanceof Uuid ? $uuid : Uuid::fromString($uuid);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAgentClass(): string
    {
        return $this->agentClass;
    }

    public function setAgentClass(string $agentClass): static
    {
        $this->agentClass = $agentClass;

        return $this;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function setMethodName(string $methodName): static
    {
        $this->methodName = $methodName;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function setArguments(array $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResult(): ?array
    {
        return $this->result;
    }

    /**
     * @param array<string, mixed>|null $result
     */
    public function setResult(?array $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function getTokensInput(): int
    {
        return $this->tokensInput;
    }

    public function setTokensInput(int $tokensInput): static
    {
        $this->tokensInput = $tokensInput;

        return $this;
    }

    public function getTokensOutput(): int
    {
        return $this->tokensOutput;
    }

    public function setTokensOutput(int $tokensOutput): static
    {
        $this->tokensOutput = $tokensOutput;

        return $this;
    }

    public function getTokensTotal(): int
    {
        return $this->tokensTotal;
    }

    public function setTokensTotal(int $tokensTotal): static
    {
        $this->tokensTotal = $tokensTotal;

        return $this;
    }

    public function getCost(): string
    {
        return $this->cost;
    }

    public function setCost(string $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getDurationMs(): int
    {
        return $this->durationMs;
    }

    public function setDurationMs(int $durationMs): static
    {
        $this->durationMs = $durationMs;

        return $this;
    }

    public function getModelUsed(): string
    {
        return $this->modelUsed;
    }

    public function setModelUsed(string $modelUsed): static
    {
        $this->modelUsed = $modelUsed;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getErrorTrace(): ?string
    {
        return $this->errorTrace;
    }

    public function setErrorTrace(?string $errorTrace): static
    {
        $this->errorTrace = $errorTrace;

        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Helpers

    public function isCompleted(): bool
    {
        return 'completed' === $this->status;
    }

    public function isFailed(): bool
    {
        return 'failed' === $this->status;
    }

    public function isProcessing(): bool
    {
        return 'processing' === $this->status;
    }

    public function getDurationSeconds(): float
    {
        return round($this->durationMs / 1000, 2);
    }

    public function getFormattedCost(): string
    {
        return '$'.number_format((float) $this->cost, 4);
    }

    public function getAgentShortName(): string
    {
        $parts = explode('\\', $this->agentClass);

        return end($parts);
    }
}
