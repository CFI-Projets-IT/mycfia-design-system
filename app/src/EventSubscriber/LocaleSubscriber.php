<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Applique automatiquement la locale de l'utilisateur connecté à chaque requête.
 *
 * Ce subscriber intercepte chaque requête et applique la locale préférée
 * de l'utilisateur connecté à la requête Symfony, permettant ainsi
 * l'affichage automatique de l'interface dans la langue de l'utilisateur.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly LoggerInterface $logger,
        private readonly string $defaultLocale = 'fr',
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Ne traiter que la requête principale (pas les sous-requêtes)
        if (! $event->isMainRequest()) {
            return;
        }

        // Si une locale est déjà définie dans la route, la conserver
        if ($request->attributes->get('_locale')) {
            return;
        }

        // Récupérer l'utilisateur connecté
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            $this->logger->debug('LocaleSubscriber: No token, setting default locale', ['locale' => $this->defaultLocale]);
            $request->setLocale($this->defaultLocale);

            return;
        }

        $user = $token->getUser();

        // Appliquer la locale de l'utilisateur ou la locale par défaut
        if ($user instanceof User) {
            $userLocale = $user->getLocale();
            $this->logger->info('LocaleSubscriber: Setting user locale', [
                'userId' => $user->getId(),
                'locale' => $userLocale,
                'uri' => $request->getRequestUri(),
            ]);
            $request->setLocale($userLocale);
        } else {
            $this->logger->debug('LocaleSubscriber: User not instance of User, setting default locale', ['locale' => $this->defaultLocale]);
            $request->setLocale($this->defaultLocale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité haute (20) pour s'exécuter avant le LocaleListener de Symfony
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
