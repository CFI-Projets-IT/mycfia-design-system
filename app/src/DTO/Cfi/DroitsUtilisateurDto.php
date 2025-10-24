<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

/**
 * DTO représentant les droits et permissions d'un utilisateur CFI.
 *
 * Correspond à la réponse de /Utilisateurs/getDroitsUtilisateur
 * Contient 25 permissions booléennes + 1 quota téléchargement HD
 */
final readonly class DroitsUtilisateurDto
{
    public function __construct(
        public bool $connexion,
        public bool $pwa,
        public bool $administrateur,
        public bool $logistique,
        public bool $production,
        public bool $developpement,
        public bool $utilisateurs_Modif,
        public bool $utilisateurs_Crea,
        public bool $utilisateurs_Supp,
        public bool $utilisateurs_EmpruntIdentite,
        public bool $divisions_Crea,
        public bool $divisions_Modif,
        public bool $divisions_Visu,
        public bool $stocks_Crea,
        public bool $stocks_Modif,
        public bool $stocks_Visu,
        public bool $stocks_Supp,
        public bool $operations_Crea,
        public bool $operations_Valid,
        public bool $operations_Visu,
        public bool $campagnes_Commande,
        public bool $campagnes_Edit,
        public bool $reprises_Visu,
        public bool $npaI_Crea,
        public bool $npaI_Visu,
        public bool $factures_Visu,
        public bool $signataire,
        public bool $valideur,
        public float $telechargementHD,
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
            connexion: (bool) ($data['connexion'] ?? false),
            pwa: (bool) ($data['pwa'] ?? false),
            administrateur: (bool) ($data['administrateur'] ?? false),
            logistique: (bool) ($data['logistique'] ?? false),
            production: (bool) ($data['production'] ?? false),
            developpement: (bool) ($data['developpement'] ?? false),
            utilisateurs_Modif: (bool) ($data['utilisateurs_Modif'] ?? false),
            utilisateurs_Crea: (bool) ($data['utilisateurs_Crea'] ?? false),
            utilisateurs_Supp: (bool) ($data['utilisateurs_Supp'] ?? false),
            utilisateurs_EmpruntIdentite: (bool) ($data['utilisateurs_EmpruntIdentite'] ?? false),
            divisions_Crea: (bool) ($data['divisions_Crea'] ?? false),
            divisions_Modif: (bool) ($data['divisions_Modif'] ?? false),
            divisions_Visu: (bool) ($data['divisions_Visu'] ?? false),
            stocks_Crea: (bool) ($data['stocks_Crea'] ?? false),
            stocks_Modif: (bool) ($data['stocks_Modif'] ?? false),
            stocks_Visu: (bool) ($data['stocks_Visu'] ?? false),
            stocks_Supp: (bool) ($data['stocks_Supp'] ?? false),
            operations_Crea: (bool) ($data['operations_Crea'] ?? false),
            operations_Valid: (bool) ($data['operations_Valid'] ?? false),
            operations_Visu: (bool) ($data['operations_Visu'] ?? false),
            campagnes_Commande: (bool) ($data['campagnes_Commande'] ?? false),
            campagnes_Edit: (bool) ($data['campagnes_Edit'] ?? false),
            reprises_Visu: (bool) ($data['reprises_Visu'] ?? false),
            npaI_Crea: (bool) ($data['npaI_Crea'] ?? false),
            npaI_Visu: (bool) ($data['npaI_Visu'] ?? false),
            factures_Visu: (bool) ($data['factures_Visu'] ?? false),
            signataire: (bool) ($data['signataire'] ?? false),
            valideur: (bool) ($data['valideur'] ?? false),
            telechargementHD: (float) ($data['telechargementHD'] ?? 0.0),
        );
    }

    /**
     * Convertir le DTO en tableau associatif.
     *
     * @return array<string, bool|float>
     */
    public function toArray(): array
    {
        return [
            'connexion' => $this->connexion,
            'pwa' => $this->pwa,
            'administrateur' => $this->administrateur,
            'logistique' => $this->logistique,
            'production' => $this->production,
            'developpement' => $this->developpement,
            'utilisateurs_Modif' => $this->utilisateurs_Modif,
            'utilisateurs_Crea' => $this->utilisateurs_Crea,
            'utilisateurs_Supp' => $this->utilisateurs_Supp,
            'utilisateurs_EmpruntIdentite' => $this->utilisateurs_EmpruntIdentite,
            'divisions_Crea' => $this->divisions_Crea,
            'divisions_Modif' => $this->divisions_Modif,
            'divisions_Visu' => $this->divisions_Visu,
            'stocks_Crea' => $this->stocks_Crea,
            'stocks_Modif' => $this->stocks_Modif,
            'stocks_Visu' => $this->stocks_Visu,
            'stocks_Supp' => $this->stocks_Supp,
            'operations_Crea' => $this->operations_Crea,
            'operations_Valid' => $this->operations_Valid,
            'operations_Visu' => $this->operations_Visu,
            'campagnes_Commande' => $this->campagnes_Commande,
            'campagnes_Edit' => $this->campagnes_Edit,
            'reprises_Visu' => $this->reprises_Visu,
            'npaI_Crea' => $this->npaI_Crea,
            'npaI_Visu' => $this->npaI_Visu,
            'factures_Visu' => $this->factures_Visu,
            'signataire' => $this->signataire,
            'valideur' => $this->valideur,
            'telechargementHD' => $this->telechargementHD,
        ];
    }
}
