import { Controller } from '@hotwired/stimulus';

/**
 * DataTable Controller - Gestion des tables de données
 * Filtrage en temps réel, tri, pagination
 */
export default class extends Controller {
    static targets = ['table', 'search'];

    connect() {
        console.log('DataTable controller connected');
        this.originalRows = Array.from(this.tableTarget.querySelectorAll('tbody tr'));
    }

    /**
     * Filtre les lignes du tableau en fonction de la recherche
     */
    filter(event) {
        const searchTerm = event.target.value.toLowerCase().trim();
        const tbody = this.tableTarget.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');

        if (searchTerm === '') {
            // Réafficher toutes les lignes
            rows.forEach((row) => {
                row.style.display = '';
            });
            this.updateNoResultsMessage(false);
            return;
        }

        let visibleCount = 0;

        rows.forEach((row) => {
            // Ignorer la ligne "aucun résultat" si elle existe
            if (row.querySelector('td[colspan]')) {
                row.style.display = 'none';
                return;
            }

            const text = row.textContent.toLowerCase();
            const matches = text.includes(searchTerm);

            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        this.updateNoResultsMessage(visibleCount === 0);
    }

    /**
     * Affiche ou cache le message "Aucun résultat"
     */
    updateNoResultsMessage(show) {
        const tbody = this.tableTarget.querySelector('tbody');
        let noResultsRow = tbody.querySelector('tr.no-results');

        if (show) {
            if (!noResultsRow) {
                const colCount = this.tableTarget.querySelectorAll('thead th').length;
                noResultsRow = document.createElement('tr');
                noResultsRow.classList.add('no-results');
                noResultsRow.innerHTML = `<td colspan="${colCount}" class="text-center">Aucun résultat trouvé</td>`;
                tbody.appendChild(noResultsRow);
            }
        } else {
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    }

    /**
     * Réinitialise le filtre
     */
    reset() {
        if (this.hasSearchTarget) {
            this.searchTarget.value = '';
            this.filter({ target: this.searchTarget });
        }
    }
}
