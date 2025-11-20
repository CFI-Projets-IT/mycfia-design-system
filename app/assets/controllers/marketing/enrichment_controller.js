import { Controller } from '@hotwired/stimulus';

/**
 * Marketing Enrichment Controller
 * Gère l'enrichissement asynchrone de projet avec Mercure SSE
 */
export default class extends Controller {
    static targets = [
        'spinner',
        'statusMessage',
        'successMessage',
        'errorMessage',
        'resultSummary',
        'errorDetails',
        'elapsedTime',
        'status',
    ];

    static values = {
        projectId: Number,
        taskId: String,
        mercureUrl: String,
        nextUrl: String,
    };

    connect() {
        console.log('Marketing enrichment controller connected');
        console.log('Project ID:', this.projectIdValue);
        console.log('Task ID:', this.taskIdValue);

        this.startTime = Date.now();
        this.startElapsedTimer();
        this.connectToMercure();
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        if (this.elapsedTimer) {
            clearInterval(this.elapsedTimer);
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

        // Timeout après 2 minutes (enrichissement devrait prendre 10-30 secondes)
        this.timeoutTimer = setTimeout(() => {
            this.handleTimeout();
        }, 120000);
    }

    /**
     * Gère le démarrage de l'enrichissement
     */
    handleStart(_data) {
        console.log('Enrichissement démarré');
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = 'En cours...';
        }
    }

    /**
     * Gère la complétion
     */
    handleComplete(data) {
        console.log('Enrichissement terminé avec succès');
        this.showSuccess(data);
    }

    /**
     * Gère les erreurs
     */
    handleError(data) {
        this.showError(data.error || "Une erreur est survenue lors de l'enrichissement");
    }

    /**
     * Gère le timeout
     */
    handleTimeout() {
        this.showError("L'enrichissement prend plus de temps que prévu. Veuillez vérifier le statut du projet.");
    }

    /**
     * Affiche le succès
     */
    showSuccess(_data) {
        if (this.hasSpinnerTarget) {
            this.spinnerTarget.classList.add('d-none');
        }

        if (this.hasStatusMessageTarget) {
            this.statusMessageTarget.classList.add('d-none');
        }

        if (this.hasSuccessMessageTarget) {
            this.successMessageTarget.classList.remove('d-none');
        }

        if (this.hasResultSummaryTarget) {
            this.resultSummaryTarget.textContent = 'Enrichissement terminé avec succès !';
        }

        if (this.hasStatusTarget) {
            this.statusTarget.textContent = 'Terminé';
        }

        if (this.eventSource) {
            this.eventSource.close();
        }

        // Auto-redirection après 2 secondes
        setTimeout(() => {
            window.location.href = this.nextUrlValue;
        }, 2000);
    }

    /**
     * Affiche une erreur
     */
    showError(message) {
        if (this.hasSpinnerTarget) {
            this.spinnerTarget.classList.add('d-none');
        }

        if (this.hasStatusMessageTarget) {
            this.statusMessageTarget.classList.add('d-none');
        }

        if (this.hasErrorMessageTarget) {
            this.errorMessageTarget.classList.remove('d-none');
        }

        if (this.hasErrorDetailsTarget) {
            this.errorDetailsTarget.textContent = message;
        }

        if (this.hasStatusTarget) {
            this.statusTarget.textContent = 'Erreur';
        }

        if (this.eventSource) {
            this.eventSource.close();
        }
    }

    /**
     * Timer pour le temps écoulé
     */
    startElapsedTimer() {
        this.elapsedTimer = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
            if (this.hasElapsedTimeTarget) {
                this.elapsedTimeTarget.textContent = `${elapsed}s`;
            }
        }, 1000);
    }
}
