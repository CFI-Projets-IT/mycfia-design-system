/**
 * Gestion de la validation de l'enrichissement du projet
 *
 * Gère la sélection obligatoire d'un nom alternatif
 * et l'activation/désactivation du bouton de validation
 */

function initEnrichmentReview() {
    const nameRadios = document.querySelectorAll('input[name="selectedName"]');
    const validateButton = document.getElementById('validate-button');
    const selectedNameInput = document.getElementById('selected-name-input');
    const validationForm = document.getElementById('validation-form');

    // Vérifier si les éléments existent (pour éviter erreurs sur autres pages)
    if (!nameRadios.length || !validateButton || !selectedNameInput || !validationForm) {
        return;
    }

    // Gérer la sélection d'un nom alternatif
    nameRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Mettre à jour le champ caché
            selectedNameInput.value = this.value;

            // Activer le bouton de validation
            validateButton.disabled = false;
            validateButton.classList.remove('btn-secondary');
            validateButton.classList.add('btn-primary');

            // Gérer l'indicateur visuel de sélection
            document.querySelectorAll('.selected-indicator').forEach(indicator => {
                indicator.classList.add('d-none');
            });
            this.closest('label').querySelector('.selected-indicator').classList.remove('d-none');

            // Retirer la classe "active" de tous les labels
            document.querySelectorAll('#alternative-names-list label').forEach(label => {
                label.classList.remove('active');
            });

            // Ajouter la classe "active" au label sélectionné
            this.closest('label').classList.add('active');
        });
    });

    // Validation du formulaire
    validationForm.addEventListener('submit', function(e) {
        if (!selectedNameInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un nom alternatif avant de valider.');
            return false;
        }
    });
}

// Exécuter immédiatement si le DOM est déjà chargé, sinon attendre
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEnrichmentReview);
} else {
    initEnrichmentReview();
}
