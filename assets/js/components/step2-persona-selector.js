/**
 * Persona Selector for Step 2
 * Gestion de la sélection des personas pour la campagne
 */

/**
 * Toggle la sélection d'une persona card
 * @param {HTMLElement} card - L'élément persona-card cliqué
 */
function togglePersonaCard(card) {
    card.classList.toggle('selected');

    // Mettre à jour l'état du bouton de validation
    updateValidateButton();

    const personaName = card.querySelector('.persona-title')?.textContent.trim() || 'Unknown';
    const isSelected = card.classList.contains('selected');

    console.log(`[persona-selector] Persona "${personaName}" ${isSelected ? 'sélectionnée' : 'désélectionnée'}`);
}

/**
 * Met à jour l'état du bouton de validation en fonction des sélections
 */
function updateValidateButton() {
    const validateBtn = document.getElementById('validateBtn');
    if (!validateBtn) return;

    const selectedPersonas = document.querySelectorAll('.persona-card.selected');

    if (selectedPersonas.length > 0) {
        validateBtn.disabled = false;
        validateBtn.classList.remove('disabled');
    } else {
        validateBtn.disabled = true;
        validateBtn.classList.add('disabled');
    }

    console.log(`[persona-selector] ${selectedPersonas.length} persona(s) sélectionnée(s)`);
}

/**
 * Valide la sélection des personas et redirige vers l'étape suivante
 */
function validatePersonas() {
    const selectedPersonas = document.querySelectorAll('.persona-card.selected');

    if (selectedPersonas.length === 0) {
        // TODO: Remplacer par Toast Bootstrap dans composant Twig final
        console.error('[persona-selector] Aucun persona sélectionné');
        alert('Veuillez sélectionner au moins un persona avant de continuer.');
        return;
    }

    console.log(`[persona-selector] Validation de ${selectedPersonas.length} persona(s)`);

    // Redirection vers l'étape suivante
    window.location.href = 'step3_loading.html';
}

/**
 * Initialise le sélecteur de personas
 */
export function initPersonaSelector() {
    const personaCards = document.querySelectorAll('.persona-card');

    if (personaCards.length === 0) {
        console.warn('[persona-selector] Aucune persona-card trouvée');
        return;
    }

    // Attacher les event listeners aux cartes
    personaCards.forEach(card => {
        card.addEventListener('click', () => {
            togglePersonaCard(card);
        });
    });

    // Attacher l'event listener au bouton de validation
    const validateBtn = document.getElementById('validateBtn');
    if (validateBtn) {
        validateBtn.addEventListener('click', validatePersonas);
    }

    // État initial du bouton
    updateValidateButton();

    console.log(`[persona-selector] ${personaCards.length} persona-cards initialisées`);
}

/**
 * Récupère les personas sélectionnés
 * @returns {Array<string>} Liste des noms de personas sélectionnés
 */
export function getSelectedPersonas() {
    const selectedCards = document.querySelectorAll('.persona-card.selected');
    return Array.from(selectedCards).map(card => {
        return card.querySelector('.persona-title')?.textContent.trim() || 'Unknown';
    });
}
