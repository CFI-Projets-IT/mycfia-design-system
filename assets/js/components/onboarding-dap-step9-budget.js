/**
 * myCFiA Design System - Digital Adoption Platform (DAP) Onboarding - Step 9 Budget
 *
 * Système d'onboarding guidé pour la page Budget (Step 9)
 * - Welcome modal (première visite)
 * - Tour guidé avec spotlight et tooltips
 * - Help button pour relancer le tour
 * - Persistence via LocalStorage
 *
 * @module OnboardingDAPStep9Budget
 * @requires Bootstrap 5 (pour modal et tooltips)
 */

/**
 * Configuration des étapes du tour guidé Step 9 Budget
 * @const {Array<Object>}
 */
/**
 * parcoursFilter : tableau des parcours pour lesquels l'étape s'affiche.
 * Valeurs possibles : 'standard', 'avanci', 'both'
 * Absent = s'affiche pour tous les parcours.
 */
const ONBOARDING_STEPS_STEP9_BUDGET = [
    {
        id: "welcome",
        type: "modal",
        target: null,
    },
    {
        id: "stepper",
        type: "hotspot",
        target: ".campaign-stepper",
        title: "Étape 9/10 : Budget",
        content: "Vous approchez de la fin ! Cette étape présente le <strong>récapitulatif budgétaire</strong> de votre campagne selon votre parcours.",
        icon: "bi-currency-euro",
    },
    // Standard / Both : grille tarifaire locations + canaux + affranchissement
    {
        id: "tarif-section-standard",
        type: "hotspot",
        parcoursFilter: ['standard', 'both'],
        target: "[data-budget-section='standard'] .budget-tarif-section",
        title: "Grille tarifaire — parcours Standard",
        content: "Ce tableau détaille vos coûts :<br>• <strong>02 — Location d'adresses</strong> : email, SMS, courrier<br>• <strong>03 — Canaux de diffusion</strong> : coût par envoi<br>• <strong>04 — Affranchissement</strong> : tarif postal selon votre choix<br><br><i class='bi bi-table text-primary'></i> Les volumes sont calculés depuis votre fichier de contacts.",
        icon: "bi-table",
    },
    // Standard / Both : sélecteur affranchissement (absent en mode Avanci pur)
    {
        id: "affr-selector",
        type: "hotspot",
        parcoursFilter: ['standard', 'both'],
        target: ".budget-affr-selector",
        title: "Choix de l'affranchissement",
        content: "Sélectionnez votre <strong>type d'affranchissement postal</strong> :<br>• <strong>G4</strong> : courrier standard (0,747 € / unité)<br>• <strong>Destineo MD7</strong> : courrier optimisé (0,388 € / unité)<br><br><i class='bi bi-calculator text-primary'></i> Le total se <strong>recalcule automatiquement</strong> selon votre choix.",
        icon: "bi-envelope-paper",
    },
    // Standard / Both : total Standard
    {
        id: "total-card-standard",
        type: "hotspot",
        parcoursFilter: ['standard', 'both'],
        target: "[data-budget-section='standard'] .budget-total-card",
        title: "Total estimé — parcours Standard",
        content: "Récapitulatif des coûts Standard :<br>• Locations · Canaux · Affranchissement<br>• Mis à jour dynamiquement selon l'affranchissement choisi<br><br><i class='bi bi-info-circle text-primary'></i> Estimation basée sur les tarifs en vigueur.",
        icon: "bi-receipt",
    },
    // Avanci / Both : grille tarifaire Avanci
    {
        id: "tarif-section-avanci",
        type: "hotspot",
        parcoursFilter: ['avanci', 'both'],
        target: "[data-budget-section='avanci'] .budget-tarif-section",
        title: "Grille tarifaire — parcours Avanci",
        content: "Ce tableau présente les coûts spécifiques Avanci :<br>• <strong>05 — Contacts Avanci</strong> : leads qualifiés enrichis (tarif à définir)<br>• <strong>03 — Canaux réseaux sociaux</strong> : inclus dans le forfait Avanci<br><br><i class='bi bi-stars text-purple'></i> Les contacts Avanci sont enrichis et ciblés comportementalement.",
        icon: "bi-table",
    },
    // Avanci / Both : total Avanci
    {
        id: "total-card-avanci",
        type: "hotspot",
        parcoursFilter: ['avanci', 'both'],
        target: "[data-budget-section='avanci'] .budget-total-card",
        title: "Total estimé — parcours Avanci",
        content: "Le montant Avanci est un <strong>tarif à définir</strong> avec votre interlocuteur commercial.<br><br><i class='bi bi-info-circle text-primary'></i> Le tarif sera communiqué selon votre volume de contacts et les canaux activés.",
        icon: "bi-receipt",
    },
    // Avanci / Both : section paiement PayPal
    {
        id: "paypal-section",
        type: "hotspot",
        parcoursFilter: ['avanci', 'both'],
        target: ".budget-paypal-section",
        title: "Paiement PayPal — Avanci",
        content: "Le règlement de la partie <strong>Avanci</strong> s'effectue via PayPal :<br>• Paiement sécurisé SSL / 3D Secure<br>• Validation immédiate après règlement<br>• Le paiement débloque l'accès à la planification<br><br><i class='bi bi-exclamation-triangle text-warning'></i> La partie Standard n'est pas concernée par ce règlement.",
        icon: "bi-paypal",
    },
    {
        id: "speed-dial-fab",
        type: "hotspot",
        target: ".speed-dial-container",
        title: "Actions disponibles",
        content: "Le <strong>Speed Dial</strong> propose 2 actions :<br>• <i class='bi bi-arrow-left'></i> <strong>Retour aux assets</strong> — revenir à l'étape 8<br>• <i class='bi bi-credit-card'></i> <strong>Continuer</strong> — accès direct (Standard) ou après paiement PayPal (Avanci)",
        icon: "bi-three-dots-vertical",
    },
];

/**
 * LocalStorage keys utilisés
 * @const {Object}
 */
const STORAGE_KEYS_STEP9_BUDGET = {
    COMPLETED: "mycfia_onboarding_step9_budget_completed",
    STEP: "mycfia_onboarding_step9_budget_step",
    SKIPPED_AT: "mycfia_onboarding_step9_budget_skipped_at",
    TOOLTIP_DISMISSED: "mycfia_onboarding_step9_budget_tooltip_dismissed",
};

/**
 * Classe principale gérant l'onboarding DAP Step 9 Budget
 * @class OnboardingDAPStep9Budget
 */
class OnboardingDAPStep9Budget {
    /**
     * @constructor
     */
    constructor() {
        this.steps = ONBOARDING_STEPS_STEP9_BUDGET;
        this.currentStep = 0;
        this.isActive = false;
        this.overlay = null;
        this.spotlight = null;
        this.tooltip = null;
        this._speedDialOpen = false;
        this._scrollPosition = 0;
    }

    /**
     * Initialise le système d'onboarding
     */
    init() {
        console.log("[OnboardingDAPStep9Budget] Initialisation...");

        const isCompleted = this.getFromStorage(STORAGE_KEYS_STEP9_BUDGET.COMPLETED);

        if (!isCompleted) {
            setTimeout(() => {
                this.showWelcomeModal();
            }, 1000);
        }

        this.initHelpButton();

        console.log("[OnboardingDAPStep9Budget] Initialisé avec succès");
    }

    /**
     * Affiche le modal de bienvenue
     */
    showWelcomeModal() {
        const modalEl = document.getElementById("onboardingWelcomeModalStep9Budget");
        if (!modalEl) {
            console.warn("[OnboardingDAPStep9Budget] Modal welcome introuvable");
            return;
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        const skipBtn = modalEl.querySelector('[data-bs-dismiss="modal"]');
        if (skipBtn) {
            skipBtn.addEventListener("click", () => {
                this.skipTour("welcome_modal");
            });
        }

        const startBtn = document.getElementById("startOnboardingTourStep9Budget");
        if (startBtn) {
            startBtn.addEventListener("click", () => {
                modal.hide();
                setTimeout(() => {
                    this.startTour();
                }, 300);
            });
        }
    }

    /**
     * Démarre le tour guidé
     */
    startTour() {
        console.log("[OnboardingDAPStep9Budget] Démarrage du tour guidé");

        this.isActive = true;
        this.currentStep = 1;

        this._scrollPosition = window.scrollY;
        document.body.classList.add('onboarding-active');
        document.body.style.top = `-${this._scrollPosition}px`;

        this.createOverlay();
        this.showStep(this.currentStep);
    }

    /**
     * Crée l'overlay sombre avec spotlight
     */
    createOverlay() {
        if (!this.overlay) {
            this.overlay = document.createElement("div");
            this.overlay.className = "onboarding-overlay";
            document.body.appendChild(this.overlay);

            setTimeout(() => {
                this.overlay.classList.add("active");
            }, 10);
        }

        if (!this.spotlight) {
            this.spotlight = document.createElement("div");
            this.spotlight.className = "onboarding-spotlight";
            document.body.appendChild(this.spotlight);
        }
    }

    /**
     * Affiche une étape spécifique du tour
     * @param {number} stepIndex
     */
    showStep(stepIndex) {
        if (stepIndex >= this.steps.length) {
            this.completeTour();
            return;
        }

        const step = this.steps[stepIndex];

        if (step.type === "modal") {
            this.showStep(stepIndex + 1);
            return;
        }

        // Vérifier si l'étape est compatible avec le parcours courant
        if (step.parcoursFilter) {
            const page = document.getElementById('budget-page');
            const parcours = page ? page.dataset.budgetParcours : 'standard';
            if (!step.parcoursFilter.includes(parcours)) {
                console.log(`[OnboardingDAPStep9Budget] Étape "${step.id}" ignorée (parcours: ${parcours})`);
                this.showStep(stepIndex + 1);
                return;
            }
        }

        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }

        const targetEl = document.querySelector(step.target);
        if (!targetEl) {
            console.warn(`[OnboardingDAPStep9Budget] Élément cible "${step.target}" introuvable, passage à l'étape suivante`);
            this.showStep(stepIndex + 1);
            return;
        }

        this.currentStep = stepIndex;

        if (step.id === "speed-dial-fab") {
            this.openSpeedDial();
        }

        // Gestion scroll
        const bodyBlocked = document.body.classList.contains('onboarding-active');
        const currentScroll = bodyBlocked && this._scrollPosition !== undefined
            ? this._scrollPosition
            : window.scrollY;

        const rect = targetEl.getBoundingClientRect();
        const elementTop = rect.top + currentScroll;
        const elementBottom = elementTop + rect.height;
        const viewportTop = currentScroll;
        const viewportBottom = currentScroll + window.innerHeight;
        const visibleTop = Math.max(elementTop, viewportTop);
        const visibleBottom = Math.min(elementBottom, viewportBottom);
        const visibleHeight = Math.max(0, visibleBottom - visibleTop);
        const visibilityRatio = (visibleHeight / rect.height) * 100;
        const isVisible = visibilityRatio >= 80;

        if (!isVisible) {
            const wasBlocked = document.body.classList.contains('onboarding-active');
            if (wasBlocked) {
                document.body.classList.remove('onboarding-active');
                document.body.style.top = '';
                if (this._scrollPosition !== undefined) {
                    window.scrollTo(0, this._scrollPosition);
                }
            }

            setTimeout(() => {
                targetEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });

                setTimeout(() => {
                    if (wasBlocked) {
                        this._scrollPosition = window.scrollY;
                        document.body.classList.add('onboarding-active');
                        document.body.style.top = `-${this._scrollPosition}px`;
                    }

                    setTimeout(() => {
                        this.positionSpotlight(targetEl);
                        this.createTooltip(step, targetEl);
                    }, 10);
                }, 500);
            }, 50);
            return;
        }

        this.positionSpotlight(targetEl);
        this.createTooltip(step, targetEl);
        this.saveToStorage(STORAGE_KEYS_STEP9_BUDGET.STEP, stepIndex);
    }

    /**
     * Positionne le spotlight sur l'élément cible
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
     * Crée et affiche le tooltip contextuel
     * @param {Object} step
     * @param {HTMLElement} targetEl
     */
    createTooltip(step, targetEl) {
        if (this.tooltip) {
            this.tooltip.remove();
        }

        this.tooltip = document.createElement("div");
        this.tooltip.className = "onboarding-tooltip";

        const page = document.getElementById('budget-page');
        const parcours = page ? page.dataset.budgetParcours : 'standard';
        const visibleSteps = this.steps.filter((s) => {
            if (s.type === "modal") return false;
            if (!s.parcoursFilter) return true;
            return s.parcoursFilter.includes(parcours);
        });
        const currentStepNum = visibleSteps.findIndex((s) => s.id === step.id) + 1;
        const totalSteps = visibleSteps.length;

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

        document.body.appendChild(this.tooltip);
        this.positionTooltip(targetEl);

        setTimeout(() => {
            this.tooltip.classList.add("active");
        }, 100);

        const skipBtn = this.tooltip.querySelector('[data-action="skip"]');
        const nextBtn = this.tooltip.querySelector('[data-action="next"]');

        skipBtn.addEventListener("click", () => this.skipTour("tooltip"));
        nextBtn.addEventListener("click", () => this.nextStep());
    }

    /**
     * Positionne le tooltip par rapport à l'élément cible
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
     * Génère les points de progression HTML
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
        const currentStepConfig = this.steps[this.currentStep];
        if (currentStepConfig && currentStepConfig.id === "speed-dial-fab") {
            this.closeSpeedDial();
        }
        this.showStep(this.currentStep + 1);
    }

    /**
     * Saute le tour
     * @param {string} source
     */
    skipTour(source) {
        console.log(`[OnboardingDAPStep9Budget] Tour sauté depuis: ${source}`);
        this.saveToStorage(STORAGE_KEYS_STEP9_BUDGET.SKIPPED_AT, Date.now());
        this.saveToStorage(STORAGE_KEYS_STEP9_BUDGET.COMPLETED, true);
        this.closeSpeedDial();
        this.cleanup();
    }

    /**
     * Termine le tour avec succès
     */
    completeTour() {
        console.log("[OnboardingDAPStep9Budget] Tour terminé avec succès");
        this.saveToStorage(STORAGE_KEYS_STEP9_BUDGET.COMPLETED, true);
        this.closeSpeedDial();
        this.cleanup();
        this.showCompletionMessage();
    }

    /**
     * Affiche un message de félicitation (toast)
     */
    showCompletionMessage() {
        const toast = document.createElement("div");
        toast.className = "onboarding-completion-toast";
        toast.innerHTML = `
            <div class="onboarding-completion-toast-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <strong>Tour terminé !</strong>
                <p class="mb-0 small">Consultez votre budget, choisissez l'affranchissement et finalisez le règlement si nécessaire.</p>
            </div>
        `;

        document.body.appendChild(toast);

        setTimeout(() => { toast.classList.add("show"); }, 100);
        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => { toast.remove(); }, 300);
        }, 5000);
    }

    /**
     * Nettoie tous les éléments d'UI du tour
     */
    cleanup() {
        this.isActive = false;

        document.body.classList.remove('onboarding-active');
        document.body.style.top = '';
        if (this._scrollPosition !== undefined) {
            window.scrollTo(0, this._scrollPosition);
        }

        if (this.overlay) {
            this.overlay.classList.remove("active");
            setTimeout(() => { this.overlay.remove(); this.overlay = null; }, 300);
        }

        if (this.spotlight) {
            this.spotlight.remove();
            this.spotlight = null;
        }

        if (this.tooltip) {
            this.tooltip.classList.remove("active");
            setTimeout(() => { this.tooltip.remove(); this.tooltip = null; }, 300);
        }
    }

    /**
     * Ouvre le Speed Dial FAB pour la démonstration
     */
    openSpeedDial() {
        const speedDialContainer = document.querySelector('.speed-dial-container');
        if (!speedDialContainer) return;

        speedDialContainer.classList.add('dap-force-open');

        const actions = speedDialContainer.querySelectorAll('.speed-dial-action');
        actions.forEach((action) => {
            action.classList.remove('d-none');
            action.style.opacity = '1';
            action.style.transform = 'translateY(0) scale(1)';
            action.style.pointerEvents = 'all';
        });

        const mainBtn = speedDialContainer.querySelector('.speed-dial-main i');
        if (mainBtn) mainBtn.className = 'bi bi-x-lg';

        this._speedDialOpen = true;
    }

    /**
     * Ferme le Speed Dial FAB
     */
    closeSpeedDial() {
        if (!this._speedDialOpen) return;

        const speedDialContainer = document.querySelector('.speed-dial-container');
        if (!speedDialContainer) return;

        speedDialContainer.classList.remove('dap-force-open');

        const actions = speedDialContainer.querySelectorAll('.speed-dial-action');
        actions.forEach(action => {
            action.style.opacity = '';
            action.style.transform = '';
            action.style.pointerEvents = '';
        });

        const mainBtn = speedDialContainer.querySelector('.speed-dial-main i');
        if (mainBtn) mainBtn.className = 'bi bi-three-dots-vertical';

        this._speedDialOpen = false;
    }

    /**
     * Initialise le bouton d'aide inline
     */
    initHelpButton() {
        const helpBtn = document.getElementById("restartOnboardingTourStep9Budget");
        if (helpBtn) {
            helpBtn.addEventListener("click", (e) => {
                e.preventDefault();
                console.log("[OnboardingDAPStep9Budget] Relancement du tour via bouton d'aide");

                if (this.isActive) {
                    this.cleanup();
                }

                setTimeout(() => {
                    this.startTour();
                }, 100);
            });
        }
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
            console.error("[OnboardingDAPStep9Budget] Erreur localStorage:", e);
        }
    }

    /**
     * Récupère depuis localStorage
     * @param {string} key
     * @returns {*}
     */
    getFromStorage(key) {
        try {
            const value = localStorage.getItem(key);
            return value ? JSON.parse(value) : null;
        } catch (e) {
            console.error("[OnboardingDAPStep9Budget] Erreur localStorage:", e);
            return null;
        }
    }
}

/**
 * Fonction d'initialisation exportée pour main.js
 */
export function initOnboardingDAPStep9Budget() {
    const onboarding = new OnboardingDAPStep9Budget();
    onboarding.init();
}

export default OnboardingDAPStep9Budget;
