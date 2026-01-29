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
import { initAssetSelector as initStep8AssetSelector } from "./components/step8-asset-selector.js";
import { initStep8Loading } from "./components/step8-loading-config.js";
import { initAssetValidation } from "./components/step8-validate-ui.js";
import { initCampaignAssetsPlanning } from "./components/campaign-assets-planning.js";
import { initOnboardingDAP } from "./components/onboarding-dap.js";
import { initOnboardingDAPStep1 } from "./components/onboarding-dap-step1.js";
import { initOnboardingDAPStep1Review } from "./components/onboarding-dap-step1-review.js";
import { initOnboardingDAPStep2 } from "./components/onboarding-dap-step2.js";
import { initOnboardingDAPStep3 } from "./components/onboarding-dap-step3.js";
import { initOnboardingDAPStep4 } from "./components/onboarding-dap-step4.js";
import { initOnboardingDAPStep5 } from "./components/onboarding-dap-step5.js";
import { initOnboardingDAPStep6 } from "./components/onboarding-dap-step6.js";
import { initOnboardingDAPStep6Errors } from "./components/onboarding-dap-step6-errors.js";
import { initOnboardingDAPStep6Suggestions } from "./components/onboarding-dap-step6-suggestions.js";
import { initOnboardingDAPStep6Mapping } from "./components/onboarding-dap-step6-mapping.js";
import { initVideoDemo } from "./components/video-demo.js";
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

    // 10. Initialiser le sélecteur d'assets Step 8 (si présent)
    if (document.querySelector(".asset-card-modern")) {
      console.log("[main] Initialisation du sélecteur d'assets Step 8...");
      initStep8AssetSelector();
    }

    // 11. Initialiser le loading Step 8 (si présent)
    if (
      document.querySelector(".loader-container") &&
      document.title.includes("Génération Assets")
    ) {
      console.log("[main] Initialisation du loading Step 8...");
      initStep8Loading();
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

    // 14. Initialiser l'onboarding DAP (si dashboard uniquement)
    if (
      window.location.pathname.includes("dashboard") ||
      document.getElementById("onboardingWelcomeModal")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP...");
      initOnboardingDAP();
    }

    // 15. Initialiser le lecteur vidéo démo (si modal présent)
    if (document.getElementById("videoDemoModal")) {
      console.log("[main] Initialisation du lecteur vidéo démo...");
      initVideoDemo();
    }

    // 16. Initialiser l'onboarding DAP Step 1 (si step1_create)
    if (
      window.location.pathname.includes("step1_create") ||
      document.getElementById("onboardingWelcomeModalStep1")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 1...");
      initOnboardingDAPStep1();
    }

    // 17. Initialiser l'onboarding DAP Step 1 Review (si step1_review)
    if (
      window.location.pathname.includes("step1_review") ||
      document.getElementById("onboardingWelcomeModalStep1Review")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 1 Review...");
      initOnboardingDAPStep1Review();
    }

    // 18. Initialiser l'onboarding DAP Step 2 (si step2_validate)
    if (
      window.location.pathname.includes("step2_validate") ||
      document.getElementById("onboardingWelcomeModalStep2")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 2...");
      initOnboardingDAPStep2();
    }

    // 19. Initialiser l'onboarding DAP Step 3 (si step3_select)
    if (
      window.location.pathname.includes("step3_select") ||
      document.getElementById("onboardingWelcomeModalStep3")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 3...");
      initOnboardingDAPStep3();
    }

    // 20. Initialiser l'onboarding DAP Step 4 (si step4_result)
    if (
      window.location.pathname.includes("step4_result") ||
      document.getElementById("onboardingWelcomeModalStep4")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 4...");
      initOnboardingDAPStep4();
    }

    // 21. Initialiser l'onboarding DAP Step 5 (si step5_select)
    if (
      window.location.pathname.includes("step5_select") ||
      document.getElementById("onboardingWelcomeModalStep5")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 5...");
      initOnboardingDAPStep5();
    }

    // 22. Initialiser l'onboarding DAP Step 6 (si step6_upload)
    if (
      window.location.pathname.includes("step6_upload") ||
      document.getElementById("onboardingWelcomeModalStep6")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 6...");
      initOnboardingDAPStep6();
    }

    // 23. Initialiser l'onboarding DAP Step 6 Errors (si step6_upload_errors)
    if (
      window.location.pathname.includes("step6_upload_errors") ||
      document.getElementById("onboardingWelcomeModalStep6Errors")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 6 Errors...");
      initOnboardingDAPStep6Errors();
    }

    // 24. Initialiser l'onboarding DAP Step 6 Suggestions (si step6_upload_suggestions)
    if (
      window.location.pathname.includes("step6_upload_suggestions") ||
      document.getElementById("onboardingWelcomeModalStep6Suggestions")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 6 Suggestions...");
      initOnboardingDAPStep6Suggestions();
    }

    // 25. Initialiser l'onboarding DAP Step 6 Mapping (si step6_upload_mapping)
    if (
      window.location.pathname.includes("step6_upload_mapping") ||
      document.getElementById("onboardingWelcomeModalStep6Mapping")
    ) {
      console.log("[main] Initialisation de l'onboarding DAP Step 6 Mapping...");
      initOnboardingDAPStep6Mapping();
    }

    console.log("===========================================");
    console.log("myCFiA Design System - Prêt ✓");
    console.log("===========================================");
  } catch (error) {
    console.error("[main] Erreur lors de l'initialisation:", error);
  }
});
