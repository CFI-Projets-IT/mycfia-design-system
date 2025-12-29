import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour gérer les options d'images par asset
 * Toggle le select de style quand la checkbox est cochée/décochée
 */
export default class extends Controller {
    static targets = ['toggle', 'styleSelect'];

    connect() {
        // Initialiser l'état au chargement
        this.toggleStyleSelect();
    }

    toggleStyleSelect() {
        if (this.toggleTarget.checked) {
            this.styleSelectTarget.classList.remove('d-none');
        } else {
            this.styleSelectTarget.classList.add('d-none');
        }
    }
}
