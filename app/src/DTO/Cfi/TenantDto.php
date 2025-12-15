<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

/**
 * DTO représentant un tenant (Division) dans le système CFI.
 *
 * Centralise les informations de division pour :
 * - Contexte utilisateur multi-tenant
 * - Filtrage des données par division
 * - Affichage des métadonnées dans les réponses IA
 */
final readonly class TenantDto
{
    /**
     * @param int                  $idCfi       Identifiant CFI de la division
     * @param string               $nom         Nom de la division
     * @param string|null          $code        Code court de la division (optionnel)
     * @param bool                 $actif       Division active ou non
     * @param array<string, mixed> $permissions Permissions utilisateur pour cette division (optionnel)
     */
    public function __construct(
        public int $idCfi,
        public string $nom,
        public ?string $code = null,
        public bool $actif = true,
        public array $permissions = [],
    ) {
    }

    /**
     * Créer depuis un UtilisateurGorilliasDto.
     *
     * Extrait les informations de tenant depuis les données utilisateur CFI.
     */
    public static function fromUtilisateur(UtilisateurGorilliasDto $utilisateur): self
    {
        return new self(
            idCfi: $utilisateur->idDivision,
            nom: $utilisateur->nomDivision ?? 'Division sans nom',
            code: null, // TODO Sprint S1+: Ajouter code division si disponible dans API CFI
            actif: true,
            permissions: [], // TODO Sprint S1+: Extraire permissions depuis API CFI
        );
    }

    /**
     * Récupérer l'ID CFI (alias pour compatibilité).
     */
    public function getIdCfi(): int
    {
        return $this->idCfi;
    }

    /**
     * Récupérer le nom (alias pour compatibilité).
     */
    public function getNom(): string
    {
        return $this->nom;
    }

    /**
     * Sérialiser en tableau pour JSON.
     *
     * @return array{idCfi: int, nom: string, code: string|null, actif: bool}
     */
    public function toArray(): array
    {
        return [
            'idCfi' => $this->idCfi,
            'nom' => $this->nom,
            'code' => $this->code,
            'actif' => $this->actif,
        ];
    }
}
