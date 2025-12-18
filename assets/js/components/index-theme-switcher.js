/**
 * Gestion du changement de thème sur la page index
 * Contrôle les boutons flottants de sélection de thème
 */

import { applyTheme, getCurrentTheme } from '../core/theme-switcher.js';

/**
 * Initialise les boutons de changement de thème sur la page index
 */
function initIndexThemeSwitcher() {
    // Vérifier qu'on est bien sur la page index
    if (!document.body.classList.contains('index-page')) {
        return;
    }

    const currentTheme = getCurrentTheme();
    const themeButtons = document.querySelectorAll('.theme-btn-floating');

    if (themeButtons.length === 0) {
        console.warn('[index-theme-switcher] Aucun bouton de thème trouvé');
        return;
    }

    // Activer le bouton correspondant au thème actuel
    themeButtons.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.theme === currentTheme);
    });

    // Attacher les événements de clic
    themeButtons.forEach(btn => {
        btn.addEventListener('click', handleThemeButtonClick);
    });

    console.log(`[index-theme-switcher] ${themeButtons.length} bouton(s) de thème initialisé(s)`);
}

/**
 * Gère le clic sur un bouton de thème
 *
 * @param {Event} event - Événement de clic
 */
function handleThemeButtonClick(event) {
    const button = event.currentTarget;
    const theme = button.dataset.theme;

    if (!theme) {
        console.warn('[index-theme-switcher] Aucun thème défini sur le bouton');
        return;
    }

    // Appliquer le nouveau thème
    applyTheme(theme);

    // Mettre à jour l'état des boutons
    document.querySelectorAll('.theme-btn-floating').forEach(btn => {
        btn.classList.remove('active');
    });
    button.classList.add('active');

    console.log(`[index-theme-switcher] Thème changé: ${theme}`);
}

// Initialiser au chargement du DOM
document.addEventListener('DOMContentLoaded', initIndexThemeSwitcher);

// Écouter les changements de thème depuis d'autres composants
document.addEventListener('themeChanged', (event) => {
    if (event.detail && event.detail.theme) {
        const themeButtons = document.querySelectorAll('.theme-btn-floating');
        themeButtons.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.theme === event.detail.theme);
        });
    }
});
