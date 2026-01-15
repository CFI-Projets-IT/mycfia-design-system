/**
 * Point d'entrée principal du JavaScript myCFiA Design System
 * Initialise tous les modules et composants
 */

import {
  initSidebarToggle,
  restoreSidebarState,
} from "./core/sidebar-toggle.js";
import { initThemeSwitcher } from "./core/theme-switcher.js";
import { initThemeCards } from "./components/theme-cards.js";
import { initButtonAnimations } from "./components/button-animations.js";
import { initAnalyticsCharts } from "./components/analytics-charts.js";
import { initAssetSelector } from "./components/step1-asset-selector.js";
import { initPersonaSelector } from "./components/step3-persona-selector.js";
import { initCompetitorSelector } from "./components/step2-competitor-selector.js";
import { initAssetSelector as initStep5AssetSelector } from "./components/step5-asset-selector.js";
import { initStep5Loading } from "./components/step5-loading-config.js";
import { initAssetValidation } from "./components/step5-validate-ui.js";
import { initCampaignAssetsPlanning } from "./components/campaign-assets-planning.js";
import "./components/campaign-stepper.js";
import "./components/campaign-form.js";
import "./components/campaign-loader.js";

/**
 * Initialisation au chargement du DOM
 */
document.addEventListener("DOMContentLoaded", () => {
  console.log("===========================================");
  console.log("myCFiA Design System - Initialisation");
  console.log("===========================================");

  try {
    // 1. Initialiser le système de thème
    console.log("[main] Initialisation du theme switcher...");
    initThemeSwitcher();

    // 2. Restaurer l'état du sidebar
    console.log("[main] Restauration de l'état de la sidebar...");
    restoreSidebarState();

    // 3. Initialiser le toggle de la sidebar
    console.log("[main] Initialisation du toggle sidebar...");
    initSidebarToggle();

    // 4. Initialiser les cartes de thème (si présentes)
    console.log("[main] Initialisation des theme cards...");
    initThemeCards();

    // 5. Initialiser les animations de boutons
    console.log("[main] Initialisation des animations de boutons...");
    initButtonAnimations();

    // 6. Initialiser les graphiques analytics (si présents)
    if (document.getElementById("channelChart")) {
      console.log("[main] Initialisation des graphiques analytics...");
      // Attendre que Chart.js soit chargé depuis le CDN
      if (typeof Chart !== "undefined") {
        initAnalyticsCharts();
      } else {
        console.warn(
          "[main] Chart.js non chargé, graphiques analytics non initialisés",
        );
      }
    }

    // 7. Initialiser le sélecteur d'assets (si présent ET pas de personas/competitors/channels)
    if (
      document.querySelector(".asset-card") &&
      !document.querySelector(".persona-card") &&
      !document.querySelector(".competitor-card") &&
      !document.getElementById("selectedAssetsCount")
    ) {
      console.log("[main] Initialisation du sélecteur d'assets...");
      initAssetSelector();
    }

    // 8. Initialiser le sélecteur de personas (si présent)
    if (document.querySelector(".persona-card")) {
      console.log("[main] Initialisation du sélecteur de personas...");
      initPersonaSelector();
    }

    // 9. Initialiser le sélecteur de concurrents (si présent sur step2_validate, mais pas sur personas)
    if (
      document.getElementById("selectedCount") &&
      document.querySelector(".asset-card") &&
      !document.querySelector(".persona-card")
    ) {
      console.log("[main] Initialisation du sélecteur de concurrents...");
      initCompetitorSelector();
    }

    // 10. Initialiser le sélecteur d'assets Step 5 (si présent)
    if (document.querySelector(".asset-card-modern")) {
      console.log("[main] Initialisation du sélecteur d'assets Step 5...");
      initStep5AssetSelector();
    }

    // 11. Initialiser le loading Step 5 (si présent)
    if (
      document.querySelector(".loader-container") &&
      document.title.includes("Génération Assets")
    ) {
      console.log("[main] Initialisation du loading Step 5...");
      initStep5Loading();
    }

    // 12. Initialiser la validation des assets Step 7 (si présent)
    if (document.querySelector(".asset-row[data-asset-id]")) {
      console.log(
        "[main] Initialisation de la validation des assets Step 7...",
      );
      initAssetValidation();
    }

    // 13. Initialiser la gestion de planification (page campaign_assets uniquement)
    if (document.getElementById("planningSectionScheduled")) {
      console.log(
        "[main] Initialisation de la planification campaign_assets...",
      );
      initCampaignAssetsPlanning();
    }

    console.log("===========================================");
    console.log("myCFiA Design System - Prêt ✓");
    console.log("===========================================");
  } catch (error) {
    console.error("[main] Erreur lors de l'initialisation:", error);
  }
});
