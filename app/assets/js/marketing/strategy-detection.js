/**
 * Workflow 2 Étapes - Détection Interactive des Concurrents
 *
 * Gère l'interaction AJAX pour la détection automatique des concurrents
 * via scraping du site web + SerpApi Google Search.
 */

function initCompetitorDetection() {
    console.log('Initializing Workflow 2 Étapes - Competitor Detection');

    const detectBtn = document.getElementById('detectCompetitorsBtn');
    const loadingZone = document.getElementById('detectionLoading');
    const errorZone = document.getElementById('detectionError');
    const resultsZone = document.getElementById('detectionResults');
    const competitorsInputId = document.querySelector('[data-competitors-input]')?.dataset.competitorsInput;
    const competitorsInput = competitorsInputId ? document.getElementById(competitorsInputId) : null;
    const projectIdElement = document.querySelector('[data-project-id]');
    const projectId = projectIdElement ? projectIdElement.dataset.projectId : null;

    console.log('Elements found:', {
        detectBtn: !!detectBtn,
        loadingZone: !!loadingZone,
        errorZone: !!errorZone,
        resultsZone: !!resultsZone,
        competitorsInput: !!competitorsInput,
        projectId: projectId,
    });

    if (!detectBtn) {
        console.error('Button #detectCompetitorsBtn not found!');
        return;
    }

    if (!projectId) {
        console.error('Project ID not found!');
        return;
    }

    let detectedCompetitors = [];

    // Gestion du clic sur le bouton de détection
    detectBtn.addEventListener('click', async () => {
        console.log('Button clicked! Starting competitor detection...');

        // Réinitialiser l'affichage
        loadingZone.classList.remove('d-none');
        errorZone.classList.add('d-none');
        resultsZone.classList.add('d-none');
        detectBtn.disabled = true;

        console.log('Loading zone shown, button disabled');

        try {
            const url = `/marketing/strategy/detect-competitors/${projectId}`;
            console.log('Fetching:', url);

            // Appel AJAX vers le backend
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            console.log('Response status:', response.status);

            const data = await response.json();
            console.log('Response data:', data);

            if (data.success) {
                // Stocker les concurrents détectés
                detectedCompetitors = data.competitors || [];

                // Afficher les résultats
                displayResults(data);
            } else {
                // Afficher l'erreur
                showError(data.error || 'Erreur lors de la détection des concurrents');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            showError(`Erreur réseau : ${error.message}`);
        } finally {
            loadingZone.classList.add('d-none');
            detectBtn.disabled = false;
        }
    });

    // Afficher les résultats de détection
    function displayResults(data) {
        console.log('Displaying results:', data);

        // Mettre à jour la source
        const sourceText = data.source === 'serpapi' ? 'Google Search' : 'Manuel';
        document.getElementById('detectionSource').textContent = sourceText;

        // Mettre à jour la requête de recherche
        const queryText = data.search_query
            ? `Requête Google : "${data.search_query}"`
            : 'Détection basée sur les informations du projet';
        document.getElementById('detectionQuery').textContent = queryText;

        // Construire la liste des concurrents
        const listContainer = document.getElementById('competitorsList');
        listContainer.innerHTML = '';

        if (detectedCompetitors.length === 0) {
            listContainer.innerHTML =
                '<p class="text-muted mb-0">Aucun concurrent détecté. Ajoutez-les manuellement ci-dessous.</p>';
        } else {
            const list = document.createElement('div');

            detectedCompetitors.forEach((competitor, index) => {
                const item = document.createElement('div');
                item.className = 'competitor-item selected';
                item.dataset.index = index;
                item.dataset.name = competitor.name;

                item.innerHTML = `
                    <div class="d-flex align-items-start">
                        <input type="checkbox" class="form-check-input me-2 mt-1" checked data-competitor-index="${index}">
                        <div class="flex-grow-1">
                            <div class="fw-bold">${escapeHtml(competitor.name)}</div>
                            ${competitor.domain ? `<div class="small text-muted"><i class="bi bi-link-45deg"></i> ${escapeHtml(competitor.domain)}</div>` : ''}
                            ${competitor.description ? `<div class="small mt-1">${escapeHtml(competitor.description)}</div>` : ''}
                        </div>
                    </div>
                `;

                list.appendChild(item);
            });

            listContainer.appendChild(list);

            // Ajouter les gestionnaires d'événements pour les checkboxes
            listContainer.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                checkbox.addEventListener('change', function () {
                    const item = this.closest('.competitor-item');
                    if (this.checked) {
                        item.classList.add('selected');
                    } else {
                        item.classList.remove('selected');
                    }
                    updateCompetitorsInput();
                });
            });

            // Pré-remplir le champ avec les concurrents sélectionnés
            updateCompetitorsInput();
        }

        // Afficher la zone de résultats
        resultsZone.classList.remove('d-none');
    }

    // Mettre à jour le champ input avec les concurrents sélectionnés
    function updateCompetitorsInput() {
        if (!competitorsInput) {
            console.warn('Competitors input field not found');
            return;
        }

        const selectedCompetitors = [];

        document.querySelectorAll('#competitorsList input[type="checkbox"]:checked').forEach((checkbox) => {
            const index = checkbox.dataset.competitorIndex;
            const competitor = detectedCompetitors[index];
            if (competitor) {
                selectedCompetitors.push(competitor.name);
            }
        });

        competitorsInput.value = selectedCompetitors.join(', ');
        console.log('Updated competitors input:', competitorsInput.value);
    }

    // Afficher une erreur
    function showError(message) {
        document.getElementById('detectionErrorMessage').textContent = message;
        errorZone.classList.remove('d-none');
    }

    // Échapper le HTML pour éviter les injections XSS
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

// Exécuter immédiatement si le DOM est déjà chargé, sinon attendre
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCompetitorDetection);
} else {
    // DOM déjà chargé (par exemple après navigation ou refresh)
    initCompetitorDetection();
}
