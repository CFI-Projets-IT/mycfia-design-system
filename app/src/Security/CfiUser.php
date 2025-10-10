<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Entité utilisateur CFI pour Symfony Security.
 *
 * Représente un utilisateur authentifié via l'API CFI.
 * Pas de table User locale : CFI est la source de vérité.
 */
class CfiUser implements UserInterface
{
    private int $id;
    private int $idDivision;
    private ?string $nomDivision = null;
    private ?string $nom = null;
    private ?string $prenom = null;
    private ?string $email = null;
    private ?string $typeOptionGA = null;
    private ?string $jeton = null;

    /**
     * @var array<string> Roles Symfony
     */
    private array $roles = [];

    /**
     * @param array<string> $roles
     */
    public function __construct(
        int $id,
        int $idDivision,
        ?string $email = null,
        array $roles = ['ROLE_USER']
    ) {
        $this->id = $id;
        $this->idDivision = $idDivision;
        $this->email = $email;
        $this->roles = $roles;
    }

    /**
     * Identifiant unique pour Symfony Security.
     * Utilise l'email comme identifiant.
     */
    public function getUserIdentifier(): string
    {
        return $this->email ?? (string) $this->id;
    }

    /**
     * Roles de l'utilisateur.
     *
     * @return array<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // Garantir que tout utilisateur a au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Efface les credentials sensibles après authentification.
     */
    public function eraseCredentials(): void
    {
        // Le jeton CFI est stocké en session (CfiSessionService), pas ici
        // Pas de mot de passe à effacer (pas de table User locale)
    }

    // === Getters et Setters ===

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdDivision(): int
    {
        return $this->idDivision;
    }

    public function setIdDivision(int $idDivision): self
    {
        $this->idDivision = $idDivision;

        return $this;
    }

    public function getNomDivision(): ?string
    {
        return $this->nomDivision;
    }

    public function setNomDivision(?string $nomDivision): self
    {
        $this->nomDivision = $nomDivision;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTypeOptionGA(): ?string
    {
        return $this->typeOptionGA;
    }

    public function setTypeOptionGA(?string $typeOptionGA): self
    {
        $this->typeOptionGA = $typeOptionGA;

        return $this;
    }

    public function getJeton(): ?string
    {
        return $this->jeton;
    }

    public function setJeton(?string $jeton): self
    {
        $this->jeton = $jeton;

        return $this;
    }

    /**
     * Nom complet de l'utilisateur.
     */
    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->prenom ?? '', $this->nom ?? ''));
    }

    /**
     * Thème de l'interface utilisateur.
     * TODO Sprint S0+ : Stocker le thème utilisateur (local storage ou préférence CFI).
     */
    public function getTheme(): string
    {
        return 'light';
    }

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }
}
