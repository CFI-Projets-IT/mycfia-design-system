/**
 * Step 5 Validate - Asset Detail Modal Logic
 * Charge les données des assets et affiche le modal de détail
 * Gère la navigation diaporama entre assets
 */

let assetsData = null;
let assetIds = [];
let currentAssetIndex = -1;

/**
 * Charge les données des assets depuis le fichier data
 * @returns {Promise<Object>} Les données des assets
 */
async function loadAssetsData() {
  if (assetsData) {
    return assetsData;
  }

  try {
    // Import dynamique du module de données
    const module = await import("../../data/step5-assets-data.js");
    assetsData = module.assetsData;
    console.log("[step5-validate-data] Données assets chargées avec succès");
    return assetsData;
  } catch (error) {
    console.error(
      "[step5-validate-data] Erreur lors du chargement des données:",
      error,
    );
    return {};
  }
}

/**
 * Initialise la liste des asset IDs depuis le DOM
 */
function initAssetIds() {
  if (assetIds.length === 0) {
    const rows = document.querySelectorAll(".asset-row[data-asset-id]");
    assetIds = Array.from(rows).map((row) => row.dataset.assetId);
    console.log(
      `[step5-validate-data] ${assetIds.length} assets trouvés pour navigation`,
    );
  }
}

/**
 * Met à jour l'état des boutons de navigation
 */
function updateNavigationButtons() {
  const prevBtn = document.getElementById("modalNavPrev");
  const nextBtn = document.getElementById("modalNavNext");
  const counter = document.getElementById("modalNavCounter");

  if (prevBtn) {
    prevBtn.disabled = currentAssetIndex <= 0;
  }
  if (nextBtn) {
    nextBtn.disabled = currentAssetIndex >= assetIds.length - 1;
  }
  if (counter) {
    counter.textContent = `${currentAssetIndex + 1} / ${assetIds.length}`;
  }
}

/**
 * Navigue vers l'asset précédent
 */
export async function navigateToPrevious() {
  if (currentAssetIndex > 0) {
    currentAssetIndex--;
    await loadAssetContent(assetIds[currentAssetIndex]);
    updateNavigationButtons();
  }
}

/**
 * Navigue vers l'asset suivant
 */
export async function navigateToNext() {
  if (currentAssetIndex < assetIds.length - 1) {
    currentAssetIndex++;
    await loadAssetContent(assetIds[currentAssetIndex]);
    updateNavigationButtons();
  }
}

/**
 * Charge le contenu d'un asset dans le modal (sans ouvrir le modal)
 * @param {string} assetId - L'identifiant de l'asset
 */
async function loadAssetContent(assetId) {
  const data = await loadAssetsData();
  const asset = data[assetId];

  if (!asset) {
    document.getElementById("modalAssetType").innerHTML = "Asset non configuré";
    document.getElementById("modalAssetVariation").textContent = "";
    document.getElementById("reasoningContent").innerHTML =
      '<p class="text-secondary">Les détails de reasoning pour cet asset seront disponibles prochainement.</p>';
    document.getElementById("kpisContent").innerHTML = "";
    document.getElementById("previewContent").innerHTML =
      '<p class="text-secondary">Preview non disponible</p>';
  } else {
    document.getElementById("modalAssetType").innerHTML = asset.type;
    document.getElementById("modalAssetVariation").textContent =
      `Variation ${asset.variation}`;

    let reasoningHtml = "";
    asset.reasoning.forEach((section) => {
      reasoningHtml += `
                <div class="reasoning-section">
                    <h6 class="reasoning-title">
                        <i class="bi ${section.icon}"></i> ${section.title}
                    </h6>
                    <p class="reasoning-content">
                        ${section.content}
                    </p>
                </div>
            `;
    });
    document.getElementById("reasoningContent").innerHTML = reasoningHtml;

    let kpisHtml = "";
    asset.kpis.forEach((kpi) => {
      kpisHtml += `
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-label">
                                <i class="bi ${kpi.icon} text-${kpi.color}"></i> ${kpi.label}
                            </div>
                            <div class="kpi-value">
                                ${kpi.value}
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-${kpi.color} kpi-badge-small">
                                ${kpi.color === "success" ? "Excellent" : kpi.color === "primary" ? "Bon" : "Moyen"}
                            </span>
                        </div>
                    </div>
                </div>
            `;
    });
    document.getElementById("kpisContent").innerHTML = kpisHtml;

    document.getElementById("previewContent").innerHTML = asset.preview;
  }
}

/**
 * Ouvre le modal avec les détails de l'asset
 * @param {string} assetId - L'identifiant de l'asset (ex: 'linkedin_1', 'google_1')
 */
export async function openAssetDetail(assetId) {
  // Initialiser la liste des assets si nécessaire
  initAssetIds();

  // Trouver l'index de l'asset actuel
  currentAssetIndex = assetIds.indexOf(assetId);

  // Charger les données si nécessaire
  const data = await loadAssetsData();
  const asset = data[assetId];

  if (!asset) {
    // Pour les assets non définis, afficher un message générique
    document.getElementById("modalAssetType").innerHTML = "Asset non configuré";
    document.getElementById("modalAssetVariation").textContent = "";
    document.getElementById("reasoningContent").innerHTML =
      '<p class="text-secondary">Les détails de reasoning pour cet asset seront disponibles prochainement.</p>';
    document.getElementById("kpisContent").innerHTML = "";
    document.getElementById("previewContent").innerHTML =
      '<p class="text-secondary">Preview non disponible</p>';
  } else {
    // Remplir le modal header
    document.getElementById("modalAssetType").innerHTML = asset.type;
    document.getElementById("modalAssetVariation").textContent =
      `Variation ${asset.variation}`;

    // Remplir le reasoning
    let reasoningHtml = "";
    asset.reasoning.forEach((section) => {
      reasoningHtml += `
                <div class="reasoning-section">
                    <h6 class="reasoning-title">
                        <i class="bi ${section.icon}"></i> ${section.title}
                    </h6>
                    <p class="reasoning-content">
                        ${section.content}
                    </p>
                </div>
            `;
    });
    document.getElementById("reasoningContent").innerHTML = reasoningHtml;

    // Remplir les KPIs
    let kpisHtml = "";
    asset.kpis.forEach((kpi) => {
      kpisHtml += `
                <div class="kpi-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="kpi-label">
                                <i class="bi ${kpi.icon} text-${kpi.color}"></i> ${kpi.label}
                            </div>
                            <div class="kpi-value">
                                ${kpi.value}
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-${kpi.color} kpi-badge-small">
                                ${kpi.color === "success" ? "Excellent" : kpi.color === "primary" ? "Bon" : "Moyen"}
                            </span>
                        </div>
                    </div>
                </div>
            `;
    });
    document.getElementById("kpisContent").innerHTML = kpisHtml;

    // Remplir la preview
    document.getElementById("previewContent").innerHTML = asset.preview;
  }

  // Mettre à jour les boutons de navigation
  updateNavigationButtons();

  // Ouvrir le modal
  const modal = new bootstrap.Modal(
    document.getElementById("assetDetailModal"),
  );
  modal.show();
}
