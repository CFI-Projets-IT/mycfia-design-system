/**
 * marketing_project_new.js
 * Gestion de l'enrichissement IA du formulaire de création de projet marketing
 *
 * Fonctionnalités :
 * - Soumission AJAX pour enrichissement IA
 * - Connexion EventSource Mercure pour notifications temps réel
 * - Affichage des résultats dans une modal Bootstrap
 * - Acceptation et application des suggestions IA
 */

/**
 * Initialise le script d'enrichissement
 * @param {Object} config - Configuration depuis data attributes
 * @param {string} config.mercureUrl - URL publique du hub Mercure
 * @param {string} config.enrichmentResultsUrl - URL pour récupérer les résultats (avec __TASK_ID__ placeholder)
 * @param {string} config.enrichmentAcceptUrl - URL pour accepter les suggestions (avec __TASK_ID__ placeholder)
 */
export function initMarketingProjectEnrichment(config) {
    console.log('🚀 Initialisation enrichissement IA');

    const form = document.querySelector('form[name="project"]');
    const analyzeBtn = document.querySelector('button[name="project[analyze]"]');
    const enrichmentModal = new bootstrap.Modal(document.getElementById('enrichmentModal'));
    const mercurePublicUrl = config.mercureUrl;

    let currentTaskId = null;
    let eventSource = null;
    let alternativeNames = [];

    // Vérifications initiales
    if (!analyzeBtn) {
        console.error('❌ Bouton "Analyser avec IA" introuvable');
        return;
    }

    console.log('✅ Bouton trouvé, attachement du listener');

    // === EVENT: Clic sur "Analyser avec IA" ===
    analyzeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        // Validation formulaire
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Soumettre via AJAX
        submitForEnrichment();
    });

    /**
     * Soumet le formulaire pour enrichissement IA
     */
    function submitForEnrichment() {
        const formData = new FormData(form);
        formData.append('analyze', '1'); // Marqueur pour détection côté serveur

        // FIX: FormData sérialise les checkboxes avec leurs indices (0, 1, 2...) au lieu des valeurs enum
        // On doit supprimer les valeurs auto-sérialisées et reconstruire manuellement avec les vraies valeurs
        formData.delete('project[selectedAssetTypes][]');

        // Récupérer toutes les checkboxes cochées des selectedAssetTypes
        const assetCheckboxes = form.querySelectorAll(
            'input[type="checkbox"][name="project[selectedAssetTypes][]"]:checked'
        );
        assetCheckboxes.forEach((checkbox) => {
            // Utiliser la valeur réelle de la checkbox (ex: "linkedin_post", pas "0")
            formData.append('project[selectedAssetTypes][]', checkbox.value);
        });

        console.log('=== FormData envoyé ===');
        for (const [key, value] of formData.entries()) {
            console.log(`${key}:`, value instanceof File ? value.name : value);
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
            .then((response) =>
                response.json().then((data) => ({
                    ok: response.ok,
                    status: response.status,
                    data: data,
                }))
            )
            .then(({ ok, data }) => {
                if (ok && data.success) {
                    currentTaskId = data.taskId;
                    openModalWithLoader();
                    connectToMercure(data.taskId);
                } else {
                    handleSubmitError(data);
                }
            })
            .catch((error) => {
                console.error('❌ Erreur AJAX:', error);
                alert(`Erreur de connexion au serveur: ${error.message}`);
            });
    }

    /**
     * Gère les erreurs de soumission
     */
    function handleSubmitError(data) {
        let errorMessage = data.error || "Erreur lors du démarrage de l'enrichissement";

        if (data.validation_errors && data.validation_errors.length > 0) {
            errorMessage = `Veuillez corriger les erreurs suivantes :\n${data.validation_errors.join('\n')}`;
        }

        alert(errorMessage);
        console.error('❌ Erreur validation:', data);
    }

    /**
     * Ouvre la modal avec le loader actif
     */
    function openModalWithLoader() {
        document.getElementById('enrichmentLoader').style.display = 'block';
        document.getElementById('enrichmentResults').style.display = 'none';
        document.getElementById('enrichmentError').style.display = 'none';
        document.getElementById('acceptEnrichmentBtn').style.display = 'none';

        enrichmentModal.show();
    }

    /**
     * Connexion au hub Mercure pour écouter les événements de tâche
     * @param {string} taskId - ID de la tâche asynchrone
     */
    function connectToMercure(taskId) {
        const url = `${mercurePublicUrl}?topic=/tasks/${taskId}`;
        console.log('📡 Connexion Mercure:', url);

        eventSource = new EventSource(url);

        // Écouter les événements spécifiques du bundle avec types SSE
        eventSource.addEventListener('TaskStartedEvent', (event) => {
            console.log('🟢 TaskStartedEvent reçu');
            const data = JSON.parse(event.data);
            console.log('📨 Données:', data);
        });

        eventSource.addEventListener('TaskCompletedEvent', (event) => {
            console.log('✅ TaskCompletedEvent reçu');
            const data = JSON.parse(event.data);
            console.log('📨 Données:', data);
            closeEventSource();
            fetchEnrichmentResults(taskId);
        });

        eventSource.addEventListener('TaskFailedEvent', (event) => {
            console.error('❌ TaskFailedEvent reçu');
            const data = JSON.parse(event.data);
            console.log('📨 Données erreur:', data);
            closeEventSource();
            showError("L'enrichissement a échoué. Veuillez réessayer.");
        });

        eventSource.onerror = (error) => {
            console.error('❌ EventSource error:', error);
        };
    }

    /**
     * Ferme la connexion EventSource
     */
    function closeEventSource() {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
    }

    /**
     * Récupère les résultats d'enrichissement via AJAX
     * @param {string} taskId - ID de la tâche
     */
    function fetchEnrichmentResults(taskId) {
        const url = config.enrichmentResultsUrl.replace('__TASK_ID__', taskId);

        fetch(url)
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    displayResults(data.results);
                } else {
                    showError(data.error || 'Erreur lors de la récupération des résultats');
                }
            })
            .catch((error) => {
                console.error('❌ Erreur récupération résultats:', error);
                showError('Erreur de connexion au serveur');
            });
    }

    /**
     * Affiche les résultats d'enrichissement dans la modal
     * @param {Object} results - Résultats depuis le cache
     */
    function displayResults(results) {
        document.getElementById('enrichmentLoader').style.display = 'none';
        document.getElementById('enrichmentResults').style.display = 'block';
        document.getElementById('acceptEnrichmentBtn').style.display = 'inline-block';

        console.log('📊 Affichage des résultats enrichissement (v3.1.0):', results);

        // Structure v3.1.0 : données venant du ProjectEnrichedEventListener
        const mappedResults = {
            alternative_names: results.alternative_names || [],
            smart_objectives: results.enhanced_objectives || '',
            strategic_recommendations: results.strategic_recommendations || [],
            success_factors: results.success_factors || [],
        };

        console.log('📊 Structure mappée v3.1.0:', mappedResults);

        // Stocker les noms alternatifs
        alternativeNames = mappedResults.alternative_names;

        // Stocker les objectifs (maintenant STRING) pour acceptEnrichment
        window.currentEnrichedObjectives = mappedResults.smart_objectives || '';

        // 1. Afficher les noms alternatifs avec radio buttons
        renderAlternativeNames(mappedResults.alternative_names);

        // 2. Afficher objectifs SMART (STRING texte formaté)
        renderSmartObjectives('objectivesDetailContainer', mappedResults.smart_objectives);

        // 3. Afficher recommandations stratégiques (array de strings)
        renderRecommendations('recommendationsContainer', mappedResults.strategic_recommendations);

        // 4. Afficher facteurs clés de succès (array de strings)
        renderSuccessFactors('successFactorsContainer', mappedResults.success_factors);

        // 5. Afficher métriques et analytics
        renderMetrics('metricsContainer', results);
    }

    /**
     * Affiche les objectifs SMART détaillés (v3.1.0 - STRING).
     *
     * @param {string} containerId - ID du conteneur HTML
     * @param {string} objectives - Texte formaté des objectifs SMART (v3.1.0)
     */
    function renderSmartObjectives(containerId, objectives) {
        const container = document.getElementById(containerId);

        if (!objectives || typeof objectives !== 'string') {
            container.innerHTML = '<p class="text-muted">Aucun objectif disponible</p>';
            return;
        }

        // v3.1.0: objectives est maintenant un STRING contenant du texte formaté
        // On affiche le texte avec formatage préservé et style élégant
        const html = `
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-bullseye"></i> Objectifs SMART Détaillés</h6>
                </div>
                <div class="card-body">
                    <div class="objectives-content" style="white-space: pre-wrap; line-height: 1.8; font-size: 0.95rem;">
                        ${escapeHtml(objectives)}
                    </div>
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    /**
     * Affiche les noms alternatifs avec radio buttons
     * @param {Array<string>} names - Liste des noms alternatifs
     */
    function renderAlternativeNames(names) {
        const container = document.getElementById('alternativeNamesContainer');
        container.innerHTML = names
            .map(
                (name, index) => `
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="selectedName" id="name${index}" value="${index}" ${index === 0 ? 'checked' : ''}>
                <label class="form-check-label fw-semibold" for="name${index}">
                    ${escapeHtml(name)}
                </label>
            </div>
        `
            )
            .join('');
    }

    /**
     * Affiche les métriques et analytics dans un conteneur formaté
     * @param {string} containerId - ID du conteneur
     * @param {Object} results - Résultats complets d'enrichissement
     */
    function renderMetrics(containerId, results) {
        const container = document.getElementById(containerId);

        let html = '<div class="row g-3">';

        // === Section Métriques depuis enhanced_objectives (si disponible) ===
        const principal = results.enhanced_objectives?.principal || results.enhanced_objectives?.objectif_principal;
        if (principal?.metriques) {
            // Métriques quantitatives (gérer les deux formats : quantitatives OU quantitatif)
            const quantitatives = principal.metriques.quantitatives || principal.metriques.quantitatif;
            if (quantitatives && Object.keys(quantitatives).length > 0) {
                html += '<div class="col-md-6">';
                html += '<div class="card h-100 border-primary">';
                html +=
                    '<div class="card-header bg-primary text-white fw-bold"><i class="bi bi-graph-up"></i> Métriques Quantitatives</div>';
                html += '<div class="card-body"><table class="table table-sm table-borderless mb-0">';
                Object.entries(quantitatives).forEach(([key, value]) => {
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
                    html += `<tr><td class="fw-bold">${escapeHtml(label)}:</td><td>${escapeHtml(valueToString(value))}</td></tr>`;
                });
                html += '</table></div></div></div>';
            }

            // Métriques qualitatives (gérer les deux formats : qualitatives OU qualitatif)
            const qualitatives = principal.metriques.qualitatives || principal.metriques.qualitatif;
            if (qualitatives && Object.keys(qualitatives).length > 0) {
                html += '<div class="col-md-6">';
                html += '<div class="card h-100 border-success">';
                html +=
                    '<div class="card-header bg-success text-white fw-bold"><i class="bi bi-clipboard-check"></i> Métriques Qualitatives</div>';
                html += '<div class="card-body"><table class="table table-sm table-borderless mb-0">';
                Object.entries(qualitatives).forEach(([key, value]) => {
                    const label = key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
                    html += `<tr><td class="fw-bold">${escapeHtml(label)}:</td><td>${escapeHtml(valueToString(value))}</td></tr>`;
                });
                html += '</table></div></div></div>';
            }
        }

        // === Colonne 1 : Budget Analysis ===
        html += '<div class="col-md-6">';
        html += '<div class="card h-100 border-success">';
        html +=
            '<div class="card-header bg-success text-white fw-bold"><i class="bi bi-cash-stack"></i> Analyse Budget</div>';
        html += '<div class="card-body">';

        // v3.1.0 : Les données budget sont au niveau racine de results
        if (results.budget_per_month !== undefined || results.budget_per_day !== undefined) {
            html += '<table class="table table-sm table-borderless mb-0">';

            if (results.budget_per_month !== undefined) {
                html += `<tr><td class="fw-bold">Budget mensuel :</td><td>${escapeHtml(String(results.budget_per_month))} €</td></tr>`;
            }
            if (results.budget_per_day !== undefined) {
                html += `<tr><td class="fw-bold">Budget journalier :</td><td>${escapeHtml(String(results.budget_per_day))} €</td></tr>`;
            }

            html += '</table>';
        } else {
            html += '<p class="text-muted mb-0">Aucune donnée disponible</p>';
        }

        html += '</div></div></div>';

        // === Colonne 2 : Timeline Analysis ===
        html += '<div class="col-md-6">';
        html += '<div class="card h-100 border-primary">';
        html +=
            '<div class="card-header bg-primary text-white fw-bold"><i class="bi bi-calendar-range"></i> Analyse Timeline</div>';
        html += '<div class="card-body">';

        // v3.1.0 : Les données timeline sont au niveau racine de results
        if (results.campaign_weeks !== undefined || results.campaign_months !== undefined) {
            html += '<table class="table table-sm table-borderless mb-0">';

            if (results.campaign_weeks !== undefined) {
                html += `<tr><td class="fw-bold">Durée campagne :</td><td>${escapeHtml(String(results.campaign_weeks))} semaines</td></tr>`;
            }
            if (results.campaign_months !== undefined) {
                html += `<tr><td class="fw-bold">Durée campagne :</td><td>${escapeHtml(String(results.campaign_months))} mois</td></tr>`;
            }

            html += '</table>';
        } else {
            html += '<p class="text-muted mb-0">Aucune donnée disponible</p>';
        }

        html += '</div></div></div>';

        html += '</div>'; // Fin row

        // === Deuxième ligne : Infos Techniques uniquement ===
        html += '<div class="row g-3 mt-2">';

        // Infos Techniques (tokens, durée, modèle) - Pleine largeur
        html += '<div class="col-12">';
        html += '<div class="card border-secondary">';
        html +=
            '<div class="card-header bg-secondary text-white fw-bold"><i class="bi bi-cpu"></i> Informations Techniques</div>';
        html += '<div class="card-body">';
        html += '<div class="row">';

        // Colonne 1 : Tokens
        html += '<div class="col-md-4">';
        html += '<table class="table table-sm table-borderless mb-0">';
        if (results.tokens_used) {
            if (results.tokens_used.input !== undefined) {
                html += `<tr><td class="fw-bold">Tokens d'entrée :</td><td>${escapeHtml(String(results.tokens_used.input))}</td></tr>`;
            }
            if (results.tokens_used.output !== undefined) {
                html += `<tr><td class="fw-bold">Tokens de sortie :</td><td>${escapeHtml(String(results.tokens_used.output))}</td></tr>`;
            }
            if (results.tokens_used.total !== undefined) {
                html += `<tr><td class="fw-bold">Total tokens :</td><td class="text-primary fw-bold">${escapeHtml(String(results.tokens_used.total))}</td></tr>`;
            }
        }
        html += '</table></div>';

        // Colonne 2 : Durée & Modèle
        html += '<div class="col-md-4">';
        html += '<table class="table table-sm table-borderless mb-0">';
        if (results.duration_ms !== undefined) {
            const seconds = (results.duration_ms / 1000).toFixed(2);
            html += `<tr><td class="fw-bold">Durée traitement :</td><td>${seconds}s</td></tr>`;
        }
        if (results.model_used) {
            html += `<tr><td class="fw-bold">Modèle IA :</td><td><code class="small">${escapeHtml(results.model_used)}</code></td></tr>`;
        }
        html += '</table></div>';

        // Colonne 3 : Campagne
        html += '<div class="col-md-4">';
        html += '<table class="table table-sm table-borderless mb-0">';
        if (results.campaign_weeks !== undefined) {
            html += `<tr><td class="fw-bold">Durée campagne :</td><td>${escapeHtml(String(results.campaign_weeks))} semaines</td></tr>`;
        }
        if (results.budget_per_month !== undefined) {
            html += `<tr><td class="fw-bold">Budget mensuel :</td><td>${escapeHtml(String(results.budget_per_month))} €</td></tr>`;
        }
        html += '</table></div>';

        html += '</div>'; // Fin row interne
        html += '</div></div></div>';

        html += '</div>'; // Fin row

        // === Warnings si présents ===
        if (results.warnings?.length > 0) {
            html += '<div class="row mt-3"><div class="col-12">';
            html += '<div class="card border-danger">';
            html +=
                '<div class="card-header bg-danger text-white fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Avertissements</div>';
            html += '<div class="card-body"><ul class="mb-0">';
            results.warnings.forEach((warning) => {
                html += `<li class="text-danger">${escapeHtml(String(warning))}</li>`;
            });
            html += '</ul></div></div></div></div>';
        }

        container.innerHTML = html;
    }

    /**
     * Convertit une valeur en chaîne de caractères sécurisée
     * @param {*} value - Valeur à convertir
     * @returns {string} Chaîne de caractères
     */
    function valueToString(value) {
        if (value === null || value === undefined) {
            return '';
        }
        if (typeof value === 'string') {
            return value;
        }
        if (typeof value === 'number' || typeof value === 'boolean') {
            return String(value);
        }
        // Pour les objets et tableaux, retourner une chaîne vide plutôt que [object Object]
        return '';
    }

    /**
     * Affiche les recommandations stratégiques (v3.1.0 - array de strings).
     *
     * @param {string} containerId - ID du conteneur
     * @param {Array<string>} recommendations - Liste des recommandations (strings)
     */
    function renderRecommendations(containerId, recommendations) {
        const container = document.getElementById(containerId);

        if (!recommendations || !Array.isArray(recommendations) || recommendations.length === 0) {
            container.innerHTML = '<p class="text-muted">Aucune recommandation disponible</p>';
            return;
        }

        // v3.1.0: recommendations est maintenant un simple array de strings
        const html = recommendations.map((rec) => `<li class="text-success mb-3">${escapeHtml(rec)}</li>`).join('');

        container.innerHTML = `<ul class="mb-0">${html}</ul>`;
    }

    /**
     * Affiche les facteurs clés de succès (v3.1.0 - array de strings).
     *
     * @param {string} containerId - ID du conteneur
     * @param {Array<string>} factors - Liste des facteurs de succès (strings)
     */
    function renderSuccessFactors(containerId, factors) {
        const container = document.getElementById(containerId);

        if (!factors || !Array.isArray(factors) || factors.length === 0) {
            container.innerHTML = '<p class="text-muted">Aucun facteur disponible</p>';
            return;
        }

        // v3.1.0: factors est maintenant un simple array de strings
        const html = factors.map((factor) => `<li class="text-info mb-3">${escapeHtml(factor)}</li>`).join('');

        container.innerHTML = `<ul class="mb-0">${html}</ul>`;
    }

    /**
     * Échappe le HTML pour prévenir XSS
     * @param {string} text - Texte à échapper
     * @returns {string} Texte échappé
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Affiche un message d'erreur dans la modal
     * @param {string} message - Message d'erreur
     */
    function showError(message) {
        document.getElementById('enrichmentLoader').style.display = 'none';
        document.getElementById('enrichmentResults').style.display = 'none';
        document.getElementById('enrichmentError').style.display = 'block';
        document.getElementById('errorMessage').textContent = message;
    }

    // === EVENT: Accepter les suggestions ===
    document.getElementById('acceptEnrichmentBtn').addEventListener('click', () => {
        if (!currentTaskId) {
            console.error('❌ Pas de taskId disponible');
            return;
        }

        acceptEnrichment();
    });

    /**
     * Accepte et applique les suggestions d'enrichissement
     */
    function acceptEnrichment() {
        const selectedNameRadio = document.querySelector('input[name="selectedName"]:checked');
        const selectedNameIndex = selectedNameRadio ? parseInt(selectedNameRadio.value) : 0;
        const selectedName = alternativeNames[selectedNameIndex] || alternativeNames[0];

        // Récupérer les objectifs depuis currentEnrichedObjectives (stocké lors de displayResults)
        const objectives = window.currentEnrichedObjectives || '';

        console.log('📤 Envoi acceptation enrichissement:', {
            taskId: currentTaskId,
            selectedName: selectedName,
            objectivesLength: objectives.length,
        });

        const acceptBtn = document.getElementById('acceptEnrichmentBtn');
        acceptBtn.disabled = true;
        acceptBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Création en cours...';

        const url = config.enrichmentAcceptUrl.replace('__TASK_ID__', currentTaskId);

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                name: selectedName,
                detailedObjectives: objectives,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    console.log('✅ Projet créé avec succès, redirection...', data);
                    window.location.href = data.redirectUrl;
                } else {
                    acceptBtn.disabled = false;
                    acceptBtn.innerHTML = '<i class="bi bi-check-circle"></i> Créer le projet avec ces suggestions';
                    showError(data.error || 'Erreur lors de la création du projet');
                }
            })
            .catch((error) => {
                console.error('❌ Erreur acceptation:', error);
                acceptBtn.disabled = false;
                acceptBtn.innerHTML = '<i class="bi bi-check-circle"></i> Créer le projet avec ces suggestions';
                showError('Erreur de connexion au serveur');
            });
    }

    // === EVENT: Fermeture de la modal ===
    document.getElementById('enrichmentModal').addEventListener('hidden.bs.modal', () => {
        closeEventSource();
    });
}

// Auto-initialisation si le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoInit);
} else {
    autoInit();
}

/**
 * Auto-initialisation avec data attributes
 */
function autoInit() {
    const form = document.querySelector('form[name="project"]');
    if (!form) {
        return;
    }

    const config = {
        mercureUrl: form.dataset.mercureUrl || 'http://localhost:82/.well-known/mercure',
        enrichmentResultsUrl: form.dataset.enrichmentResultsUrl || '',
        enrichmentAcceptUrl: form.dataset.enrichmentAcceptUrl || '',
    };

    if (!config.enrichmentResultsUrl || !config.enrichmentAcceptUrl) {
        console.warn("⚠️ URLs d'enrichissement non configurées dans data attributes");
        return;
    }

    initMarketingProjectEnrichment(config);
}
