import { startStimulusApp } from '@symfony/stimulus-bundle';
import '@hotwired/turbo';

// Import des controllers marketing custom
import PersonaController from './controllers/marketing/persona_controller.js';
import EnrichmentController from './controllers/marketing/enrichment_controller.js';

const app = startStimulusApp();

// Enregistrer les controllers marketing
app.register('marketing-persona', PersonaController);
app.register('marketing-enrichment', EnrichmentController);
