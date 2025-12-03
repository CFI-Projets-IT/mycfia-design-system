<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

/**
 * DTO représentant une Division depuis l'API CFI.
 *
 * Utilisé pour le endpoint /Division/getDivisions qui retourne
 * les divisions enfants accessibles à l'utilisateur (hiérarchie).
 */
final readonly class DivisionDto
{
    /**
     * @param int         $id  Identifiant unique de la division (idDivision CFI)
     * @param string|null $nom Nom de la division
     */
    public function __construct(
        public int $id,
        public ?string $nom = null,
    ) {
    }

    /**
     * Créer un DivisionDto depuis les données brutes de l'API CFI.
     *
     * @param array<string, mixed> $data
     */
    public static function fromApiData(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            nom: $data['nom'] ?? null,
        );
    }

    /**
     * Sérialiser en tableau pour JSON.
     *
     * @return array{id: int, nom: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
        ];
    }
}
