<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

/**
 * DTO représentant une facturation CFI (regroupement mensuel de factures).
 *
 * Correspond à un élément racine dans la réponse /Facturations/getFacturations
 *
 * Structure hiérarchique :
 * - FactureDto (ce DTO) : Facturation mensuelle
 *   └─ FactureDetailDto[] : Factures individuelles
 *      └─ FactureLigneDto[] : Lignes de détail
 */
final readonly class FactureDto
{
    /**
     * @param FactureDetailDto[] $factures
     */
    public function __construct(
        public int $id,
        public \DateTimeInterface $dateMiseADispo,
        public \DateTimeInterface $moisFacturation,
        public array $factures = [],
    ) {
    }

    /**
     * Créer un DTO depuis les données brutes de l'API CFI.
     *
     * @param array<string, mixed> $data
     */
    public static function fromApiData(array $data): self
    {
        // Mapper les factures
        $factures = [];
        if (isset($data['factures']) && is_array($data['factures'])) {
            foreach ($data['factures'] as $factureData) {
                if (is_array($factureData)) {
                    $factures[] = FactureDetailDto::fromApiData($factureData);
                }
            }
        }

        return new self(
            id: (int) $data['id'],
            dateMiseADispo: new \DateTime($data['dateMiseADispo']),
            moisFacturation: new \DateTime($data['moisFacturation']),
            factures: $factures,
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
            'dateMiseADispo' => $this->dateMiseADispo->format('Y-m-d H:i:s'),
            'moisFacturation' => $this->moisFacturation->format('Y-m'),
            'nb_factures' => count($this->factures),
            'factures' => array_map(fn (FactureDetailDto $facture) => $facture->toArray(), $this->factures),
        ];
    }

    /**
     * Calculer le montant total HT de toutes les factures.
     */
    public function getMontantTotalHT(): float
    {
        $total = 0.0;
        foreach ($this->factures as $facture) {
            $total += $facture->montantHT;
        }

        return $total;
    }

    /**
     * Calculer le montant total TTC de toutes les factures.
     */
    public function getMontantTotalTTC(): float
    {
        $total = 0.0;
        foreach ($this->factures as $facture) {
            $total += $facture->montantTTC;
        }

        return $total;
    }
}
