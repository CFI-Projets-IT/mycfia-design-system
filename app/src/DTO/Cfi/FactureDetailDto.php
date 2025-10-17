<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

/**
 * DTO représentant une facture CFI avec ses détails et lignes.
 *
 * Correspond à un élément du tableau "factures" dans la réponse /Facturations/getFacturations
 */
final readonly class FactureDetailDto
{
    /**
     * @param FactureLigneDto[] $lignes
     */
    public function __construct(
        public int $id,
        public string $adresse,
        public string $nomCommande,
        public string $demandeur,
        public float $montantTTC,
        public float $montantHT,
        public int $idTypeCout,
        public int $idTypePaiement,
        public int $idDelaiPaiement,
        public array $lignes = [],
    ) {
    }

    /**
     * Créer un DTO depuis les données brutes de l'API CFI.
     *
     * @param array<string, mixed> $data
     */
    public static function fromApiData(array $data): self
    {
        // Mapper les lignes
        $lignes = [];
        if (isset($data['lignes']) && is_array($data['lignes'])) {
            foreach ($data['lignes'] as $ligneData) {
                if (is_array($ligneData)) {
                    $lignes[] = FactureLigneDto::fromApiData($ligneData);
                }
            }
        }

        return new self(
            id: (int) $data['id'],
            adresse: (string) $data['adresse'],
            nomCommande: (string) $data['nomCommande'],
            demandeur: (string) $data['demandeur'],
            montantTTC: (float) $data['montantTTC'],
            montantHT: (float) $data['montantHT'],
            idTypeCout: (int) $data['idTypeCout'],
            idTypePaiement: (int) $data['idTypePaiement'],
            idDelaiPaiement: (int) $data['idDelaiPaiement'],
            lignes: $lignes,
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
            'adresse' => $this->adresse,
            'nomCommande' => $this->nomCommande,
            'demandeur' => $this->demandeur,
            'montantTTC' => $this->montantTTC,
            'montantHT' => $this->montantHT,
            'idTypeCout' => $this->idTypeCout,
            'idTypePaiement' => $this->idTypePaiement,
            'idDelaiPaiement' => $this->idDelaiPaiement,
            'nb_lignes' => count($this->lignes),
            'lignes' => array_map(fn (FactureLigneDto $ligne) => $ligne->toArray(), $this->lignes),
        ];
    }
}
