/**
 * Campaign Form Component
 * Gestion des formulaires de création de campagne
 */

// Toggle asset card selection
export function toggleAssetCard(card) {
  card.classList.toggle("selected");
}

// Launch AI enrichment
export function launchAIEnrichment() {
  // Validate form
  const form = document.getElementById("campaignForm");
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  // Check if at least one asset is selected
  const selectedAssets = document.querySelectorAll(".asset-card.selected");
  if (selectedAssets.length === 0) {
    console.error("[campaign-form] Aucun canal de diffusion sélectionné");
    return;
  }

  // Redirect to loading page
  window.location.href = "step1_loading_light.html";
}

// Auto-select date (start = today, end = today + 90 days)
function initializeDates() {
  const today = new Date();
  const startDate = today.toISOString().split("T")[0];

  const endDate = new Date(today);
  endDate.setDate(endDate.getDate() + 90);
  const endDateStr = endDate.toISOString().split("T")[0];

  const startInput = document.querySelector(
    'input[type="date"]:nth-of-type(1)',
  );
  const endInput = document.querySelector('input[type="date"]:nth-of-type(2)');

  if (startInput) startInput.value = startDate;
  if (endInput) endInput.value = endDateStr;
}

/**
 * Auto-select radio button when focusing on associated input
 * Usage: <input data-auto-select-radio="radioId">
 */
function initAutoSelectRadio() {
  const inputs = document.querySelectorAll("[data-auto-select-radio]");

  inputs.forEach((input) => {
    const radioId = input.dataset.autoSelectRadio;
    const radio = document.getElementById(radioId);

    if (radio) {
      // Select radio on focus
      input.addEventListener("focus", () => {
        radio.checked = true;
      });

      // Select radio on input
      input.addEventListener("input", () => {
        radio.checked = true;
      });
    }
  });
}

// Initialize on DOM ready
function initCampaignForm() {
  initializeDates();
  initAutoSelectRadio();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initCampaignForm);
} else {
  initCampaignForm();
}

// TODO: Supprimer ces expositions globales lors de l'intégration Twig
// Les event handlers seront gérés via Stimulus controllers
window.toggleAssetCard = toggleAssetCard;
window.launchAIEnrichment = launchAIEnrichment;
