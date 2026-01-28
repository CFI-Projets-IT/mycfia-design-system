/**
 * myCFiA Design System - Digital Adoption Platform (DAP) Onboarding - Step 5 Select
 *
 * Système d'onboarding guidé pour la page de sélection des canaux (Step 5)
 * - Welcome modal (première visite)
 * - Tour guidé avec spotlight et tooltips
 * - Help button pour relancer le tour
 * - Persistence via LocalStorage
 *
 * @module OnboardingDAPStep5
 * @requires Bootstrap 5 (pour modal et tooltips)
 */

/**
 * Configuration des étapes du tour guidé Step 5
 * @const {Array<Object>}
 */
const ONBOARDING_STEPS_STEP5 = [
    {
        id: "welcome",
        type: "modal",
        target: null,
    },
    {
        id: "stepper",
        type: "hotspot",
        target: ".campaign-stepper",
        title: "Étape 5 : Sélection des Canaux",
        content: "Vous êtes à l'étape 5 sur 8. Choisissez les canaux de diffusion pour votre campagne basés sur les recommandations IA.",
        icon: "bi-collection",
    },
    {
        id: "recommended-channels",
        type: "hotspot",
        target: ".form-section-delay-1",
        title: "Canaux recommandés par l'IA",
        content: "L'IA a analysé votre stratégie et identifié 4 canaux prioritaires avec leurs scores de pertinence. Les 3 premiers sont déjà activés.",
        icon: "bi-stars",
    },
    {
        id: "channel-card-example",
        type: "hotspot",
        target: ".asset-card-modern.active[data-asset='linkedin']",
        title: "Configurez chaque canal",
        content: "Pour chaque canal : activez/désactivez, choisissez le nombre de variations (stepper), et activez la génération d'images avec sélection du style visuel.",
        icon: "bi-sliders",
    },
    {
        id: "other-channels",
        type: "hotspot",
        target: ".form-section-delay-2",
        title: "Autres canaux disponibles",
        content: "Ces canaux n'ont pas été recommandés par l'IA mais restent disponibles si vous souhaitez élargir votre stratégie (Instagram, Bing Ads, IAB Display, SMS, Article, Courrier).",
        icon: "bi-collection",
    },
    {
        id: "estimation-summary",
        type: "hotspot",
        target: ".estimation-card",
        title: "Résumé de votre sélection",
        content: "Visualisez en temps réel le nombre de canaux actifs, le total de variations et la durée estimée de génération. Les badges montrent les canaux sélectionnés.",
        icon: "bi-lightning-charge-fill",
    },
    {
        id: "speed-dial-fab",
        type: "hotspot",
        target: ".speed-dial-container",
        title: "Validez et continuez",
        content: "Cliquez ici pour valider votre sélection. Une modal vous demandera de choisir la source de vos contacts (Upload, Avanci) avant de passer à l'étape 6.",
        icon: "bi-check-lg",
    },
];

/**
 * LocalStorage keys utilisés
 * @const {Object}
 */
const STORAGE_KEYS_STEP5 = {
    COMPLETED: "mycfia_onboarding_step5_completed",
    STEP: "mycfia_onboarding_step5_step",
    SKIPPED_AT: "mycfia_onboarding_step5_skipped_at",
};

/**
 * Classe principale gérant l'onboarding DAP Step 5
 * @class OnboardingDAPStep5
 */
class OnboardingDAPStep5 {
    /**
     * Initialise l'instance OnboardingDAPStep5
     * @constructor
     */
    constructor() {
        this.steps = ONBOARDING_STEPS_STEP5;
        this.currentStep = 0;
        this.isActive = false;
        this.overlay = null;
        this.spotlight = null;
        this.tooltip = null;
        this.helpButton = null;
        this._scrollPosition = 0;
        this._speedDialOpen = false;
    }

    /**
     * Initialise le système d'onboarding
     * @public
     */
    init() {
        console.log("[OnboardingDAPStep5] Initialisation...");

        // Setup help button
        this.setupHelpButton();

        // Vérifier si déjà complété
        const completed = this.loadFromStorage(STORAGE_KEYS_STEP5.COMPLETED);
        if (completed) {
            console.log("[OnboardingDAPStep5] Onboarding déjà complété");
            return;
        }

        // Afficher la welcome modal
        this.showWelcomeModal();
    }

    /**
     * Affiche la welcome modal
     */
    showWelcomeModal() {
        const modal = document.getElementById("onboardingWelcomeModalStep5");
        if (!modal) {
            console.warn("[OnboardingDAPStep5] Modal de bienvenue introuvable");
            return;
        }

        const bsModal = new bootstrap.Modal(modal, {
            backdrop: "static",
            keyboard: false,
        });

        const startBtn = document.getElementById("startOnboardingTourStep5");
        if (startBtn) {
            startBtn.addEventListener("click", () => {
                bsModal.hide();
                modal.addEventListener(
                    "hidden.bs.modal",
                    () => {
                        this.startTour();
                    },
                    { once: true }
                );
            });
        }

        const skipButtons = modal.querySelectorAll('[data-bs-dismiss="modal"]');
        skipButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                this.skipTour("welcome_modal");
            });
        });

        bsModal.show();
    }

    /**
     * Démarre le tour guidé
     */
    startTour() {
        console.log("[OnboardingDAPStep5] Démarrage du tour guidé");
        this.isActive = true;
        this.currentStep = 1; // Passer le welcome modal (index 0)

        // Sauvegarder la position de scroll actuelle
        this._scrollPosition = window.scrollY;

        // Bloquer le scroll
        document.body.classList.add("onboarding-active");
        document.body.style.top = `-${this._scrollPosition}px`;

        // Créer overlay et spotlight
        this.createOverlay();
        this.createSpotlight();
        this.createTooltip();

        // Afficher la première étape
        this.showStep(this.currentStep);
    }

    /**
     * Crée l'overlay
     */
    createOverlay() {
        this.overlay = document.createElement("div");
        this.overlay.className = "onboarding-overlay active";
        document.body.appendChild(this.overlay);
    }

    /**
     * Crée le spotlight
     */
    createSpotlight() {
        this.spotlight = document.createElement("div");
        this.spotlight.className = "onboarding-spotlight";
        document.body.appendChild(this.spotlight);
    }

    /**
     * Crée le conteneur tooltip vide
     */
    createTooltip() {
        this.tooltip = document.createElement("div");
        this.tooltip.className = "onboarding-tooltip";
        document.body.appendChild(this.tooltip);
    }

    /**
     * Affiche une étape
     * @param {number} stepIndex
     */
    showStep(stepIndex) {
        if (stepIndex >= this.steps.length) {
            this.completeTour();
            return;
        }

        const step = this.steps[stepIndex];
        if (step.type === "modal") {
            // Passer les modals
            this.showStep(stepIndex + 1);
            return;
        }

        const targetEl = document.querySelector(step.target);
        if (!targetEl) {
            console.warn(`[OnboardingDAPStep5] Élément "${step.target}" introuvable`);
            this.showStep(stepIndex + 1);
            return;
        }

        // Ouvrir le Speed Dial FAB si on arrive à cette étape
        if (step.id === "speed-dial-fab") {
            this.openSpeedDial();
        }

        // Vérifier si l'élément est suffisamment visible
        const bodyBlocked = document.body.classList.contains('onboarding-active');
        const currentScroll = bodyBlocked && this._scrollPosition !== undefined
            ? this._scrollPosition
            : window.scrollY;

        const rect = targetEl.getBoundingClientRect();

        // Calculer la position absolue de l'élément dans le document
        const elementTop = rect.top + currentScroll;
        const elementBottom = elementTop + rect.height;

        // Calculer ce qui est visible dans le viewport actuel
        const viewportTop = currentScroll;
        const viewportBottom = currentScroll + window.innerHeight;

        // Calculer la hauteur visible
        const visibleTop = Math.max(elementTop, viewportTop);
        const visibleBottom = Math.min(elementBottom, viewportBottom);
        const visibleHeight = Math.max(0, visibleBottom - visibleTop);
        const elementHeight = rect.height;
        const visibilityRatio = (visibleHeight / elementHeight) * 100;

        const isVisible = visibilityRatio >= 80;

        console.log(`[OnboardingDAPStep5] Étape "${step.id}": ${visibilityRatio.toFixed(0)}% visible`);

        if (!isVisible) {
            console.log(`[OnboardingDAPStep5] Scroll nécessaire pour "${step.id}"`);

            // Débloquer temporairement le scroll
            const wasBlocked = document.body.classList.contains('onboarding-active');
            if (wasBlocked) {
                document.body.classList.remove('onboarding-active');
                document.body.style.top = '';
                if (this._scrollPosition !== undefined) {
                    window.scrollTo(0, this._scrollPosition);
                }
            }

            // Petit délai pour que le déblocage prenne effet
            setTimeout(() => {
                targetEl.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'center'
                });

                // Attendre que le scroll se termine
                setTimeout(() => {
                    // Rebloquer le scroll
                    if (wasBlocked) {
                        this._scrollPosition = window.scrollY;
                        document.body.classList.add('onboarding-active');
                        document.body.style.top = `-${this._scrollPosition}px`;
                    }

                    // Afficher spotlight et tooltip
                    setTimeout(() => {
                        this.positionSpotlight(targetEl);
                        this.updateTooltip(step, targetEl);
                    }, 10);
                }, 500);
            }, 50);
            return;
        }

        // Élément déjà visible, afficher directement
        this.positionSpotlight(targetEl);
        this.updateTooltip(step, targetEl);

        // Sauvegarder la progression
        this.saveToStorage(STORAGE_KEYS_STEP5.STEP, stepIndex);
    }

    /**
     * Positionne le spotlight
     * @param {HTMLElement} targetEl
     */
    positionSpotlight(targetEl) {
        const rect = targetEl.getBoundingClientRect();
        const padding = 8;

        const scrollY = document.body.classList.contains('onboarding-active')
            ? (this._scrollPosition || 0)
            : window.scrollY;

        this.spotlight.style.top = `${rect.top - padding + scrollY}px`;
        this.spotlight.style.left = `${rect.left - padding + window.scrollX}px`;
        this.spotlight.style.width = `${rect.width + padding * 2}px`;
        this.spotlight.style.height = `${rect.height + padding * 2}px`;

        this.spotlight.classList.add("pulse");
    }

    /**
     * Met à jour le tooltip avec le contenu de l'étape
     * @param {Object} step
     * @param {HTMLElement} targetEl
     */
    updateTooltip(step, targetEl) {
        const currentStepNum = this.currentStep;
        const totalSteps = this.steps.filter((s) => s.type !== "modal").length;

        this.tooltip.innerHTML = `
            <div class="onboarding-tooltip-header">
                <div class="onboarding-tooltip-icon">
                    <i class="${step.icon}"></i>
                </div>
                <h6 class="onboarding-tooltip-title">${step.title}</h6>
            </div>
            <div class="onboarding-tooltip-body">
                <p class="onboarding-tooltip-content">${step.content}</p>
            </div>
            <div class="onboarding-tooltip-footer">
                <div class="onboarding-tooltip-progress">
                    ${this.generateProgressDots(currentStepNum, totalSteps)}
                </div>
                <div class="d-flex gap-2">
                    <button class="onboarding-btn onboarding-btn-secondary" data-action="skip">
                        Passer le tour
                    </button>
                    <button class="onboarding-btn onboarding-btn-primary" data-action="next">
                        ${currentStepNum === totalSteps ? "Terminer" : "Suivant"}
                        <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>
        `;

        // Positionner le tooltip
        this.positionTooltip(targetEl);

        // Activer le tooltip
        setTimeout(() => {
            this.tooltip.classList.add("active");
        }, 100);

        // Event listeners
        const skipBtn = this.tooltip.querySelector('[data-action="skip"]');
        const nextBtn = this.tooltip.querySelector('[data-action="next"]');

        if (skipBtn) {
            skipBtn.addEventListener("click", () => this.skipTour("tooltip"));
        }

        if (nextBtn) {
            nextBtn.addEventListener("click", () => this.nextStep());
        }
    }

    /**
     * Positionne le tooltip
     * @param {HTMLElement} targetEl
     */
    positionTooltip(targetEl) {
        const rect = targetEl.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;
        const minTopMargin = 20;
        const minBottomMargin = 20;
        const spacing = 20;

        const scrollY = document.body.classList.contains('onboarding-active')
            ? (this._scrollPosition || 0)
            : window.scrollY;

        // Cas spécial Speed Dial FAB
        const isSpeedDialFAB = targetEl.classList.contains('speed-dial-container');

        if (isSpeedDialFAB) {
            const tooltipHeight = tooltipRect.height || 200;
            const tooltipWidth = tooltipRect.width || 400;

            const centerY = rect.top + rect.height / 2;
            const topPos = Math.max(
                minTopMargin,
                Math.min(
                    centerY - tooltipHeight / 2,
                    viewportHeight - tooltipHeight - minBottomMargin
                )
            );

            const leftPos = rect.left - tooltipWidth - spacing - 20;

            this.tooltip.classList.remove('position-top', 'position-bottom', 'position-left', 'position-right');
            this.tooltip.classList.add('position-left');

            this.tooltip.style.top = `${topPos + scrollY}px`;
            this.tooltip.style.left = `${leftPos + window.scrollX}px`;
            this.tooltip.style.transform = "translateY(0)";

            return;
        }

        // Position par défaut
        let position = "bottom";

        const spaceBelow = viewportHeight - rect.bottom - minBottomMargin;
        const spaceAbove = rect.top - minTopMargin;
        const tooltipHeight = tooltipRect.height || 200;

        if (spaceBelow < tooltipHeight + spacing && spaceAbove > tooltipHeight + spacing) {
            position = "top";
        }

        this.tooltip.classList.remove('position-top', 'position-bottom', 'position-left', 'position-right');
        this.tooltip.classList.add(`position-${position}`);

        if (position === "bottom") {
            const topPos = Math.min(
                rect.bottom + spacing,
                viewportHeight - tooltipHeight - minBottomMargin
            );
            this.tooltip.style.top = `${topPos + scrollY}px`;
            this.tooltip.style.left = `${rect.left + rect.width / 2 + window.scrollX}px`;
            this.tooltip.style.transform = "translateX(-50%)";
        } else if (position === "top") {
            const topPos = Math.max(
                minTopMargin,
                rect.top - tooltipHeight - spacing
            );
            this.tooltip.style.top = `${topPos + scrollY}px`;
            this.tooltip.style.left = `${rect.left + rect.width / 2 + window.scrollX}px`;
            this.tooltip.style.transform = "translateX(-50%)";
        }
    }

    /**
     * Génère les progress dots
     * @param {number} current
     * @param {number} total
     * @returns {string}
     */
    generateProgressDots(current, total) {
        let html = "";
        for (let i = 1; i <= total; i++) {
            const activeClass = i === current ? "active" : "";
            const completedClass = i < current ? "completed" : "";
            html += `<span class="progress-dot ${activeClass} ${completedClass}"></span>`;
        }
        return html;
    }

    /**
     * Passe à l'étape suivante
     */
    nextStep() {
        // Fermer le Speed Dial si on était sur l'étape FAB
        const currentStepConfig = this.steps[this.currentStep];
        if (currentStepConfig && currentStepConfig.id === "speed-dial-fab") {
            this.closeSpeedDial();
        }

        this.currentStep++;
        this.showStep(this.currentStep);
    }

    /**
     * Saute le tour
     * @param {string} source
     */
    skipTour(source) {
        console.log(`[OnboardingDAPStep5] Tour sauté depuis: ${source}`);
        this.saveToStorage(STORAGE_KEYS_STEP5.SKIPPED_AT, Date.now());
        this.saveToStorage(STORAGE_KEYS_STEP5.COMPLETED, true);

        // Fermer le Speed Dial si ouvert
        this.closeSpeedDial();

        this.cleanup();
    }

    /**
     * Ouvre le Speed Dial FAB
     */
    openSpeedDial() {
        console.log("[OnboardingDAPStep5] Ouverture du Speed Dial FAB");

        const speedDialContainer = document.querySelector('.speed-dial-container');
        if (!speedDialContainer) {
            console.warn("[OnboardingDAPStep5] Speed Dial container introuvable");
            return;
        }

        // Ajouter classe pour forcer l'affichage
        speedDialContainer.classList.add('dap-force-open');

        // Trouver toutes les actions
        const actions = speedDialContainer.querySelectorAll('.speed-dial-action');

        // Forcer l'affichage avec styles inline (pour surpasser le CSS :hover)
        actions.forEach((action, index) => {
            action.classList.remove('d-none');
            action.style.opacity = '1';
            action.style.transform = 'translateY(0) scale(1)';
            action.style.pointerEvents = 'all';
        });

        // Changer l'icône du bouton principal (trois points → X)
        const mainBtn = speedDialContainer.querySelector('.speed-dial-main i');
        if (mainBtn) {
            mainBtn.className = 'bi bi-x-lg';
        }

        // Marquer comme ouvert
        this._speedDialOpen = true;
    }

    /**
     * Ferme le Speed Dial FAB
     */
    closeSpeedDial() {
        if (!this._speedDialOpen) {
            return;
        }

        console.log("[OnboardingDAPStep5] Fermeture du Speed Dial FAB");

        const speedDialContainer = document.querySelector('.speed-dial-container');
        if (!speedDialContainer) {
            return;
        }

        // Retirer classe de forçage
        speedDialContainer.classList.remove('dap-force-open');

        // Trouver toutes les actions
        const actions = speedDialContainer.querySelectorAll('.speed-dial-action');

        // Retirer les styles inline pour restaurer le comportement CSS par défaut
        actions.forEach(action => {
            action.style.opacity = '';
            action.style.transform = '';
            action.style.pointerEvents = '';
        });

        // Restaurer l'icône du bouton principal (X → trois points)
        const mainBtn = speedDialContainer.querySelector('.speed-dial-main i');
        if (mainBtn) {
            mainBtn.className = 'bi bi-three-dots-vertical';
        }

        // Marquer comme fermé
        this._speedDialOpen = false;
    }

    /**
     * Termine le tour
     */
    completeTour() {
        console.log("[OnboardingDAPStep5] Tour complété");
        this.saveToStorage(STORAGE_KEYS_STEP5.COMPLETED, true);

        // Fermer le Speed Dial si ouvert
        this.closeSpeedDial();

        this.cleanup();
    }

    /**
     * Nettoie l'UI
     */
    cleanup() {
        if (this.overlay) this.overlay.remove();
        if (this.spotlight) this.spotlight.remove();
        if (this.tooltip) this.tooltip.remove();

        // Débloquer le scroll
        document.body.classList.remove("onboarding-active");
        document.body.style.top = "";
        window.scrollTo(0, this._scrollPosition);

        this.isActive = false;
    }

    /**
     * Setup du bouton d'aide
     */
    setupHelpButton() {
        this.helpButton = document.getElementById("restartOnboardingTourStep5");
        if (!this.helpButton) {
            console.warn("[OnboardingDAPStep5] Bouton d'aide introuvable");
            return;
        }

        this.helpButton.addEventListener("click", () => {
            console.log("[OnboardingDAPStep5] Relance du tour via bouton d'aide");
            this.restart();
        });
    }

    /**
     * Relance le tour
     */
    restart() {
        console.log("[OnboardingDAPStep5] Relance du tour guidé");

        // Nettoyer localStorage
        localStorage.removeItem(STORAGE_KEYS_STEP5.COMPLETED);
        localStorage.removeItem(STORAGE_KEYS_STEP5.STEP);

        // Nettoyer UI existante
        this.cleanup();

        // Redémarrer
        this.startTour();
    }

    /**
     * Sauvegarde dans localStorage
     * @param {string} key
     * @param {*} value
     */
    saveToStorage(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error("[OnboardingDAPStep5] Erreur sauvegarde localStorage:", e);
        }
    }

    /**
     * Charge depuis localStorage
     * @param {string} key
     * @returns {*}
     */
    loadFromStorage(key) {
        try {
            const value = localStorage.getItem(key);
            return value ? JSON.parse(value) : null;
        } catch (e) {
            console.error("[OnboardingDAPStep5] Erreur lecture localStorage:", e);
            return null;
        }
    }
}

/**
 * Initialise l'onboarding DAP Step 5
 * @public
 * @returns {OnboardingDAPStep5} Instance de l'onboarding
 */
export function initOnboardingDAPStep5() {
    console.log("[OnboardingDAPStep5] Fonction d'initialisation appelée");
    const onboarding = new OnboardingDAPStep5();
    onboarding.init();
    return onboarding;
}
