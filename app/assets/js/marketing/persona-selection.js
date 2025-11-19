/**
 * Gestion de la sélection des personas
 *
 * Gère la sélection/désélection des personas avec checkboxes
 * et mise à jour du compteur de sélection
 */

function initPersonaSelection() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const personaCheckboxes = document.querySelectorAll('.persona-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');

    // Vérifier si les éléments existent (pour éviter erreurs sur autres pages)
    if (!selectAllCheckbox || !personaCheckboxes.length || !selectedCountSpan) {
        return;
    }

    // Fonction pour mettre à jour le compteur
    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.persona-checkbox:checked').length;
        selectedCountSpan.textContent = checkedCount;

        // Mettre à jour l'état de la checkbox "Tout sélectionner"
        const allChecked = checkedCount === personaCheckboxes.length;
        const someChecked = checkedCount > 0 && checkedCount < personaCheckboxes.length;
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = someChecked;
    }

    // Gestionnaire pour la checkbox "Tout sélectionner"
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        personaCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        updateSelectedCount();
    });

    // Gestionnaire pour chaque checkbox individuelle
    personaCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    // Initialiser le compteur au chargement
    updateSelectedCount();
}

// Exécuter immédiatement si le DOM est déjà chargé, sinon attendre
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPersonaSelection);
} else {
    initPersonaSelection();
}
