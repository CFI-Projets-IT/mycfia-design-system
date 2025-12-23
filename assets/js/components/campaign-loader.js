/**
 * Campaign Loader Component
 * Gestion des loaders de processus asynchrones avec messages dynamiques
 */

/**
 * Messages de progression pour enrichissement IA
 */
const ENRICHMENT_MESSAGES = [
    'Analyse de votre site web en cours...',
    'Extraction des informations clés...',
    'Analyse de la marque et de l\'identité visuelle...',
    'Détection des mots-clés Google Ads...',
    'Génération des recommandations stratégiques...',
    'Finalisation de l\'enrichissement...'
];

/**
 * Tips marketing pendant le chargement
 */
const MARKETING_TIPS = [
    {
        title: 'Ciblage Précis',
        text: 'Une audience bien définie améliore le ROI de vos campagnes de 200% en moyenne.'
    },
    {
        title: 'Contenu Personnalisé',
        text: 'Les campagnes personnalisées génèrent 6x plus d\'engagement qu\'un contenu générique.'
    },
    {
        title: 'Multi-Canal',
        text: 'Les campagnes utilisant 3+ canaux ont un taux de conversion 287% supérieur.'
    },
    {
        title: 'A/B Testing',
        text: 'Tester plusieurs variations de contenu peut augmenter vos conversions de 49%.'
    },
    {
        title: 'Timing Optimal',
        text: 'Envoyer vos messages au bon moment augmente l\'engagement de 73%.'
    }
];

/**
 * Simule un processus de chargement avec progression
 * @param {Object} options - Options de configuration
 * @param {number} options.duration - Durée totale en ms (défaut: 15000)
 * @param {string} options.redirectUrl - URL de redirection après chargement
 * @param {Array} options.messages - Messages personnalisés (optionnel)
 */
export function simulateLoading(options = {}) {
    const {
        duration = 15000,
        redirectUrl = 'step1_review.html',
        messages = ENRICHMENT_MESSAGES
    } = options;

    const progressBar = document.querySelector('.progress-bar');
    const statusMessage = document.querySelector('.status-message');
    const progressText = document.querySelector('.progress-text');
    const phaseDots = document.querySelectorAll('.phase-dot');

    let progress = 0;
    const interval = 100; // Update every 100ms
    const totalSteps = duration / interval;
    const increment = 100 / totalSteps;

    let currentMessageIndex = 0;
    const messageInterval = duration / messages.length;

    // Initial state
    if (statusMessage) {
        statusMessage.textContent = messages[0];
    }

    // Update progress
    const progressInterval = setInterval(() => {
        progress += increment;

        if (progress >= 100) {
            progress = 100;
            clearInterval(progressInterval);

            // Redirect after completion
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 500);
        }

        // Update progress bar
        // TODO: Remplacer par classe CSS lors de l'intégration Twig
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }

        // Update progress text
        if (progressText) {
            progressText.textContent = `${Math.round(progress)}% complété`;
        }

        // Update message with fade effect
        // TODO: Utiliser classes CSS 'fade-out'/'fade-in' dans composant Twig
        const expectedMessageIndex = Math.floor((progress / 100) * messages.length);
        if (expectedMessageIndex !== currentMessageIndex && expectedMessageIndex < messages.length) {
            currentMessageIndex = expectedMessageIndex;
            if (statusMessage) {
                statusMessage.style.opacity = '0';
                setTimeout(() => {
                    statusMessage.textContent = messages[currentMessageIndex];
                    statusMessage.style.opacity = '1';
                }, 150);
            }
        }

        // Update phase dots
        if (phaseDots.length > 0) {
            const activePhase = Math.floor((progress / 100) * phaseDots.length);
            phaseDots.forEach((dot, index) => {
                dot.classList.remove('active');
                if (index < activePhase) {
                    dot.classList.add('completed');
                } else if (index === activePhase) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('completed');
                }
            });
        }
    }, interval);
}

/**
 * Affiche un tip aléatoire
 */
function displayRandomTip() {
    const tipsTitle = document.querySelector('.tips-title');
    const tipsText = document.querySelector('.tips-text');

    if (tipsTitle && tipsText) {
        const randomTip = MARKETING_TIPS[Math.floor(Math.random() * MARKETING_TIPS.length)];
        tipsTitle.textContent = randomTip.title;
        tipsText.textContent = randomTip.text;
    }
}

/**
 * Change le tip toutes les 5 secondes
 */
function rotateTips() {
    displayRandomTip();
    setInterval(displayRandomTip, 5000);
}

/**
 * Initialise le loader enrichissement
 */
function initEnrichmentLoader() {
    // Display initial tip
    displayRandomTip();

    // Rotate tips every 5 seconds
    setInterval(displayRandomTip, 5000);

    // Start loading simulation
    simulateLoading({
        duration: 15000,
        redirectUrl: 'step1_review.html',
        messages: ENRICHMENT_MESSAGES
    });
}

/**
 * Initialise le loader personas
 */
function initPersonaLoader() {
    const personaMessages = [
        "Analyse de votre audience cible...",
        "Génération des profils personas...",
        "Calcul des scores de qualité...",
        "Enrichissement des comportements...",
        "Finalisation des personas..."
    ];

    displayRandomTip();
    setInterval(displayRandomTip, 5000);

    simulateLoading({
        duration: 12000,
        redirectUrl: 'step2_select.html',
        messages: personaMessages
    });
}

/**
 * Initialise le loader concurrents
 */
function initCompetitorLoader() {
    const competitorMessages = [
        "Recherche de concurrents dans votre secteur...",
        "Analyse des sites web concurrents...",
        "Détection des stratégies marketing...",
        "Calcul des scores d'alignement...",
        "Finalisation de l'analyse concurrentielle..."
    ];

    displayRandomTip();
    setInterval(displayRandomTip, 5000);

    simulateLoading({
        duration: 10000,
        redirectUrl: 'step3_validate.html',
        messages: competitorMessages
    });
}

/**
 * Initialise le loader stratégie
 */
function initStrategyLoader() {
    const strategyMessages = [
        "Analyse de votre positionnement...",
        "Optimisation de l'allocation budgétaire...",
        "Génération des tactiques marketing...",
        "Définition des KPIs et objectifs...",
        "Finalisation de la stratégie..."
    ];

    displayRandomTip();
    setInterval(displayRandomTip, 5000);

    simulateLoading({
        duration: 13000,
        redirectUrl: 'step4_result.html',
        messages: strategyMessages
    });
}

/**
 * Initialise le loader assets
 */
function initAssetLoader() {
    const assetMessages = [
        "Préparation des templates...",
        "Génération des contenus LinkedIn...",
        "Génération des contenus Google Ads...",
        "Génération des contenus réseaux sociaux...",
        "Génération des contenus email...",
        "Finalisation des assets..."
    ];

    displayRandomTip();
    setInterval(displayRandomTip, 5000);

    simulateLoading({
        duration: 18000,
        redirectUrl: 'step5_validate.html',
        messages: assetMessages
    });
}

// Auto-detect loader type from URL and initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoader);
} else {
    initLoader();
}

function initLoader() {
    const path = window.location.pathname;

    if (path.includes('step1_loading')) {
        initEnrichmentLoader();
    } else if (path.includes('step2_loading')) {
        initPersonaLoader();
    } else if (path.includes('step3_loading')) {
        initCompetitorLoader();
    } else if (path.includes('step4_loading')) {
        initStrategyLoader();
    } else if (path.includes('step5_loading')) {
        initAssetLoader();
    }
}

// TODO: Supprimer ces expositions globales lors de l'intégration Twig
// Le loader sera géré via Stimulus controller
// window.simulateLoading = simulateLoading;
// window.initEnrichmentLoader = initEnrichmentLoader;
// window.initPersonaLoader = initPersonaLoader;
// window.initCompetitorLoader = initCompetitorLoader;
// window.initStrategyLoader = initStrategyLoader;
// window.initAssetLoader = initAssetLoader;
