/**
 * Configuration de la génération des personas
 *
 * Gère les estimations de durée/coût selon le nombre de personas
 * et l'animation du bouton de soumission
 */

function initPersonaConfigure() {
    const countSelect = document.getElementById('persona_generation_config_count');
    const estimatedDuration = document.getElementById('estimated-duration');
    const estimatedCost = document.getElementById('estimated-cost');
    const diversityLevel = document.getElementById('diversity-level');
    const form = document.getElementById('persona-config-form');
    const submitBtn = document.getElementById('submit-btn');

    // Vérifier si les éléments existent (pour éviter erreurs sur autres pages)
    if (!countSelect || !estimatedDuration || !estimatedCost || !diversityLevel || !form || !submitBtn) {
        return;
    }

    // Données d'estimation par nombre de personas
    const estimations = {
        1: { duration: '~30 secondes', cost: '~0.015 $', diversity: 1 },
        3: { duration: '~85 secondes', cost: '~0.04 $', diversity: 2 },
        5: { duration: '~140 secondes', cost: '~0.065 $', diversity: 3 },
        10: { duration: '~280 secondes', cost: '~0.13 $', diversity: 4 },
    };

    function updateEstimations() {
        const count = parseInt(countSelect.value);
        const data = estimations[count];

        if (data) {
            estimatedDuration.textContent = data.duration;
            estimatedCost.textContent = data.cost;

            // Mise à jour des étoiles de diversité
            let stars = '';
            for (let i = 1; i <= 4; i++) {
                if (i <= data.diversity) {
                    stars += '<i class="bi bi-star-fill text-warning"></i> ';
                } else if (i === data.diversity + 1 && count < 10) {
                    stars += '<i class="bi bi-star-half text-warning"></i> ';
                } else {
                    stars += '<i class="bi bi-star text-muted"></i> ';
                }
            }
            diversityLevel.innerHTML = stars.trim();
        }
    }

    // Écouter les changements de sélection
    countSelect.addEventListener('change', updateEstimations);

    // Initialiser avec la valeur par défaut
    updateEstimations();

    // Animation du bouton de soumission
    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Lancement en cours...';
    });
}

// Exécuter immédiatement si le DOM est déjà chargé, sinon attendre
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPersonaConfigure);
} else {
    initPersonaConfigure();
}
