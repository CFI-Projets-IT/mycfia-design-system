/**
 * myCFiA Design System - Digital Adoption Platform (DAP) Onboarding - Step 2 Validate
 *
 * Système d'onboarding guidé pour la page de validation des concurrents (Step 2)
 * - Welcome modal (première visite)
 * - Tour guidé avec spotlight et tooltips
 * - Help button pour relancer le tour
 * - Persistence via LocalStorage
 *
 * @module OnboardingDAPStep2
 * @requires Bootstrap 5 (pour modal et tooltips)
 */

/**
 * Configuration des étapes du tour guidé Step 2 Validate
 * @const {Array<Object>}
 */
const ONBOARDING_STEPS_STEP2 = [
    {
        id: "welcome",
        type: "modal",
        target: null,
    },
    {
        id: "stepper-progress",
        type: "hotspot",
        target: ".campaign-stepper",
        title: "Suivez votre progression",
        content:
            "Vous êtes à l'étape 2 sur 8 du processus de création de campagne. Le stepper vous aide à visualiser où vous en êtes.",
        icon: "bi-signpost",
    },
    {
        id: "competitors-detected",
        type: "hotspot",
        target: ".form-section-header",
        title: "Nombre de concurrents sélectionnés",
        content:
            "Ce compteur affiche le nombre de concurrents que vous avez sélectionnés. L'IA en a détecté 7, vous pouvez en sélectionner autant que nécessaire pour votre stratégie.",
        icon: "bi-building",
    },
    {
        id: "direct-competitors",
        type: "hotspot",
        target: ".row.g-4 .col-12:first-child",
        title: "Concurrents directs et importants",
        content:
            "Ces concurrents ont un score d'alignement élevé avec votre offre et votre marché. L'overlap offre et marché vous aide à identifier vos vrais concurrents.",
        icon: "bi-exclamation-triangle-fill",
    },
    {
        id: "competitor-metrics",
        type: "hotspot",
        target: ".competitor-score",
        title: "Scores d'alignement concurrentiel",
        content:
            "Le score d'alignement combine l'overlap de votre offre et de votre marché cible. Un score élevé (>80%) indique un concurrent direct à surveiller de près.",
        icon: "bi-bar-chart-line",
    },
    {
        id: "indirect-competitors-section",
        type: "hotspot",
        target: ".mt-5.mb-4 .competitor-section-header",
        title: "Concurrents indirects",
        content:
            "Ces concurrents ont un score d'alignement plus faible mais restent pertinents à surveiller. Ils peuvent cibler un marché adjacent ou une offre complémentaire.",
        icon: "bi-info-circle-fill",
    },
    {
        id: "add-competitor-manually",
        type: "hotspot",
        target: ".card.border-0.shadow-sm",
        title: "Ajoutez vos propres concurrents",
        content:
            "L'IA n'a pas trouvé un concurrent que vous connaissez ? Ajoutez-le manuellement avec son nom, URL et type (direct ou indirect).",
        icon: "bi-plus-circle-fill",
    },
    {
        id: "speed-dial-fab",
        type: "hotspot",
        target: ".speed-dial-container",
        title: "Validez ou régénérez l'analyse",
        content:
            "Utilisez la boîte à outils pour valider et continuer vers l'étape 3 (Personas), ou régénérer l'analyse concurrentielle si nécessaire.",
        icon: "bi-three-dots-vertical",
    },
];

/**
 * LocalStorage keys utilisés
 * @const {Object}
 */
const STORAGE_KEYS_STEP2 = {
    COMPLETED: "mycfia_onboarding_step2_completed",
    STEP: "mycfia_onboarding_step2_step",
    SKIPPED_AT: "mycfia_onboarding_step2_skipped_at",
    TOOLTIP_DISMISSED: "mycfia_onboarding_step2_tooltip_dismissed",
};

/**
 * Classe principale gérant l'onboarding DAP Step 2
 * @class OnboardingDAPStep2
 */
class OnboardingDAPStep2 {
    /**
     * Initialise l'instance OnboardingDAPStep2
     * @constructor
     */
    constructor() {
        this.currentStep = 0;
        this.steps = ONBOARDING_STEPS_STEP2;
        this.overlay = null;
        this.spotlight = null;
        this.tooltip = null;
        this.helpButton = null;
        this.isActive = false;
        this._speedDialOpen = false;
    }

    /**
     * Initialise l'onboarding (point d'entrée principal)
     * - Vérifie si première visite
     * - Affiche modal si nécessaire
     * - Crée le help button
     */
    init() {
        console.log("[OnboardingDAPStep2] Initialisation...");

        // Vérifier si l'utilisateur a déjà complété l'onboarding
        const hasCompleted = this.getFromStorage(STORAGE_KEYS_STEP2.COMPLETED);

        if (!hasCompleted) {
            // Première visite : afficher modal après 1 seconde
            setTimeout(() => {
                this.showWelcomeModal();
            }, 1000);
        }

        // Toujours créer le help button pour permettre de relancer le tour
        this.createHelpButton();

        // Initialiser les tooltips Bootstrap
        this.initTooltips();

        console.log("[OnboardingDAPStep2] Initialisé avec succès");
    }

    /**
     * Affiche le modal de bienvenue (bootstrap modal)
     * Gère les événements des boutons "Passer" et "Faire le tour guidé"
     */
    showWelcomeModal() {
        const modalEl = document.getElementById("onboardingWelcomeModalStep2");
        if (!modalEl) {
            console.warn(
                "[OnboardingDAPStep2] Modal #onboardingWelcomeModalStep2 introuvable"
            );
            return;
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Bouton "Passer"
        const skipBtn = modalEl.querySelector('[data-bs-dismiss="modal"]');
        if (skipBtn) {
            skipBtn.addEventListener("click", () => {
                this.skipTour("welcome_modal");
            });
        }

        // Bouton "Faire le tour guidé"
        const startBtn = document.getElementById("startOnboardingTourStep2");
        if (startBtn) {
            startBtn.addEventListener("click", () => {
                modal.hide();
                // Attendre que le modal soit complètement fermé
                setTimeout(() => {
                    this.startTour();
                }, 300);
            });
        }
    }

    /**
     * Démarre le tour guidé
     * - Crée l'overlay
     * - Affiche la première étape (après welcome)
     */
    startTour() {
        console.log("[OnboardingDAPStep2] Démarrage du tour guidé");

        this.isActive = true;
        this.currentStep = 1; // Commencer après le modal (step 0 = welcome)

        // Créer l'overlay
        this.createOverlay();

        // Afficher la première étape
        this.showStep(this.currentStep);
    }

    /**
     * Crée l'overlay sombre avec spotlight
     */
    createOverlay() {
        // Créer l'overlay si n'existe pas déjà
        if (!this.overlay) {
            this.overlay = document.createElement("div");
            this.overlay.className = "onboarding-overlay";
            document.body.appendChild(this.overlay);
        }

        // Activer l'overlay
        setTimeout(() => {
            this.overlay.classList.add("active");
        }, 10);

        // Créer le spotlight
        if (!this.spotlight) {
            this.spotlight = document.createElement("div");
            this.spotlight.className = "onboarding-spotlight";
            document.body.appendChild(this.spotlight);
        }
    }

    /**
     * Affiche une étape spécifique du tour
     * @param {number} stepIndex - Index de l'étape à afficher
     */
    async showStep(stepIndex) {
        if (stepIndex >= this.steps.length) {
            this.completeTour();
            return;
        }

        const step = this.steps[stepIndex];

        // Ignorer l'étape welcome (gérée par modal)
        if (step.type === "modal") {
            this.showStep(stepIndex + 1);
            return;
        }

        // Trouver l'élément cible
        const targetEl = document.querySelector(step.target);
        if (!targetEl) {
            console.warn(
                `[OnboardingDAPStep2] Élément cible "${step.target}" introuvable, passage à l'étape suivante`
            );
            this.showStep(stepIndex + 1);
            return;
        }

        // Scroll automatique vers l'élément si pas suffisamment visible
        const rect = targetEl.getBoundingClientRect();

        // Vérifier si l'élément est suffisamment visible (au moins 80% dans le viewport)
        const visibleHeight = Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, 0);
        const visibleWidth = Math.min(rect.right, window.innerWidth) - Math.max(rect.left, 0);
        const elementHeight = rect.height;
        const elementWidth = rect.width;

        const isVisible = (
            visibleHeight >= elementHeight * 0.8 &&
            visibleWidth >= elementWidth * 0.8 &&
            rect.top >= 0 &&
            rect.bottom <= window.innerHeight
        );

        if (!isVisible) {
            // Débloquer temporairement le scroll pour permettre scrollIntoView
            const wasBlocked = document.body.classList.contains('onboarding-active');
            if (wasBlocked) {
                document.body.classList.remove('onboarding-active');
                document.body.style.top = '';
                if (this._scrollPosition !== undefined) {
                    window.scrollTo(0, this._scrollPosition);
                }
            }

            targetEl.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'center'
            });

            // Attendre que le scroll se termine avant de positionner
            await new Promise(resolve => setTimeout(resolve, 500));

            // Rebloquer le scroll
            if (wasBlocked) {
                this._scrollPosition = window.scrollY;
                document.body.classList.add('onboarding-active');
                document.body.style.top = `-${this._scrollPosition}px`;
            }
        }

        // Ouvrir le Speed Dial FAB si on arrive à cette étape
        if (step.id === "speed-dial-fab") {
            this.openSpeedDial();
        }

        // Positionner le spotlight
        this.positionSpotlight(targetEl);

        // Créer et afficher le tooltip
        this.createTooltip(step, targetEl);

        // Sauvegarder la progression
        this.saveToStorage(STORAGE_KEYS_STEP2.STEP, stepIndex);
    }

    /**
     * Positionne le spotlight sur l'élément cible
     * @param {HTMLElement} targetEl - Élément à mettre en valeur
     */
    positionSpotlight(targetEl) {
        const rect = targetEl.getBoundingClientRect();
        const padding = 8;

        // Si le body est bloqué, utiliser _scrollPosition au lieu de window.scrollY
        const scrollY = document.body.classList.contains('onboarding-active')
            ? (this._scrollPosition || 0)
            : window.scrollY;

        this.spotlight.style.top = `${rect.top - padding + scrollY}px`;
        this.spotlight.style.left = `${rect.left - padding + window.scrollX}px`;
        this.spotlight.style.width = `${rect.width + padding * 2}px`;
        this.spotlight.style.height = `${rect.height + padding * 2}px`;

        // Ajouter animation pulse
        this.spotlight.classList.add("pulse");
    }

    /**
     * Crée et affiche le tooltip contextuel
     * @param {Object} step - Configuration de l'étape
     * @param {HTMLElement} targetEl - Élément cible
     */
    createTooltip(step, targetEl) {
        // Supprimer tooltip existant
        if (this.tooltip) {
            this.tooltip.remove();
        }

        // Créer le tooltip
        this.tooltip = document.createElement("div");
        this.tooltip.className = "onboarding-tooltip";

        // Calculer le nombre d'étapes (sans compter le modal welcome)
        const totalSteps = this.steps.filter((s) => s.type !== "modal").length;
        const currentStepNum = this.currentStep; // Car on a déjà sauté le welcome

        // HTML du tooltip
        this.tooltip.innerHTML = `
            <div class="onboarding-tooltip-header">
                <div class="onboarding-tooltip-icon">
                    <i class="${step.icon}"></i>
                </div>
                <h5 class="onboarding-tooltip-title">${step.title}</h5>
            </div>
            <div class="onboarding-tooltip-content">
                <p>${step.content}</p>
            </div>
            <div class="onboarding-tooltip-footer">
                <div class="onboarding-progress">
                    ${this.renderProgressDots(currentStepNum, totalSteps)}
                </div>
                <div class="onboarding-tooltip-actions">
                    <button class="onboarding-btn onboarding-btn-secondary" data-action="skip">
                        Passer le tour
                    </button>
                    <button class="onboarding-btn onboarding-btn-primary" data-action="next">
                        ${currentStepNum === totalSteps ? "Terminer" : "Suivant"}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(this.tooltip);

        // Positionner le tooltip
        this.positionTooltip(targetEl);

        // Activer l'affichage avec transition
        setTimeout(() => {
            this.tooltip.classList.add("active");
        }, 100);

        // Gérer les événements des boutons
        this.tooltip
            .querySelector('[data-action="skip"]')
            .addEventListener("click", () => {
                this.skipTour("during_tour");
            });

        this.tooltip
            .querySelector('[data-action="next"]')
            .addEventListener("click", () => {
                this.nextStep();
            });
    }

    /**
     * Génère les dots de progression
     * @param {number} current - Étape actuelle
     * @param {number} total - Nombre total d'étapes
     * @returns {string} HTML des dots
     */
    renderProgressDots(current, total) {
        let dotsHTML = "";
        for (let i = 1; i <= total; i++) {
            let className = "onboarding-progress-dot";
            if (i < current) className += " completed";
            if (i === current) className += " active";
            dotsHTML += `<span class="${className}"></span>`;
        }
        return dotsHTML;
    }

    /**
     * Positionne le tooltip par rapport à l'élément cible
     * @param {HTMLElement} targetEl - Élément cible
     */
    positionTooltip(targetEl) {
        const targetRect = targetEl.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const padding = 16;
        const arrowSize = 8;

        // Si le body est bloqué, utiliser _scrollPosition au lieu de window.scrollY
        const scrollY = document.body.classList.contains('onboarding-active')
            ? (this._scrollPosition || 0)
            : window.scrollY;

        let position = "bottom"; // Position par défaut
        let top, left;

        // Calculer l'espace disponible dans chaque direction
        const spaceAbove = targetRect.top;
        const spaceBelow = window.innerHeight - targetRect.bottom;
        const spaceLeft = targetRect.left;
        const spaceRight = window.innerWidth - targetRect.right;

        // Déterminer la meilleure position
        if (spaceBelow >= tooltipRect.height + padding) {
            position = "bottom";
            top = targetRect.bottom + padding + scrollY;
            left = targetRect.left + targetRect.width / 2 - tooltipRect.width / 2 + window.scrollX;
        } else if (spaceAbove >= tooltipRect.height + padding) {
            position = "top";
            top = targetRect.top - tooltipRect.height - padding + scrollY;
            left = targetRect.left + targetRect.width / 2 - tooltipRect.width / 2 + window.scrollX;
        } else if (spaceRight >= tooltipRect.width + padding) {
            position = "right";
            top = targetRect.top + targetRect.height / 2 - tooltipRect.height / 2 + scrollY;
            left = targetRect.right + padding + window.scrollX;
        } else if (spaceLeft >= tooltipRect.width + padding) {
            position = "left";
            top = targetRect.top + targetRect.height / 2 - tooltipRect.height / 2 + scrollY;
            left = targetRect.left - tooltipRect.width - padding + window.scrollX;
        } else {
            // Si pas assez de place nulle part, positionner en bas centré
            position = "bottom";
            top = targetRect.bottom + padding + scrollY;
            left = window.innerWidth / 2 - tooltipRect.width / 2 + window.scrollX;
        }

        // S'assurer que le tooltip reste dans le viewport horizontalement
        if (left < padding) left = padding;
        if (left + tooltipRect.width > window.innerWidth - padding) {
            left = window.innerWidth - tooltipRect.width - padding;
        }

        // Appliquer la position
        this.tooltip.style.top = `${top}px`;
        this.tooltip.style.left = `${left}px`;
        this.tooltip.classList.add(`position-${position}`);
    }

    /**
     * Passe à l'étape suivante
     */
    nextStep() {
        this.currentStep++;
        this.showStep(this.currentStep);
    }

    /**
     * Saute le tour et nettoie
     * @param {string} reason - Raison du skip ("welcome_modal" ou "during_tour")
     */
    skipTour(reason) {
        console.log(`[OnboardingDAPStep2] Tour sauté : ${reason}`);

        this.saveToStorage(STORAGE_KEYS_STEP2.COMPLETED, true);
        this.saveToStorage(STORAGE_KEYS_STEP2.SKIPPED_AT, Date.now());

        this.cleanup();
    }

    /**
     * Termine le tour avec succès
     */
    completeTour() {
        console.log("[OnboardingDAPStep2] Tour terminé avec succès");

        this.saveToStorage(STORAGE_KEYS_STEP2.COMPLETED, true);

        // Fermer le Speed Dial si ouvert
        this.closeSpeedDial();

        // Afficher message de félicitation
        this.showCompletionMessage();

        // Nettoyer
        this.cleanup();
    }

    /**
     * Affiche un message de félicitation (toast)
     */
    showCompletionMessage() {
        // Créer le toast
        const toast = document.createElement("div");
        toast.className = "onboarding-completion-toast";
        toast.innerHTML = `
            <div class="onboarding-completion-toast-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="onboarding-completion-toast-content">
                <h6>Tour guidé terminé !</h6>
                <p>Vous êtes maintenant prêt à valider vos concurrents et à passer à l'étape suivante.</p>
            </div>
        `;

        document.body.appendChild(toast);

        // Afficher après un court délai
        setTimeout(() => {
            toast.classList.add("show");
        }, 100);

        // Retirer après 5 secondes
        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    }

    /**
     * Nettoie le DOM (overlay, spotlight, tooltip)
     */
    cleanup() {
        console.log("[OnboardingDAPStep2] Nettoyage...");

        this.isActive = false;

        if (this.overlay) {
            this.overlay.classList.remove("active");
            setTimeout(() => {
                this.overlay?.remove();
                this.overlay = null;
            }, 300);
        }

        if (this.spotlight) {
            this.spotlight.remove();
            this.spotlight = null;
        }

        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
    }

    /**
     * Crée le bouton d'aide (Help Button)
     */
    createHelpButton() {
        const existingBtn = document.getElementById("helpButtonInlineStep2");
        if (existingBtn) {
            this.helpButton = existingBtn;
            this.helpButton.addEventListener("click", () => {
                this.restartTour();
            });
        }
    }

    /**
     * Relance le tour guidé (depuis help button)
     */
    restartTour() {
        console.log("[OnboardingDAPStep2] Relance du tour guidé");

        // Réinitialiser le stockage
        this.saveToStorage(STORAGE_KEYS_STEP2.COMPLETED, false);
        this.saveToStorage(STORAGE_KEYS_STEP2.STEP, 0);

        // Démarrer le tour
        this.startTour();
    }

    /**
     * Ouvre le Speed Dial FAB
     */
    openSpeedDial() {
        const speedDialMain = document.querySelector(".speed-dial-main");
        const speedDialContainer = document.querySelector(".speed-dial-container");

        if (speedDialContainer && !speedDialContainer.classList.contains("open")) {
            speedDialContainer.classList.add("open");
            this._speedDialOpen = true;
        }
    }

    /**
     * Ferme le Speed Dial FAB
     */
    closeSpeedDial() {
        const speedDialContainer = document.querySelector(".speed-dial-container");

        if (speedDialContainer && this._speedDialOpen) {
            speedDialContainer.classList.remove("open");
            this._speedDialOpen = false;
        }
    }

    /**
     * Initialise les tooltips Bootstrap (si présents)
     */
    initTooltips() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        if (tooltipTriggerList.length > 0 && typeof bootstrap !== "undefined") {
            [...tooltipTriggerList].map(
                (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
            );
        }
    }

    /**
     * Sauvegarde une valeur dans le LocalStorage
     * @param {string} key - Clé de stockage
     * @param {*} value - Valeur à sauvegarder
     */
    saveToStorage(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.warn(`[OnboardingDAPStep2] Erreur sauvegarde LocalStorage:`, e);
        }
    }

    /**
     * Récupère une valeur du LocalStorage
     * @param {string} key - Clé de stockage
     * @returns {*} Valeur récupérée ou null
     */
    getFromStorage(key) {
        try {
            const value = localStorage.getItem(key);
            return value ? JSON.parse(value) : null;
        } catch (e) {
            console.warn(`[OnboardingDAPStep2] Erreur lecture LocalStorage:`, e);
            return null;
        }
    }
}

/**
 * Fonction d'initialisation exportée
 * @returns {OnboardingDAPStep2|null} Instance de la classe ou null
 */
export function initOnboardingDAPStep2() {
    // Vérifier si on est sur la page step2_validate
    const isStep2ValidatePage =
        window.location.pathname.includes("step2_validate") ||
        document.querySelector(".step2-validate-breadcrumb");

    if (!isStep2ValidatePage) {
        console.log(
            "[OnboardingDAPStep2] Page non concernée, initialisation annulée"
        );
        return null;
    }

    const onboarding = new OnboardingDAPStep2();
    onboarding.init();
    return onboarding;
}
