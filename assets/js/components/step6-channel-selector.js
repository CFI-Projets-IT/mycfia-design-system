/**
 * Channel Selector for Step 6
 * Gestion de la sélection des canaux de diffusion
 */

/**
 * Toggle la sélection d'une channel card
 * @param {HTMLElement} card - L'élément asset-card cliqué
 */
function toggleChannelCard(card) {
    card.classList.toggle('selected');
    updateSelectedCount();

    const channelName = card.querySelector('.asset-card-title')?.textContent.trim() || 'Unknown';
    const isSelected = card.classList.contains('selected');

    console.log(`[channel-selector] Canal "${channelName}" ${isSelected ? 'sélectionné' : 'désélectionné'}`);
}

/**
 * Met à jour le compteur de canaux sélectionnés
 */
function updateSelectedCount() {
    const countElement = document.getElementById('selectedAssetsCount');
    if (!countElement) return;

    const selectedChannels = document.querySelectorAll('.asset-card.selected');
    countElement.textContent = selectedChannels.length;

    console.log(`[channel-selector] ${selectedChannels.length} canal(aux) sélectionné(s)`);
}

/**
 * Initialise le sélecteur de canaux
 */
export function initChannelSelector() {
    const channelCards = document.querySelectorAll('.asset-card[data-asset]');

    if (channelCards.length === 0) {
        console.warn('[channel-selector] Aucune asset-card trouvée');
        return;
    }

    // Attacher les event listeners aux cartes
    channelCards.forEach(card => {
        card.addEventListener('click', () => {
            toggleChannelCard(card);
        });
    });

    // Mettre à jour le compteur initial
    updateSelectedCount();

    console.log(`[channel-selector] ${channelCards.length} channel-cards initialisées`);
}

/**
 * Récupère les canaux sélectionnés
 * @returns {Array<string>} Liste des identifiants de canaux sélectionnés
 */
export function getSelectedChannels() {
    const selectedCards = document.querySelectorAll('.asset-card.selected');
    return Array.from(selectedCards).map(card => {
        return card.dataset.asset || 'unknown';
    });
}
