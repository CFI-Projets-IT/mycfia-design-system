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
        taskId: String,
        stage: String,
        mercureUrl: String,
        mercureJwt: String,
        nextUrl: String,
        multiple: { type: Boolean, default: false },
    };

    connect() {
        console.log('Marketing generation controller connected');
        console.log('Project ID:', this.projectIdValue);
        console.log('Task ID:', this.taskIdValue);
        console.log('Stage:', this.stageValue);
        console.log('Multiple mode:', this.multipleValue);

        this.startTime = Date.now();
        this.completedAssets = 0;
        this.totalAssets = 0;
        this.assets = new Map();
        this.retryCount = 0;
        this.maxRetries = 3;
        this.hasSeenFailure = false;

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
        // Utiliser le topic /tasks/{taskId} du Marketing AI Bundle
        const topic = `/tasks/${this.taskIdValue}`;
        const mercureUrl = new URL(this.mercureUrlValue);
        mercureUrl.searchParams.append('topic', topic);

        // Ajouter le JWT Mercure pour l'authentification
        if (this.mercureJwtValue) {
            mercureUrl.searchParams.append('authorization', this.mercureJwtValue);
        }

        const finalUrl = mercureUrl.toString();
        console.log('Connecting to Mercure:', finalUrl);
        console.log('Topic:', topic);

        this.eventSource = new EventSource(finalUrl);

        // Écouter les événements spécifiques du bundle
        this.eventSource.addEventListener('TaskStartedEvent', (event) => {
            console.log('TaskStartedEvent received:', event.data);
            const data = JSON.parse(event.data);
            this.handleStart({ message: 'Agent IA démarré', ...data });
        });

        this.eventSource.addEventListener('TaskCompletedEvent', (event) => {
            console.log('TaskCompletedEvent received:', event.data);
            const data = JSON.parse(event.data);

            // Message personnalisé selon le stage
            let message = 'Génération terminée avec succès !';
            if (this.stageValue === 'personas') {
                message = 'Personas générés avec succès !';
            } else if (this.stageValue === 'strategy') {
                message = 'Stratégie générée avec succès !';
            } else if (this.stageValue === 'assets') {
                message = 'Assets générés avec succès !';
            }

            this.handleComplete({ message, ...data });
        });

        this.eventSource.addEventListener('TaskFailedEvent', (event) => {
            console.error('TaskFailedEvent received:', event.data);
            const data = JSON.parse(event.data);

            this.retryCount++;
            this.hasSeenFailure = true;

            // Si c'est une erreur récupérable et qu'on n'a pas dépassé les retries, continuer d'écouter
            const isRecoverable = data.is_recoverable !== false;
            const canRetry = this.retryCount <= this.maxRetries;

            if (isRecoverable && canRetry) {
                console.log(
                    `Tentative ${this.retryCount}/${this.maxRetries} échouée, en attente de retry automatique...`
                );
                this.handleRetry(data, this.retryCount);
            } else {
                // Erreur définitive après tous les retries
                console.error('Échec définitif après', this.retryCount, 'tentatives');
                this.handleError({
                    message: data.error || 'Une erreur est survenue après plusieurs tentatives',
                    ...data,
                });
            }
        });

        this.eventSource.onerror = (error) => {
            console.error('EventSource error:', error);
            // EventSource tente de se reconnecter automatiquement
        };

        // Timeout après 5 minutes (génération personas devrait prendre 10-60 secondes)
        this.timeoutTimer = setTimeout(() => {
            this.handleTimeout();
        }, 300000);
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
     * Gère un retry automatique
     */
    handleRetry(data, attemptNumber) {
        // Mise à jour du message de statut pour informer l'utilisateur
        if (this.hasProgressMessageTarget) {
            this.progressMessageTarget.innerHTML = `
                <span class="text-warning">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Tentative ${attemptNumber}/3 échouée, nouvelle tentative en cours...
                </span>
            `;
        }

        if (this.hasStatusTarget) {
            this.statusTarget.innerHTML = `<span class="text-warning">Retry ${attemptNumber}/3</span>`;
        }

        // Ne PAS fermer l'EventSource, continuer d'écouter pour le succès
        console.log('En attente de TaskCompletedEvent ou TaskFailedEvent suivant...');
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
            // Si on a eu des échecs mais finalement réussi, le mentionner
            if (this.hasSeenFailure && this.retryCount > 0) {
                this.resultSummaryTarget.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        ${data.message}
                        <br><small class="text-muted">Réussi après ${this.retryCount} tentative(s) échouée(s)</small>
                    </div>
                `;
            } else {
                this.resultSummaryTarget.textContent = data.message;
            }
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

        const formattedType = assetType.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());

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
