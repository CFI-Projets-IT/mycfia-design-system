import { Controller } from '@hotwired/stimulus';

/**
 * Marketing Generation Controller
 * Gère la génération asynchrone de personas, stratégies et assets avec Mercure SSE
 */
export default class extends Controller {
    static targets = [
        'spinner',
        'statusMessage',
        'successMessage',
        'errorMessage',
        'progressBar',
        'progressText',
        'progressMessage',
        'resultSummary',
        'errorDetails',
        'elapsedTime',
        'status',
        'overallProgress',
        'completedCount',
        'assetsList',
        'assetsContainer',
    ];

    static values = {
        projectId: Number,
        stage: String,
        mercureUrl: String,
        nextUrl: String,
        multiple: { type: Boolean, default: false },
    };

    connect() {
        console.log('Marketing generation controller connected');
        console.log('Project ID:', this.projectIdValue);
        console.log('Stage:', this.stageValue);
        console.log('Multiple mode:', this.multipleValue);

        this.startTime = Date.now();
        this.completedAssets = 0;
        this.totalAssets = 0;
        this.assets = new Map();

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
     * Connexion à Mercure EventSource
     */
    connectToMercure() {
        const topic = `https://mycfia.local/marketing/generation/${this.projectIdValue}/${this.stageValue}`;
        const url = new URL(this.mercureUrlValue);
        url.searchParams.append('topic', topic);

        console.log('Connecting to Mercure:', url.toString());

        this.eventSource = new EventSource(url.toString());

        this.eventSource.onmessage = (event) => {
            console.log('Mercure event received:', event.data);
            const data = JSON.parse(event.data);
            this.handleMercureEvent(data);
        };

        this.eventSource.onerror = (error) => {
            console.error('EventSource error:', error);
            this.showError('Erreur de connexion Mercure. Veuillez rafraîchir la page.');
        };

        // Timeout après 10 minutes
        this.timeoutTimer = setTimeout(() => {
            this.handleTimeout();
        }, 600000);
    }

    /**
     * Traite les événements Mercure
     */
    handleMercureEvent(data) {
        console.log('Processing event:', data);

        switch (data.status) {
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
        }
    }

    /**
     * Gère le démarrage de la génération
     */
    handleStart(data) {
        if (this.hasProgressMessageTarget) {
            this.progressMessageTarget.textContent = data.message || 'Démarrage...';
        }

        if (this.multipleValue && data.metadata && data.metadata.totalAssets) {
            this.totalAssets = data.metadata.totalAssets;
            this.initializeAssetsDisplay();
        }
    }

    /**
     * Gère la progression
     */
    handleProgress(data) {
        const percentage = data.data?.percentage || 0;

        if (this.hasProgressBarTarget) {
            this.progressBarTarget.style.width = `${percentage}%`;
        }

        if (this.hasProgressTextTarget) {
            this.progressTextTarget.textContent = `${percentage}%`;
        }

        if (this.hasProgressMessageTarget && data.message) {
            this.progressMessageTarget.textContent = data.message;
        }

        // Mode multiple : mise à jour asset spécifique
        if (this.multipleValue && data.metadata && data.metadata.assetType) {
            this.updateAssetStatus(data.metadata.assetType, 'in_progress', data.message);
        }
    }

    /**
     * Gère la complétion
     */
    handleComplete(data) {
        if (this.multipleValue) {
            this.completedAssets++;

            if (data.metadata && data.metadata.assetType) {
                this.updateAssetStatus(data.metadata.assetType, 'completed');
            }

            if (this.hasCompletedCountTarget) {
                this.completedCountTarget.textContent = this.completedAssets;
            }

            if (this.hasOverallProgressTarget) {
                const percentage = Math.round((this.completedAssets / this.totalAssets) * 100);
                this.overallProgressTarget.textContent = `${percentage}%`;
            }

            if (this.hasProgressBarTarget) {
                const percentage = (this.completedAssets / this.totalAssets) * 100;
                this.progressBarTarget.style.width = `${percentage}%`;
            }

            if (this.hasProgressTextTarget) {
                this.progressTextTarget.textContent = `${this.completedAssets}/${this.totalAssets}`;
            }

            // Tous les assets terminés
            if (this.completedAssets >= this.totalAssets) {
                this.showSuccess(data);
            }
        } else {
            // Mode single
            this.showSuccess(data);
        }
    }

    /**
     * Gère les erreurs
     */
    handleError(data) {
        this.showError(data.message || 'Une erreur est survenue', data.error);
    }

    /**
     * Gère le timeout
     */
    handleTimeout() {
        this.showError('La génération prend plus de temps que prévu. Veuillez vérifier le statut du projet.');
    }

    /**
     * Affiche le succès
     */
    showSuccess(data) {
        if (this.hasSpinnerTarget) {
            this.spinnerTarget.classList.add('d-none');
        }

        if (this.hasStatusMessageTarget) {
            this.statusMessageTarget.classList.add('d-none');
        }

        if (this.hasSuccessMessageTarget) {
            this.successMessageTarget.classList.remove('d-none');
        }

        if (this.hasResultSummaryTarget && data.message) {
            this.resultSummaryTarget.textContent = data.message;
        }

        if (this.hasStatusTarget) {
            this.statusTarget.textContent = 'Terminé';
        }

        if (this.eventSource) {
            this.eventSource.close();
        }

        // Auto-redirection après 3 secondes
        setTimeout(() => {
            window.location.href = this.nextUrlValue;
        }, 3000);
    }

    /**
     * Affiche une erreur
     */
    showError(message, details = '') {
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
            if (details) {
                this.errorDetailsTarget.innerHTML += `<br><small class="text-muted">${details}</small>`;
            }
        }

        if (this.hasStatusTarget) {
            this.statusTarget.textContent = 'Erreur';
        }

        if (this.eventSource) {
            this.eventSource.close();
        }
    }

    /**
     * Initialise l'affichage des assets (mode multiple)
     */
    initializeAssetsDisplay() {
        if (!this.hasAssetsContainerTarget) {
            return;
        }

        this.assetsContainerTarget.innerHTML = '';
    }

    /**
     * Met à jour le statut d'un asset
     */
    updateAssetStatus(assetType, status, message = '') {
        if (!this.hasAssetsContainerTarget) {
            return;
        }

        // Chercher ou créer l'élément asset
        let assetElement = this.assetsContainerTarget.querySelector(`[data-asset-type="${assetType}"]`);

        if (!assetElement) {
            assetElement = this.createAssetElement(assetType);
            this.assetsContainerTarget.appendChild(assetElement);
        }

        // Mettre à jour l'icône et le statut
        const icon = assetElement.querySelector('.asset-icon');
        const statusText = assetElement.querySelector('.asset-status');
        const messageText = assetElement.querySelector('.asset-message');

        switch (status) {
            case 'pending':
                icon.className = 'bi bi-circle text-muted asset-icon';
                statusText.textContent = 'En attente';
                break;
            case 'in_progress':
                icon.className = 'bi bi-arrow-repeat text-primary asset-icon';
                icon.classList.add('spinner-border', 'spinner-border-sm');
                statusText.textContent = 'En cours';
                if (messageText && message) {
                    messageText.textContent = message;
                }
                break;
            case 'completed':
                icon.className = 'bi bi-check-circle-fill text-success asset-icon';
                icon.classList.remove('spinner-border', 'spinner-border-sm');
                statusText.textContent = 'Terminé';
                break;
            case 'error':
                icon.className = 'bi bi-x-circle-fill text-danger asset-icon';
                icon.classList.remove('spinner-border', 'spinner-border-sm');
                statusText.textContent = 'Erreur';
                break;
        }
    }

    /**
     * Crée un élément d'affichage pour un asset
     */
    createAssetElement(assetType) {
        const div = document.createElement('div');
        div.className = 'col-md-6';
        div.setAttribute('data-asset-type', assetType);

        const formattedType = assetType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

        div.innerHTML = `
            <div class="d-flex align-items-center p-2 border rounded">
                <i class="bi bi-circle text-muted asset-icon me-2"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold small">${formattedType}</div>
                    <div class="text-muted very-small asset-message"></div>
                </div>
                <span class="badge bg-secondary asset-status">En attente</span>
            </div>
        `;

        return div;
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
