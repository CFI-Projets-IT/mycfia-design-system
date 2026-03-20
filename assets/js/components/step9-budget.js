/**
 * Step 9 Budget - Gestion de la vue récapitulatif budgétaire
 *
 * Responsabilités :
 * - Toggle parcours prototype (Standard / Avanci / Les deux)
 * - Sélecteur affranchissement avec recalcul dynamique du total
 * - Simulation paiement PayPal (prototype)
 * - Mise à jour du Speed Dial après paiement validé
 */

// ─────────────────────────────────────────────────────────────────
// Données tarifaires mockées (volumes basés sur contacts uploadés)
// En production : ces valeurs viennent du back-end
// ─────────────────────────────────────────────────────────────────
const TARIFS = {
    locationEmail:    { prix: 0.12,    qty: 5000 },
    locationSms:      { prix: 0.14,    qty: 5000 },
    locationCourrier: { prix: 0.23,    qty: 5000 },
    canalEmail:       { prix: 0.01,    qty: 5000 },
    canalSms:         { prix: 0.055,   qty: 5000 },
    canalPrint:       { prix: 0.30,    qty: 5000 },
    affrG4:           { prix: 0.747,   qty: 5000 },
    affrDestinee:     { prix: 0.388,   qty: 5000 },
};

// Choix affranchissement courant
let currentAffr = 'g4';

// État paiement par parcours
const paymentState = {
    standard: false,
    avanci:   false,
};

// ─────────────────────────────────────────────────────────────────
// Initialisation principale
// ─────────────────────────────────────────────────────────────────
export function initStep9Budget() {
    if (!document.getElementById('budget-page')) return;

    console.log('[step9-budget] Initialisation...');

    _initParcoursToggle();
    _initAffranchissementSelector();
    _initPaymentButtons();
    _initSpeedDial();
}

// ─────────────────────────────────────────────────────────────────
// Toggle parcours (prototype uniquement)
// ─────────────────────────────────────────────────────────────────
function _initParcoursToggle() {
    const toggleBtns = document.querySelectorAll('[data-budget-toggle]');
    if (!toggleBtns.length) return;

    toggleBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const parcours = btn.dataset.budgetToggle;
            _switchParcours(parcours);
        });
    });
}

function _switchParcours(parcours) {
    // Mise à jour du data attribute sur la page
    const page = document.getElementById('budget-page');
    if (page) page.dataset.budgetParcours = parcours;

    // Mise à jour des boutons toggle
    document.querySelectorAll('[data-budget-toggle]').forEach((btn) => {
        btn.classList.remove('btn-glass-primary', 'btn-glass');
        btn.classList.add(btn.dataset.budgetToggle === parcours ? 'btn-glass-primary' : 'btn-glass');
    });

    // Affichage des sections
    const sectionStandard = document.querySelector('[data-budget-section="standard"]');
    const sectionAvanci   = document.querySelector('[data-budget-section="avanci"]');
    const sectionBoth     = document.querySelector('[data-budget-section="both"]');

    if (sectionStandard) sectionStandard.classList.toggle('d-none', parcours === 'avanci');
    if (sectionAvanci)   sectionAvanci.classList.toggle('d-none', parcours === 'standard');
    if (sectionBoth)     sectionBoth.classList.toggle('d-none', parcours !== 'both');
}

// ─────────────────────────────────────────────────────────────────
// Sélecteur affranchissement
// ─────────────────────────────────────────────────────────────────
function _initAffranchissementSelector() {
    const radios = document.querySelectorAll('[data-budget-affr-option]');
    if (!radios.length) return;

    radios.forEach((radio) => {
        radio.addEventListener('change', () => {
            if (!radio.checked) return;
            currentAffr = radio.dataset.budgetAffrOption;
            _updateAffranchissementLigne();
            _recalculerTotal('standard');
        });
    });
}

function _updateAffranchissementLigne() {
    // Masquer/afficher les lignes d'affranchissement
    const ligneG4       = document.querySelector('[data-budget-affr-ligne="g4"]');
    const ligneDestinee = document.querySelector('[data-budget-affr-ligne="destineo"]');

    if (ligneG4)       ligneG4.classList.toggle('d-none', currentAffr !== 'g4');
    if (ligneDestinee) ligneDestinee.classList.toggle('d-none', currentAffr !== 'destineo');
}

// ─────────────────────────────────────────────────────────────────
// Recalcul dynamique du total
// ─────────────────────────────────────────────────────────────────
function _recalculerTotal(parcours) {
    let total = 0;

    if (parcours === 'standard') {
        const affrTarif = currentAffr === 'g4' ? TARIFS.affrG4 : TARIFS.affrDestinee;
        total = (
            TARIFS.locationEmail.prix    * TARIFS.locationEmail.qty +
            TARIFS.locationSms.prix      * TARIFS.locationSms.qty +
            TARIFS.locationCourrier.prix * TARIFS.locationCourrier.qty +
            TARIFS.canalEmail.prix       * TARIFS.canalEmail.qty +
            TARIFS.canalSms.prix         * TARIFS.canalSms.qty +
            TARIFS.canalPrint.prix       * TARIFS.canalPrint.qty +
            affrTarif.prix               * affrTarif.qty
        );
    }

    const totalEl = document.querySelector('[data-budget-total-amount="standard"]');
    if (totalEl) {
        totalEl.textContent = _formatEuro(total);
    }

    // Mettre à jour aussi le montant sur le bouton PayPal
    const payAmountEl = document.querySelector('[data-budget-pay-amount="standard"]');
    if (payAmountEl) {
        payAmountEl.textContent = _formatEuro(total);
    }
}

function _formatEuro(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
    }).format(amount);
}

// ─────────────────────────────────────────────────────────────────
// Simulation paiement PayPal (prototype)
// ─────────────────────────────────────────────────────────────────
function _initPaymentButtons() {
    document.querySelectorAll('[data-budget-pay-btn]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const parcours = btn.dataset.budgetPayBtn;
            _simulatePaiement(parcours);
        });
    });
}

function _simulatePaiement(parcours) {
    paymentState[parcours] = true;

    // Cacher le bloc "en attente", afficher le bloc "validé"
    const pendingEl = document.querySelector(`[data-budget-payment-status="pending-${parcours}"]`);
    const doneEl    = document.querySelector(`[data-budget-payment-status="done-${parcours}"]`);

    if (pendingEl) pendingEl.classList.add('d-none');
    if (doneEl)    doneEl.classList.remove('d-none');

    // Mettre à jour le speed dial
    _updateSpeedDial();
}

// ─────────────────────────────────────────────────────────────────
// Speed Dial
// ─────────────────────────────────────────────────────────────────
function _initSpeedDial() {
    const speedPayBtn = document.getElementById('budget-speed-pay');
    if (!speedPayBtn) return;

    speedPayBtn.addEventListener('click', () => {
        const page    = document.getElementById('budget-page');
        const parcours = page ? page.dataset.budgetParcours : 'standard';

        if (paymentState[parcours] || paymentState.standard || paymentState.avanci) {
            window.location.href = 'step9_schedule_light.html';
        } else {
            // Scroll vers la section paiement visible
            const paySection = document.querySelector('.budget-paypal-section:not(.d-none)');
            if (paySection) {
                paySection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
}

function _updateSpeedDial() {
    const speedPayBtn = document.getElementById('budget-speed-pay');
    if (!speedPayBtn) return;

    const page     = document.getElementById('budget-page');
    const parcours = page ? page.dataset.budgetParcours : 'standard';
    const isPaid   = paymentState[parcours] || paymentState.standard || paymentState.avanci;

    if (isPaid) {
        speedPayBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
        speedPayBtn.classList.remove('speed-dial-primary');
        speedPayBtn.classList.add('speed-dial-success');
    }
}
