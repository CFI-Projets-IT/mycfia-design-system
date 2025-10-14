<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

final readonly class EtatOperationDto
{
    public function __construct(
        public int $id,
        public string $libelle,
        public ?string $description = null,
    ) {
    }

    /**
     * Créer un DTO depuis les données brutes de l'API CFI.
     *
     * @param array<string, mixed> $data
     */
    public static function fromApiData(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            libelle: (string) $data['libelle'],
            description: $data['description'] ?? null,
        );
    }

    /**
     * Convertir le DTO en tableau pour l'agent IA.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'description' => $this->description,
        ];
    }
}
