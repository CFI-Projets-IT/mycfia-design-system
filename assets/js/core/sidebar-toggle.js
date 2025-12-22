/**
 * Gestion du toggle sidebar
 * Permet de réduire/étendre la sidebar et de sauvegarder l'état
 */

/**
 * Initialise les listeners pour le toggle de la sidebar
 * Attache les événements de clic sur tous les boutons toggle
 */
export function initSidebarToggle() {
    const toggleButtons = document.querySelectorAll('[data-sidebar-toggle]');

    if (toggleButtons.length === 0) {
        console.warn('[sidebar-toggle] Aucun bouton toggle trouvé');
        return;
    }

    toggleButtons.forEach(button => {
        button.addEventListener('click', toggleSidebar);
    });

    console.log(`[sidebar-toggle] ${toggleButtons.length} bouton(s) toggle initialisé(s)`);
}

/**
 * Toggle l'état de la sidebar (réduite/étendue)
 * Met à jour le margin du contenu principal
 * Sauvegarde l'état dans localStorage
 */
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main');

    if (!sidebar || !main) {
        console.error('[sidebar-toggle] Éléments sidebar ou main introuvables');
        return;
    }

    const isCollapsed = sidebar.classList.toggle('collapsed');
    main.style.marginLeft = isCollapsed ? '70px' : '260px';

    // Sauvegarder l'état dans localStorage
    localStorage.setItem('sidebarCollapsed', isCollapsed);

    console.log(`[sidebar-toggle] Sidebar ${isCollapsed ? 'réduite' : 'étendue'}`);
}

/**
 * Restaure l'état du sidebar au chargement de la page
 * Lit la préférence depuis localStorage
 */
export function restoreSidebarState() {
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main');

    if (!sidebar || !main) {
        console.warn('[sidebar-toggle] Éléments sidebar ou main introuvables pour restauration');
        return;
    }

    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

    if (isCollapsed) {
        sidebar.classList.add('collapsed');
        main.style.marginLeft = '70px';
        console.log('[sidebar-toggle] État restauré : sidebar réduite');
    } else {
        console.log('[sidebar-toggle] État restauré : sidebar étendue');
    }
}
