/**
 * Détection automatique des concurrents via CompetitorIntelligenceTool
 *
 * Gère l'interaction AJAX pour la détection automatique des concurrents
 * avec validation et ajout manuel.
 */

console.log('🚀 TRACE: Fichier competitor-detection.js chargé');

// Flag global pour empêcher les exécutions multiples
let isDetectionRunning = false;

function initCompetitorDetection() {
    console.log('🚀 TRACE: initCompetitorDetection() appelée');

    // Vérifier si on est sur la page de détection de concurrents
    const detectUrlElement = document.querySelector('[data-detect-url]');
    console.log('🚀 TRACE: detectUrlElement =', detectUrlElement);

    if (!detectUrlElement) {
        console.log('🚀 TRACE: Pas de data-detect-url, return');
        return;
    }

    // ✅ GUARD: Empêcher les exécutions multiples (Turbo events)
    if (isDetectionRunning) {
        console.log('⚠️ TRACE: Détection déjà en cours, ignorer cet appel');
        return;
    }

    console.log('🔍 TRACE: Initialisation détection concurrents');

    const loaderSection = document.getElementById('detection-loader');
    const resultsSection = document.getElementById('detection-results');
    const errorSection = document.getElementById('detection-error');
    const competitorsInput = document.getElementById('competitors-input');
    const selectedCountSpan = document.getElementById('selected-count');
    // Variables pour ajout manuel de concurrents (fonctionnalité future)
    // const newCompetitorInput = document.getElementById('new-competitor-input');
    // const addCompetitorBtn = document.getElementById('add-competitor-btn');
    const validateBtn = document.getElementById('validate-btn');
    const emptyState = document.getElementById('empty-state');
    const errorMessage = document.getElementById('error-message');
    const competitorsTableBody = document.getElementById('competitors-table-body');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const selectAllBtn = document.getElementById('select-all-btn');
    const deselectAllBtn = document.getElementById('deselect-all-btn');

    const detectUrl = detectUrlElement.dataset.detectUrl;
    const validateManualUrl = detectUrlElement.dataset.validateManualUrl;
    console.log('🔍 TRACE: URL de détection:', detectUrl);
    console.log('🔍 TRACE: URL validation manuelle:', validateManualUrl);

    let competitorsData = []; // Stocker toutes les données pour le tableau

    // Fonction pour remplir le tableau avec toutes les données
    function updateCompetitorsTable() {
        competitorsTableBody.innerHTML = '';

        competitorsData.forEach((competitor, index) => {
            const row = document.createElement('tr');

            // Extraire toutes les données disponibles
            const title = competitor.title || 'N/A';
            const domain = competitor.domain || 'N/A';
            // const url = competitor.url || '#'; // Non utilisé actuellement
            const validation = competitor.validation || {};

            // Structure réelle de validation
            const isCompetitor = validation.isCompetitor !== undefined ? validation.isCompetitor : null;
            const alignmentScore = validation.alignmentScore !== undefined ? validation.alignmentScore : 'N/A';
            const overlaps =
                [validation.offeringOverlap, validation.marketOverlap, validation.geoOverlap]
                    .filter((o) => o)
                    .join(' / ') || 'N/A';

            // Nouveaux champs v3.27.0
            const hasAds = competitor.has_ads || false;
            const isFeatured = competitor.is_featured || false;
            const adPosition = competitor.ad_position || '';

            const keywordSource = competitor.keyword_source || 'N/A';
            const keywordVolume = competitor.keyword_volume !== undefined ? competitor.keyword_volume : 'N/A';
            const position = competitor.position || 'N/A';

            // Vérifier si coché par défaut (tous cochés au départ si isCompetitor = true)
            const isChecked = competitor.selected !== undefined ? competitor.selected : isCompetitor === true;

            // Construire badges signaux marketing (v3.27.0)
            let marketingSignals = '';
            if (hasAds) {
                marketingSignals += `<span class="badge bg-warning text-dark" title="Publicité Google Ads active (position #${adPosition})">💰 SEA</span> `;
            }
            if (isFeatured) {
                marketingSignals += `<span class="badge bg-info" title="Featured Snippet - Position Zéro Google">🏆 SEO</span>`;
            }
            if (!hasAds && !isFeatured) {
                marketingSignals = '<small class="text-muted">—</small>';
            }

            row.innerHTML = `
                <td class="text-center">
                    <input type="checkbox" class="form-check-input competitor-checkbox" data-index="${index}" ${isChecked ? 'checked' : ''}>
                </td>
                <td title="${escapeHtml(title)}" class="cursor-pointer" data-index="${index}">${escapeHtml(title.length > 35 ? `${title.substring(0, 35)}...` : title)}</td>
                <td class="cursor-pointer" data-index="${index}">${escapeHtml(domain)}</td>
                <td class="text-center cursor-pointer" data-index="${index}">
                    <span class="badge ${alignmentScore >= 70 ? 'bg-success' : alignmentScore >= 50 ? 'bg-warning' : 'bg-secondary'}">${alignmentScore}</span>
                </td>
                <td class="text-center cursor-pointer" data-index="${index}">${marketingSignals}</td>
                <td class="cursor-pointer" data-index="${index}"><small class="text-muted">${overlaps}</small></td>
                <td class="cursor-pointer" data-index="${index}">
                    ${escapeHtml(keywordSource)}<br>
                    <small class="text-muted">Vol: ${keywordVolume}</small>
                </td>
                <td class="text-center cursor-pointer" data-index="${index}">#${position}</td>
            `;

            // Ajouter l'événement sur la checkbox
            const checkbox = row.querySelector('.competitor-checkbox');
            checkbox.addEventListener('change', function () {
                competitor.selected = this.checked;
                updateSelectedCount();
            });

            // Ajouter l'événement clic sur toutes les cellules sauf la checkbox
            row.querySelectorAll('.cursor-pointer').forEach((cell) => {
                cell.addEventListener('click', () => {
                    openCompetitorModal(competitor);
                });
            });

            competitorsTableBody.appendChild(row);
        });

        updateSelectedCount();
    }

    // Fonction pour mettre à jour le compteur et l'état du bouton
    function updateSelectedCount() {
        const selectedCheckboxes = document.querySelectorAll('.competitor-checkbox:checked');
        const selectedCount = selectedCheckboxes.length;

        // Mettre à jour le compteur
        selectedCountSpan.textContent = selectedCount;

        // Activer/désactiver le bouton de validation
        if (selectedCount === 0) {
            emptyState.classList.remove('d-none');
            validateBtn.disabled = true;
        } else {
            emptyState.classList.add('d-none');
            validateBtn.disabled = false;
        }

        // Collecter les objets complets des concurrents sélectionnés
        const selectedCompetitorsData = [];
        const selectedCompetitorsNames = [];

        competitorsData.forEach((competitor, index) => {
            const checkbox = document.querySelector(`.competitor-checkbox[data-index="${index}"]`);
            if (checkbox && checkbox.checked) {
                // Sauvegarder l'objet complet avec toutes les métadonnées
                selectedCompetitorsData.push(competitor);
                // Conserver aussi les noms pour compatibilité d'affichage
                selectedCompetitorsNames.push(competitor.title || competitor.domain);
            }
        });

        // Mettre à jour le champ caché avec les noms (pour affichage)
        competitorsInput.value = selectedCompetitorsNames.join(', ');

        // Mettre à jour le champ caché avec les objets complets (pour sauvegarde)
        const competitorsDataInput = document.getElementById('competitors-data-input');
        if (competitorsDataInput) {
            competitorsDataInput.value = JSON.stringify(selectedCompetitorsData);
        }

        // Gérer l'affichage des boutons Tout sélectionner/déselectionner
        const allCheckboxes = document.querySelectorAll('.competitor-checkbox');
        if (selectedCount === allCheckboxes.length && allCheckboxes.length > 0) {
            selectAllBtn.classList.add('d-none');
            deselectAllBtn.classList.remove('d-none');
            selectAllCheckbox.checked = true;
        } else {
            selectAllBtn.classList.remove('d-none');
            deselectAllBtn.classList.add('d-none');
            selectAllCheckbox.checked = false;
        }
    }

    // Gestionnaire pour "Tout sélectionner"
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.competitor-checkbox').forEach((checkbox) => {
                checkbox.checked = true;
                const index = parseInt(checkbox.dataset.index);
                competitorsData[index].selected = true;
            });
            updateSelectedCount();
        });
    }

    // Gestionnaire pour "Tout déselectionner"
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.competitor-checkbox').forEach((checkbox) => {
                checkbox.checked = false;
                const index = parseInt(checkbox.dataset.index);
                competitorsData[index].selected = false;
            });
            updateSelectedCount();
        });
    }

    // Gestionnaire pour la checkbox principale du header
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            document.querySelectorAll('.competitor-checkbox').forEach((checkbox) => {
                checkbox.checked = isChecked;
                const index = parseInt(checkbox.dataset.index);
                competitorsData[index].selected = isChecked;
            });
            updateSelectedCount();
        });
    }

    // ⚠️ DÉSACTIVÉ TEMPORAIREMENT - EN DÉVELOPPEMENT
    // Gestionnaire pour ajouter un concurrent manuellement
    // Cette fonctionnalité sera réactivée après optimisation du bundle (problème timeout)
    /*
    if (addCompetitorBtn) {
        addCompetitorBtn.addEventListener('click', function() {
            const newCompetitor = newCompetitorInput.value.trim();
            if (!newCompetitor) {
                return;
            }

            // Désactiver le bouton pendant la recherche
            addCompetitorBtn.disabled = true;
            const originalBtnText = addCompetitorBtn.innerHTML;
            addCompetitorBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Recherche...';

            console.log('🔍 TRACE: Validation concurrent manuel:', newCompetitor);

            // Appeler l'API de validation manuelle
            fetch(validateManualUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    competitor_name: newCompetitor
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('🔍 TRACE: Réponse validation manuelle:', data);

                if (data.success && data.competitor) {
                    // Marquer comme sélectionné par défaut
                    data.competitor.selected = true;

                    // Ajouter le concurrent aux données (tableau)
                    competitorsData.push(data.competitor);
                    updateCompetitorsTable();

                    // Vider le champ et afficher un message de succès
                    newCompetitorInput.value = '';

                    // Message de succès temporaire
                    const successMsg = document.createElement('div');
                    successMsg.className = 'alert alert-success alert-dismissible fade show mt-2';
                    successMsg.innerHTML = `
                        <i class="bi bi-check-circle"></i>
                        <strong>${escapeHtml(data.competitor.title || data.competitor.domain)}</strong> ajouté avec succès !
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    newCompetitorInput.parentElement.parentElement.appendChild(successMsg);

                    // Retirer le message après 5 secondes
                    setTimeout(() => {
                        successMsg.remove();
                    }, 5000);
                } else {
                    // Afficher l'erreur
                    alert(data.error || 'Erreur lors de la validation du concurrent');
                }
            })
            .catch(error => {
                console.error('🔍 TRACE: Erreur validation concurrent manuel:', error);
                alert('Erreur de connexion au serveur. Veuillez réessayer.');
            })
            .finally(() => {
                // Réactiver le bouton
                addCompetitorBtn.disabled = false;
                addCompetitorBtn.innerHTML = originalBtnText;
            });
        });
    }

    // Permettre l'ajout avec la touche Entrée
    if (newCompetitorInput) {
        newCompetitorInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCompetitorBtn.click();
            }
        });
    }
    */

    // Lancer la détection automatique au chargement
    console.log('🔍 TRACE: Début détection concurrents, URL:', detectUrl);

    // ✅ Marquer comme en cours
    isDetectionRunning = true;

    // ✅ Ajouter un AbortController pour gérer le timeout (120s)
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 120000); // 120 secondes

    fetch(detectUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        signal: controller.signal, // ✅ Ajouter le signal d'annulation
    })
        .then((response) => {
            clearTimeout(timeoutId); // ✅ Annuler le timeout si réponse reçue
            console.log('🔍 TRACE: Réponse reçue, status:', response.status);
            return response.json();
        })
        .then((data) => {
            console.log('🔍 TRACE: Données reçues:', data);
            loaderSection.classList.add('d-none');

            if (data.success) {
                console.log('🔍 TRACE: Succès, nombre de concurrents:', data.competitors?.length || 0);
                // Stocker toutes les données des concurrents
                if (data.competitors && data.competitors.length > 0) {
                    // Marquer tous les concurrents validés comme sélectionnés par défaut
                    competitorsData = data.competitors.map((competitor) => ({
                        ...competitor,
                        selected: competitor.validation?.isCompetitor === true,
                    }));

                    // Mettre à jour le compteur de détection (total détecté)
                    const competitorsCountElem = document.getElementById('competitors-count');
                    if (competitorsCountElem) {
                        competitorsCountElem.textContent = data.competitors.length;
                    }

                    // Remplir le tableau avec toutes les données
                    updateCompetitorsTable();
                }

                resultsSection.classList.remove('d-none');
            } else {
                console.error('🔍 TRACE: Erreur dans la réponse:', data.error);
                errorMessage.textContent = data.error || "Une erreur inconnue s'est produite";
                errorSection.classList.remove('d-none');
            }
        })
        .catch((error) => {
            clearTimeout(timeoutId); // ✅ Nettoyer le timeout
            console.error('🔍 TRACE: Erreur détection concurrents:', error);
            loaderSection.classList.add('d-none');

            // ✅ Message spécifique pour timeout
            if (error.name === 'AbortError') {
                errorMessage.innerHTML = `
                <strong>La détection de concurrents prend trop de temps (>10 min).</strong><br>
                Cette opération nécessite de nombreux appels API (SERP, Firecrawl, Mistral AI).<br>
                <small class="text-muted">La tâche continue en arrière-plan. Vérifiez les logs ou revenez plus tard.</small>
            `;
            } else {
                errorMessage.textContent = 'Erreur de connexion au serveur. Veuillez réessayer.';
            }

            errorSection.classList.remove('d-none');
        })
        .finally(() => {
            clearTimeout(timeoutId); // ✅ Toujours nettoyer le timeout
            // ✅ Libérer le flag une fois terminé (succès ou erreur)
            isDetectionRunning = false;
            console.log('🔍 TRACE: Détection terminée, flag libéré');
        });

    // Fonction pour ouvrir la modal avec les détails du concurrent
    function openCompetitorModal(competitor) {
        console.log('🔍 TRACE: Ouverture modal pour concurrent', competitor);

        const validation = competitor.validation || {};

        // Informations principales
        document.getElementById('modal-title').textContent = competitor.title || 'N/A';
        document.getElementById('modal-domain').textContent = competitor.domain || 'N/A';
        document.getElementById('modal-url').href = competitor.url || '#';
        document.getElementById('modal-url').textContent = competitor.url || 'N/A';
        document.getElementById('modal-snippet').textContent = competitor.snippet || 'Aucune description disponible';

        // Validation IA
        const alignmentScore = validation.alignmentScore !== undefined ? validation.alignmentScore : 'N/A';
        const scoreElem = document.getElementById('modal-score');
        scoreElem.textContent = alignmentScore;
        scoreElem.className = `badge fs-6 ${alignmentScore >= 70 ? 'bg-success' : alignmentScore >= 50 ? 'bg-warning' : 'bg-secondary'}`;

        const isCompetitor = validation.isCompetitor;
        const isCompetitorElem = document.getElementById('modal-is-competitor');
        isCompetitorElem.textContent = isCompetitor ? '✅ Oui' : '❌ Non';
        isCompetitorElem.className = `badge fs-6 ${isCompetitor ? 'bg-success' : 'bg-danger'}`;

        // Overlaps
        const overlapsHTML = `
            <span class="badge bg-primary me-1">Offre: ${validation.offeringOverlap || 'N/A'}</span>
            <span class="badge bg-info me-1">Marché: ${validation.marketOverlap || 'N/A'}</span>
            <span class="badge bg-secondary">Géo: ${validation.geoOverlap || 'N/A'}</span>
        `;
        document.getElementById('modal-overlaps').innerHTML = overlapsHTML;

        document.getElementById('modal-reasoning').textContent = validation.reasoning || 'Aucune analyse disponible';

        // Données SEO/SEM (v3.27.0)
        document.getElementById('modal-position').textContent = `#${competitor.position || 'N/A'}`;

        const hasAds = competitor.has_ads || false;
        const adsElem = document.getElementById('modal-ads');
        if (hasAds) {
            adsElem.innerHTML = `<span class="badge bg-warning text-dark">💰 Oui</span> ${competitor.ad_position ? `(position #${competitor.ad_position})` : ''}`;
        } else {
            adsElem.innerHTML = '<span class="badge bg-secondary">Non</span>';
        }

        const isFeatured = competitor.is_featured || false;
        const featuredElem = document.getElementById('modal-featured');
        if (isFeatured) {
            featuredElem.innerHTML = '<span class="badge bg-info">🏆 Oui (Position Zéro)</span>';
        } else {
            featuredElem.innerHTML = '<span class="badge bg-secondary">Non</span>';
        }

        document.getElementById('modal-keyword-source').textContent = competitor.keyword_source || 'N/A';
        document.getElementById('modal-keyword-volume').textContent = competitor.keyword_volume
            ? `${competitor.keyword_volume.toLocaleString('fr-FR')} recherches/mois`
            : 'N/A';

        // Données techniques
        document.getElementById('modal-source').textContent = competitor.source || 'N/A';
        document.getElementById('modal-scraper').textContent = competitor.scraper || 'N/A';
        document.getElementById('modal-page').textContent = competitor.page ? `Page ${competitor.page}` : 'N/A';

        // Ouvrir la modal
        const modalElement = document.getElementById('competitorDetailModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Échapper le HTML pour éviter les injections XSS
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') {
            return '';
        }
        return unsafe
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

// Exécuter au chargement initial et après chaque navigation Turbo
console.log('🚀 TRACE: document.readyState =', document.readyState);

// Chargement initial
if (document.readyState === 'loading') {
    console.log('🚀 TRACE: Attente DOMContentLoaded');
    document.addEventListener('DOMContentLoaded', initCompetitorDetection);
} else {
    console.log('🚀 TRACE: DOM déjà chargé, appel immédiat');
    initCompetitorDetection();
}

// Turbo/Hotwire : Réexécuter après chaque navigation
document.addEventListener('turbo:load', () => {
    console.log('🚀 TRACE: turbo:load déclenché');
    initCompetitorDetection();
});
document.addEventListener('turbo:render', () => {
    console.log('🚀 TRACE: turbo:render déclenché');
    initCompetitorDetection();
});
