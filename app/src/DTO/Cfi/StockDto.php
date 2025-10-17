<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

final readonly class StockDto
{
    /**
     * DTO Stock - Format CFI API.
     *
     * Structure conforme au Swagger CFI :
     * - id (int32) : OBLIGATOIRE
     * - idDivision (int32) : OBLIGATOIRE
     * - Tous les autres champs : NULLABLE
     * - qte et stockMinimum : DOUBLE (pas int !)
     */
    public function __construct(
        public int $id,
        public int $idDivision,
        public ?string $nom = null,
        public ?string $codeClient = null,
        public ?string $refStockage = null,
        public ?float $qte = null,
        public ?float $stockMinimum = null,
        public ?float $hauteurCm = null,
        public ?float $largeurCm = null,
        public ?float $profondeurCm = null,
        public ?float $poidsG = null,
        public ?string $commentaire = null,
    ) {
    }

    /**
     * Créer un DTO depuis les données brutes de l'API CFI.
     *
     * @param array<string, mixed> $data
     */
    public static function fromApiData(array $data): self
    {
        // Validation : id et idDivision sont obligatoires
        if (! isset($data['id'])) {
            throw new \InvalidArgumentException('Stock data must have an id');
        }
        if (! isset($data['idDivision'])) {
            throw new \InvalidArgumentException('Stock data must have an idDivision');
        }

        return new self(
            id: (int) $data['id'],
            idDivision: (int) $data['idDivision'],
            nom: $data['nom'] ?? null,
            codeClient: $data['codeClient'] ?? null,
            refStockage: $data['refStockage'] ?? null,
            qte: isset($data['qte']) ? (float) $data['qte'] : null,
            stockMinimum: isset($data['stockMinimum']) ? (float) $data['stockMinimum'] : null,
            hauteurCm: isset($data['hauteurCm']) ? (float) $data['hauteurCm'] : null,
            largeurCm: isset($data['largeurCm']) ? (float) $data['largeurCm'] : null,
            profondeurCm: isset($data['profondeurCm']) ? (float) $data['profondeurCm'] : null,
            poidsG: isset($data['poidsG']) ? (float) $data['poidsG'] : null,
            commentaire: $data['commentaire'] ?? null,
        );
    }

    /**
     * Convertir le DTO en tableau pour l'agent IA.
     *
     * Noms simplifiés pour l'IA (mapping vers français).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idDivision' => $this->idDivision,
            'reference' => $this->refStockage ?? sprintf('STOCK-%d', $this->id),
            'designation' => $this->nom ?? '',
            'quantite' => $this->qte,
            'quantiteMin' => $this->stockMinimum,
            'unite' => $this->codeClient,
            'dimensions' => [
                'hauteurCm' => $this->hauteurCm,
                'largeurCm' => $this->largeurCm,
                'profondeurCm' => $this->profondeurCm,
            ],
            'poidsG' => $this->poidsG,
            'commentaire' => $this->commentaire,
            'isEnAlerte' => $this->isEnAlerte(),
        ];
    }

    /**
     * Vérifier si le stock est en alerte (quantité < stockMinimum).
     */
    public function isEnAlerte(): bool
    {
        if (null === $this->stockMinimum || null === $this->qte) {
            return false;
        }

        return $this->qte < $this->stockMinimum;
    }
}
