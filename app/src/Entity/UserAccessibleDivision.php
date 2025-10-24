<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserAccessibleDivisionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant les divisions accessibles par un utilisateur dans le système multi-tenant.
 *
 * Cette table pivot stocke les relations Many-to-Many entre User et Division
 * avec un timestamp de synchronisation depuis l'API CFI.
 *
 * Architecture :
 * - Un utilisateur peut avoir accès à plusieurs divisions (hiérarchie)
 * - Les divisions sont synchronisées depuis l'API CFI au login
 * - Le timestamp permet de détecter les données obsolètes (mode dégradé)
 */
#[ORM\Entity(repositoryClass: UserAccessibleDivisionRepository::class)]
#[ORM\Table(name: 'user_accessible_divisions')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_accessible_divisions_user')]
#[ORM\Index(columns: ['synced_at'], name: 'idx_user_accessible_divisions_synced')]
#[ORM\UniqueConstraint(name: 'unique_user_division', columns: ['user_id', 'division_id'])]
class UserAccessibleDivision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    /**
     * Utilisateur ayant accès à la division.
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'accessibleDivisions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Division accessible par l'utilisateur.
     */
    #[ORM\ManyToOne(targetEntity: Division::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Division $division;

    /**
     * Date de dernière synchronisation depuis l'API CFI.
     *
     * Utilisé pour détecter les données obsolètes et activer le mode dégradé.
     * Seuil recommandé : 24h (données considérées obsolètes après).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $syncedAt;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDivision(): Division
    {
        return $this->division;
    }

    public function setDivision(Division $division): static
    {
        $this->division = $division;

        return $this;
    }

    public function getSyncedAt(): \DateTimeImmutable
    {
        return $this->syncedAt;
    }

    public function setSyncedAt(\DateTimeImmutable $syncedAt): static
    {
        $this->syncedAt = $syncedAt;

        return $this;
    }

    /**
     * Vérifie si la synchronisation est récente (< maxAgeHours).
     */
    public function isSyncRecent(int $maxAgeHours = 24): bool
    {
        $threshold = new \DateTimeImmutable("-{$maxAgeHours} hours");

        return $this->syncedAt >= $threshold;
    }
}
