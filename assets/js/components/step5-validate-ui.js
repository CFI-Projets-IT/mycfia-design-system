/**
 * Step 5 Validate UI - Asset Validation Interactive Logic
 * Gère l'interaction utilisateur sur la page de validation des assets
 */

import { openAssetDetail } from './step5-validate-data.js';

// TODO: Supprimer cette exposition globale lors de l'intégration Twig
// Les event handlers seront gérés via Stimulus controllers
window.openAssetDetail = openAssetDetail;

/**
 * Initialise les event listeners pour la validation des assets
 */
export function initAssetValidation() {
    // Event delegation sur les lignes de tableau pour ouvrir le détail
    const assetRows = document.querySelectorAll('.asset-row[data-asset-id]');

    assetRows.forEach(row => {
        row.addEventListener('click', (e) => {
            // Ne pas ouvrir le modal si on clique sur le checkbox
            if (e.target.closest('.form-check-input')) {
                e.stopPropagation();
                return;
            }

            const assetId = row.dataset.assetId;

            // Appeler la fonction openAssetDetail importée du module
            openAssetDetail(assetId);
        });
    });

    // Event listener sur les checkboxes pour empêcher la propagation
    const checkboxes = document.querySelectorAll('.asset-row .form-check-input');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    });
}
