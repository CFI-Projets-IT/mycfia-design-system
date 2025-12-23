/**
 * Gestion des cartes de sélection de thème
 * Interactions avec les theme-card pour changer de thème
 */

import { applyTheme } from '../core/theme-switcher.js';

/**
 * Initialise les listeners sur les cartes de thème
 * Attache les événements de clic et hover
 */
export function initThemeCards() {
    const themeCards = document.querySelectorAll('.theme-card');

    if (themeCards.length === 0) {
        console.log('[theme-cards] Aucune carte de thème trouvée (normal si pas sur page settings)');
        return;
    }

    themeCards.forEach(card => {
        card.addEventListener('click', handleCardClick);
        card.addEventListener('mouseenter', handleCardHover);
        card.addEventListener('mouseleave', handleCardLeave);
    });

    console.log(`[theme-cards] ${themeCards.length} carte(s) de thème initialisée(s)`);
}

/**
 * Gère le clic sur une carte de thème
 * Sélectionne la carte, décoche les autres et applique le thème
 *
 * @param {Event} event - Événement de clic
 */
function handleCardClick(event) {
    const card = event.currentTarget;
    const input = card.querySelector('input');

    if (!input) {
        console.warn('[theme-cards] Input introuvable dans la carte');
        return;
    }

    const inputName = input.name;
    const themeName = input.value;

    // Retirer active de tous les cards du même groupe
    document.querySelectorAll(`input[name="${inputName}"]`).forEach(i => {
        const parentCard = i.closest('.theme-card');
        if (parentCard) {
            parentCard.classList.remove('active', 'selecting');
        }
    });

    // Ajouter l'animation de sélection
    card.classList.add('selecting', 'active');
    input.checked = true;

    // Retirer la classe d'animation après son exécution
    setTimeout(() => {
        card.classList.remove('selecting');
    }, 600);

    // NOTE: Changement de thème désactivé pour les mockups/previews
    // Les cartes sont juste visuelles, pas fonctionnelles
    console.log(`[theme-cards] Sélection visuelle uniquement: ${inputName} = ${themeName}`);

    // Pour activer le changement de thème réel, décommenter :
    // if (inputName === 'theme') {
    //     applyTheme(themeName);
    // }
}

/**
 * Gère le survol d'une carte de thème
 * Applique un effet de scale sur la preview
 * TODO: Remplacer par classe CSS '.theme-preview-hover' dans composant Twig
 *
 * @param {Event} event - Événement mouseenter
 */
function handleCardHover(event) {
    const card = event.currentTarget;
    const preview = card.querySelector('.theme-preview');

    if (preview) {
        // CSS équivalent: .theme-preview-hover { transform: scale(1.02); transition: transform 0.3s ease; }
        preview.style.transform = 'scale(1.02)';
        preview.style.transition = 'transform 0.3s ease';
    }
}

/**
 * Gère la sortie du survol d'une carte de thème
 * Réinitialise le scale de la preview
 * TODO: Utiliser :hover CSS dans composant Twig final
 *
 * @param {Event} event - Événement mouseleave
 */
function handleCardLeave(event) {
    const card = event.currentTarget;
    const preview = card.querySelector('.theme-preview');

    if (preview) {
        // CSS équivalent: .theme-preview { transform: scale(1); }
        preview.style.transform = 'scale(1)';
    }
}
