<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

/**
 * DTO représentant un Utilisateur depuis l'API CFI.
 *
 * Utilisé pour le endpoint /Division/getUtilisateurs qui retourne
 * les utilisateurs enfants accessibles à l'utilisateur loggé (hiérarchie).
 *
 * Note: Ce DTO est différent de UtilisateurGorilliasDto qui représente
 * l'utilisateur authentifié complet.
 */
final readonly class UtilisateurDto
{
    /**
     * @param int         $id          Identifiant unique de l'utilisateur CFI
     * @param int         $idDivision  Division de l'utilisateur
     * @param string|null $nomDivision Nom de la division
     * @param string|null $nom         Nom de famille
     * @param string|null $prenom      Prénom
     * @param string|null $email       Email
     */
    public function __construct(
        public int $id,
        public int $idDivision,
        public ?string $nomDivision = null,
        public ?string $nom = null,
        public ?string $prenom = null,
        public ?string $email = null,
    ) {
    }

    /**
     * Créer un UtilisateurDto depuis les données brutes de l'API CFI.
     *
     * @param array<string, mixed> $data
     */
    public static function fromApiData(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            idDivision: (int) $data['idDivision'],
            nomDivision: $data['nomDivision'] ?? null,
            nom: $data['nom'] ?? null,
            prenom: $data['prenom'] ?? null,
            email: $data['email'] ?? null,
        );
    }

    /**
     * Nom complet de l'utilisateur.
     */
    public function getFullName(): string
    {
        $parts = array_filter([$this->prenom, $this->nom]);

        return implode(' ', $parts) ?: ($this->email ?? 'User #'.$this->id);
    }

    /**
     * Sérialiser en tableau pour JSON.
     *
     * @return array{id: int, idDivision: int, nomDivision: string|null, nom: string|null, prenom: string|null, email: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idDivision' => $this->idDivision,
            'nomDivision' => $this->nomDivision,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
        ];
    }
}
