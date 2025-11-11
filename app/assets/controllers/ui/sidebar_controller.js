import { Controller } from '@hotwired/stimulus';

/**
 * Sidebar Controller - Gestion du menu latéral
 * Toggle sidebar sur mobile/desktop
 */
export default class extends Controller {
    static targets = ['sidebar'];

    connect() {
        console.log('Sidebar controller connected');
        this.initializeSidebar();
    }

    initializeSidebar() {
        // Récupérer l'état du sidebar depuis localStorage
        const sidebarState = localStorage.getItem('sidebarCollapsed');
        const sidebar = document.querySelector('.app-sidebar');

        if (sidebar && sidebarState === 'true') {
            sidebar.classList.add('collapsed');
        }
    }

    toggle() {
        const sidebar = document.querySelector('.app-sidebar');

        if (sidebar) {
            sidebar.classList.toggle('collapsed');

            // Sauvegarder l'état dans localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        }
    }

    /**
     * Ferme le sidebar sur mobile après clic sur un lien
     */
    closeMobile(_event) {
        if (window.innerWidth < 768) {
            const sidebar = document.querySelector('.app-sidebar');
            if (sidebar) {
                sidebar.classList.add('collapsed');
            }
        }
    }
}
