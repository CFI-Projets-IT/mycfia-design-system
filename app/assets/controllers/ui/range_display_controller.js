import { Controller } from '@hotwired/stimulus';

/**
 * Range Display Controller
 * Affiche dynamiquement la valeur d'un input range
 */
export default class extends Controller {
    static targets = ['value'];

    connect() {
        console.log('Range display controller connected');

        // Trouver l'input range associé
        this.rangeInput = this.element.closest('.mb-4').querySelector('input[type="range"]');

        if (this.rangeInput) {
            this.updateDisplay();
            this.rangeInput.addEventListener('input', () => this.updateDisplay());
        }
    }

    updateDisplay() {
        if (this.hasValueTarget && this.rangeInput) {
            const value = this.rangeInput.value;
            const suffix = this.rangeInput.dataset.suffix || '';
            this.valueTarget.textContent = `${value} ${suffix}`;
        }
    }
}
