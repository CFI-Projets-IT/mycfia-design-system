import { Controller } from '@hotwired/stimulus';

/**
 * Marketing Generation Controller
 * Gère la génération asynchrone (stratégie, assets, etc.) avec Mercure SSE
 */
export default class extends Controller {
    static values = {
        taskId: String,
        mercureUrl: String,
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
        const url = `${this.mercureUrlValue}?topic=${encodeURIComponent(topic)}`;

        console.log('Connecting to Mercure:', url);
        console.log('Topic:', topic);

        this.eventSource = new EventSource(url);

        // Écouter les événements spécifiques du bundle
        this.eventSource.addEventListener('TaskStartedEvent', (event) => {
            console.log('TaskStartedEvent received:', event.data);
            const data = JSON.parse(event.data);
            this.handleStart(data);
        });

        this.eventSource.addEventListener('TaskCompletedEvent', (event) => {
            console.log('TaskCompletedEvent received:', event.data);
            const data = JSON.parse(event.data);
            this.handleComplete(data);
        });

        this.eventSource.addEventListener('TaskFailedEvent', (event) => {
            console.error('TaskFailedEvent received:', event.data);
            const data = JSON.parse(event.data);
            this.handleError(data);
        });

        this.eventSource.onerror = (error) => {
            console.error('EventSource error:', error);
            // EventSource tente de se reconnecter automatiquement
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
        console.log('Task terminée:', data.agent_name);

        if (this.generationTypeValue === 'strategy') {
            // Pour la stratégie, on attend un peu pour que StrategyAnalyst se termine
            // et que la stratégie soit sauvegardée en BDD
            console.log('Waiting for strategy to be saved...');
            setTimeout(() => {
                this.checkStrategyStatus();
            }, 2000);
        } else {
            // Pour les autres types, redirection immédiate
            this.showSuccess(data);
        }
    }

    /**
     * Vérifie si la stratégie a été sauvegardée en BDD
     */
    async checkStrategyStatus() {
        try {
            const projectId = this.extractProjectId();
            const response = await fetch(`/marketing/project/${projectId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const html = await response.text();
                // Si la page contient le lien vers la stratégie, c'est prêt
                if (html.includes('marketing_strategy_show') || html.includes('strategy_generated')) {
                    console.log('Strategy saved, redirecting...');
                    this.showSuccess({});
                } else {
                    // Réessayer après 1 seconde
                    setTimeout(() => this.checkStrategyStatus(), 1000);
                }
            }
        } catch (error) {
            console.error('Error checking strategy status:', error);
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
