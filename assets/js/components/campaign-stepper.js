/**
 * Campaign Stepper Component
 * Gestion du stepper de progression
 */

/**
 * Met à jour le stepper selon l'étape active
 * @param {number} currentStep - Numéro de l'étape active (1-5)
 */
export function updateStepper(currentStep) {
    const steps = document.querySelectorAll('.stepper-step');
    const progressBar = document.querySelector('.stepper-progress');

    steps.forEach((step, index) => {
        const stepNumber = index + 1;

        // Remove all states
        step.classList.remove('active', 'completed');

        if (stepNumber < currentStep) {
            // Completed step
            step.classList.add('completed');
            const circle = step.querySelector('.stepper-circle');
            circle.innerHTML = '<i class="bi bi-check-lg"></i>';
        } else if (stepNumber === currentStep) {
            // Active step
            step.classList.add('active');
            const circle = step.querySelector('.stepper-circle');
            circle.textContent = stepNumber;
        } else {
            // Inactive step
            const circle = step.querySelector('.stepper-circle');
            circle.textContent = stepNumber;
        }
    });

    // Update progress bar
    // TODO: Remplacer par classe CSS dynamique lors de l'intégration Twig
    const progressPercentage = ((currentStep - 1) / (steps.length - 1)) * 100;
    if (progressBar) {
        progressBar.style.width = `${progressPercentage}%`;
    }
}

// Auto-detect current step from URL
function autoDetectStep() {
    const path = window.location.pathname;
    let step = 1;

    if (path.includes('step1')) step = 1;
    else if (path.includes('step2')) step = 2;
    else if (path.includes('step3')) step = 3;
    else if (path.includes('step4')) step = 4;
    else if (path.includes('step5')) step = 5;

    updateStepper(step);
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoDetectStep);
} else {
    autoDetectStep();
}

// TODO: Supprimer cette exposition globale lors de l'intégration Twig
// Le stepper sera géré via Stimulus controller
// window.updateStepper = updateStepper;
