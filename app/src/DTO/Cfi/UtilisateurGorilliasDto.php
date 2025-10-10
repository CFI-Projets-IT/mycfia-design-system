<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

/**
 * DTO Response contenant les informations utilisateur CFI.
 *
 * Retourne par l'endpoint POST /Utilisateurs/getUtilisateurGorillias
 * Contient les donnees utilisateur + le nouveau token CFI (jeton)
 */
class UtilisateurGorilliasDto
{
    /**
     * Identifiant unique de l'utilisateur dans CFI.
     */
    public int $id;

    /**
     * Identifiant de la division/organisation (tenant)
     * Ex: 1114 pour Caisse d'Epargne IDF.
     */
    public int $idDivision;

    /**
     * Nom de la division/organisation
     * Ex: "Caisse d'Epargne IDF".
     */
    public ?string $nomDivision = null;

    /**
     * Nom de famille de l'utilisateur.
     */
    public ?string $nom = null;

    /**
     * Prenom de l'utilisateur.
     */
    public ?string $prenom = null;

    /**
     * Adresse email de l'utilisateur.
     */
    public ?string $email = null;

    /**
     * Type d'option Google Analytics
     * Ex: "GEN1".
     */
    public ?string $type_d_option_GA = null;

    /**
     * Token CFI (jeton) pour les appels API subsequents
     * Duree de validite : 30 minutes
     * A utiliser dans le header "Jeton: {valeur}".
     */
    public ?string $jeton = null;

    /**
     * Cree un DTO depuis un tableau de donnees API.
     *
     * @param array<string, mixed> $data Donnees brutes de l'API CFI
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->id = (int) $data['id'];
        $dto->idDivision = (int) $data['idDivision'];
        $dto->nomDivision = $data['nomDivision'] ?? null;
        $dto->nom = $data['nom'] ?? null;
        $dto->prenom = $data['prenom'] ?? null;
        $dto->email = $data['email'] ?? null;
        $dto->type_d_option_GA = $data['type_d_option_GA'] ?? null;
        $dto->jeton = $data['jeton'] ?? null;

        return $dto;
    }

    /**
     * Converti le DTO en tableau.
     *
     * @return array<string, mixed>
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
            'type_d_option_GA' => $this->type_d_option_GA,
            'jeton' => $this->jeton,
        ];
    }

    /**
     * Retourne le nom complet de l'utilisateur.
     */
    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->prenom ?? '', $this->nom ?? ''));
    }
}
