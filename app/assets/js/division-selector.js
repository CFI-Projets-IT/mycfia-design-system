/**
 * DivisionSelector - Gestion du switch multi-tenant
 *
 * Fonctionnalités :
 * - Chargement des divisions accessibles depuis l'API
 * - Affichage de la division courante
 * - Switch de division avec validation
 * - Rechargement de la page après switch réussi
 */

class DivisionSelector {
    constructor() {
        this.selectorElement = document.getElementById('division-selector');
        if (!this.selectorElement) {
            console.warn('DivisionSelector: Élément #division-selector non trouvé');
            return;
        }

        this.currentDivisionName = document.getElementById('current-division-name');
        this.divisionsList = document.getElementById('divisions-list');
        this.loadingIndicator = document.getElementById('divisions-loading');
        this.isLoading = false;
        this.isLoaded = false;

        this.init();
    }

    /**
     * Initialisation du composant
     */
    async init() {
        // Protection contre les appels multiples
        if (this.isLoading || this.isLoaded) {
            return;
        }

        this.isLoading = true;

        try {
            await this.loadDivisions();
            this.isLoaded = true;
        } catch (error) {
            console.error('DivisionSelector: Erreur lors de l\'initialisation', error);
            this.showError('Impossible de charger les divisions');
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Charge la liste des divisions depuis l'API
     */
    async loadDivisions() {
        try {
            const response = await fetch('/api/tenant/divisions', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (!data.success || !Array.isArray(data.divisions)) {
                throw new Error('Format de réponse invalide');
            }

            this.renderDivisions(data.divisions);
            this.updateCurrentDivisionName(data.divisions);

        } catch (error) {
            console.error('DivisionSelector: Erreur loadDivisions', error);
            throw error;
        }
    }

    /**
     * Affiche les divisions dans le dropdown
     */
    renderDivisions(divisions) {
        // Masquer le loading
        if (this.loadingIndicator) {
            this.loadingIndicator.remove();
        }

        if (divisions.length === 0) {
            const li = document.createElement('li');
            li.className = 'px-3 py-2 text-muted';
            li.innerHTML = '<i class="bi bi-info-circle me-2"></i>Aucune division accessible';
            this.divisionsList.appendChild(li);
            return;
        }

        // Créer les items de division
        divisions.forEach(division => {
            const li = document.createElement('li');

            const a = document.createElement('a');
            a.className = 'dropdown-item';
            a.href = '#';
            a.dataset.divisionId = division.id;

            // Nom de la division
            const nameSpan = document.createElement('span');
            nameSpan.textContent = division.nom || `Division #${division.id}`;
            a.appendChild(nameSpan);

            // Badge "Actuelle" si c'est la division courante
            if (division.current) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary ms-2';
                badge.innerHTML = '<i class="bi bi-check-circle-fill"></i> Actuelle';
                a.appendChild(badge);
                a.classList.add('active');
            }

            // Événement de click
            a.addEventListener('click', (e) => {
                e.preventDefault();
                if (!division.current) {
                    this.switchDivision(division.id, division.nom);
                }
            });

            li.appendChild(a);
            this.divisionsList.appendChild(li);
        });
    }

    /**
     * Met à jour le nom de la division courante dans le bouton
     */
    updateCurrentDivisionName(divisions) {
        const currentDivision = divisions.find(d => d.current);

        if (currentDivision && this.currentDivisionName) {
            this.currentDivisionName.textContent = currentDivision.nom || `Division #${currentDivision.id}`;
        } else if (this.currentDivisionName) {
            this.currentDivisionName.textContent = 'Sélectionner une division';
        }
    }

    /**
     * Change la division active
     */
    async switchDivision(idDivision, nomDivision) {
        try {
            // Confirmation utilisateur
            const confirmed = confirm(`Changer vers la division "${nomDivision}" ?\n\nLa page sera rechargée.`);
            if (!confirmed) {
                return;
            }

            // Afficher un loading sur le bouton
            const dropdownButton = this.selectorElement.querySelector('.dropdown-toggle');
            const originalContent = dropdownButton.innerHTML;
            dropdownButton.disabled = true;
            dropdownButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Changement...';

            // Appel API
            const response = await fetch('/api/tenant/switch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ idDivision }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || `HTTP ${response.status}`);
            }

            // Succès → Recharger la page pour appliquer le nouveau contexte tenant
            console.log('DivisionSelector: Switch réussi, rechargement de la page...');
            window.location.reload();

        } catch (error) {
            console.error('DivisionSelector: Erreur switchDivision', error);
            alert(`Erreur lors du changement de division:\n${error.message}`);

            // Restaurer le bouton
            const dropdownButton = this.selectorElement.querySelector('.dropdown-toggle');
            dropdownButton.disabled = false;
            dropdownButton.innerHTML = originalContent;
        }
    }

    /**
     * Affiche un message d'erreur dans le dropdown
     */
    showError(message) {
        if (this.loadingIndicator) {
            this.loadingIndicator.remove();
        }

        const li = document.createElement('li');
        li.className = 'px-3 py-2 text-danger';
        li.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>${message}`;
        this.divisionsList.appendChild(li);
    }
}

// Initialisation automatique au chargement du DOM (singleton)
let instance = null;
document.addEventListener('DOMContentLoaded', () => {
    if (!instance && document.getElementById('division-selector')) {
        instance = new DivisionSelector();
        console.log('DivisionSelector: Instance créée');
    }
});

export default DivisionSelector;
