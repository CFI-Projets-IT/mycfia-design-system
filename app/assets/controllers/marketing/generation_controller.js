import { Controller } from '@hotwired/stimulus';

/**
 * Marketing Generation Controller
 * Gère la génération asynchrone (stratégie, assets, etc.) avec Mercure SSE
 */
export default class extends Controller {
    static values = {
        taskId: String,
        projectId: String, // ID du projet pour le topic marketing/project/{id}
        mercureUrl: String,
        mercureJwt: String,
        nextUrl: String,
        generationType: String, // 'strategy', 'asset', etc.
    };

    connect() {
        console.log('Marketing generation controller connected');
        console.log('Project ID:', this.projectIdValue);
        console.log('Generation Type:', this.generationTypeValue);

        this.startTime = Date.now();
        this.connectToMercure();
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
    }

    /**
     * Connexion à Mercure EventSource avec système MarketingGenerationPublisher
     * Topic : marketing/project/{projectId}
     * Format : JSON générique avec type: "start" | "progress" | "complete" | "error"
     */
    connectToMercure() {
        const topic = `marketing/project/${this.projectIdValue}`;
        const mercureUrl = new URL(this.mercureUrlValue);
        mercureUrl.searchParams.append('topic', topic);

        // Ajouter le JWT Mercure pour l'authentification
        if (this.mercureJwtValue) {
            mercureUrl.searchParams.append('authorization', this.mercureJwtValue);
        }

        const finalUrl = mercureUrl.toString();
        console.log('[MERCURE DEBUG] Connecting to Mercure:', finalUrl);
        console.log('[MERCURE DEBUG] Project ID:', this.projectIdValue);
        console.log('[MERCURE DEBUG] Topic:', topic);
        console.log('[MERCURE DEBUG] Mercure URL base:', this.mercureUrlValue);

        this.eventSource = new EventSource(finalUrl);

        // Log de connexion
        this.eventSource.onopen = () => {
            console.log('[MERCURE DEBUG] EventSource connection opened');
        };

        // Écouter les messages génériques MarketingGenerationPublisher
        this.eventSource.onmessage = (event) => {
            console.log('[MERCURE DEBUG] Message received:', event.data);

            try {
                const data = JSON.parse(event.data);
                console.log('[MERCURE DEBUG] Parsed data:', data);
                console.log('[MERCURE DEBUG] Event type:', data.type);

                // Router selon le type d'événement
                switch (data.type) {
                    case 'start':
                        this.handleStart(data);
                        break;
                    case 'progress':
                        this.handleProgress(data);
                        break;
                    case 'complete':
                        this.handleComplete(data);
                        break;
                    case 'error':
                        this.handleError(data);
                        break;
                    default:
                        console.warn('[MERCURE DEBUG] Unknown event type:', data.type);
                }
            } catch (error) {
                console.error('[MERCURE DEBUG] Failed to parse message:', error);
            }
        };

        this.eventSource.onerror = (error) => {
            console.error('[MERCURE DEBUG] EventSource error:', error);
            console.error('[MERCURE DEBUG] EventSource readyState:', this.eventSource.readyState);
            // 0 = CONNECTING, 1 = OPEN, 2 = CLOSED
        };
    }

    /**
     * Gère le démarrage
     */
    handleStart(_data) {
        console.log('Génération démarrée');
        this.updateStatus('En cours...');
    }

    /**
     * Gère la progression temps réel
     *
     * Format MarketingGenerationPublisher :
     * {
     *   type: "progress",
     *   projectId: 1,
     *   stage: "assets",
     *   message: "Génération asset 1/1 : linkedin_post...",
     *   data: { progress: 100, current_type: "linkedin_post", current_variation: 1 },
     *   timestamp: "2025-12-09 16:52:27"
     * }
     */
    handleProgress(data) {
        // Le pourcentage est dans data.data.progress
        const percentage = data.data?.progress || 0;
        const message = data.message || 'En cours...';

        console.log(`[PROGRESS] ${percentage}% - ${message}`, data.data);

        // Mettre à jour barre de progression si disponible
        const progressBar = document.querySelector('[data-progress-bar]');
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute('aria-valuenow', percentage);
        }

        // Mettre à jour pourcentage texte
        const progressText = document.querySelector('[data-progress-percentage]');
        if (progressText) {
            progressText.textContent = `${Math.round(percentage)}%`;
        }

        // Mettre à jour message
        const progressMessage = document.querySelector('[data-progress-message]');
        if (progressMessage) {
            progressMessage.textContent = message;
        }

        // Mettre à jour indicateur de phase si disponible
        const phaseIndicator = document.querySelector('[data-phase-indicator]');
        if (phaseIndicator && data.data?.current_phase && data.data?.total_phases) {
            phaseIndicator.textContent = `Phase ${data.data.current_phase}/${data.data.total_phases}`;
        }

        // Mettre à jour le statut général
        this.updateStatus(message);
    }

    /**
     * Gère la complétion
     *
     * Format MarketingGenerationPublisher :
     * {
     *   type: "complete",
     *   projectId: 1,
     *   stage: "assets" | "strategy" | "personas",
     *   message: "X assets générés avec succès !",
     *   metadata: { assets_count: 1, asset_types: [...], duration_ms: 15244 },
     *   timestamp: "2025-12-09 16:52:42"
     * }
     */
    handleComplete(data) {
        console.log('[MERCURE DEBUG] handleComplete called');
        console.log('[MERCURE DEBUG] Stage:', data.stage);
        console.log('[MERCURE DEBUG] Message:', data.message);
        console.log('[MERCURE DEBUG] Full data:', JSON.stringify(data, null, 2));

        this.updateStatus(data.message || '✅ Génération terminée !');

        // Pour la stratégie, utiliser le polling car elle peut être générée en arrière-plan
        if (data.stage === 'strategy') {
            console.log('[MERCURE DEBUG] Strategy generation completed, polling for BDD confirmation...');
            this.pollStrategyCompletion();
        } else {
            // Pour les assets et personas : redirection immédiate après 2s
            console.log('[MERCURE DEBUG] Generation completed, redirecting in 2s...');
            setTimeout(() => {
                this.showSuccess(data);
            }, 2000);
        }
    }

    /**
     * Polling pour vérifier que la stratégie existe en BDD avant de rediriger
     *
     * FIX v3.30.1 : StrategyAnalystAgent peut prendre 20-30 secondes pour terminer.
     * Au lieu de deviner un délai fixe, on poll le statut du projet jusqu'à ce que
     * la stratégie soit persistée en BDD.
     */
    pollStrategyCompletion() {
        console.log('[MERCURE DEBUG] Starting strategy completion polling...');

        const maxAttempts = 40; // 40 tentatives max (40 secondes)
        let attempts = 0;

        const pollInterval = setInterval(async () => {
            attempts++;
            console.log(`[MERCURE DEBUG] Polling attempt ${attempts}/${maxAttempts}`);

            try {
                // Extraire l'ID du projet depuis nextUrl
                // Format: /marketing/strategy/show/{id} ou /marketing/projects/{id}
                const projectId = this.nextUrlValue.match(/\/(\d+)$/)?.[1];

                if (!projectId) {
                    console.error('[MERCURE DEBUG] Could not extract project ID from nextUrl:', this.nextUrlValue);
                    clearInterval(pollInterval);
                    this.showSuccess({}); // Rediriger quand même
                    return;
                }

                // Vérifier le statut du projet
                const response = await fetch(`/marketing/projects/${projectId}/status`, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    console.warn(`[MERCURE DEBUG] Status check failed: ${response.status}`);

                    // Si max attempts atteint, rediriger quand même
                    if (attempts >= maxAttempts) {
                        console.warn('[MERCURE DEBUG] Max polling attempts reached, redirecting anyway...');
                        clearInterval(pollInterval);
                        this.showSuccess({});
                    }
                    return;
                }

                const data = await response.json();
                console.log('[MERCURE DEBUG] Project status:', data);

                // Vérifier si la stratégie existe
                if (data.has_strategy === true) {
                    console.log('[MERCURE DEBUG] Strategy detected in database, redirecting!');
                    clearInterval(pollInterval);
                    this.updateStatus('✅ Stratégie générée avec succès !');
                    setTimeout(() => {
                        this.showSuccess({});
                    }, 1000);
                } else {
                    // Mettre à jour le message de progression
                    const progressMessage = `Génération en cours... (${attempts}s)`;
                    this.updateStatus(progressMessage);
                }
            } catch (error) {
                console.error('[MERCURE DEBUG] Polling error:', error);

                // Si max attempts atteint, rediriger quand même
                if (attempts >= maxAttempts) {
                    console.warn('[MERCURE DEBUG] Max polling attempts reached after error, redirecting anyway...');
                    clearInterval(pollInterval);
                    this.showSuccess({});
                }
            }
        }, 1000); // Poll toutes les secondes
    }

    /**
     * Gère les erreurs
     *
     * Format MarketingGenerationPublisher :
     * {
     *   type: "error",
     *   projectId: 1,
     *   stage: "assets",
     *   message: "Échec de la génération des assets. Veuillez réessayer.",
     *   technical: "ContentCreatorAgent: Failed to parse...",
     *   timestamp: "2025-12-09 16:52:42"
     * }
     */
    handleError(data) {
        const errorMessage = data.message || 'Une erreur est survenue lors de la génération';
        const technicalError = data.technical || '';

        console.error('[MERCURE DEBUG] Generation failed:', errorMessage);
        if (technicalError) {
            console.error('[MERCURE DEBUG] Technical error:', technicalError);
        }

        this.showError(errorMessage);
    }

    /**
     * Affiche le succès et redirige
     */
    showSuccess(_data) {
        console.log('Generation completed, redirecting to:', this.nextUrlValue);

        this.updateStatus('✅ Génération terminée !');

        if (this.eventSource) {
            this.eventSource.close();
        }

        // Auto-redirection après 1 seconde
        setTimeout(() => {
            window.location.href = this.nextUrlValue;
        }, 1000);
    }

    /**
     * Affiche une erreur
     */
    showError(message) {
        const statusElement = document.getElementById('status-message');
        if (statusElement) {
            statusElement.classList.remove('alert-info');
            statusElement.classList.add('alert-danger');
            statusElement.innerHTML = `<i class="bi bi-exclamation-triangle"></i> ${message}`;
        }

        if (this.eventSource) {
            this.eventSource.close();
        }
    }

    /**
     * Met à jour le message de statut
     */
    updateStatus(message) {
        const statusElement = document.getElementById('status-message');
        if (statusElement) {
            statusElement.innerHTML = `<i class="bi bi-hourglass-split"></i> ${message}`;
        }
    }
}
