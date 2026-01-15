/**
 * Competitor Selector for Step 2
 * Gestion de la sélection des concurrents pour la campagne
 */

/**
 * Detecte le suffixe de theme depuis l'URL actuelle
 * @returns {string} Le suffixe de theme (_light, _dark-blue, _dark-red)
 */
function getThemeSuffix() {
    const path = window.location.pathname;
    if (path.includes('_dark-blue')) {
        return '_dark-blue';
    } else if (path.includes('_dark-red')) {
        return '_dark-red';
    }
    return '_light';
}

/**
 * Adapte une URL de base au theme actuel
 * @param {string} baseUrl - URL avec suffixe _light par defaut
 * @returns {string} URL adaptee au theme actuel
 */
function adaptUrlToTheme(baseUrl) {
    const themeSuffix = getThemeSuffix();
    return baseUrl.replace('_light.html', `${themeSuffix}.html`);
}

/**
 * Toggle la sélection d'une competitor card
 * @param {HTMLElement} card - L'élément competitor-card cliqué
 */
function toggleCompetitorCard(card) {
    card.classList.toggle('selected');

    // Force reflow pour s'assurer que les styles CSS sont réappliqués
    void card.offsetHeight;

    updateSelectedCount();
}

/**
 * Met à jour le compteur de concurrents sélectionnés
 */
function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.asset-card.selected').length;
    const countElement = document.getElementById('selectedCount');

    if (countElement) {
        countElement.textContent = selectedCount;
    }

    console.log(`[competitor-selector] ${selectedCount} concurrent(s) sélectionné(s)`);
}

/**
 * Valide la sélection des concurrents et redirige vers l'étape suivante
 */
function validateCompetitors() {
    const selectedCompetitors = document.querySelectorAll('.asset-card.selected');

    if (selectedCompetitors.length === 0) {
        // TODO: Remplacer par Toast Bootstrap dans composant Twig final
        console.error('[competitor-selector] Aucun concurrent sélectionné');
        alert('Veuillez sélectionner au moins un concurrent.');
        return;
    }

    console.log(`[competitor-selector] Validation de ${selectedCompetitors.length} concurrent(s)`);

    // Redirection vers l'étape suivante (loading personas)
    window.location.href = adaptUrlToTheme('step3_loading_light.html');
}

/**
 * Initialise le sélecteur de concurrents
 */
export function initCompetitorSelector() {
    const competitorCards = document.querySelectorAll('.asset-card');

    if (competitorCards.length === 0) {
        console.warn('[competitor-selector] Aucune competitor card trouvée');
        return;
    }

    // Attacher les event listeners aux cartes
    competitorCards.forEach(card => {
        card.addEventListener('click', () => {
            toggleCompetitorCard(card);
        });
    });

    // Attacher l'event listener au bouton de validation
    const validateBtn = document.getElementById('validateBtn');
    if (validateBtn) {
        validateBtn.addEventListener('click', validateCompetitors);
    }

    // Initialiser le compteur
    updateSelectedCount();

    console.log(`[competitor-selector] ${competitorCards.length} competitor-cards initialisées`);
}

/**
 * Récupère les concurrents sélectionnés
 * @returns {Array<string>} Liste des noms de concurrents sélectionnés
 */
export function getSelectedCompetitors() {
    const selectedCards = document.querySelectorAll('.asset-card.selected');
    return Array.from(selectedCards).map(card => {
        return card.querySelector('h5')?.textContent.trim() || 'Unknown';
    });
}
