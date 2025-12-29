import { Controller } from '@hotwired/stimulus';

/**
 * Controller Stimulus pour les tests de génération d'images IA.
 * Gère les appels AJAX vers les routes de test et l'affichage des résultats.
 */
export default class extends Controller {
    static targets = [
        'builderSelect',
        'styleSelect',
        'submitButton',
        'loadingContainer',
        'errorContainer',
        'errorMessage',
        'resultContainer',
        'generatedImage',
        'metadataTable',
        'multipleResultsContainer',
        'multipleResults',
        'emptyState',
    ];

    /**
     * Gérer la soumission du formulaire de génération.
     */
    async generate(event) {
        event.preventDefault();

        const builder = this.builderSelectTarget.value;
        const style = this.styleSelectTarget.value;

        if (!builder || !style) {
            this.showError('Veuillez sélectionner un AssetBuilder et un style');
            return;
        }

        this.showLoading();

        try {
            const url = `/test/image-generation/${builder}/${style}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.showResult(data);
            } else {
                this.showError(data.error || 'Erreur inconnue lors de la génération');
            }
        } catch (error) {
            this.showError(`Erreur réseau: ${error.message}`);
        }
    }

    /**
     * Tester tous les styles pour le builder sélectionné.
     */
    async generateAllStyles(event) {
        event.preventDefault();

        const builder = this.builderSelectTarget.value;

        if (!builder) {
            this.showError('Veuillez sélectionner un AssetBuilder');
            return;
        }

        this.showLoading();

        try {
            const url = `/test/image-generation/all-styles/${builder}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.showMultipleResults(data);
            } else {
                this.showError(data.error || 'Erreur inconnue lors de la génération');
            }
        } catch (error) {
            this.showError(`Erreur réseau: ${error.message}`);
        }
    }

    /**
     * Afficher l'état de chargement.
     */
    showLoading() {
        this.hideAllContainers();
        this.loadingContainerTarget.classList.remove('d-none');
        this.submitButtonTarget.disabled = true;
    }

    /**
     * Afficher un résultat unique.
     */
    showResult(data) {
        this.hideAllContainers();
        this.resultContainerTarget.classList.remove('d-none');

        this.generatedImageTarget.src = `data:image/png;base64,${data.image_base64}`;
        this.generatedImageTarget.alt = `${data.builder} - ${data.style}`;

        const metadata = [
            { label: 'Builder', value: data.builder },
            { label: 'Format', value: data.format },
            { label: 'Style', value: data.style },
            { label: 'File ID', value: data.file_id },
            { label: 'Taille', value: `${data.size_kb} KB` },
            { label: 'Durée', value: `${data.duration_seconds}s` },
            { label: 'Sauvegardé', value: data.saved_to },
        ];

        this.metadataTableTarget.innerHTML = metadata
            .map(
                (item) => `
            <tr>
                <th class="text-nowrap">${this.escapeHtml(item.label)}</th>
                <td>${this.escapeHtml(item.value)}</td>
            </tr>
        `,
            )
            .join('');

        this.submitButtonTarget.disabled = false;
    }

    /**
     * Afficher plusieurs résultats (tous les styles).
     */
    showMultipleResults(data) {
        this.hideAllContainers();
        this.multipleResultsContainerTarget.classList.remove('d-none');

        const resultsHtml = Object.entries(data.results)
            .map(([style, result]) => {
                if (result.success) {
                    return `
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">${this.escapeHtml(style)}</h6>
                            </div>
                            <div class="card-body">
                                <img
                                    src="data:image/png;base64,${result.image_base64}"
                                    class="img-fluid rounded mb-2"
                                    alt="${this.escapeHtml(style)}"
                                >
                                <div class="small text-muted">
                                    <strong>Taille:</strong> ${result.size_kb} KB<br>
                                    <strong>Durée:</strong> ${result.duration_seconds}s
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                } else {
                    return `
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">${this.escapeHtml(style)}</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-danger mb-0">
                                    ${this.escapeHtml(result.error)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                }
            })
            .join('');

        this.multipleResultsTarget.innerHTML = resultsHtml;
        this.submitButtonTarget.disabled = false;
    }

    /**
     * Afficher une erreur.
     */
    showError(message) {
        this.hideAllContainers();
        this.errorContainerTarget.classList.remove('d-none');
        this.errorMessageTarget.textContent = message;
        this.submitButtonTarget.disabled = false;
    }

    /**
     * Masquer tous les conteneurs de résultats.
     */
    hideAllContainers() {
        this.loadingContainerTarget.classList.add('d-none');
        this.errorContainerTarget.classList.add('d-none');
        this.resultContainerTarget.classList.add('d-none');
        this.multipleResultsContainerTarget.classList.add('d-none');
        this.emptyStateTarget.classList.add('d-none');
    }

    /**
     * Échapper les caractères HTML pour éviter les injections XSS.
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
