import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur Stimulus pour gérer les barres de progression dynamiques
 * Utilise data-* attributes pour définir le pourcentage
 */
export default class extends Controller {
    static values = {
        percent: Number
    }

    connect() {
        // Appliquer le pourcentage à la barre de progression
        this.element.style.width = `${this.percentValue}%`;
    }
}
