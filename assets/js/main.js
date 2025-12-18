/**
 * Point d'entrée principal du JavaScript myCFiA Design System
 * Initialise tous les modules et composants
 */

import { initSidebarToggle, restoreSidebarState } from './core/sidebar-toggle.js';
import { initThemeSwitcher } from './core/theme-switcher.js';
import { initThemeCards } from './components/theme-cards.js';
import { initButtonAnimations } from './components/button-animations.js';

/**
 * Initialisation au chargement du DOM
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log('===========================================');
    console.log('myCFiA Design System - Initialisation');
    console.log('===========================================');

    try {
        // 1. Initialiser le système de thème
        console.log('[main] Initialisation du theme switcher...');
        initThemeSwitcher();

        // 2. Restaurer l'état du sidebar
        console.log('[main] Restauration de l\'état de la sidebar...');
        restoreSidebarState();

        // 3. Initialiser le toggle de la sidebar
        console.log('[main] Initialisation du toggle sidebar...');
        initSidebarToggle();

        // 4. Initialiser les cartes de thème (si présentes)
        console.log('[main] Initialisation des theme cards...');
        initThemeCards();

        // 5. Initialiser les animations de boutons
        console.log('[main] Initialisation des animations de boutons...');
        initButtonAnimations();

        console.log('===========================================');
        console.log('myCFiA Design System - Prêt ✓');
        console.log('===========================================');
    } catch (error) {
        console.error('[main] Erreur lors de l\'initialisation:', error);
    }
});
