/**
 * myCFiA Design System - Digital Adoption Platform (DAP) Onboarding
 *
 * Système d'onboarding guidé pour l'application myCFiA
 * - Welcome modal (première visite)
 * - Tour guidé avec spotlight et tooltips
 * - Help button pour relancer le tour
 * - Persistence via LocalStorage
 *
 * @module OnboardingDAP
 * @requires Bootstrap 5 (pour modal et tooltips)
 */

/**
 * Configuration des étapes du tour guidé Dashboard
 * @const {Array<Object>}
 */
const ONBOARDING_STEPS = [
    {
        id: "welcome",
        type: "modal",
        target: null,
    },
    {
        id: "dashboard-overview",
        type: "hotspot",
        target: ".content",
        title: "Votre Dashboard",
        content:
            "Retrouvez ici toutes vos campagnes marketing en cours, terminées et leurs performances en temps réel.",
        icon: "bi-speedometer2",
    },
    {
        id: "new-campaign-button",
        type: "hotspot",
        target: ".btn-ai",
        title: "Créer une Campagne",
        content:
            "Cliquez ici pour démarrer une nouvelle campagne assistée par IA. Le workflow complet ne prend que 10-15 minutes.",
        icon: "bi-plus-circle",
    },
    {
        id: "sidebar-navigation",
        type: "hotspot",
        target: ".nav-section:first-child",
        title: "Navigation Marketing",
        content:
            "Accédez rapidement à vos campagnes, analytics, favoris et historique. Tout est organisé pour une navigation efficace.",
        icon: "bi-compass",
    },
    {
        id: "campaign-row",
        type: "hotspot",
        target: "table tbody tr:first-child",
        title: "Vos Campagnes",
        content:
            "Cliquez sur une campagne pour voir les détails, modifier la configuration ou analyser les performances.",
        icon: "bi-folder-fill",
    },
];

/**
 * LocalStorage keys utilisés
 * @const {Object}
 */
const STORAGE_KEYS = {
    COMPLETED: "mycfia_onboarding_completed",
    STEP: "mycfia_onboarding_step",
    SKIPPED_AT: "mycfia_onboarding_skipped_at",
    TOOLTIP_DISMISSED: "mycfia_tooltip_dismissed",
};

/**
 * Classe principale gérant l'onboarding DAP
 * @class OnboardingDAP
 */
class OnboardingDAP {
    /**
     * Initialise l'instance OnboardingDAP
     * @constructor
     */
    constructor() {
        this.currentStep = 0;
        this.steps = ONBOARDING_STEPS;
        this.overlay = null;
        this.spotlight = null;
        this.tooltip = null;
        this.helpButton = null;
        this.isActive = false;
    }

    /**
     * Initialise l'onboarding (point d'entrée principal)
     * - Vérifie si première visite
     * - Affiche modal si nécessaire
     * - Crée le help button
     */
    init() {
        console.log("[OnboardingDAP] Initialisation...");

        // Vérifier si l'utilisateur a déjà complété l'onboarding
        const hasCompleted = this.getFromStorage(STORAGE_KEYS.COMPLETED);

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

        console.log("[OnboardingDAP] Initialisé avec succès");
    }

    /**
     * Affiche le modal de bienvenue (bootstrap modal)
     * Gère les événements des boutons "Passer" et "Faire le tour guidé"
     */
    showWelcomeModal() {
        const modalEl = document.getElementById("onboardingWelcomeModal");
        if (!modalEl) {
            console.warn(
                "[OnboardingDAP] Modal #onboardingWelcomeModal introuvable"
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
        const startBtn = document.getElementById("startOnboardingTour");
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
        console.log("[OnboardingDAP] Démarrage du tour guidé");

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
    showStep(stepIndex) {
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

        // Nettoyer le wrapper temporaire de l'étape précédente
        if (this._tempWrapper) {
            this._tempWrapper.remove();
            this._tempWrapper = null;
        }

        // Trouver l'élément cible
        let targetEl = document.querySelector(step.target);
        if (!targetEl) {
            console.warn(
                `[OnboardingDAP] Élément cible "${step.target}" introuvable, passage à l'étape suivante`
            );
            this.showStep(stepIndex + 1);
            return;
        }

        // Cas spécial : sidebar-navigation doit englober les 3 nav-section
        if (step.id === "sidebar-navigation") {
            const navSections = document.querySelectorAll('.nav-section');
            if (navSections.length >= 3) {
                // Créer un wrapper temporaire pour englober les 3 sections
                const wrapper = document.createElement('div');
                wrapper.id = 'onboarding-nav-wrapper';
                wrapper.style.position = 'absolute';

                // Calculer la bounding box des 3 sections
                const firstRect = navSections[0].getBoundingClientRect();
                const lastRect = navSections[2].getBoundingClientRect();

                wrapper.style.top = `${firstRect.top + window.scrollY}px`;
                wrapper.style.left = `${firstRect.left + window.scrollX}px`;
                wrapper.style.width = `${firstRect.width}px`;
                wrapper.style.height = `${lastRect.bottom - firstRect.top}px`;
                wrapper.style.pointerEvents = 'none';

                document.body.appendChild(wrapper);
                targetEl = wrapper;

                // Marquer pour nettoyage ultérieur
                this._tempWrapper = wrapper;
            }
        }

        // Positionner le spotlight
        this.positionSpotlight(targetEl);

        // Créer et afficher le tooltip
        this.createTooltip(step, targetEl);

        // Sauvegarder la progression
        this.saveToStorage(STORAGE_KEYS.STEP, stepIndex);
    }

    /**
     * Positionne le spotlight sur l'élément cible
     * @param {HTMLElement} targetEl - Élément à mettre en valeur
     */
    positionSpotlight(targetEl) {
        const rect = targetEl.getBoundingClientRect();
        const padding = 8;

        this.spotlight.style.top = `${rect.top - padding + window.scrollY}px`;
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
                    <i class="bi ${step.icon}"></i>
                </div>
                <h6 class="onboarding-tooltip-title">${step.title}</h6>
            </div>
            <div class="onboarding-tooltip-content">
                ${step.content}
            </div>
            <div class="onboarding-tooltip-footer">
                <div class="onboarding-progress">
                    ${this.renderProgressDots(currentStepNum, totalSteps)}
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

        // Positionner le tooltip
        this.positionTooltip(targetEl);

        // Activer l'affichage
        setTimeout(() => {
            this.tooltip.classList.add("active");
        }, 100);

        // Gérer les événements
        const skipBtn = this.tooltip.querySelector('[data-action="skip"]');
        const nextBtn = this.tooltip.querySelector('[data-action="next"]');

        skipBtn.addEventListener("click", () => this.skipTour("tooltip"));
        nextBtn.addEventListener("click", () => this.nextStep());
    }

    /**
     * Positionne le tooltip par rapport à l'élément cible
     * Utilise position fixed pour rester dans le viewport sans scroll
     * @param {HTMLElement} targetEl - Élément cible
     */
    positionTooltip(targetEl) {
        const rect = targetEl.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;
        const minTopMargin = 20; // Margin minimum en haut
        const minBottomMargin = 20; // Margin minimum en bas
        const spacing = 20; // Espacement entre élément et tooltip

        let position = "bottom"; // Position par défaut

        // Cas spécial : sidebar-navigation (wrapper temporaire) -> centrage vertical
        const isSidebarNav = targetEl.id === 'onboarding-nav-wrapper';

        if (isSidebarNav) {
            // Centrer verticalement le tooltip par rapport à l'élément
            const tooltipHeight = tooltipRect.height || 200;
            const centerY = rect.top + rect.height / 2;
            const topPos = Math.max(
                minTopMargin,
                Math.min(
                    centerY - tooltipHeight / 2,
                    viewportHeight - tooltipHeight - minBottomMargin
                )
            );

            // Nettoyer les anciennes classes de direction
            this.tooltip.classList.remove('position-top', 'position-bottom', 'position-left', 'position-right');
            this.tooltip.classList.add('position-right'); // Flèche à gauche

            this.tooltip.style.top = `${topPos}px`;
            this.tooltip.style.left = `${rect.right + spacing}px`;
            this.tooltip.style.transform = "translateY(0)";

            console.log(`[DAP] Sidebar nav: centrage vertical à ${topPos}px`);
        } else {
            // Déterminer la meilleure position (logique normale)
            const spaceBelow = viewportHeight - rect.bottom - minBottomMargin;
            const spaceAbove = rect.top - minTopMargin;
            const tooltipHeight = tooltipRect.height || 200;

            // Choisir position selon espace disponible
            if (spaceBelow < tooltipHeight + spacing && spaceAbove > tooltipHeight + spacing) {
                position = "top";
            }

            // Nettoyer les anciennes classes de direction
            this.tooltip.classList.remove('position-top', 'position-bottom', 'position-left', 'position-right');

            // Appliquer la classe de direction (correspond à la position du tooltip)
            this.tooltip.classList.add(`position-${position}`);
            console.log(`[DAP] Position initiale: ${position}, classe ajoutée: position-${position}`);

            if (position === "bottom") {
                // Positionner en dessous, mais limiter pour ne pas dépasser viewport
                const topPos = Math.min(
                    rect.bottom + spacing,
                    viewportHeight - tooltipHeight - minBottomMargin
                );
                this.tooltip.style.top = `${topPos}px`;
                this.tooltip.style.left = `${rect.left + rect.width / 2}px`;
                this.tooltip.style.transform = "translateX(-50%)";
            } else if (position === "top") {
                // Positionner au-dessus
                const topPos = Math.max(
                    minTopMargin,
                    rect.top - tooltipHeight - spacing
                );
                this.tooltip.style.top = `${topPos}px`;
                this.tooltip.style.left = `${rect.left + rect.width / 2}px`;
                this.tooltip.style.transform = "translateX(-50%)";
            }
        }

        // Stocker la position initiale de l'élément cible pour détection ultérieure
        this.tooltip.dataset.targetLeft = rect.left;
        this.tooltip.dataset.targetCenterX = rect.left + rect.width / 2;

        // Ajuster si déborde à gauche ou droite (avec seuil de tolérance)
        // Sauf pour sidebar-navigation qui a déjà un positionnement personnalisé
        if (!isSidebarNav) {
            setTimeout(() => {
                const finalRect = this.tooltip.getBoundingClientRect();
                const margin = 20;
                const sidebarWidth = 250; // Largeur approximative de la sidebar
                const leftBoundary = sidebarWidth + margin; // Ne pas déborder sous la sidebar
                const targetCenterX = parseFloat(this.tooltip.dataset.targetCenterX);
                let wasRepositioned = false;

            // Calculer le débordement
            const overflowLeft = leftBoundary - finalRect.left;
            const overflowRight = finalRect.right - (viewportWidth - margin);
            const repositionThreshold = 50; // Ne repositionner que si débordement > 50px

            // Vérifier débordement significatif à gauche (sous la sidebar)
            if (overflowLeft > repositionThreshold) {
                this.tooltip.style.left = `${leftBoundary}px`;
                this.tooltip.style.transform = "translateX(0)";
                wasRepositioned = true;
                console.log(`[DAP] Repositionné à gauche (débordement: ${overflowLeft.toFixed(0)}px)`);
            }
            // Vérifier débordement significatif à droite
            else if (overflowRight > repositionThreshold) {
                this.tooltip.style.left = `${viewportWidth - finalRect.width - margin}px`;
                this.tooltip.style.transform = "translateX(0)";
                wasRepositioned = true;
                console.log(`[DAP] Repositionné à droite (débordement: ${overflowRight.toFixed(0)}px)`);
            }

            // Si repositionné très loin de l'élément cible (> 150px), changer vers flèche horizontale
            if (wasRepositioned) {
                const tooltipCenterX = finalRect.left + finalRect.width / 2;
                const distanceFromTarget = Math.abs(tooltipCenterX - targetCenterX);
                console.log(`[DAP] Repositionné: distance=${distanceFromTarget.toFixed(0)}px, targetX=${targetCenterX.toFixed(0)}, tooltipX=${tooltipCenterX.toFixed(0)}`);

                // Seulement si très décalé (> 150px), changer pour flèche horizontale
                if (distanceFromTarget > 150) {
                    this.tooltip.classList.remove('position-top', 'position-bottom', 'position-left', 'position-right');

                    if (targetCenterX < tooltipCenterX) {
                        // L'élément cible est à gauche du tooltip → flèche à gauche du tooltip
                        // position-right = flèche à gauche (left: -16px) pointant vers la droite →
                        this.tooltip.classList.add('position-right');
                        console.log(`[DAP] Flèche placée à gauche du tooltip (position-right)`);
                    } else {
                        // L'élément cible est à droite du tooltip → flèche à droite du tooltip
                        // position-left = flèche à droite (right: -16px) pointant vers la gauche ←
                        this.tooltip.classList.add('position-left');
                        console.log(`[DAP] Flèche placée à droite du tooltip (position-left)`);
                    }
                } else {
                    console.log(`[DAP] Distance < 150px, flèche verticale conservée`);
                }
                // Sinon, garder la direction verticale (top/bottom) déjà définie ligne 349
            }

            // Vérification finale : le tooltip doit être visible verticalement
            const updatedRect = this.tooltip.getBoundingClientRect();
            if (updatedRect.top < minTopMargin) {
                this.tooltip.style.top = `${minTopMargin}px`;
            }
            if (updatedRect.bottom > viewportHeight - minBottomMargin) {
                this.tooltip.style.top = `${viewportHeight - updatedRect.height - minBottomMargin}px`;
            }
            }, 10);
        } // Fin du if (!isSidebarNav)
    }

    /**
     * Génère le HTML des progress dots
     * @param {number} current - Étape actuelle
     * @param {number} total - Nombre total d'étapes
     * @returns {string} HTML des dots
     */
    renderProgressDots(current, total) {
        let html = "";
        for (let i = 1; i <= total; i++) {
            let className = "onboarding-progress-dot";
            if (i === current) {
                className += " active";
            } else if (i < current) {
                className += " completed";
            }
            html += `<span class="${className}"></span>`;
        }
        return html;
    }

    /**
     * Passe à l'étape suivante
     */
    nextStep() {
        this.currentStep++;
        this.showStep(this.currentStep);
    }

    /**
     * Saute le tour (utilisateur clique "Passer")
     * @param {string} source - Source du skip (welcome_modal, tooltip)
     */
    skipTour(source) {
        console.log(`[OnboardingDAP] Tour sauté depuis: ${source}`);

        // Marquer comme sauté
        this.saveToStorage(STORAGE_KEYS.SKIPPED_AT, Date.now());
        this.saveToStorage(STORAGE_KEYS.COMPLETED, true);

        // Nettoyer l'UI
        this.cleanup();
    }

    /**
     * Termine le tour avec succès
     */
    completeTour() {
        console.log("[OnboardingDAP] Tour terminé avec succès");

        // Marquer comme complété
        this.saveToStorage(STORAGE_KEYS.COMPLETED, true);

        // Nettoyer l'UI
        this.cleanup();

        // Optionnel : afficher message de félicitation
        this.showCompletionMessage();
    }

    /**
     * Affiche un message de félicitation à la fin du tour
     */
    showCompletionMessage() {
        // Créer une petite notification toast en haut à droite
        const toast = document.createElement("div");
        toast.className = "position-fixed top-0 end-0 p-3";
        toast.style.zIndex = "11000";
        toast.style.marginTop = "80px"; // En dessous du header
        toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <strong class="me-auto">Tour terminé !</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    Vous êtes prêt à créer votre première campagne marketing.
                </div>
            </div>
        `;

        document.body.appendChild(toast);

        // Gérer la fermeture manuelle
        const closeBtn = toast.querySelector('.btn-close');
        closeBtn.addEventListener('click', () => {
            toast.remove();
        });

        // Supprimer après 5 secondes
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    /**
     * Nettoie tous les éléments d'UI du tour
     */
    cleanup() {
        this.isActive = false;

        // Supprimer overlay
        if (this.overlay) {
            this.overlay.classList.remove("active");
            setTimeout(() => {
                this.overlay.remove();
                this.overlay = null;
            }, 300);
        }

        // Supprimer spotlight
        if (this.spotlight) {
            this.spotlight.remove();
            this.spotlight = null;
        }

        // Supprimer tooltip
        if (this.tooltip) {
            this.tooltip.classList.remove("active");
            setTimeout(() => {
                this.tooltip.remove();
                this.tooltip = null;
            }, 300);
        }

        // Supprimer wrapper temporaire (sidebar-navigation)
        if (this._tempWrapper) {
            this._tempWrapper.remove();
            this._tempWrapper = null;
        }
    }

    /**
     * Initialise le bouton d'aide inline (déjà dans le HTML)
     */
    createHelpButton() {
        // Chercher le bouton inline dans le HTML
        this.helpButton = document.getElementById("helpButtonInline");

        if (!this.helpButton) {
            console.warn("[OnboardingDAP] Bouton d'aide #helpButtonInline introuvable dans le HTML");
            return;
        }

        // Gérer le clic
        this.helpButton.addEventListener("click", () => {
            console.log("[OnboardingDAP] Help button cliqué - Relance du tour");

            // Réinitialiser l'état
            this.currentStep = 1;
            this.removeFromStorage(STORAGE_KEYS.COMPLETED);
            this.removeFromStorage(STORAGE_KEYS.SKIPPED_AT);

            // Redémarrer le tour
            this.startTour();
        });
    }

    /**
     * Initialise les tooltips Bootstrap
     */
    initTooltips() {
        const tooltipTriggerList = document.querySelectorAll(
            '[data-bs-toggle="tooltip"]'
        );
        [...tooltipTriggerList].map(
            (tooltipTriggerEl) =>
                new bootstrap.Tooltip(tooltipTriggerEl, {
                    trigger: "hover focus",
                    boundary: "window",
                    customClass: "onboarding-tooltip",
                })
        );
    }

    /**
     * Sauvegarde une valeur dans localStorage
     * @param {string} key - Clé
     * @param {*} value - Valeur
     */
    saveToStorage(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error("[OnboardingDAP] Erreur localStorage:", e);
        }
    }

    /**
     * Récupère une valeur du localStorage
     * @param {string} key - Clé
     * @returns {*} Valeur ou null
     */
    getFromStorage(key) {
        try {
            const value = localStorage.getItem(key);
            return value ? JSON.parse(value) : null;
        } catch (e) {
            console.error("[OnboardingDAP] Erreur localStorage:", e);
            return null;
        }
    }

    /**
     * Supprime une valeur du localStorage
     * @param {string} key - Clé
     */
    removeFromStorage(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            console.error("[OnboardingDAP] Erreur localStorage:", e);
        }
    }
}

/**
 * Fonction d'initialisation exportée
 * Utilisée dans main.js
 * @export
 */
export function initOnboardingDAP() {
    const onboarding = new OnboardingDAP();
    onboarding.init();
}
