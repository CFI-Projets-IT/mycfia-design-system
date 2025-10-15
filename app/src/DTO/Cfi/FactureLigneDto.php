<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

/**
 * DTO représentant une ligne de détail d'une facture CFI.
 *
 * Correspond à la structure "lignes" dans la réponse /Facturations/getFacturations
 */
final readonly class FactureLigneDto
{
    public function __construct(
        public int $id,
        public string $libelle,
        public ?int $qte = null,
        public ?float $montantHT = null,
        public ?float $tauxTVA = null,
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
            qte: isset($data['qte']) ? (int) $data['qte'] : null,
            montantHT: isset($data['montantHT']) ? (float) $data['montantHT'] : null,
            tauxTVA: isset($data['tauxTVA']) ? (float) $data['tauxTVA'] : null,
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
            'qte' => $this->qte,
            'montantHT' => $this->montantHT,
            'tauxTVA' => $this->tauxTVA,
        ];
    }
}
