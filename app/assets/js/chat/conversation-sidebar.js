/**
 * Gestion des interactions de la sidebar des conversations chat.
 *
 * Responsabilités :
 * - Toggle favori (AJAX POST)
 * - Suppression de conversation (AJAX DELETE avec confirmation)
 * - Rechargement dynamique de la sidebar après actions
 *
 * Architecture : Vanilla JavaScript (ES6+)
 */

// ====================================
// 1. Gestion du toggle favori
// ====================================

/**
 * Toggle le statut favori d'une conversation.
 *
 * @param {number} conversationId - ID de la conversation
 * @param {string} favoriteUrl - URL de l'endpoint toggle favori
 * @param {HTMLElement} iconElement - Élément icône à mettre à jour
 */
async function toggleConversationFavorite(conversationId, favoriteUrl, iconElement) {
    try {
        const response = await fetch(favoriteUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        // Mettre à jour l'icône immédiatement pour le feedback visuel
        if (data.isFavorite) {
            iconElement.classList.remove('bi-star');
            iconElement.classList.add('bi-star-fill', 'text-warning');
        } else {
            iconElement.classList.remove('bi-star-fill', 'text-warning');
            iconElement.classList.add('bi-star');
        }

        // Mettre à jour le dataset chatData si on est sur la page de cette conversation
        const chatData = document.getElementById('chatData');
        if (chatData && chatData.dataset.loadedConversation === conversationId.toString()) {
            chatData.dataset.isFavorite = data.isFavorite ? '1' : '0';

            // Mettre à jour aussi l'icône du bouton dans la navigation
            const navFavoriteBtn = document.querySelector('#favoriteButtonContainer [data-action="toggle-favorite"]');
            if (navFavoriteBtn) {
                const navIcon = navFavoriteBtn.querySelector('i');
                if (navIcon) {
                    if (data.isFavorite) {
                        navIcon.classList.remove('bi-star');
                        navIcon.classList.add('bi-star-fill', 'text-warning');
                    } else {
                        navIcon.classList.remove('bi-star-fill', 'text-warning');
                        navIcon.classList.add('bi-star');
                    }
                }
                navFavoriteBtn.title = data.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
            }
        }

        console.log('[ConversationSidebar] Toggle favori:', data.message);

        // Recharger les Turbo Frames de la sidebar (favoris + historique)
        const favoritesFrame = document.getElementById('sidebar-favorites');
        const historyFrame = document.getElementById('sidebar-history');

        if (favoritesFrame) {
            favoritesFrame.reload();
        }

        if (historyFrame) {
            historyFrame.reload();
        }
    } catch (error) {
        console.error('[ConversationSidebar] Erreur toggle favori:', error);
        alert('Erreur lors de la modification du statut favori.');
    }
}

// ====================================
// 2. Gestion de la suppression
// ====================================

/**
 * Supprimer une conversation après confirmation.
 *
 * @param {number} conversationId - ID de la conversation
 * @param {string} deleteUrl - URL de l'endpoint de suppression
 * @param {string} conversationTitle - Titre de la conversation (pour confirmation)
 */
async function deleteConversation(conversationId, deleteUrl, conversationTitle) {
    // Confirmation utilisateur
    const confirmed = confirm(
        `Êtes-vous sûr de vouloir supprimer cette conversation ?\n\n"${conversationTitle}"\n\nCette action est irréversible.`
    );

    if (!confirmed) {
        return;
    }

    try {
        const response = await fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        console.log('[ConversationSidebar] Conversation supprimée:', data.message);

        // Rediriger vers le chat général
        window.location.href = '/chat/general';
    } catch (error) {
        console.error('[ConversationSidebar] Erreur suppression:', error);
        alert('Erreur lors de la suppression de la conversation.');
    }
}

// ====================================
// 3. Initialisation des événements
// ====================================

// Flag pour éviter les multiples initialisations
let sidebarEventsInitialized = false;

function initConversationSidebarEvents() {
    // Éviter les multiples attachements d'événements
    if (sidebarEventsInitialized) {
        return;
    }
    sidebarEventsInitialized = true;

    // Délégation d'événements sur le conteneur de la sidebar
    // Les éléments peuvent être ajoutés dynamiquement par Turbo

    document.addEventListener('click', (e) => {
        // Toggle favori
        const favoriteBtn = e.target.closest('[data-action="toggle-favorite"]');
        if (favoriteBtn) {
            e.preventDefault();
            e.stopPropagation(); // Empêcher la propagation pour éviter les doubles clics

            const conversationId = parseInt(favoriteBtn.dataset.conversationId, 10);
            const favoriteUrl = favoriteBtn.dataset.favoriteUrl;
            const iconElement = favoriteBtn.querySelector('i');

            toggleConversationFavorite(conversationId, favoriteUrl, iconElement);
            return;
        }

        // Suppression
        const deleteBtn = e.target.closest('[data-action="delete-conversation"]');
        if (deleteBtn) {
            e.preventDefault();
            e.stopPropagation(); // Empêcher la propagation pour éviter les doubles clics

            const conversationId = parseInt(deleteBtn.dataset.conversationId, 10);
            const deleteUrl = deleteBtn.dataset.deleteUrl;
            const conversationTitle = deleteBtn.dataset.conversationTitle || 'Sans titre';

            deleteConversation(conversationId, deleteUrl, conversationTitle);
            return;
        }
    });
}

// Écouter les événements de chargement (DOMContentLoaded + Turbo)
document.addEventListener('DOMContentLoaded', initConversationSidebarEvents);
document.addEventListener('turbo:load', initConversationSidebarEvents);

// Export pour usage en module (si nécessaire)
export { toggleConversationFavorite, deleteConversation };
