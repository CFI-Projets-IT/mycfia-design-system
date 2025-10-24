<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    /**
     * ID utilisateur depuis CFI (id).
     * Un utilisateur peut changer de Division au fil du temps (dernière connexion = Division active).
     */
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    private int $idCfi;

    /**
     * Email de l'utilisateur (nullable car certains users n'ont qu'un identifiant CFI).
     */
    #[ORM\Column(length: 180, unique: true, nullable: true)]
    private ?string $email = null;

    /**
     * Nom de famille.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nom = null;

    /**
     * Prénom.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $prenom = null;

    /**
     * Type d'option Google Analytics depuis CFI.
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeOptionGA = null;

    /**
     * Division (tenant) de l'utilisateur.
     */
    #[ORM\ManyToOne(targetEntity: Division::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Division $division = null;

    /**
     * Divisions accessibles par l'utilisateur (système multi-tenant hiérarchique).
     *
     * Cette collection contient toutes les divisions auxquelles l'utilisateur
     * a accès selon la hiérarchie CFI, synchronisée depuis l'API CFI.
     *
     * @var Collection<int, UserAccessibleDivision>
     */
    #[ORM\OneToMany(
        targetEntity: UserAccessibleDivision::class,
        mappedBy: 'user',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $accessibleDivisions;

    /**
     * Thème de l'interface utilisateur (light|dark).
     */
    #[ORM\Column(length: 20, options: ['default' => 'light'])]
    private string $theme = 'light';

    /**
     * Locale de l'utilisateur (fr|en).
     */
    #[ORM\Column(length: 5, options: ['default' => 'fr'])]
    private string $locale = 'fr';

    /**
     * Préférences utilisateur myCfia (JSON).
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $preferences = null;

    /**
     * Permissions utilisateur CFI (JSON).
     *
     * Contient les 25 permissions + quota téléchargement HD récupérés depuis getDroitsUtilisateur.
     * Format : {connexion: bool, pwa: bool, administrateur: bool, ..., telechargementHD: double}
     *
     * @var array<string, bool|float>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $permissions = null;

    /**
     * Date de dernière connexion.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    /**
     * Nombre total de connexions.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $loginCount = 0;

    /**
     * Date de création (gérée automatiquement par Gedmo).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière mise à jour (gérée automatiquement par Gedmo).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->accessibleDivisions = new ArrayCollection();
    }

    // ========================================
    // UserInterface Implementation
    // ========================================

    public function getUserIdentifier(): string
    {
        // Utiliser email si disponible, sinon idCfi comme fallback
        return $this->email ?? (string) $this->idCfi;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Rien à effacer : pas de mot de passe stocké (CFI gère l'auth)
    }

    // ========================================
    // Business Methods
    // ========================================

    /**
     * Nom complet de l'utilisateur.
     */
    public function getFullName(): string
    {
        $parts = array_filter([$this->prenom, $this->nom]);

        return implode(' ', $parts) ?: ($this->email ?? 'User #'.$this->idCfi);
    }

    /**
     * Met à jour le tracking de connexion.
     * Note: updatedAt est géré automatiquement par Gedmo lors du flush.
     */
    public function updateLoginTracking(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        ++$this->loginCount;
    }

    // ========================================
    // Getters & Setters
    // ========================================

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getIdCfi(): int
    {
        return $this->idCfi;
    }

    public function setIdCfi(int $idCfi): static
    {
        $this->idCfi = $idCfi;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTypeOptionGA(): ?string
    {
        return $this->typeOptionGA;
    }

    public function setTypeOptionGA(?string $typeOptionGA): static
    {
        $this->typeOptionGA = $typeOptionGA;

        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): static
    {
        $this->division = $division;

        return $this;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPreferences(): ?array
    {
        return $this->preferences;
    }

    /**
     * @param array<string, mixed>|null $preferences
     */
    public function setPreferences(?array $preferences): static
    {
        $this->preferences = $preferences;

        return $this;
    }

    /**
     * @return array<string, bool|float>|null
     */
    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    /**
     * @param array<string, bool|float>|null $permissions
     */
    public function setPermissions(?array $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    /**
     * Vérifie si l'utilisateur possède une permission spécifique.
     *
     * @param string $permission Nom de la permission (ex: 'factures_Visu', 'administrateur')
     */
    public function hasPermission(string $permission): bool
    {
        return (bool) ($this->permissions[$permission] ?? false);
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getLoginCount(): int
    {
        return $this->loginCount;
    }

    public function setLoginCount(int $loginCount): static
    {
        $this->loginCount = $loginCount;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // ========================================
    // Multi-Tenant Methods
    // ========================================

    /**
     * Récupère les divisions accessibles à cet utilisateur.
     *
     * Retourne une Collection de Division (pas UserAccessibleDivision).
     * Utilisé pour afficher le sélecteur de divisions dans l'interface.
     *
     * @return Collection<int, Division>
     */
    public function getAccessibleDivisions(): Collection
    {
        return $this->accessibleDivisions->map(
            fn (UserAccessibleDivision $uad) => $uad->getDivision()
        );
    }

    /**
     * Récupère la collection brute UserAccessibleDivision.
     *
     * @return Collection<int, UserAccessibleDivision>
     */
    public function getAccessibleDivisionsRaw(): Collection
    {
        return $this->accessibleDivisions;
    }

    /**
     * Vérifie si l'utilisateur a accès à une division spécifique.
     *
     * @param int $idDivision ID CFI de la division
     */
    public function hasAccessToDivision(int $idDivision): bool
    {
        foreach ($this->accessibleDivisions as $uad) {
            if ($uad->getDivision()->getIdDivision() === $idDivision) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compte le nombre de divisions accessibles.
     */
    public function countAccessibleDivisions(): int
    {
        return $this->accessibleDivisions->count();
    }
}
