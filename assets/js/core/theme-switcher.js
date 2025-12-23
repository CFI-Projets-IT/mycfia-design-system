/**
 * Gestion du changement de thème dynamique
 * Permet de basculer entre les thèmes light, dark-blue, dark-red
 */

/**
 * Constantes des thèmes disponibles
 */
export const THEMES = {
    LIGHT: 'light',
    DARK_BLUE: 'dark-blue',
    DARK_RED: 'dark-red'
};

/**
 * Initialise le système de thème
 * Détecte le thème depuis le HTML, puis localStorage, sinon défaut à light
 */
export function initThemeSwitcher() {
    // Détecter le thème depuis le lien CSS déjà présent dans le HTML
    const themeLink = document.getElementById('theme-stylesheet');
    let currentTheme = THEMES.LIGHT;

    if (themeLink && themeLink.href) {
        // Extraire le nom du thème depuis l'URL du CSS
        const match = themeLink.href.match(/themes\/([^.]+)\.css/);
        if (match && match[1]) {
            currentTheme = match[1];
        }
    }

    // Si pas de thème dans le HTML, utiliser localStorage ou défaut
    const savedTheme = localStorage.getItem('theme');
    if (!currentTheme || currentTheme === THEMES.LIGHT) {
        currentTheme = savedTheme || THEMES.LIGHT;
    }

    console.log('[theme-switcher] Initialisation avec thème:', currentTheme);

    // Sauvegarder le thème détecté pour la prochaine session
    localStorage.setItem('theme', currentTheme);
    document.body.setAttribute('data-theme', currentTheme);

    // Écouter les changements de thème personnalisés
    document.addEventListener('themeChange', (e) => {
        if (e.detail && e.detail.theme) {
            applyTheme(e.detail.theme);
        }
    });
}

/**
 * Applique un thème spécifique
 * Change le fichier CSS chargé et met à jour l'attribut data-theme
 *
 * @param {string} themeName - Nom du thème (light, dark-blue, dark-red)
 */
export function applyTheme(themeName) {
    // Valider le nom du thème
    if (!Object.values(THEMES).includes(themeName)) {
        console.error(`[theme-switcher] Thème invalide: ${themeName}`);
        return;
    }

    // Charger le fichier CSS correspondant
    const themeLink = document.getElementById('theme-stylesheet');
    if (themeLink) {
        // Construire le chemin relatif en fonction de la profondeur du fichier
        const depth = window.location.pathname.split('/').filter(x => x).length;
        const prefix = depth > 2 ? '../' : '';
        themeLink.href = `${prefix}assets/css/themes/${themeName}.css`;

        console.log(`[theme-switcher] Chargement du thème: ${themeLink.href}`);
    } else {
        console.warn('[theme-switcher] Élément #theme-stylesheet introuvable');
    }

    // Mettre à jour l'attribut data-theme sur body pour hooks CSS
    document.body.setAttribute('data-theme', themeName);

    // Sauvegarder la préférence dans localStorage
    localStorage.setItem('theme', themeName);

    // Émettre un événement pour notifier les autres composants
    document.dispatchEvent(new CustomEvent('themeChanged', {
        detail: { theme: themeName }
    }));

    console.log(`[theme-switcher] Thème appliqué: ${themeName}`);
}

/**
 * Retourne le thème actuellement actif
 *
 * @returns {string} Nom du thème actuel
 */
export function getCurrentTheme() {
    return localStorage.getItem('theme') || THEMES.LIGHT;
}
