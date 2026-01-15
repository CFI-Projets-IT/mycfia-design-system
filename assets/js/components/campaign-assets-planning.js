/**
 * Campaign Assets Planning
 * Gère l'affichage des sections de planification dans la modal
 * selon le statut de l'asset
 */

// Données de planification simulées pour la démo
const planningData = {
  email_2: { date: "Mercredi 22 Janvier 2025", time: "10:00" },
  sms_2: { date: "Jeudi 23 Janvier 2025", time: "14:30" },
};

/**
 * Met à jour l'affichage de la section planification selon l'asset
 * @param {string} assetId - L'identifiant de l'asset
 */
export function updatePlanningSection(assetId) {
  const scheduledSection = document.getElementById("planningSectionScheduled");
  const notScheduledSection = document.getElementById(
    "planningSectionNotScheduled",
  );
  const planningDateEl = document.getElementById("planningDate");
  const planningTimeEl = document.getElementById("planningTime");

  if (!scheduledSection || !notScheduledSection) return;

  // Vérifier si l'asset a une planification
  const planning = planningData[assetId];

  if (planning) {
    // Afficher la version planifiée
    scheduledSection.classList.remove("d-none");
    notScheduledSection.classList.add("d-none");

    // Mettre à jour les données
    if (planningDateEl) planningDateEl.textContent = planning.date;
    if (planningTimeEl) planningTimeEl.textContent = planning.time;
  } else {
    // Afficher la version non planifiée
    scheduledSection.classList.add("d-none");
    notScheduledSection.classList.remove("d-none");
  }
}

/**
 * Initialise les event listeners pour la planification
 */
export function initCampaignAssetsPlanning() {
  // Observer les clics sur les lignes d'assets pour mettre à jour la planification
  const assetRows = document.querySelectorAll(".asset-row[data-asset-id]");

  assetRows.forEach((row) => {
    row.addEventListener("click", () => {
      const assetId = row.dataset.assetId;
      // Petit délai pour laisser la modal s'ouvrir
      setTimeout(() => {
        updatePlanningSection(assetId);
      }, 100);
    });
  });

  console.log("[campaign-assets-planning] Initialisé");
}
