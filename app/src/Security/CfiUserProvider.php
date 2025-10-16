<?php

declare(strict_types=1);

namespace App\Security;

use App\DTO\Cfi\UtilisateurGorilliasDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provider personnalisé pour charger les utilisateurs CFI.
 *
 * Charge les utilisateurs depuis l'API CFI (pas de table User locale).
 *
 * @implements UserProviderInterface<CfiUser>
 */
class CfiUserProvider implements UserProviderInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.auth')]
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Charge un utilisateur par son identifiant (email ou ID CFI).
     *
     * Note : Cette méthode n'est utilisée que pour refresh le user depuis la session.
     * L'authentification initiale passe par CfiAuthenticator.
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $this->logger->info('CFI UserProvider: Tentative de chargement utilisateur', [
            'identifier' => $identifier,
        ]);

        // Pour le refresh depuis session, on retourne un CfiUser minimal
        // Le vrai chargement se fait via CfiAuthenticator lors du login
        throw new UserNotFoundException($this->translator->trans('cfi.user.not_found', ['%identifier%' => $identifier], 'security'));
    }

    /**
     * Rafraîchit l'utilisateur depuis la session.
     *
     * Pour CFI : on ne recharge PAS depuis l'API à chaque requête (performance).
     * On fait confiance à la session Symfony + CfiSessionService pour le TTL.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (! $user instanceof CfiUser) {
            throw new \InvalidArgumentException(sprintf('Instance de %s attendue, %s reçu', CfiUser::class, $user::class));
        }

        $this->logger->debug('CFI UserProvider: Refresh utilisateur depuis session', [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
        ]);

        // Retourner le même user (session fait foi)
        // CfiSessionService gère l'expiration du token CFI
        return $user;
    }

    /**
     * Vérifie si ce provider supporte la classe User donnée.
     */
    public function supportsClass(string $class): bool
    {
        return CfiUser::class === $class || is_subclass_of($class, CfiUser::class);
    }

    /**
     * Crée un CfiUser depuis un DTO CFI.
     */
    public function createUserFromDto(UtilisateurGorilliasDto $dto): CfiUser
    {
        $user = new CfiUser(
            id: $dto->id,
            idDivision: $dto->idDivision,
            email: $dto->email,
            roles: ['ROLE_USER'] // Rôle de base, extensible selon type_d_option_GA
        );

        $user->setNomDivision($dto->nomDivision)
            ->setNom($dto->nom)
            ->setPrenom($dto->prenom)
            ->setTypeOptionGA($dto->type_d_option_GA)
            ->setJeton($dto->jeton);

        $this->logger->info('CFI UserProvider: Utilisateur créé depuis DTO', [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'division' => $user->getNomDivision(),
        ]);

        return $user;
    }
}
