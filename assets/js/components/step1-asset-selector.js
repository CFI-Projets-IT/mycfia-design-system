/**
 * Asset Selector for Step 1 Create Campaign
 * Gestion de la sélection des canaux de diffusion
 */

/**
 * Toggle la sélection d'une asset card
 * @param {HTMLElement} card - L'élément asset-card cliqué
 */
function toggleAssetCard(card) {
    card.classList.toggle('active');

    const assetType = card.dataset.asset;
    const isSelected = card.classList.contains('active');

    console.log(`[asset-selector] Asset ${assetType} ${isSelected ? 'sélectionné' : 'désélectionné'}`);
}

/**
 * Initialise le sélecteur d'assets
 */
export function initAssetSelector() {
    const assetCards = document.querySelectorAll('.asset-card');

    if (assetCards.length === 0) {
        console.warn('[asset-selector] Aucune asset-card trouvée');
        return;
    }

    // Attacher les event listeners
    assetCards.forEach(card => {
        card.addEventListener('click', () => {
            toggleAssetCard(card);
        });
    });

    console.log(`[asset-selector] ${assetCards.length} asset-cards initialisées`);
}

/**
 * Récupère les assets sélectionnés
 * @returns {Array<string>} Liste des types d'assets sélectionnés
 */
export function getSelectedAssets() {
    const selectedCards = document.querySelectorAll('.asset-card.active');
    return Array.from(selectedCards).map(card => card.dataset.asset);
}
