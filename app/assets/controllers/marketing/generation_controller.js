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
     * Gère la complétion
     * Pour la stratégie : CompetitorAnalyst termine d'abord, puis StrategyAnalyst
     * On attend que le statut projet change avant de rediriger
     */
    handleComplete(data) {
        console.log('[MERCURE DEBUG] handleComplete called');
        console.log('[MERCURE DEBUG] Task terminée:', data.agentName);
        console.log('[MERCURE DEBUG] Generation type:', this.generationTypeValue);
        console.log('[MERCURE DEBUG] Full data:', JSON.stringify(data, null, 2));

        // ✅ FIX: Pour la stratégie, vérifier qu'elle est sauvegardée en BDD avant de rediriger
        // L'EventSubscriber peut prendre quelques ms pour persister
        if (this.generationTypeValue === 'strategy') {
            console.log('[MERCURE DEBUG] Strategy generation, checking DB status before redirect...');
            setTimeout(() => {
                this.checkStrategyStatus();
            }, 2000); // Attendre 2s pour laisser l'EventSubscriber persister
        } else {
            // Pour les autres types (assets), redirection immédiate
            console.log('[MERCURE DEBUG] Non-strategy generation, redirecting in 1s...');
            setTimeout(() => {
                this.showSuccess(data);
            }, 1000);
        }
    }

    /**
     * Vérifie si la stratégie a été sauvegardée en BDD
     */
    async checkStrategyStatus() {
        try {
            const projectId = this.extractProjectId();
            console.log('[MERCURE DEBUG] checkStrategyStatus - projectId:', projectId);
            console.log('[MERCURE DEBUG] checkStrategyStatus - fetching /marketing/projects/' + projectId);

            const response = await fetch(`/marketing/projects/${projectId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            console.log('[MERCURE DEBUG] checkStrategyStatus - response status:', response.status);

            if (response.ok) {
                const html = await response.text();
                console.log('[MERCURE DEBUG] checkStrategyStatus - HTML length:', html.length);
                console.log('[MERCURE DEBUG] checkStrategyStatus - contains marketing_strategy_show:', html.includes('marketing_strategy_show'));
                console.log('[MERCURE DEBUG] checkStrategyStatus - contains strategy_generated:', html.includes('strategy_generated'));

                // Si la page contient le lien vers la stratégie, c'est prêt
                if (html.includes('marketing_strategy_show') || html.includes('strategy_generated')) {
                    console.log('[MERCURE DEBUG] Strategy saved, redirecting...');
                    this.showSuccess({});
                } else {
                    // Réessayer après 1 seconde
                    console.log('[MERCURE DEBUG] Strategy not ready, retrying in 1s...');
                    setTimeout(() => this.checkStrategyStatus(), 1000);
                }
            } else {
                console.error('[MERCURE DEBUG] checkStrategyStatus - response not OK:', response.status);
                // Rediriger quand même après un délai
                setTimeout(() => this.showSuccess({}), 3000);
            }
        } catch (error) {
            console.error('[MERCURE DEBUG] Error checking strategy status:', error);
            // Rediriger quand même après timeout
            setTimeout(() => this.showSuccess({}), 3000);
        }
    }

    /**
     * Extrait le project ID depuis nextUrl
     */
    extractProjectId() {
        const match = this.nextUrlValue.match(/\/(\d+)$/);
        return match ? match[1] : null;
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
