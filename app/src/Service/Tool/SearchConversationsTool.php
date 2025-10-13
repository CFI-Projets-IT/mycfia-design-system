<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\Entity\User;
use App\Repository\ConversationRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Tool IA pour rechercher dans l'historique des conversations avec l'IA.
 *
 * Permet de retrouver des conversations passées par mots-clés dans le titre.
 *
 * NOTE: L'attribut #[AsTool] sera ajouté une fois Symfony AI Bundle configuré (Étape 6)
 * Pour l'instant, le tool est enregistré manuellement dans services.yaml
 */
final readonly class SearchConversationsTool
{
    public function __construct(
        private ConversationRepository $conversationRepository,
        private Security $security,
    ) {
    }

    /**
     * Recherche dans l'historique des conversations.
     *
     * @param string $query Mots-clés à rechercher
     * @param int    $limit Nombre maximum de résultats (1-20)
     *
     * @return array<int|string, mixed>
     */
    public function __invoke(
        string $query,
        int $limit = 5
    ): array {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return ['error' => 'User not authenticated'];
        }

        $conversations = $this->conversationRepository->searchByUser($user, $query, $limit);

        return array_map(fn ($conversation) => [
            'id' => $conversation->getId()->toString(),
            'title' => $conversation->getTitle(),
            'slug' => $conversation->getSlug(),
            'last_activity' => $conversation->getUpdatedAt()->format('Y-m-d H:i:s'),
        ], $conversations);
    }
}
