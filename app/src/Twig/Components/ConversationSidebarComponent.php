<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\ChatConversation;
use App\Entity\User;
use App\Service\Cfi\CfiTenantService;
use App\Service\ChatHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Composant Twig pour afficher la sidebar des conversations chat.
 *
 * Affiche soit les conversations favorites, soit l'historique récent
 * selon la section spécifiée. Utilisé dans le layout home.html.twig.
 */
#[AsTwigComponent('ConversationSidebar')]
final class ConversationSidebarComponent
{
    /**
     * Section à afficher ('favorites' ou 'history').
     */
    public string $section = 'history';

    /**
     * Nombre de conversations à afficher (défaut: 5 pour sidebar).
     */
    public int $limit = 5;

    public function __construct(
        private readonly ChatHistoryService $historyService,
        private readonly Security $security,
        private readonly CfiTenantService $tenantService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Récupère les conversations à afficher selon la section.
     *
     * @return array<ChatConversation> Conversations (favoris ou historique)
     */
    public function getConversations(): array
    {
        $user = $this->security->getUser();
        if (! $user instanceof User) {
            return [];
        }

        $tenantId = $this->tenantService->getCurrentTenantOrNull();
        if (null === $tenantId) {
            return [];
        }

        // Récupérer l'entité Division depuis idDivision CFI (pas l'ID local)
        $tenant = $this->entityManager->getRepository(\App\Entity\Division::class)->findOneBy([
            'idDivision' => $tenantId,
        ]);
        if (null === $tenant) {
            return [];
        }

        return match ($this->section) {
            'favorites' => $this->historyService->getFavoriteConversations($user, $tenant, $this->limit),
            'history' => $this->historyService->getRecentConversations($user, $tenant, $this->limit),
            default => [],
        };
    }

    /**
     * Retourne le titre de la section.
     */
    public function getSectionTitle(): string
    {
        return match ($this->section) {
            'favorites' => 'Favoris',
            'history' => 'Historique',
            default => 'Conversations',
        };
    }

    /**
     * Retourne l'icône de la section.
     */
    public function getSectionIcon(): string
    {
        return match ($this->section) {
            'favorites' => 'bi-star-fill',
            'history' => 'bi-clock-history',
            default => 'bi-chat-dots',
        };
    }

    /**
     * Vérifie si la section est vide.
     */
    public function isEmpty(): bool
    {
        return empty($this->getConversations());
    }
}
