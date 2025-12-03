/**
 * Gestion de la sidebar homepage (toggle collapse)
 *
 * Gère le comportement responsive de la sidebar :
 * - Mode ouvert (280px)
 * - Mode collapsed (60px - icônes uniquement)
 */

/**
 * Toggle l'état collapsed de la sidebar
 */
function toggleSidebar() {
    const sidebar = document.querySelector('.home-sidebar');
    const main = document.querySelector('.home-main');

    if (sidebar && main) {
        sidebar.classList.toggle('sidebar-collapsed');

        // Éléments à centrer en mode collapsed (Bootstrap classes)
        const sidebarLinks = sidebar.querySelectorAll('.sidebar-link');
        const navLinks = sidebar.querySelectorAll('.nav-link');
        const navPills = sidebar.querySelectorAll('.nav-section-title-pill');

        // Ajuster le margin du main selon l'état
        if (sidebar.classList.contains('sidebar-collapsed')) {
            main.style.marginLeft = '60px';

            // Ajouter justify-content-center (Bootstrap) en mode collapsed
            sidebarLinks.forEach((link) => link.classList.add('justify-content-center'));
            navLinks.forEach((link) => link.classList.add('justify-content-center'));
            navPills.forEach((pill) => pill.classList.add('justify-content-center'));
        } else {
            main.style.marginLeft = '280px';

            // Retirer justify-content-center en mode ouvert
            sidebarLinks.forEach((link) => link.classList.remove('justify-content-center'));
            navLinks.forEach((link) => link.classList.remove('justify-content-center'));
            navPills.forEach((pill) => pill.classList.remove('justify-content-center'));
        }
    }
}

// Exposer la fonction globalement pour usage dans les onclick
window.toggleSidebar = toggleSidebar;
