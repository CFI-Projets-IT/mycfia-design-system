/**
 * myCFiA Design System - Digital Adoption Platform (DAP) Onboarding - Step 1
 *
 * Système d'onboarding guidé pour la page de création de campagne (Step 1)
 * - Welcome modal (première visite)
 * - Tour guidé avec spotlight et tooltips
 * - Help button pour relancer le tour
 * - Persistence via LocalStorage
 *
 * @module OnboardingDAPStep1
 * @requires Bootstrap 5 (pour modal et tooltips)
 */

/**
 * Configuration des étapes du tour guidé Step 1
 * @const {Array<Object>}
 */
const ONBOARDING_STEPS_STEP1 = [
    {
        id: "welcome",
        type: "modal",
        target: null,
    },
    {
        id: "stepper",
        type: "hotspot",
        target: ".campaign-stepper",
        title: "Visualisez votre progression",
        content:
            "Suivez votre avancement à travers les 8 étapes de création. Vous êtes actuellement à l'étape 1 : définition de votre projet.",
        icon: "bi-list-ol",
    },
    {
        id: "section-info-base",
        type: "hotspot",
        target: ".form-section-delay-1",
        title: "Informations de base",
        content:
            "Renseignez le nom de votre projet, votre entreprise et votre secteur. L'IA utilisera ces données pour personnaliser toutes les recommandations.",
        icon: "bi-info-circle-fill",
    },
    {
        id: "section-objectifs",
        type: "hotspot",
        target: ".form-section-delay-2",
        title: "Objectifs marketing",
        content:
            "Définissez votre objectif principal et détaillez-le avec la méthode SMART. L'IA analysera vos objectifs pour recommander les meilleurs canaux et stratégies.",
        icon: "bi-bullseye",
    },
    {
        id: "section-budget",
        type: "hotspot",
        target: ".form-section-delay-3",
        title: "Budget et Timeline",
        content:
            "Indiquez votre budget global et vos dates de campagne. L'IA optimisera la répartition budgétaire et proposera un planning adapté.",
        icon: "bi-cash-stack",
    },
    {
        id: "speed-dial-fab",
        type: "hotspot",
        target: ".speed-dial-container",
        title: "Lancez l'analyse IA",
        content:
            "Une fois le formulaire complété, cliquez ici pour lancer l'analyse. L'IA va enrichir votre projet avec des insights personnalisés et vous guider vers l'étape 2.",
        icon: "bi-lightning-charge-fill",
    },
];

/**
 * LocalStorage keys utilisés
 * @const {Object}
 */
const STORAGE_KEYS_STEP1 = {
    COMPLETED: "mycfia_onboarding_step1_completed",
    STEP: "mycfia_onboarding_step1_step",
    SKIPPED_AT: "mycfia_onboarding_step1_skipped_at",
    TOOLTIP_DISMISSED: "mycfia_onboarding_step1_tooltip_dismissed",
};

/**
 * Classe principale gérant l'onboarding DAP Step 1
 * @class OnboardingDAPStep1
 */
class OnboardingDAPStep1 {
    /**
     * Initialise l'instance OnboardingDAPStep1
     * @constructor
     */
    constructor() {
        this.currentStep = 0;
        this.steps = ONBOARDING_STEPS_STEP1;
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
        console.log("[OnboardingDAPStep1] Initialisation...");

        // Vérifier si l'utilisateur a déjà complété l'onboarding
        const hasCompleted = this.getFromStorage(STORAGE_KEYS_STEP1.COMPLETED);

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

        console.log("[OnboardingDAPStep1] Initialisé avec succès");
    }

    /**
     * Affiche le modal de bienvenue (bootstrap modal)
     * Gère les événements des boutons "Passer" et "Faire le tour guidé"
     */
    showWelcomeModal() {
        const modalEl = document.getElementById("onboardingWelcomeModalStep1");
        if (!modalEl) {
            console.warn(
                "[OnboardingDAPStep1] Modal #onboardingWelcomeModalStep1 introuvable"
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
        const startBtn = document.getElementById("startOnboardingTourStep1");
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
        console.log("[OnboardingDAPStep1] Démarrage du tour guidé");

        this.isActive = true;
        this.currentStep = 1; // Commencer après le modal (step 0 = welcome)

        // Sauvegarder la position de scroll actuelle
        this._scrollPosition = window.scrollY;

        // Bloquer le scroll du body (UX: seul le DAP contrôle le scroll)
        document.body.classList.add('onboarding-active');
        document.body.style.top = `-${this._scrollPosition}px`;

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

        // Trouver l'élément cible
        const targetEl = document.querySelector(step.target);
        if (!targetEl) {
            console.warn(
                `[OnboardingDAPStep1] Élément cible "${step.target}" introuvable, passage à l'étape suivante`
            );
            this.showStep(stepIndex + 1);
            return;
        }

        // Ouvrir le Speed Dial FAB si on arrive à cette étape
        if (step.id === "speed-dial-fab") {
            this.openSpeedDial();
        }

        // ✅ CORRECTION DAP : Scroll automatique vers l'élément si pas suffisamment visible
        const rect = targetEl.getBoundingClientRect();

        // Vérifier si l'élément est suffisamment visible (au moins 80% dans le viewport)
        const visibleHeight = Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, 0);
        const visibleWidth = Math.min(rect.right, window.innerWidth) - Math.max(rect.left, 0);
        const elementHeight = rect.height;
        const elementWidth = rect.width;

        const visibilityRatio = (visibleHeight / elementHeight) * 100;

        console.log(`[DAP Step1] Étape "${step.id}":`, {
            top: rect.top,
            bottom: rect.bottom,
            height: elementHeight,
            visibleHeight,
            visibilityRatio: visibilityRatio.toFixed(0) + '%',
            viewportHeight: window.innerHeight
        });

        const isVisible = (
            visibleHeight >= elementHeight * 0.8 &&
            visibleWidth >= elementWidth * 0.8 &&
            rect.top >= 0 &&
            rect.bottom <= window.innerHeight
        );

        if (!isVisible) {
            console.log(`[DAP Step1] Scroll nécessaire pour "${step.id}" (${visibilityRatio.toFixed(0)}% visible)`);

            // Débloquer temporairement le scroll pour permettre scrollIntoView
            const wasBlocked = document.body.classList.contains('onboarding-active');
            console.log(`[DAP Step1] Body bloqué ? ${wasBlocked}`);

            if (wasBlocked) {
                document.body.classList.remove('onboarding-active');
                document.body.style.top = '';
                if (this._scrollPosition !== undefined) {
                    window.scrollTo(0, this._scrollPosition);
                }
                console.log(`[DAP Step1] Scroll débloqué, position restaurée à ${this._scrollPosition || 0}`);
            }

            // Petit délai pour que le déblocage prenne effet
            setTimeout(() => {
                console.log(`[DAP Step1] Lancement scrollIntoView pour "${step.id}"`);

                targetEl.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'center'
                });

                // Attendre que le scroll se termine (500ms suffisant)
                setTimeout(() => {
                    console.log(`[DAP Step1] Scroll terminé, reblocage du body`);

                    // Rebloquer le scroll
                    if (wasBlocked) {
                        this._scrollPosition = window.scrollY;
                        document.body.classList.add('onboarding-active');
                        document.body.style.top = `-${this._scrollPosition}px`;
                        console.log(`[DAP Step1] Body rebloqué à position ${this._scrollPosition}`);
                    }

                    // Petit délai pour que le reblocage prenne effet avant de positionner
                    setTimeout(() => {
                        this.positionSpotlight(targetEl);
                        this.createTooltip(step, targetEl);
                    }, 10);
                }, 500);
            }, 50); // Petit délai pour que le déblocage prenne effet

            return;
        }
        console.log(`[DAP Step1] Élément "${step.id}" déjà visible (${visibilityRatio.toFixed(0)}%)`);
        // ✅ FIN DU BLOC

        // Positionner le spotlight (si déjà visible)
        this.positionSpotlight(targetEl);

        // Créer et afficher le tooltip
        this.createTooltip(step, targetEl);

        // Sauvegarder la progression
        this.saveToStorage(STORAGE_KEYS_STEP1.STEP, stepIndex);
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
     * Utilise position absolute avec window.scrollY/scrollX pour suivre l'élément pendant le scroll
     * @param {HTMLElement} targetEl - Élément cible
     */
    positionTooltip(targetEl) {
        const rect = targetEl.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const viewportWidth = window.innerWidth;
        const minTopMargin = 20;
        const minBottomMargin = 20;
        const spacing = 20;

        // Si le body est bloqué, utiliser _scrollPosition au lieu de window.scrollY
        const scrollY = document.body.classList.contains('onboarding-active')
            ? (this._scrollPosition || 0)
            : window.scrollY;

        // Cas spécial pour Speed Dial FAB : positionner à gauche avec plus d'espace
        const isSpeedDialFAB = targetEl.classList.contains('speed-dial-container');

        if (isSpeedDialFAB) {
            // Positionner le tooltip à gauche du Speed Dial
            const tooltipHeight = tooltipRect.height || 200;
            const tooltipWidth = tooltipRect.width || 400;

            // Centrer verticalement par rapport au Speed Dial
            const centerY = rect.top + rect.height / 2;
            const topPos = Math.max(
                minTopMargin,
                Math.min(
                    centerY - tooltipHeight / 2,
                    viewportHeight - tooltipHeight - minBottomMargin
                )
            );

            // Positionner à gauche avec espacement
            const leftPos = rect.left - tooltipWidth - spacing - 20; // +20 pour plus d'espace

            this.tooltip.classList.remove('position-top', 'position-bottom', 'position-left', 'position-right');
            this.tooltip.classList.add('position-left'); // Flèche à droite pointant vers la gauche

            // ✅ CORRECTION DAP : Ajouter scrollY et window.scrollX
            this.tooltip.style.top = `${topPos + scrollY}px`;
            this.tooltip.style.left = `${leftPos + window.scrollX}px`;
            this.tooltip.style.transform = "translateY(0)";

            return; // Sortir, pas besoin du reste du code
        }

        let position = "bottom"; // Position par défaut

        // Déterminer la meilleure position
        const spaceBelow = viewportHeight - rect.bottom - minBottomMargin;
        const spaceAbove = rect.top - minTopMargin;
        const tooltipHeight = tooltipRect.height || 200;

        // Choisir position selon espace disponible
        if (spaceBelow < tooltipHeight + spacing && spaceAbove > tooltipHeight + spacing) {
            position = "top";
        }

        // Nettoyer les anciennes classes de direction
        this.tooltip.classList.remove('position-top', 'position-bottom', 'position-left', 'position-right');

        // Appliquer la classe de direction
        this.tooltip.classList.add(`position-${position}`);

        if (position === "bottom") {
            // Positionner en dessous
            const topPos = Math.min(
                rect.bottom + spacing,
                viewportHeight - tooltipHeight - minBottomMargin
            );
            // ✅ CORRECTION DAP : Ajouter scrollY et window.scrollX
            this.tooltip.style.top = `${topPos + scrollY}px`;
            this.tooltip.style.left = `${rect.left + rect.width / 2 + window.scrollX}px`;
            this.tooltip.style.transform = "translateX(-50%)";
        } else if (position === "top") {
            // Positionner au-dessus
            const topPos = Math.max(
                minTopMargin,
                rect.top - tooltipHeight - spacing
            );
            // ✅ CORRECTION DAP : Ajouter scrollY et window.scrollX
            this.tooltip.style.top = `${topPos + scrollY}px`;
            this.tooltip.style.left = `${rect.left + rect.width / 2 + window.scrollX}px`;
            this.tooltip.style.transform = "translateX(-50%)";
        }

        // Stocker la position initiale
        this.tooltip.dataset.targetCenterX = rect.left + rect.width / 2;

        // Ajuster si déborde à gauche ou droite
        setTimeout(() => {
            const finalRect = this.tooltip.getBoundingClientRect();
            const margin = 20;
            const targetCenterX = parseFloat(this.tooltip.dataset.targetCenterX);
            let wasRepositioned = false;

            // Calculer le débordement
            const overflowLeft = margin - finalRect.left;
            const overflowRight = finalRect.right - (viewportWidth - margin);
            const repositionThreshold = 50;

            // Vérifier débordement à gauche
            if (overflowLeft > repositionThreshold) {
                this.tooltip.style.left = `${margin}px`;
                this.tooltip.style.transform = "translateX(0)";
                wasRepositioned = true;
            }
            // Vérifier débordement à droite
            else if (overflowRight > repositionThreshold) {
                this.tooltip.style.left = `${viewportWidth - finalRect.width - margin}px`;
                this.tooltip.style.transform = "translateX(0)";
                wasRepositioned = true;
            }

            // Si repositionné très loin, changer vers flèche horizontale
            if (wasRepositioned) {
                const tooltipCenterX = finalRect.left + finalRect.width / 2;
                const distanceFromTarget = Math.abs(tooltipCenterX - targetCenterX);

                if (distanceFromTarget > 150) {
                    this.tooltip.classList.remove('position-top', 'position-bottom', 'position-left', 'position-right');

                    if (targetCenterX < tooltipCenterX) {
                        this.tooltip.classList.add('position-right');
                    } else {
                        this.tooltip.classList.add('position-left');
                    }
                }
            }

            // Vérification finale verticale
            const updatedRect = this.tooltip.getBoundingClientRect();
            if (updatedRect.top < minTopMargin) {
                this.tooltip.style.top = `${minTopMargin}px`;
            }
            if (updatedRect.bottom > viewportHeight - minBottomMargin) {
                this.tooltip.style.top = `${viewportHeight - updatedRect.height - minBottomMargin}px`;
            }
        }, 10);
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
        // Fermer le Speed Dial si on était sur l'étape FAB
        const currentStepConfig = this.steps[this.currentStep];
        if (currentStepConfig && currentStepConfig.id === "speed-dial-fab") {
            this.closeSpeedDial();
        }

        this.currentStep++;
        this.showStep(this.currentStep);
    }

    /**
     * Saute le tour (utilisateur clique "Passer")
     * @param {string} source - Source du skip (welcome_modal, tooltip)
     */
    skipTour(source) {
        console.log(`[OnboardingDAPStep1] Tour sauté depuis: ${source}`);

        // Marquer comme sauté
        this.saveToStorage(STORAGE_KEYS_STEP1.SKIPPED_AT, Date.now());
        this.saveToStorage(STORAGE_KEYS_STEP1.COMPLETED, true);

        // Nettoyer l'UI
        this.cleanup();
    }

    /**
     * Termine le tour avec succès
     */
    completeTour() {
        console.log("[OnboardingDAPStep1] Tour terminé avec succès");

        // Marquer comme complété
        this.saveToStorage(STORAGE_KEYS_STEP1.COMPLETED, true);

        // Nettoyer l'UI
        this.cleanup();

        // Afficher message de félicitation
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
        toast.style.marginTop = "80px";
        toast.innerHTML = `
            <div class="toast show" role="alert">
                <div class="toast-header">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <strong class="me-auto">Tour terminé !</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    Vous pouvez maintenant remplir le formulaire et lancer l'analyse IA.
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

        // Fermer le Speed Dial s'il était ouvert
        this.closeSpeedDial();

        // Débloquer le scroll du body et restaurer la position
        document.body.classList.remove('onboarding-active');
        document.body.style.top = '';

        // Restaurer la position de scroll sauvegardée
        if (this._scrollPosition !== undefined) {
            window.scrollTo(0, this._scrollPosition);
            this._scrollPosition = undefined;
        }

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
    }

    /**
     * Ouvre le Speed Dial FAB pour l'étape de démonstration
     */
    openSpeedDial() {
        console.log("[OnboardingDAPStep1] Ouverture du Speed Dial FAB");

        const speedDialContainer = document.querySelector('.speed-dial-container');
        if (!speedDialContainer) {
            console.warn("[OnboardingDAPStep1] Speed Dial container introuvable");
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

        console.log("[OnboardingDAPStep1] Fermeture du Speed Dial FAB");

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
     * Initialise le bouton d'aide inline (déjà dans le HTML)
     */
    createHelpButton() {
        // Chercher le bouton inline dans le HTML
        this.helpButton = document.getElementById("helpButtonInlineStep1");

        if (!this.helpButton) {
            console.warn("[OnboardingDAPStep1] Bouton d'aide #helpButtonInlineStep1 introuvable dans le HTML");
            return;
        }

        // Gérer le clic
        this.helpButton.addEventListener("click", () => {
            console.log("[OnboardingDAPStep1] Help button cliqué - Relance du tour");

            // Réinitialiser l'état
            this.currentStep = 1;
            this.removeFromStorage(STORAGE_KEYS_STEP1.COMPLETED);
            this.removeFromStorage(STORAGE_KEYS_STEP1.SKIPPED_AT);

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
            console.error("[OnboardingDAPStep1] Erreur localStorage:", e);
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
            console.error("[OnboardingDAPStep1] Erreur localStorage:", e);
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
            console.error("[OnboardingDAPStep1] Erreur localStorage:", e);
        }
    }
}

/**
 * Fonction d'initialisation exportée
 * Utilisée dans main.js
 * @export
 */
export function initOnboardingDAPStep1() {
    const onboarding = new OnboardingDAPStep1();
    onboarding.init();
}
