<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

final readonly class LigneOperationDto
{
    public function __construct(
        public int $id,
        public string $nom,
        public string $type,
        public \DateTimeInterface $dateCreation,
        public ?int $idCampagne = null,
        public ?string $nomCampagne = null,
        public ?string $statut = null,
        public ?int $nbDestinataires = null,
        public ?int $nbEnvoyes = null,
        public ?int $idEtatOperation = null,
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
            nom: (string) $data['nom'],
            type: (string) $data['type'],
            dateCreation: new \DateTime($data['dateCreation']),
            idCampagne: isset($data['idCampagne']) ? (int) $data['idCampagne'] : null,
            nomCampagne: $data['nomCampagne'] ?? null,
            statut: $data['statut'] ?? null,
            nbDestinataires: isset($data['nbDestinataires']) ? (int) $data['nbDestinataires'] : null,
            nbEnvoyes: isset($data['nbEnvoyes']) ? (int) $data['nbEnvoyes'] : null,
            idEtatOperation: isset($data['idEtatOperation']) ? (int) $data['idEtatOperation'] : null,
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
            'nom' => $this->nom,
            'type' => $this->type,
            'dateCreation' => $this->dateCreation->format('Y-m-d H:i:s'),
            'idCampagne' => $this->idCampagne,
            'nomCampagne' => $this->nomCampagne,
            'statut' => $this->statut,
            'nbDestinataires' => $this->nbDestinataires,
            'nbEnvoyes' => $this->nbEnvoyes,
            'idEtatOperation' => $this->idEtatOperation,
        ];
    }
}
