/**
 * Gestion du formulaire de connexion myCfia
 *
 * Toggle entre les modes d'authentification :
 * - Mode Token Gorillias (actuel)
 * - Mode Email/Password (futur)
 *
 * IMPORTANT : Les éléments DOM sont récupérés dynamiquement à chaque interaction
 * pour gérer le cas où la page est rechargée avec une erreur de formulaire.
 * Bootstrap stoppe la propagation des événements, donc on utilise la phase de capture.
 */

(function() {
    'use strict';

    function initLoginForm() {
        // Fonction pour basculer entre les modes
        function switchAuthMode(mode) {
            // Récupérer les éléments à chaque fois (ne pas stocker les références)
            // car le DOM peut être rechargé après une erreur de formulaire
            const tokenFields = document.getElementById('token-fields');
            const credentialsFields = document.getElementById('credentials-fields');
            const jetonInput = document.getElementById('jetonUtilisateur');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            if (!tokenFields || !credentialsFields || !jetonInput || !emailInput || !passwordInput) {
                return;
            }

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
            }
        }

        // Capturer les clics AVANT Bootstrap avec useCapture: true
        // car Bootstrap stoppe la propagation des événements
        document.addEventListener('click', function(e) {
            const target = e.target;

            // Récupérer les radios à chaque clic (ne pas stocker les références)
            const modeTokenRadio = document.getElementById('mode-token');
            const modeCredentialsRadio = document.getElementById('mode-credentials');

            // Détecter les clics sur les radios ou leurs labels
            if (target.id === 'mode-token' || (target.tagName === 'LABEL' && target.getAttribute('for') === 'mode-token')) {
                if (modeTokenRadio && modeCredentialsRadio) {
                    modeTokenRadio.checked = true;
                    modeCredentialsRadio.checked = false;
                    switchAuthMode('token');
                }
            } else if (target.id === 'mode-credentials' || (target.tagName === 'LABEL' && target.getAttribute('for') === 'mode-credentials')) {
                if (modeTokenRadio && modeCredentialsRadio) {
                    modeCredentialsRadio.checked = true;
                    modeTokenRadio.checked = false;
                    switchAuthMode('credentials');
                }
            }
        }, true);

        // Initialiser l'état au chargement
        const modeTokenRadio = document.getElementById('mode-token');
        const modeCredentialsRadio = document.getElementById('mode-credentials');

        if (modeTokenRadio && modeCredentialsRadio) {
            if (modeTokenRadio.checked) {
                switchAuthMode('token');
            } else if (modeCredentialsRadio.checked) {
                switchAuthMode('credentials');
            }
        }
    }

    // Initialiser dès que possible
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoginForm);
    } else {
        // DOM déjà chargé (cas du rechargement avec erreur)
        initLoginForm();
    }
})();
