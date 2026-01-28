/**
 * Step 8 Validate UI - Asset Validation Interactive Logic
 * Gère l'interaction utilisateur sur la page de validation des assets
 * Gère la navigation diaporama entre assets
 */

import {
  openAssetDetail,
  navigateToPrevious,
  navigateToNext,
} from "./step8-validate-data.js";

// TODO: Supprimer ces expositions globales lors de l'intégration Twig
// Les event handlers seront gérés via Stimulus controllers
window.openAssetDetail = openAssetDetail;
window.navigateToPrevious = navigateToPrevious;
window.navigateToNext = navigateToNext;

/**
 * Initialise les event listeners pour la validation des assets
 */
export function initAssetValidation() {
  // Event delegation sur les lignes de tableau pour ouvrir le détail
  const assetRows = document.querySelectorAll(".asset-row[data-asset-id]");

  assetRows.forEach((row) => {
    row.addEventListener("click", (e) => {
      // Ne pas ouvrir le modal si on clique sur le checkbox
      if (e.target.closest(".form-check-input")) {
        e.stopPropagation();
        return;
      }

      const assetId = row.dataset.assetId;

      // Appeler la fonction openAssetDetail importée du module
      openAssetDetail(assetId);
    });
  });

  // Event listener sur les checkboxes pour empêcher la propagation
  const checkboxes = document.querySelectorAll(".asset-row .form-check-input");
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener("click", (e) => {
      e.stopPropagation();
    });
  });

  // Event listeners pour la navigation diaporama
  const prevBtn = document.getElementById("modalNavPrev");
  const nextBtn = document.getElementById("modalNavNext");

  if (prevBtn) {
    prevBtn.addEventListener("click", navigateToPrevious);
  }
  if (nextBtn) {
    nextBtn.addEventListener("click", navigateToNext);
  }

  // Navigation clavier dans le modal
  document.addEventListener("keydown", (e) => {
    const modal = document.getElementById("assetDetailModal");
    if (modal && modal.classList.contains("show")) {
      if (e.key === "ArrowLeft") {
        e.preventDefault();
        navigateToPrevious();
      } else if (e.key === "ArrowRight") {
        e.preventDefault();
        navigateToNext();
      }
    }
  });
}
