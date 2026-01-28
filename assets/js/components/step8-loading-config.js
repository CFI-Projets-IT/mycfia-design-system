/**
 * Step 5 Loading Configuration - Assets Generation Loading Config
 * Configuration pour la simulation de génération d'assets
 */

import { simulateLoading } from './campaign-loader.js';

/**
 * Messages de progression pour la génération d'assets
 */
const ASSET_MESSAGES = [
    { phase: 0, text: 'Génération des visuels Linkedin...', progress: 0 },
    { phase: 1, text: 'Création des bannières GoogleAds...', progress: 15 },
    { phase: 2, text: 'Design des stories Facebook/Instagram...', progress: 30 },
    { phase: 3, text: 'Génération des templates Mail...', progress: 50 },
    { phase: 4, text: 'Rédaction des articles...', progress: 70 },
    { phase: 5, text: 'Création des Sms...', progress: 85 },
    { phase: 6, text: 'Optimisation et finalisation...', progress: 95 }
];

/**
 * Tips affichées pendant la génération
 */
const ASSET_TIPS = [
    "Les visuels avec visages humains obtiennent 38% d'engagement en plus sur Linkedin.",
    "GoogleAds Display avec CTA clair augmentent le CTR de 47% en moyenne.",
    "Les stories Instagram de 15 secondes ont le meilleur taux de complétion (85%).",
    "Les mails avec emojis dans l'objet augmentent le taux d'ouverture de 45%.",
    "Les articles de 2000+ mots rankent 2x mieux sur Google.",
    "Les Sms envoyés entre 10h-12h ont un taux d'ouverture de 98%.",
    "Le A/B testing augmente les conversions de 49% en moyenne."
];

/**
 * Initialise le loader de génération d'assets
 */
export function initStep5Loading() {
    simulateLoading({
        duration: 120000,
        redirectUrl: 'step5_validate.html',
        messages: ASSET_MESSAGES,
        tips: ASSET_TIPS
    });
}
