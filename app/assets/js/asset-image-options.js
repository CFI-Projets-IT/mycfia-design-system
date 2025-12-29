/**
 * Gestion des options d'images par asset
 * Toggle le select de style quand la checkbox est cochée/décochée
 */
document.addEventListener('DOMContentLoaded', () => {
    // Liste des assets compatibles avec génération d'images
    const imageCompatibleAssets = ['instagram_post', 'facebook_post', 'linkedin_post', 'article', 'iab'];

    imageCompatibleAssets.forEach((assetType) => {
        const checkbox = document.querySelector(`.image-toggle-${assetType}`);
        const styleSelect = document.querySelector(`.image-style-${assetType}`);

        if (checkbox && styleSelect) {
            // Event listener sur changement de checkbox
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    styleSelect.classList.remove('d-none');
                } else {
                    styleSelect.classList.add('d-none');
                }
            });
        }
    });
});
