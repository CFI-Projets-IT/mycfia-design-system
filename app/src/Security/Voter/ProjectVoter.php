<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour gérer les permissions sur les projets marketing.
 *
 * Règles :
 * - VIEW : L'utilisateur doit être propriétaire du projet ou du même tenant
 * - EDIT : L'utilisateur doit être propriétaire du projet
 * - DELETE : L'utilisateur doit être propriétaire du projet
 *
 * @extends Voter<string, Project>
 */
final class ProjectVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // On supporte les attributs VIEW, EDIT, DELETE sur les objets Project
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // L'utilisateur doit être authentifié
        if (! $user instanceof User) {
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($project, $user),
            self::EDIT => $this->canEdit($project, $user),
            self::DELETE => $this->canDelete($project, $user),
            default => false,
        };
    }

    /**
     * Vérifie si l'utilisateur peut voir le projet.
     *
     * Règle : L'utilisateur doit appartenir au même tenant que le projet
     */
    private function canView(Project $project, User $user): bool
    {
        $userDivision = $user->getDivision();
        if (null === $userDivision) {
            return false;
        }

        // L'utilisateur peut voir si c'est le même tenant
        return $project->getTenant()->getIdDivision() === $userDivision->getIdDivision();
    }

    /**
     * Vérifie si l'utilisateur peut éditer le projet.
     *
     * Règle : L'utilisateur doit être le propriétaire du projet
     */
    private function canEdit(Project $project, User $user): bool
    {
        // L'utilisateur peut éditer s'il est propriétaire
        return $project->getUser()->getId() === $user->getId();
    }

    /**
     * Vérifie si l'utilisateur peut supprimer le projet.
     *
     * Règle : L'utilisateur doit être le propriétaire du projet
     */
    private function canDelete(Project $project, User $user): bool
    {
        // L'utilisateur peut supprimer s'il est propriétaire
        return $project->getUser()->getId() === $user->getId();
    }
}
