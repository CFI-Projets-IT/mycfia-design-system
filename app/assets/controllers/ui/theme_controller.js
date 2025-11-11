import { Controller } from '@hotwired/stimulus';

/*
 * Controller Stimulus pour gérer le changement de thème
 * Écoute les changements du Live Component ThemeSelector
 * et applique la classe theme correspondante sur body
 */
export default class extends Controller {
    static values = {
        current: String,
    };

    connect() {
        // Appliquer le thème initial au chargement
        this.applyTheme(this.currentValue || 'light');

        // Écouter les changements du Live Component
        this.element.addEventListener('live:update-finished', () => {
            this.applyTheme(this.currentValue);
        });
    }

    applyTheme(theme) {
        // Retirer toutes les classes de thème existantes
        document.body.classList.remove('theme-light', 'theme-dark-blue', 'theme-dark-red');

        // Ajouter la classe du nouveau thème
        document.body.classList.add(`theme-${theme}`);

        // Ajouter une classe temporaire pour animer la transition
        document.body.classList.add('theme-transition');
        setTimeout(() => {
            document.body.classList.remove('theme-transition');
        }, 300);
    }

    currentValueChanged() {
        this.applyTheme(this.currentValue);
    }
}
