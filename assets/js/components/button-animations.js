/**
 * Animations des boutons au hover
 * Applique un effet de scale subtil sur les boutons
 */

/**
 * Initialise les animations sur les boutons
 * Attache les événements de hover sur boutons et liens
 */
export function initButtonAnimations() {
    const buttons = document.querySelectorAll('.btn-primary-custom, .btn-glass, .btn-glass-primary, a[href]');

    if (buttons.length === 0) {
        console.warn('[button-animations] Aucun bouton trouvé');
        return;
    }

    // Filtrer pour éviter les éléments qui ont déjà des animations spécifiques
    const validButtons = Array.from(buttons).filter(btn => {
        // Exclure les boutons de la sidebar et les FAB
        return !btn.closest('.sidebar') && !btn.classList.contains('fab-back');
    });

    validButtons.forEach(btn => {
        btn.addEventListener('mouseenter', handleButtonHover);
        btn.addEventListener('mouseleave', handleButtonLeave);
    });

    console.log(`[button-animations] ${validButtons.length} bouton(s) avec animation initialisé(s)`);
}

/**
 * Gère le hover sur un bouton
 * Applique un léger scale (1.02)
 *
 * @param {Event} event - Événement mouseenter
 */
function handleButtonHover(event) {
    const button = event.currentTarget;

    // Éviter de casser les transitions CSS existantes
    if (!button.style.transition) {
        button.style.transition = 'all 0.2s ease';
    }

    button.style.transform = 'scale(1.02)';
}

/**
 * Gère la sortie du hover sur un bouton
 * Réinitialise le scale à 1
 *
 * @param {Event} event - Événement mouseleave
 */
function handleButtonLeave(event) {
    const button = event.currentTarget;
    button.style.transform = 'scale(1)';
}
