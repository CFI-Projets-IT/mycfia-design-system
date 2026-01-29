/**
 * myCFiA Design System - Digital Adoption Platform (DAP) Onboarding - Step 8 Validate
 *
 * Système d'onboarding guidé pour la page de validation des assets (Step 8 Validate)
 * - Welcome modal (première visite)
 * - Tour guidé avec spotlight et tooltips
 * - Help button pour relancer le tour
 * - Persistence via LocalStorage
 *
 * @module OnboardingDAPStep8Validate
 * @requires Bootstrap 5 (pour modal et tooltips)
 */

/**
 * Configuration des étapes du tour guidé Step 8 Validate
 * @const {Array<Object>}
 */
const ONBOARDING_STEPS_STEP8_VALIDATE = [
    {
        id: "welcome",
        type: "modal",
        target: null,
    },
    {
        id: "stepper",
        type: "hotspot",
        target: ".campaign-stepper",
        title: "Étape 7/8 : Validation des Assets",
        content: "Vous êtes à l'avant-dernière étape du processus. Cette page vous permet de <strong>valider les 15 assets marketing</strong> générés automatiquement par l'IA avant la planification.",
        icon: "bi-list-ol",
    },
    {
        id: "form-section-header",
        type: "hotspot",
        target: ".form-section-header",
        title: "15 assets générés avec succès",
        content: "L'IA a créé <strong>15 assets marketing</strong> personnalisés basés sur votre stratégie, vos personas et vos canaux sélectionnés.<br><br><i class='bi bi-cursor-fill text-primary'></i> <strong>Cliquez sur un asset</strong> pour voir le reasoning IA détaillé et la preview visuelle.",
        icon: "bi-check-circle",
    },
    {
        id: "asset-table",
        type: "hotspot",
        target: ".asset-table",
        title: "Tableau des assets marketing",
        content: "Ce tableau présente tous les assets générés :<br>• <strong>Type d'asset</strong> : Linkedin, GoogleAds, Facebook, Email, Article, SMS, Courrier<br>• <strong>Variation</strong> : numéro de version (1/2, 2/3, etc.)<br>• <strong>Aperçu du contenu</strong> : extrait du texte généré<br>• <strong>Score IA</strong> : confiance de l'IA (76% à 95%)<br>• <strong>Actions</strong> : checkbox pour valider/rejeter<br><br><i class='bi bi-cursor-fill text-primary'></i> <strong>Cliquez sur une ligne</strong> pour ouvrir le détail complet.",
        icon: "bi-table",
    },
    {
        id: "validation-card",
        type: "hotspot",
        target: ".step8-validation-summary-card",
        title: "Résumé de validation",
        content: "Cette card affiche le <strong>statut global de validation</strong> :<br>• <strong>15 assets validés sur 15</strong> : tous les assets sont cochés<br>• <strong>Tout régénérer</strong> : relancer la génération complète si insatisfait<br><br><i class='bi bi-info-circle text-primary'></i> Vous pouvez aussi régénérer individuellement depuis le détail d'un asset.",
        icon: "bi-clipboard-check",
    },
    {
        id: "speed-dial-fab",
        type: "hotspot",
        target: ".speed-dial-container",
        title: "Actions disponibles",
        content: "Le <strong>Speed Dial</strong> vous propose 2 actions principales :<br>• <i class='bi bi-arrow-clockwise'></i> <strong>Régénérer les assets</strong> - relancer la génération IA complète<br>• <i class='bi bi-calendar-event'></i> <strong>Planifier les publications</strong> - passer à l'étape finale (planning)<br><br><i class='bi bi-check-circle text-success'></i> Validez tous les assets avant de planifier les publications.",
        icon: "bi-three-dots-vertical",
    },
];

/**
 * LocalStorage keys utilisés
 * @const {Object}
 */
const STORAGE_KEYS_STEP8_VALIDATE = {
    COMPLETED: "mycfia_onboarding_step8_validate_completed",
    STEP: "mycfia_onboarding_step8_validate_step",
    SKIPPED_AT: "mycfia_onboarding_step8_validate_skipped_at",
    TOOLTIP_DISMISSED: "mycfia_onboarding_step8_validate_tooltip_dismissed",
};

/**
 * Classe principale gérant l'onboarding DAP Step 8 Validate
 * @class OnboardingDAPStep8Validate
 */
class OnboardingDAPStep8Validate {
    /**
     * Initialise l'instance OnboardingDAPStep8Validate
     * @constructor
     */
    constructor() {
        this.steps = ONBOARDING_STEPS_STEP8_VALIDATE;
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
     * - Vérifie si c'est la première visite
     * - Affiche le welcome modal si nécessaire
     * - Initialise le bouton d'aide
     */
    init() {
        console.log("[OnboardingDAPStep8Validate] Initialisation...");

        // Vérifier si déjà complété
        const isCompleted = this.getFromStorage(STORAGE_KEYS_STEP8_VALIDATE.COMPLETED);

        if (!isCompleted) {
            // Première visite : afficher le modal
            setTimeout(() => {
                this.showWelcomeModal();
            }, 1000);
        }

        // Initialiser le bouton d'aide (toujours disponible)
        this.initHelpButton();

        console.log("[OnboardingDAPStep8Validate] Initialisé avec succès");
    }

    /**
     * Affiche le modal de bienvenue
     */
    showWelcomeModal() {
        const modalEl = document.getElementById("onboardingWelcomeModalStep8Validate");
        if (!modalEl) {
            console.warn(
                "[OnboardingDAPStep8Validate] Modal welcome introuvable"
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
        const startBtn = document.getElementById("startOnboardingTourStep8Validate");
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
     * - Crée l'overlay
     * - Bloque le scroll
     * - Affiche la première étape
     */
    startTour() {
        console.log("[OnboardingDAPStep8Validate] Démarrage du tour guidé");

        this.isActive = true;
        this.currentStep = 1; // Commencer après le modal (step 0 = welcome)

        // Bloquer le scroll pendant le tour
        this._scrollPosition = window.scrollY;
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

            // Activer avec transition
            setTimeout(() => {
                this.overlay.classList.add("active");
            }, 10);
        }

        // Créer le spotlight si n'existe pas
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

        // Supprimer tooltip existant
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }

        // Trouver l'élément cible
        const targetEl = document.querySelector(step.target);
        if (!targetEl) {
            console.warn(
                `[OnboardingDAPStep8Validate] Élément cible "${step.target}" introuvable, passage à l'étape suivante`
            );
            this.showStep(stepIndex + 1);
            return;
        }

        // Mettre à jour currentStep pour refléter l'étape actuellement affichée
        this.currentStep = stepIndex;

        // Ouvrir le Speed Dial FAB si on arrive à cette étape
        if (step.id === "speed-dial-fab") {
            this.openSpeedDial();
        }

        // Scroll automatique vers l'élément si pas suffisamment visible
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

        console.log(`[DAP Step8Validate] Étape "${step.id}":`, {
            elementTop,
            elementBottom,
            viewportTop,
            viewportBottom,
            visibleHeight,
            elementHeight,
            visibilityRatio: visibilityRatio.toFixed(0) + '%',
            bodyBlocked,
            currentScroll
        });

        if (!isVisible) {
            console.log(`[DAP Step8Validate] Scroll nécessaire pour "${step.id}" (${visibilityRatio.toFixed(0)}% visible)`);

            // Débloquer temporairement le scroll
            const wasBlocked = document.body.classList.contains('onboarding-active');
            if (wasBlocked) {
                console.log('[DAP Step8Validate] Body bloqué ? true');
                document.body.classList.remove('onboarding-active');
                document.body.style.top = '';
                if (this._scrollPosition !== undefined) {
                    window.scrollTo(0, this._scrollPosition);
                    console.log(`[DAP Step8Validate] Scroll débloqué, position restaurée à ${this._scrollPosition}`);
                }
            }

            // Petit délai pour que le déblocage prenne effet
            setTimeout(() => {
                console.log(`[DAP Step8Validate] Lancement scrollIntoView pour "${step.id}"`);
                targetEl.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'center'
                });

                // Attendre que le scroll se termine (500ms)
                setTimeout(() => {
                    console.log('[DAP Step8Validate] Scroll terminé, reblocage du body');
                    // Rebloquer le scroll
                    if (wasBlocked) {
                        this._scrollPosition = window.scrollY;
                        document.body.classList.add('onboarding-active');
                        document.body.style.top = `-${this._scrollPosition}px`;
                        console.log(`[DAP Step8Validate] Body rebloqué à position ${this._scrollPosition}`);
                    }

                    // Petit délai pour que le reblocage prenne effet
                    setTimeout(() => {
                        this.positionSpotlight(targetEl);
                        this.createTooltip(step, targetEl);
                    }, 10);
                }, 500);
            }, 50);
            return;
        }
        console.log(`[DAP Step8Validate] Élément "${step.id}" déjà visible (${visibilityRatio.toFixed(0)}%)`);

        // Positionner le spotlight (si déjà visible)
        this.positionSpotlight(targetEl);

        // Créer et afficher le tooltip
        this.createTooltip(step, targetEl);

        // Sauvegarder la progression
        this.saveToStorage(STORAGE_KEYS_STEP8_VALIDATE.STEP, stepIndex);
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

        // Créer le nouveau tooltip
        this.tooltip = document.createElement("div");
        this.tooltip.className = "onboarding-tooltip";

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
            this.tooltip.style.top = `${topPos + scrollY}px`;
            this.tooltip.style.left = `${rect.left + rect.width / 2 + window.scrollX}px`;
            this.tooltip.style.transform = "translateX(-50%)";
        } else if (position === "top") {
            // Positionner au-dessus
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
     * @param {number} current - Étape actuelle
     * @param {number} total - Total d'étapes
     * @returns {string} HTML des points
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

        // Passer à l'étape suivante (showStep() mettra à jour this.currentStep)
        this.showStep(this.currentStep + 1);
    }

    /**
     * Saute le tour (utilisateur clique "Passer")
     * @param {string} source - Source du skip (welcome_modal, tooltip)
     */
    skipTour(source) {
        console.log(`[OnboardingDAPStep8Validate] Tour sauté depuis: ${source}`);

        // Marquer comme sauté
        this.saveToStorage(STORAGE_KEYS_STEP8_VALIDATE.SKIPPED_AT, Date.now());
        this.saveToStorage(STORAGE_KEYS_STEP8_VALIDATE.COMPLETED, true);

        // Fermer le Speed Dial si ouvert
        this.closeSpeedDial();

        // Nettoyer l'UI
        this.cleanup();
    }

    /**
     * Termine le tour avec succès
     */
    completeTour() {
        console.log("[OnboardingDAPStep8Validate] Tour terminé avec succès");

        // Marquer comme complété
        this.saveToStorage(STORAGE_KEYS_STEP8_VALIDATE.COMPLETED, true);

        // Fermer le Speed Dial si ouvert
        this.closeSpeedDial();

        // Nettoyer l'UI
        this.cleanup();

        // Afficher message de félicitation
        this.showCompletionMessage();
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
            <div>
                <strong>Tour terminé !</strong>
                <p class="mb-0 small">Validez tous les assets, puis planifiez les publications pour finaliser votre campagne.</p>
            </div>
        `;

        document.body.appendChild(toast);

        // Afficher avec animation
        setTimeout(() => {
            toast.classList.add("show");
        }, 100);

        // Supprimer après 5 secondes
        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 5000);
    }

    /**
     * Nettoie tous les éléments d'UI du tour
     */
    cleanup() {
        this.isActive = false;

        // Débloquer le scroll à la fin du tour
        document.body.classList.remove('onboarding-active');
        document.body.style.top = '';
        if (this._scrollPosition !== undefined) {
            window.scrollTo(0, this._scrollPosition);
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
        console.log("[OnboardingDAPStep8Validate] Ouverture du Speed Dial FAB");

        const speedDialContainer = document.querySelector('.speed-dial-container');
        if (!speedDialContainer) {
            console.warn("[OnboardingDAPStep8Validate] Speed Dial container introuvable");
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

        console.log("[OnboardingDAPStep8Validate] Fermeture du Speed Dial FAB");

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
     * Permet de relancer le tour à tout moment
     */
    initHelpButton() {
        const helpBtn = document.getElementById("restartOnboardingTourStep8Validate");
        if (helpBtn) {
            helpBtn.addEventListener("click", (e) => {
                e.preventDefault();
                console.log(
                    "[OnboardingDAPStep8Validate] Relancement du tour via bouton d'aide"
                );

                // Nettoyer l'état existant si le tour est actif
                if (this.isActive) {
                    this.cleanup();
                }

                // Redémarrer le tour
                setTimeout(() => {
                    this.startTour();
                }, 100);
            });
        }
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
            console.error("[OnboardingDAPStep8Validate] Erreur localStorage:", e);
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
            console.error("[OnboardingDAPStep8Validate] Erreur localStorage:", e);
            return null;
        }
    }
}

/**
 * Fonction d'initialisation exportée pour main.js
 */
export function initOnboardingDAPStep8Validate() {
    const onboarding = new OnboardingDAPStep8Validate();
    onboarding.init();
}

// Export par défaut
export default OnboardingDAPStep8Validate;
