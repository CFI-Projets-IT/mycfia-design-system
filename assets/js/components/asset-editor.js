/**
 * Asset Editor - Gestion de l'édition des assets marketing
 * Permet à l'utilisateur de modifier le contenu des assets et de les enrichir avec l'IA
 */

// État de l'éditeur
let isEditMode = false;
let originalContent = "";
let currentAssetId = null;

/**
 * Initialise l'éditeur d'assets
 */
export function initAssetEditor() {
  const btnStartEdit = document.getElementById("btnStartEdit");
  const btnSaveEdit = document.getElementById("btnSaveEdit");
  const btnEnrichAI = document.getElementById("btnEnrichAI");
  const btnCancelEdit = document.getElementById("btnCancelEdit");

  if (btnStartEdit) {
    btnStartEdit.addEventListener("click", startEditMode);
  }
  if (btnSaveEdit) {
    btnSaveEdit.addEventListener("click", saveEdits);
  }
  if (btnEnrichAI) {
    btnEnrichAI.addEventListener("click", enrichWithAI);
  }
  if (btnCancelEdit) {
    btnCancelEdit.addEventListener("click", cancelEdits);
  }

  // Écouter l'événement d'ouverture de la modal pour s'assurer que le contenu est non-éditable
  const assetModal = document.getElementById("assetDetailModal");
  if (assetModal) {
    assetModal.addEventListener("shown.bs.modal", () => {
      // S'assurer que le contenu est NON-éditable au démarrage
      const previewContent = document.getElementById("previewContent");
      if (previewContent && !isEditMode) {
        disableAllEditing();
      }
    });
  }
}

/**
 * Désactive complètement toute édition dans le preview
 */
function disableAllEditing() {
  const previewContent = document.getElementById("previewContent");
  if (previewContent) {
    // Retirer contenteditable du conteneur principal
    previewContent.removeAttribute("contenteditable");
    previewContent.classList.remove("editable", "edit-mode");

    // Retirer contenteditable de tous les éléments enfants
    const allElements = previewContent.querySelectorAll("*");
    allElements.forEach((el) => {
      el.removeAttribute("contenteditable");
      el.classList.remove("editable");
    });
  }
}

/**
 * Active le mode édition
 */
function startEditMode() {
  isEditMode = true;

  // Sauvegarder le contenu original
  const previewContent = document.getElementById("previewContent");
  if (previewContent) {
    originalContent = previewContent.innerHTML;

    // Activer l'édition du contenu
    enableContentEditing(previewContent);
  }

  // Toggle des toolbars
  toggleToolbars(true);

  // Ajouter une classe pour styling en mode édition
  previewContent?.classList.add("edit-mode");
}

/**
 * Sauvegarde les modifications
 */
function saveEdits() {
  const previewContent = document.getElementById("previewContent");

  if (previewContent) {
    // Ici, dans la vraie app, on enverrait les données au backend
    console.log("💾 Sauvegarde des modifications...");
    console.log("Asset ID:", currentAssetId);
    console.log("Nouveau contenu:", previewContent.innerHTML);

    // Désactiver l'édition
    disableContentEditing(previewContent);

    // Mettre à jour le contenu original
    originalContent = previewContent.innerHTML;
  }

  // Retour au mode lecture
  exitEditMode();

  // Feedback visuel (toast notification)
  showNotification("✅ Modifications enregistrées avec succès", "success");
}

/**
 * Enrichit le contenu avec l'IA
 */
function enrichWithAI() {
  const previewContent = document.getElementById("previewContent");

  if (previewContent) {
    // Simuler l'appel API à l'IA
    console.log("🤖 Enrichissement avec l'IA...");
    console.log("Asset ID:", currentAssetId);
    console.log("Contenu original:", originalContent);
    console.log("Contenu modifié:", previewContent.innerHTML);

    // Feedback visuel
    showNotification("🤖 Enrichissement IA en cours...", "info");

    // Simuler un délai d'appel API
    setTimeout(() => {
      // Dans la vraie app, on recevrait le contenu enrichi du backend
      // Ici, on simule juste un ajout
      const enrichedContent = previewContent.innerHTML + " [✨ Enrichi par l'IA]";
      previewContent.innerHTML = enrichedContent;

      showNotification("✨ Contenu enrichi avec succès", "success");
    }, 1500);
  }
}

/**
 * Annule les modifications et revient au contenu original
 */
function cancelEdits() {
  const previewContent = document.getElementById("previewContent");

  if (previewContent) {
    // Restaurer le contenu original
    previewContent.innerHTML = originalContent;

    // Désactiver l'édition
    disableContentEditing(previewContent);
  }

  // Retour au mode lecture
  exitEditMode();

  // Feedback visuel
  showNotification("❌ Modifications annulées", "warning");
}

/**
 * Quitte le mode édition
 */
function exitEditMode() {
  isEditMode = false;

  // Toggle des toolbars
  toggleToolbars(false);

  // Retirer la classe edit-mode
  const previewContent = document.getElementById("previewContent");
  previewContent?.classList.remove("edit-mode");
}

/**
 * Toggle entre les toolbars lecture/édition
 * @param {boolean} editMode - true pour mode édition, false pour mode lecture
 */
function toggleToolbars(editMode) {
  const toolbarRead = document.getElementById("editToolbarRead");
  const toolbarEdit = document.getElementById("editToolbarEdit");

  if (editMode) {
    toolbarRead?.classList.add("d-none");
    toolbarEdit?.classList.remove("d-none");
  } else {
    toolbarRead?.classList.remove("d-none");
    toolbarEdit?.classList.add("d-none");
  }
}

/**
 * Active l'édition du contenu
 * @param {HTMLElement} element - L'élément à rendre éditable
 */
function enableContentEditing(element) {
  // Trouver tous les éléments de texte dans le preview (paragraphes, titres, etc.)
  const editableElements = element.querySelectorAll("p, h1, h2, h3, h4, h5, h6, span:not(.badge), div.text-content");

  if (editableElements.length > 0) {
    // Rendre éditables uniquement les éléments de texte trouvés
    editableElements.forEach((el) => {
      // Ignorer les badges et autres éléments non pertinents
      if (!el.closest('.badge') && !el.classList.contains('badge')) {
        el.setAttribute("contenteditable", "true");
        el.classList.add("editable");
      }
    });
  } else {
    // Fallback : rendre tout le conteneur éditable SEULEMENT si aucun élément n'est trouvé
    element.setAttribute("contenteditable", "true");
    element.classList.add("editable");
  }
}

/**
 * Désactive l'édition du contenu
 * @param {HTMLElement} element - L'élément à rendre non-éditable
 */
function disableContentEditing(element) {
  const editableElements = element.querySelectorAll("[contenteditable='true']");

  editableElements.forEach((el) => {
    el.removeAttribute("contenteditable");
    el.classList.remove("editable");
  });
}

/**
 * Affiche une notification toast
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type de notification (success, info, warning, danger)
 */
function showNotification(message, type = "info") {
  // Dans la vraie app, on utiliserait un système de toast (Bootstrap Toast, Toastify, etc.)
  console.log(`[${type.toUpperCase()}] ${message}`);

  // Simuler un toast simple pour le mockup
  const toast = document.createElement("div");
  toast.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
  toast.style.zIndex = "9999";
  toast.textContent = message;

  document.body.appendChild(toast);

  // Retirer le toast après 3 secondes
  setTimeout(() => {
    toast.remove();
  }, 3000);
}

/**
 * Définit l'asset actuellement en cours d'édition
 * @param {string} assetId - L'ID de l'asset
 */
export function setCurrentAsset(assetId) {
  currentAssetId = assetId;
}

/**
 * Réinitialise l'éditeur lors de l'ouverture d'un nouvel asset
 */
export function resetEditor() {
  isEditMode = false;
  originalContent = "";
  toggleToolbars(false);

  const previewContent = document.getElementById("previewContent");
  if (previewContent) {
    // S'assurer que tout le contenu est NON-éditable
    disableContentEditing(previewContent);
    previewContent.classList.remove("edit-mode");

    // Double sécurité : retirer contenteditable du conteneur principal
    previewContent.removeAttribute("contenteditable");

    // Retirer contenteditable de tous les enfants (au cas où)
    const allElements = previewContent.querySelectorAll("*");
    allElements.forEach((el) => {
      el.removeAttribute("contenteditable");
      el.classList.remove("editable");
    });
  }
}
