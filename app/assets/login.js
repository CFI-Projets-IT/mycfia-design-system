/**
 * Gestion du formulaire de connexion myCfia
 *
 * Toggle entre les modes d'authentification :
 * - Mode Token Gorillias (actuel)
 * - Mode Email/Password (futur)
 */

document.addEventListener('DOMContentLoaded', function() {
    const tokenFields = document.getElementById('token-fields');
    const credentialsFields = document.getElementById('credentials-fields');
    const jetonInput = document.getElementById('jetonUtilisateur');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const modeRadios = document.querySelectorAll('[name="mode"]');

    // Fonction pour basculer entre les modes
    function switchAuthMode(mode) {
        if (mode === 'token') {
            // Afficher champs token, masquer champs credentials
            tokenFields.classList.remove('d-none');
            credentialsFields.classList.add('d-none');

            // Activer validation token, désactiver validation credentials
            jetonInput.setAttribute('required', 'required');
            emailInput.removeAttribute('required');
            passwordInput.removeAttribute('required');

            // Vider les champs credentials
            emailInput.value = '';
            passwordInput.value = '';

            // Focus sur le champ token
            jetonInput.focus();
        } else {
            // Afficher champs credentials, masquer champs token
            tokenFields.classList.add('d-none');
            credentialsFields.classList.remove('d-none');

            // Activer validation credentials, désactiver validation token
            emailInput.setAttribute('required', 'required');
            passwordInput.setAttribute('required', 'required');
            jetonInput.removeAttribute('required');

            // Vider le champ token
            jetonInput.value = '';

            // Focus sur le champ email
            emailInput.focus();
        }
    }

    // Écouter les changements du toggle
    modeRadios.forEach(function(radio) {
        radio.addEventListener('change', function(e) {
            switchAuthMode(e.target.value);
        });
    });

    // Initialiser l'état au chargement (mode token par défaut)
    switchAuthMode('token');
});
