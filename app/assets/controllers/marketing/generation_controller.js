import { Controller } from '@hotwired/stimulus';

/**
 * Marketing Generation Controller
 * Gère la génération asynchrone (stratégie, assets, etc.) avec Mercure SSE
 */
export default class extends Controller {
    static values = {
        taskId: String,
        mercureUrl: String,
        mercureJwt: String,
        nextUrl: String,
        generationType: String, // 'strategy', 'asset', etc.
    };

    connect() {
        console.log('Marketing generation controller connected');
        console.log('Task ID:', this.taskIdValue);
        console.log('Generation Type:', this.generationTypeValue);

        this.startTime = Date.now();
        this.connectToMercure();
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        if (this.timeoutTimer) {
            clearTimeout(this.timeoutTimer);
        }
    }

    /**
     * Connexion à Mercure EventSource avec système taskId du Marketing AI Bundle
     */
    connectToMercure() {
        const topic = `/tasks/${this.taskIdValue}`;
        const mercureUrl = new URL(this.mercureUrlValue);
        mercureUrl.searchParams.append('topic', topic);

        // Ajouter le JWT Mercure pour l'authentification
        if (this.mercureJwtValue) {
            mercureUrl.searchParams.append('authorization', this.mercureJwtValue);
        }

        const finalUrl = mercureUrl.toString();
        console.log('[MERCURE DEBUG] Connecting to Mercure:', finalUrl);
        console.log('[MERCURE DEBUG] Task ID:', this.taskIdValue);
        console.log('[MERCURE DEBUG] Topic:', topic);
        console.log('[MERCURE DEBUG] Mercure URL base:', this.mercureUrlValue);

        this.eventSource = new EventSource(finalUrl);

        // Log de connexion
        this.eventSource.onopen = () => {
            console.log('[MERCURE DEBUG] EventSource connection opened');
        };

        // Écouter TOUS les messages (debug)
        this.eventSource.onmessage = (event) => {
            console.log('[MERCURE DEBUG] Generic message received:', event);
            console.log('[MERCURE DEBUG] Message data:', event.data);
            console.log('[MERCURE DEBUG] Message type:', event.type);
        };

        // Écouter les événements spécifiques du bundle
        this.eventSource.addEventListener('TaskStartedEvent', (event) => {
            console.log('[MERCURE DEBUG] TaskStartedEvent received:', event.data);
            const data = JSON.parse(event.data);
            this.handleStart(data);
        });

        this.eventSource.addEventListener('TaskProgressEvent', (event) => {
            console.log('[MERCURE DEBUG] TaskProgressEvent received:', event.data);
            const data = JSON.parse(event.data);
            this.handleProgress(data);
        });

        this.eventSource.addEventListener('TaskCompletedEvent', (event) => {
            console.log('[MERCURE DEBUG] TaskCompletedEvent received:', event.data);
            const data = JSON.parse(event.data);
            this.handleComplete(data);
        });

        this.eventSource.addEventListener('TaskFailedEvent', (event) => {
            console.error('[MERCURE DEBUG] TaskFailedEvent received:', event.data);
            const data = JSON.parse(event.data);
            this.handleError(data);
        });

        this.eventSource.onerror = (error) => {
            console.error('[MERCURE DEBUG] EventSource error:', error);
            console.error('[MERCURE DEBUG] EventSource readyState:', this.eventSource.readyState);
            // 0 = CONNECTING, 1 = OPEN, 2 = CLOSED
        };

        // Timeout après 3 minutes (génération peut prendre du temps)
        this.timeoutTimer = setTimeout(() => {
            this.handleTimeout();
        }, 180000);
    }

    /**
     * Gère le démarrage
     */
    handleStart(_data) {
        console.log('Génération démarrée');
        this.updateStatus('En cours...');
    }

    /**
     * Gère la progression temps réel (v3.34.0)
     */
    handleProgress(data) {
        const { percentage, message, metadata } = data;

        console.log(`[PROGRESS] ${percentage}% - ${message}`, metadata);

        // Mettre à jour barre de progression si disponible
        const progressBar = document.querySelector('[data-progress-bar]');
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
            progressBar.setAttribute('aria-valuenow', percentage);
        }

        // Mettre à jour pourcentage texte
        const progressText = document.querySelector('[data-progress-percentage]');
        if (progressText) {
            progressText.textContent = `${percentage}%`;
        }

        // Mettre à jour message
        const progressMessage = document.querySelector('[data-progress-message]');
        if (progressMessage) {
            progressMessage.textContent = message;
        }

        // Mettre à jour indicateur de phase
        const phaseIndicator = document.querySelector('[data-phase-indicator]');
        if (phaseIndicator && metadata.current_phase && metadata.total_phases) {
            phaseIndicator.textContent = `Phase ${metadata.current_phase}/${metadata.total_phases}`;
        }

        // Mettre à jour le statut général
        this.updateStatus(message);
    }

    /**
     * Gère la complétion
     *
     * FIX v3.30.0 : Pour la stratégie, CompetitorAnalystAgent est le point de complétion final
     * car le subscriber lance StrategyAnalystAgent avec un nouveau taskId (non écouté par le frontend).
     * La stratégie est bien générée en BDD par StrategyOptimizedEventSubscriber, donc on peut rediriger.
     */
    handleComplete(data) {
        console.log('[MERCURE DEBUG] handleComplete called');
        console.log('[MERCURE DEBUG] Task terminée:', data.agentName);
        console.log('[MERCURE DEBUG] Generation type:', this.generationTypeValue);
        console.log('[MERCURE DEBUG] Full data:', JSON.stringify(data, null, 2));

        // ✅ FIX v3.30.0: Pour la stratégie, CompetitorAnalystAgent = succès final
        // Le subscriber CompetitorToStrategySubscriber lance StrategyAnalystAgent en arrière-plan
        // avec un nouveau taskId, donc le frontend ne recevra jamais l'événement StrategyAnalystAgent
        // sur ce taskId. La stratégie est bien générée (persistée par StrategyOptimizedEventSubscriber).
        if (this.generationTypeValue === 'strategy') {
            // Accepter CompetitorAnalystAgent comme succès final pour type "strategy"
            if (data.agentName && data.agentName.includes('CompetitorAnalystAgent')) {
                console.log(
                    '[MERCURE DEBUG] CompetitorAnalystAgent completed, strategy generation continues in background'
                );
                this.updateStatus('Analyse terminée, génération de la stratégie en cours...');
                // ✅ Polling pour vérifier que la stratégie existe en BDD avant de rediriger
                this.pollStrategyCompletion();
            } else if (data.agentName && data.agentName.includes('StrategyAnalystAgent')) {
                // Au cas où on recevrait quand même StrategyAnalystAgent (ne devrait pas arriver)
                console.log('[MERCURE DEBUG] StrategyAnalystAgent completed (unexpected), redirecting...');
                setTimeout(() => {
                    this.showSuccess(data);
                }, 2000);
            } else {
                // Agent inconnu pour le type strategy
                console.warn('[MERCURE DEBUG] Unknown agent for strategy generation:', data.agentName);
            }
        } else {
            // Pour les autres types : redirection immédiate après 2s
            console.log('[MERCURE DEBUG] Non-strategy generation, redirecting in 2s...');
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
     */
    handleError(data) {
        console.error('Generation failed:', data.error);
        this.showError(data.error || 'Une erreur est survenue lors de la génération');
    }

    /**
     * Gère le timeout
     */
    handleTimeout() {
        console.warn('Generation timeout');
        this.showError('La génération prend plus de temps que prévu. Veuillez vérifier le statut du projet.');
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
