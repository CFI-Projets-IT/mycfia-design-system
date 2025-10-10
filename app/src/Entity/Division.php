<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DivisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: DivisionRepository::class)]
#[ORM\Table(name: 'division')]
class Division
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    /**
     * ID Division depuis CFI (idDivision).
     */
    #[ORM\Column(type: Types::INTEGER, unique: true)]
    private int $idDivision;

    /**
     * Nom de la division depuis CFI (ex: "Caisse d'Epargne IDF").
     */
    #[ORM\Column(length: 255)]
    private string $nomDivision;

    /**
     * Slug URL-friendly pour la division (généré automatiquement depuis nomDivision).
     */
    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['nomDivision'])]
    private string $slug;

    /**
     * Paramètres JSON personnalisés de la division.
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $settings = null;

    /**
     * Division active ou désactivée.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

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

    /**
     * Utilisateurs de cette division.
     *
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'division', orphanRemoval: true)]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getIdDivision(): int
    {
        return $this->idDivision;
    }

    public function setIdDivision(int $idDivision): static
    {
        $this->idDivision = $idDivision;

        return $this;
    }

    public function getNomDivision(): string
    {
        return $this->nomDivision;
    }

    public function setNomDivision(string $nomDivision): static
    {
        $this->nomDivision = $nomDivision;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }

    /**
     * @param array<string, mixed>|null $settings
     */
    public function setSettings(?array $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (! $this->users->contains($user)) {
            $this->users->add($user);
            $user->setDivision($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getDivision() === $this) {
                $user->setDivision(null);
            }
        }

        return $this;
    }
}
