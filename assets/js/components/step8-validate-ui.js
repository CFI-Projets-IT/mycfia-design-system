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

import {
  initAssetEditor,
  resetEditor,
  setCurrentAsset,
} from "./asset-editor.js";

// TODO: Supprimer ces expositions globales lors de l'intégration Twig
// Les event handlers seront gérés via Stimulus controllers
window.openAssetDetail = openAssetDetail;
window.navigateToPrevious = navigateToPrevious;
window.navigateToNext = navigateToNext;

/**
 * Initialise les event listeners pour la validation des assets
 */
export function initAssetValidation() {
  // Initialiser l'éditeur d'assets
  initAssetEditor();

  // Event delegation sur les lignes de tableau pour ouvrir le détail
  const assetRows = document.querySelectorAll(".asset-row[data-asset-id]");

  assetRows.forEach((row) => {
    row.addEventListener("click", (e) => {
      const assetId = row.dataset.assetId;

      // Réinitialiser l'éditeur avant d'ouvrir un nouvel asset
      resetEditor();

      // Définir l'asset courant pour l'éditeur
      setCurrentAsset(assetId);

      // Appeler la fonction openAssetDetail importée du module
      openAssetDetail(assetId);
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
