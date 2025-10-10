<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * User Provider Doctrine pour Symfony Security.
 *
 * Ce provider charge les utilisateurs depuis la base de données (entité User Doctrine)
 * au lieu de les créer à la volée depuis l'API CFI.
 *
 * @implements UserProviderInterface<User>
 */
readonly class DoctrineUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Charge un utilisateur par son identifiant (email).
     *
     * @param string $identifier Email de l'utilisateur
     *
     * @return User Utilisateur chargé depuis la BDD
     *
     * @throws UserNotFoundException Si l'utilisateur n'existe pas en BDD
     */
    public function loadUserByIdentifier(string $identifier): User
    {
        $user = $this->userRepository->findByEmail($identifier);

        if (! $user) {
            throw new UserNotFoundException(sprintf('Utilisateur avec email "%s" introuvable en base de données.', $identifier));
        }

        return $user;
    }

    /**
     * Refresh un utilisateur depuis la base de données.
     *
     * Utilisé par Symfony Security pour recharger les données de l'utilisateur
     * depuis la BDD à chaque requête (pour synchroniser les changements).
     *
     * @param UserInterface $user Utilisateur à rafraîchir
     *
     * @return User Utilisateur rechargé depuis la BDD
     */
    public function refreshUser(UserInterface $user): User
    {
        if (! $user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Expected instance of "%s", got "%s".', User::class, $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    /**
     * Indique si ce provider supporte la classe d'utilisateur donnée.
     *
     * @param string $class Nom de la classe à vérifier
     *
     * @return bool True si la classe est supportée
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
