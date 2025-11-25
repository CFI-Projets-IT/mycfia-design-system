import './bootstrap.js';

// Import CSS files (AssetMapper way - ordre important)
import './styles/fonts.css';
import './styles/variables.css';
import './styles/themes/variables.css';
import './styles/themes/light.css';
import './styles/themes/dark-blue.css';
import './styles/themes/dark-red.css';
import './styles/components/glass-effects.css';
import './styles/components/hexagons.css';
import './styles/components/permissions.css';
import './styles/components/sidebar.css';
import './styles/components/topbar.css';
import './styles/components/chat.css';
import './styles/components/division-selector.css';
import './styles/layouts/auth.css';
import './styles/layouts/app-layout.css';
import './styles/layouts/home-layout.css';
import './styles/app.css';

// Bootstrap CSS
import 'bootstrap/dist/css/bootstrap.min.css';

// Bootstrap Icons CSS
import 'bootstrap-icons/font/bootstrap-icons.css';

// Bootstrap JavaScript
import * as bootstrap from 'bootstrap';

// Exposer Bootstrap globalement (optionnel, pour usage dans le HTML)
window.bootstrap = bootstrap;

// Import des composants JavaScript
import './js/ui/division-selector.js';
import './js/marketing/competitor-detection.js';
import './js/marketing/enrichment-review.js';
import './js/marketing/persona-selection.js';
import './js/marketing/persona-configure.js';

// Initialiser automatiquement les tooltips et popovers
document.addEventListener('DOMContentLoaded', () => {
    // Initialisation des tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl));

    // Initialisation des popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl));
});

console.log('Bootstrap loaded!');
