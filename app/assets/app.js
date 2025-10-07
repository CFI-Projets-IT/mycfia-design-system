import './bootstrap.js';
import './styles/app.css';

// Bootstrap CSS
import 'bootstrap/dist/css/bootstrap.min.css';

// Bootstrap Icons CSS
import 'bootstrap-icons/font/bootstrap-icons.css';

// Bootstrap JavaScript
import * as bootstrap from 'bootstrap';

// Exposer Bootstrap globalement (optionnel, pour usage dans le HTML)
window.bootstrap = bootstrap;

// Initialiser automatiquement les tooltips et popovers
document.addEventListener('DOMContentLoaded', () => {
    // Initialisation des tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Initialisation des popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
});

console.log('Bootstrap loaded!');
