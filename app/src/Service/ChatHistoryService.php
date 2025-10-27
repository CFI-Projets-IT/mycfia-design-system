<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ChatConversation;
use App\Entity\Division;
use App\Entity\User;
use App\Repository\ChatConversationRepository;

/**
 * Service de gestion de l'historique des conversations chat.
 *
 * Fournit les opérations de récupération et manipulation des conversations
 * pour l'affichage dans la sidebar et la page dédiée historique.
 *
 * Fonctionnalités :
 * - Récupération des conversations récentes (top 5 pour sidebar)
 * - Récupération des conversations favorites (top 5 pour sidebar)
 * - Recherche de conversations avec filtres (date, texte)
 * - Chargement d'une conversation avec ses messages (optimisé avec JOIN)
 * - Comptage des conversations par utilisateur
 */
class ChatHistoryService
{
    public function __construct(
        private readonly ChatConversationRepository $conversationRepository,
    ) {
    }

    /**
     * Récupère les conversations récentes d'un utilisateur.
     *
     * Ordonnées par date de mise à jour décroissante (plus récentes en premier).
     * Utilisé pour la section "Historique" de la sidebar.
     *
     * @param User     $user   Utilisateur propriétaire
     * @param Division $tenant Division (isolation multi-tenant)
     * @param int      $limit  Nombre de conversations à récupérer (défaut: 5 pour sidebar)
     *
     * @return array<ChatConversation> Conversations récentes
     */
    public function getRecentConversations(User $user, Division $tenant, int $limit = 5): array
    {
        return $this->conversationRepository->findRecentByUser($user, $tenant, $limit);
    }

    /**
     * Récupère les conversations favorites d'un utilisateur.
     *
     * Ordonnées par date de mise à jour décroissante (plus récentes en premier).
     * Utilisé pour la section "Favoris" de la sidebar.
     *
     * @param User     $user   Utilisateur propriétaire
     * @param Division $tenant Division (isolation multi-tenant)
     * @param int      $limit  Nombre de conversations à récupérer (défaut: 5 pour sidebar)
     *
     * @return array<ChatConversation> Conversations favorites
     */
    public function getFavoriteConversations(User $user, Division $tenant, int $limit = 5): array
    {
        return $this->conversationRepository->findFavoritesByUser($user, $tenant, $limit);
    }

    /**
     * Recherche des conversations avec filtres.
     *
     * Permet de filtrer par :
     * - Texte de recherche (dans le titre)
     * - Date de début (conversations créées après cette date)
     * - Date de fin (conversations créées avant cette date)
     * - Favoris uniquement
     *
     * Utilisé pour la page dédiée historique (Phase 2).
     *
     * @param User                    $user          Utilisateur propriétaire
     * @param Division                $tenant        Division (isolation multi-tenant)
     * @param string                  $query         Texte de recherche dans le titre
     * @param \DateTimeInterface|null $dateDebut     Date de début du filtre
     * @param \DateTimeInterface|null $dateFin       Date de fin du filtre
     * @param bool|null               $favoritesOnly Filtrer sur favoris uniquement
     *
     * @return array<ChatConversation> Conversations correspondant aux filtres
     */
    public function searchConversations(
        User $user,
        Division $tenant,
        string $query,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        ?bool $favoritesOnly = null
    ): array {
        return $this->conversationRepository->findByUserAndTenantWithFilters(
            $user,
            $tenant,
            $query,
            $dateDebut,
            $dateFin,
            $favoritesOnly
        );
    }

    /**
     * Récupère une conversation avec tous ses messages.
     *
     * Utilise une jointure optimisée pour éviter le problème N+1.
     * Utilisé pour charger une conversation existante dans le chat.
     *
     * @param int $conversationId ID de la conversation
     *
     * @return ChatConversation|null Conversation avec messages, ou null si non trouvée
     */
    public function getConversationWithMessages(int $conversationId): ?ChatConversation
    {
        return $this->conversationRepository->findOneByIdWithMessages($conversationId);
    }

    /**
     * Compte le nombre total de conversations d'un utilisateur.
     *
     * Utilisé pour afficher des statistiques ou la pagination.
     *
     * @param User     $user   Utilisateur propriétaire
     * @param Division $tenant Division (isolation multi-tenant)
     *
     * @return int Nombre de conversations
     */
    public function countUserConversations(User $user, Division $tenant): int
    {
        return $this->conversationRepository->countByUser($user, $tenant);
    }

    /**
     * Récupère toutes les conversations d'un utilisateur.
     *
     * Sans limite, pour la page dédiée historique (Phase 2).
     *
     * @param User     $user   Utilisateur propriétaire
     * @param Division $tenant Division (isolation multi-tenant)
     *
     * @return array<ChatConversation> Toutes les conversations
     */
    public function getAllConversations(User $user, Division $tenant): array
    {
        return $this->conversationRepository->findByUserAndTenantWithFilters($user, $tenant);
    }

    /**
     * Récupère la dernière conversation d'un utilisateur pour un contexte spécifique.
     *
     * Utilisé pour charger automatiquement la dernière conversation lorsque
     * l'utilisateur accède à un contexte (factures, commandes, stocks, general).
     *
     * @param User   $user     Utilisateur propriétaire
     * @param int    $tenantId ID de la division (idDivision CFI)
     * @param string $context  Contexte du chat (factures|commandes|stocks|general)
     *
     * @return ChatConversation|null Dernière conversation ou null si aucune
     */
    public function getLatestConversationByContext(User $user, int $tenantId, string $context): ?ChatConversation
    {
        return $this->conversationRepository->findLatestByUserAndContext($user, $tenantId, $context);
    }
}
