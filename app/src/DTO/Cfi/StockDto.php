<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

final readonly class StockDto
{
    public function __construct(
        public int $id,
        public string $reference,
        public string $designation,
        public int $quantite,
        public ?int $quantiteMin = null,
        public ?int $quantiteMax = null,
        public ?string $unite = null,
        public ?\DateTimeInterface $dateDerniereMAJ = null,
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
            reference: (string) $data['reference'],
            designation: (string) $data['designation'],
            quantite: (int) $data['quantite'],
            quantiteMin: isset($data['quantiteMin']) ? (int) $data['quantiteMin'] : null,
            quantiteMax: isset($data['quantiteMax']) ? (int) $data['quantiteMax'] : null,
            unite: $data['unite'] ?? null,
            dateDerniereMAJ: isset($data['dateDerniereMAJ']) ? new \DateTime($data['dateDerniereMAJ']) : null,
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
            'reference' => $this->reference,
            'designation' => $this->designation,
            'quantite' => $this->quantite,
            'quantiteMin' => $this->quantiteMin,
            'quantiteMax' => $this->quantiteMax,
            'unite' => $this->unite,
            'dateDerniereMAJ' => $this->dateDerniereMAJ?->format('Y-m-d H:i:s'),
            'isEnAlerte' => $this->isEnAlerte(),
        ];
    }

    /**
     * Vérifier si le stock est en alerte (quantité < quantiteMin).
     */
    public function isEnAlerte(): bool
    {
        if (null === $this->quantiteMin) {
            return false;
        }

        return $this->quantite < $this->quantiteMin;
    }
}
