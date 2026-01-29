/**
 * myCFiA Design System - Digital Adoption Platform (DAP) Onboarding - Campaign Show
 *
 * Système d'onboarding guidé pour la page vue d'ensemble campagne (Campaign Show)
 * - Welcome modal (première visite)
 * - Tour guidé avec spotlight et tooltips
 * - Help button pour relancer le tour
 * - Persistence via LocalStorage
 *
 * @module OnboardingDAPCampaignShow
 * @requires Bootstrap 5 (pour modal et tooltips)
 */

/**
 * Configuration des étapes du tour guidé Campaign Show
 * @const {Array<Object>}
 */
const ONBOARDING_STEPS_CAMPAIGN_SHOW = [
    {
        id: "welcome",
        type: "modal",
        target: null,
    },
    {
        id: "campaign-header",
        type: "hotspot",
        target: ".campaign-show-header",
        title: "En-tête de la campagne",
        content: "Vue d'ensemble rapide de votre campagne :<br>• <strong>Titre</strong> : \"Lancement Produit Q1 2025\"<br>• <strong>Badge Active</strong> : statut en temps réel<br>• <strong>Informations clés</strong> : Formation professionnelle • Créée le 15 Jan 2025 • Durée 90 jours<br>• <strong>Actions rapides</strong> : Planifier, Éditer, Pause, Dupliquer, Exporter, Archiver<br><br><i class='bi bi-pencil text-primary'></i> Toutes les actions de gestion de campagne sont accessibles directement ici.",
        icon: "bi-megaphone-fill",
    },
    {
        id: "kpi-cards",
        type: "hotspot",
        target: ".campaign-show-kpi-cards",
        title: "KPIs principaux en temps réel",
        content: "4 métriques clés de performance mises à jour en temps réel :<br>• <strong>Budget</strong> : 32,450€ dépensé / 50,000€ (65%) - progression visuelle<br>• <strong>Leads qualifiés</strong> : 387 générés (+24%) - CPL: 83,85€<br>• <strong>ROI</strong> : 4.2x (+18%) - Revenu: 136,290€<br>• <strong>Taux de conversion</strong> : 18.2% (objectif: 18%) - 70 inscrits<br><br><i class='bi bi-graph-up-arrow text-success'></i> Les badges colorés indiquent la progression par rapport aux objectifs.",
        icon: "bi-bar-chart-fill",
    },
    {
        id: "planning-overview",
        type: "hotspot",
        target: ".campaign-show-planning-card",
        title: "Planification des publications",
        content: "Résumé complet de votre planning de publications :<br>• <strong>8/62 planifiées</strong> : progression globale<br>• <strong>3 cette semaine</strong>, <strong>8 ce mois</strong>, <strong>54 non planifiées</strong><br>• <strong>Statut visuel</strong> : badge vert avec icône calendrier<br>• <strong>Prochaines publications</strong> : LinkedIn (dans 2h), Google Ads (demain), Instagram (dans 3 jours)<br><br><i class='bi bi-calendar-plus text-success'></i> Cliquez sur \"Continuer la planification\" pour planifier les 54 assets restants.",
        icon: "bi-calendar-check",
    },
    {
        id: "performance-chart",
        type: "hotspot",
        target: ".campaign-show-performance-chart",
        title: "Évolution des performances",
        content: "Graphique d'analyse temporelle (à intégrer avec Chart.js) :<br>• <strong>Filtres temporels</strong> : 7 jours, 30 jours, Tout<br>• <strong>Métriques affichées</strong> : évolution leads, conversions, dépenses<br>• <strong>Tendances visuelles</strong> : courbes de progression dans le temps<br><br><i class='bi bi-info-circle text-primary'></i> Ce graphique sera rempli avec des données réelles via Chart.js pour visualiser les tendances.",
        icon: "bi-graph-up",
    },
    {
        id: "performance-by-channel",
        type: "hotspot",
        target: ".campaign-show-channel-performance",
        title: "Performance par canal (3 canaux)",
        content: "Détail des performances par canal marketing :<br><br><strong>LinkedIn</strong> (+32%) : 12,000€ budget, 7,890€ dépensé • 142 leads • CPL: 55,56€ • CTR: 4.2% • ROI: 5.1x<br><br><strong>GoogleAds</strong> (+28%) : 18,000€ budget, 11,240€ dépensé • 168 leads • CPL: 66,90€ • CTR: 3.8% • ROI: 4.6x<br><br><strong>Social Media</strong> (+12%) : 8,000€ budget, 5,120€ dépensé • 77 leads • CPL: 66,49€ • CTR: 2.9% • ROI: 3.2x<br><br><i class='bi bi-trophy text-warning'></i> LinkedIn est le canal le plus performant (ROI 5.1x).",
        icon: "bi-layers",
    },
    {
        id: "assets-deployed",
        type: "hotspot",
        target: ".campaign-show-assets",
        title: "Assets déployés (62 total)",
        content: "Aperçu des assets marketing déployés :<br>• <strong>62 assets au total</strong> sur tous les canaux<br>• <strong>4 exemples affichés</strong> : LinkedIn Post (4.8K vues, 187 clics), Google Ad (CTR 4.1%, QS 8/10), Email (Open rate 32.4%, 892 opens), Article (1.2K vues, 5m 24s lecture)<br>• <strong>Métriques temps réel</strong> : vues, clics, taux d'engagement, CTR<br><br><i class='bi bi-images text-primary'></i> Cliquez sur \"Voir tous les assets\" pour accéder au détail complet des 62 assets.",
        icon: "bi-images",
    },
    {
        id: "campaign-strategy",
        type: "hotspot",
        target: ".campaign-show-strategy",
        title: "Stratégie de campagne",
        content: "Rappel de votre stratégie définie lors de la création :<br><br><strong>3 Personas ciblés :</strong><br>• Sophie Martin - Jeune professionnelle (Score: 92%)<br>• Marc Dubois - Cadre senior (Score: 89%)<br>• Claire Rousseau - En reconversion (Score: 85%)<br><br><strong>3 Concurrents analysés :</strong><br>• AFPA - Direct 94%<br>• CEGOS - Important 82%<br>• EFE Formation - Important 79%<br><br><i class='bi bi-bullseye text-primary'></i> Ces données stratégiques guident la génération des assets et le ciblage des campagnes.",
        icon: "bi-bullseye",
    },
];

/**
 * LocalStorage keys utilisés
 * @const {Object}
 */
const STORAGE_KEYS_CAMPAIGN_SHOW = {
    COMPLETED: "mycfia_onboarding_campaign_show_completed",
    STEP: "mycfia_onboarding_campaign_show_step",
    SKIPPED_AT: "mycfia_onboarding_campaign_show_skipped_at",
    TOOLTIP_DISMISSED: "mycfia_onboarding_campaign_show_tooltip_dismissed",
};

/**
 * Classe principale gérant l'onboarding DAP Campaign Show
 * @class OnboardingDAPCampaignShow
 */
class OnboardingDAPCampaignShow {
    /**
     * Initialise l'instance OnboardingDAPCampaignShow
     * @constructor
     */
    constructor() {
        this.steps = ONBOARDING_STEPS_CAMPAIGN_SHOW;
        this.currentStep = 0;
        this.isActive = false;
        this.overlay = null;
        this.spotlight = null;
        this.tooltip = null;
        this._scrollPosition = 0;
    }

    /**
     * Initialise le système d'onboarding
     * - Vérifie si c'est la première visite
     * - Affiche le welcome modal si nécessaire
     * - Initialise le bouton d'aide
     */
    init() {
        console.log("[OnboardingDAPCampaignShow] Initialisation...");

        // Vérifier si déjà complété
        const isCompleted = this.getFromStorage(STORAGE_KEYS_CAMPAIGN_SHOW.COMPLETED);

        if (!isCompleted) {
            // Première visite : afficher le modal
            setTimeout(() => {
                this.showWelcomeModal();
            }, 1000);
        }

        // Initialiser le bouton d'aide (toujours disponible)
        this.initHelpButton();

        console.log("[OnboardingDAPCampaignShow] Initialisé avec succès");
    }

    /**
     * Affiche le modal de bienvenue
     */
    showWelcomeModal() {
        const modalEl = document.getElementById("onboardingWelcomeModalCampaignShow");
        if (!modalEl) {
            console.warn(
                "[OnboardingDAPCampaignShow] Modal welcome introuvable"
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
        const startBtn = document.getElementById("startOnboardingTourCampaignShow");
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
        console.log("[OnboardingDAPCampaignShow] Démarrage du tour guidé");

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
                `[OnboardingDAPCampaignShow] Élément cible "${step.target}" introuvable, passage à l'étape suivante`
            );
            this.showStep(stepIndex + 1);
            return;
        }

        // Mettre à jour currentStep pour refléter l'étape actuellement affichée
        this.currentStep = stepIndex;

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

        console.log(`[DAP CampaignShow] Étape "${step.id}":`, {
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
            console.log(`[DAP CampaignShow] Scroll nécessaire pour "${step.id}" (${visibilityRatio.toFixed(0)}% visible)`);

            // Débloquer temporairement le scroll
            const wasBlocked = document.body.classList.contains('onboarding-active');
            if (wasBlocked) {
                console.log('[DAP CampaignShow] Body bloqué ? true');
                document.body.classList.remove('onboarding-active');
                document.body.style.top = '';
                if (this._scrollPosition !== undefined) {
                    window.scrollTo(0, this._scrollPosition);
                    console.log(`[DAP CampaignShow] Scroll débloqué, position restaurée à ${this._scrollPosition}`);
                }
            }

            // Petit délai pour que le déblocage prenne effet
            setTimeout(() => {
                console.log(`[DAP CampaignShow] Lancement scrollIntoView pour "${step.id}"`);
                targetEl.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'center'
                });

                // Attendre que le scroll se termine (500ms)
                setTimeout(() => {
                    console.log('[DAP CampaignShow] Scroll terminé, reblocage du body');
                    // Rebloquer le scroll
                    if (wasBlocked) {
                        this._scrollPosition = window.scrollY;
                        document.body.classList.add('onboarding-active');
                        document.body.style.top = `-${this._scrollPosition}px`;
                        console.log(`[DAP CampaignShow] Body rebloqué à position ${this._scrollPosition}`);
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
        console.log(`[DAP CampaignShow] Élément "${step.id}" déjà visible (${visibilityRatio.toFixed(0)}%)`);

        // Positionner le spotlight (si déjà visible)
        this.positionSpotlight(targetEl);

        // Créer et afficher le tooltip
        this.createTooltip(step, targetEl);

        // Sauvegarder la progression
        this.saveToStorage(STORAGE_KEYS_CAMPAIGN_SHOW.STEP, stepIndex);
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
     * @param {HTMLElement} targetEl - Élément cible
     */
    positionTooltip(targetEl) {
        const rect = targetEl.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const minTopMargin = 20;
        const minBottomMargin = 20;
        const spacing = 20;

        const scrollY = document.body.classList.contains('onboarding-active')
            ? (this._scrollPosition || 0)
            : window.scrollY;

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
        this.showStep(this.currentStep + 1);
    }

    /**
     * Saute le tour
     * @param {string} source - Source du skip
     */
    skipTour(source) {
        console.log(`[OnboardingDAPCampaignShow] Tour sauté depuis: ${source}`);
        this.saveToStorage(STORAGE_KEYS_CAMPAIGN_SHOW.SKIPPED_AT, Date.now());
        this.saveToStorage(STORAGE_KEYS_CAMPAIGN_SHOW.COMPLETED, true);
        this.cleanup();
    }

    /**
     * Termine le tour avec succès
     */
    completeTour() {
        console.log("[OnboardingDAPCampaignShow] Tour terminé avec succès");
        this.saveToStorage(STORAGE_KEYS_CAMPAIGN_SHOW.COMPLETED, true);
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
                <p class="mb-0 small">Consultez les métriques en temps réel et continuez la planification des publications.</p>
            </div>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add("show");
        }, 100);

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

        document.body.classList.remove('onboarding-active');
        document.body.style.top = '';
        if (this._scrollPosition !== undefined) {
            window.scrollTo(0, this._scrollPosition);
        }

        if (this.overlay) {
            this.overlay.classList.remove("active");
            setTimeout(() => {
                this.overlay.remove();
                this.overlay = null;
            }, 300);
        }

        if (this.spotlight) {
            this.spotlight.remove();
            this.spotlight = null;
        }

        if (this.tooltip) {
            this.tooltip.classList.remove("active");
            setTimeout(() => {
                this.tooltip.remove();
                this.tooltip = null;
            }, 300);
        }
    }

    /**
     * Initialise le bouton d'aide inline
     */
    initHelpButton() {
        const helpBtn = document.getElementById("restartOnboardingTourCampaignShow");
        if (helpBtn) {
            helpBtn.addEventListener("click", (e) => {
                e.preventDefault();
                console.log("[OnboardingDAPCampaignShow] Relancement du tour via bouton d'aide");

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
     * Sauvegarde une valeur dans localStorage
     * @param {string} key - Clé
     * @param {*} value - Valeur
     */
    saveToStorage(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error("[OnboardingDAPCampaignShow] Erreur localStorage:", e);
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
            console.error("[OnboardingDAPCampaignShow] Erreur localStorage:", e);
            return null;
        }
    }
}

/**
 * Fonction d'initialisation exportée pour main.js
 */
export function initOnboardingDAPCampaignShow() {
    const onboarding = new OnboardingDAPCampaignShow();
    onboarding.init();
}

// Export par défaut
export default OnboardingDAPCampaignShow;
