/**
 * Step 5 Asset Selector - Asset Configuration Interactive Logic
 * Gère la sélection des assets marketing et le calcul des estimations
 */

/**
 * Active/désactive une carte d'asset
 * @param {HTMLElement} card - La carte d'asset cliquée
 */
export function toggleAssetCard(card) {
    const toggle = card.querySelector('.toggle-switch');
    const stepper = card.querySelector('.stepper-control');

    card.classList.toggle('active');
    toggle.classList.toggle('active');

    if (card.classList.contains('active')) {
        stepper.classList.remove('stepper-control-inactive');
    } else {
        stepper.classList.add('stepper-control-inactive');
    }

    updateEstimation();
}

/**
 * Augmente le nombre de variations pour un asset
 * @param {HTMLElement} btn - Le bouton + cliqué
 */
export function increaseVariation(btn) {
    const stepper = btn.closest('.stepper-control');
    const valueElement = stepper.querySelector('.stepper-value');
    let value = parseInt(valueElement.textContent);

    if (value < 3) {
        value++;
        valueElement.textContent = value;
        updateEstimation();
    }
}

/**
 * Diminue le nombre de variations pour un asset
 * @param {HTMLElement} btn - Le bouton - cliqué
 */
export function decreaseVariation(btn) {
    const stepper = btn.closest('.stepper-control');
    const valueElement = stepper.querySelector('.stepper-value');
    let value = parseInt(valueElement.textContent);

    if (value > 1) {
        value--;
        valueElement.textContent = value;
        updateEstimation();
    }
}

/**
 * Met à jour l'estimation du nombre d'assets et de variations
 */
export function updateEstimation() {
    const activeCards = document.querySelectorAll('.asset-card-modern.active');
    let totalVariations = 0;

    activeCards.forEach(card => {
        const valueElement = card.querySelector('.stepper-value');
        totalVariations += parseInt(valueElement.textContent);
    });

    // Mettre à jour le compteur dans le titre de section
    const selectedAssetsCountElement = document.getElementById('selectedAssetsCount');
    if (selectedAssetsCountElement) {
        selectedAssetsCountElement.textContent = activeCards.length;
    }

    // Mettre à jour le compteur dans l'estimation card
    const totalAssetsCountElement = document.getElementById('totalAssetsCount');
    if (totalAssetsCountElement) {
        totalAssetsCountElement.textContent = activeCards.length + ' canaux';
    }

    const totalVariationsElement = document.getElementById('totalVariations');
    if (totalVariationsElement) {
        totalVariationsElement.textContent = totalVariations + ' variations';
    }

    // Mettre à jour les badges actifs
    updateActiveBadges(activeCards);
}

/**
 * Met à jour les badges des canaux actifs dans l'estimation card
 * @param {NodeList} activeCards - Les cartes actives
 */
function updateActiveBadges(activeCards) {
    const badgesContainer = document.getElementById('activeBadges');
    if (!badgesContainer) return;

    badgesContainer.innerHTML = '';

    activeCards.forEach(card => {
        const title = card.querySelector('.asset-card-title')?.textContent || 'Unknown';
        const variations = card.querySelector('.stepper-value')?.textContent || '1';

        const badge = document.createElement('span');
        badge.className = 'badge bg-success estimation-badge';
        badge.textContent = `${title} (${variations})`;
        badgesContainer.appendChild(badge);
    });
}

/**
 * Bascule l'affichage de la zone d'instructions
 */
export function toggleInstructions() {
    const instructionsArea = document.getElementById('instructionsArea');
    const chevron = document.getElementById('instructionsChevron');

    if (instructionsArea) {
        instructionsArea.classList.toggle('d-none');

        // Mettre à jour le chevron immédiatement
        if (instructionsArea.classList.contains('d-none')) {
            chevron.classList.remove('bi-chevron-down');
            chevron.classList.add('bi-chevron-right');
        } else {
            chevron.classList.remove('bi-chevron-right');
            chevron.classList.add('bi-chevron-down');
        }
    }
}

/**
 * Gère le toggle de génération d'image pour un asset
 * @param {HTMLElement} toggle - Le toggle image cliqué
 */
export function toggleImageGeneration(toggle) {
    const card = toggle.closest('.asset-card-modern');
    const selectContainer = card.querySelector('.image-style-select');

    toggle.classList.toggle('active');

    if (toggle.classList.contains('active')) {
        selectContainer.classList.remove('d-none');
    } else {
        selectContainer.classList.add('d-none');
        // Réinitialiser la sélection
        const select = selectContainer.querySelector('select');
        if (select) {
            select.value = '';
        }
    }
}

/**
 * Initialise les event listeners pour les asset cards
 */
export function initAssetSelector() {
    // Event delegation pour les cartes d'assets
    document.querySelectorAll('.asset-card-modern').forEach(card => {
        card.addEventListener('click', (e) => {
            // Si le clic provient d'un bouton stepper, ne pas toggle la carte
            if (e.target.closest('.stepper-btn')) {
                return;
            }
            toggleAssetCard(card);
        });
    });

    // Event delegation pour les boutons stepper
    document.querySelectorAll('.stepper-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();

            if (btn.querySelector('.bi-plus')) {
                increaseVariation(btn);
            } else if (btn.querySelector('.bi-dash')) {
                decreaseVariation(btn);
            }
        });
    });

    // Toggle instructions area
    const instructionsToggle = document.querySelector('.instructions-toggle');
    if (instructionsToggle) {
        instructionsToggle.addEventListener('click', toggleInstructions);
    }

    // Event delegation pour les toggles de génération d'image
    document.querySelectorAll('.image-generation-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleImageGeneration(toggle);
        });
    });

    // Mise à jour initiale de l'estimation
    updateEstimation();
}
