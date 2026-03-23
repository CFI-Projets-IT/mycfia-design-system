# Guide de Tests - DAP Phase 1

**Objectif :** Valider le fonctionnement complet du système d'onboarding DAP (Digital Adoption Platform) Phase 1.

---

## Prérequis

### Environnement
- Navigateur moderne (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- LocalStorage activé
- JavaScript activé
- Serveur local ou fichiers HTML accessibles

### Fichiers à Tester
- `campaign_generation/dashboard_light.html`

---

## Tests Étape par Étape

### Test 1 : Première Visite (Welcome Modal)

**Objectif :** Vérifier que le modal s'affiche correctement pour les nouveaux utilisateurs.

**Étapes :**
1. Ouvrir la console développeur (`F12`)
2. Supprimer LocalStorage : `localStorage.clear()`
3. Recharger la page `dashboard_light.html`
4. **Résultat attendu :**
   - Modal `#onboardingWelcomeModal` s'affiche après 1 seconde
   - Titre "Bienvenue dans Mes Campagnes !"
   - 4 bénéfices clés visibles
   - Bouton "Passer" visible
   - Bouton "Faire le tour guidé" visible

**Validation :**
- [ ] Modal s'affiche automatiquement après 1s
- [ ] Console log : `[OnboardingDAP] Initialisation...`
- [ ] Console log : `[OnboardingDAP] Initialisé avec succès`

---

### Test 2 : Bouton "Passer" (Skip Tour)

**Objectif :** Vérifier que l'utilisateur peut sauter le tour.

**Étapes :**
1. Modal visible (Test 1)
2. Cliquer sur "Passer"
3. **Résultat attendu :**
   - Modal se ferme
   - LocalStorage `mycfia_onboarding_completed = true`
   - LocalStorage `mycfia_onboarding_skipped_at` = timestamp

**Validation :**
- [ ] Modal se ferme immédiatement
- [ ] Console développeur → Application → LocalStorage :
  - `mycfia_onboarding_completed: true`
  - `mycfia_onboarding_skipped_at: [timestamp]`
- [ ] Recharger page → Modal ne s'affiche PAS

---

### Test 3 : Tour Guidé - Étape 1/4 (Dashboard Overview)

**Objectif :** Vérifier le démarrage du tour guidé.

**Étapes :**
1. Supprimer LocalStorage : `localStorage.clear()`
2. Recharger page → Modal s'affiche
3. Cliquer sur "Faire le tour guidé"
4. **Résultat attendu :**
   - Modal se ferme
   - Overlay sombre apparaît (fond noir transparent)
   - Spotlight sur `.content` (zone principale dashboard)
   - Tooltip apparaît avec :
     - Icône `bi-speedometer2`
     - Titre "Votre Dashboard"
     - Contenu explicatif
     - Progress dots : 1er actif (bleu), 3 autres gris
     - Boutons "Passer le tour" et "Suivant"

**Validation :**
- [ ] Console log : `[OnboardingDAP] Démarrage du tour guidé`
- [ ] Overlay visible (fond noir 70% opacité)
- [ ] Spotlight positionné sur `.content`
- [ ] Tooltip visible avec contenu correct
- [ ] Progress dots : 1/4 actif
- [ ] Bouton "Suivant" cliquable

---

### Test 4 : Tour Guidé - Étape 2/4 (Bouton Nouvelle Campagne)

**Objectif :** Vérifier la navigation entre étapes.

**Étapes :**
1. Tour guidé étape 1 actif (Test 3)
2. Cliquer sur "Suivant"
3. **Résultat attendu :**
   - Spotlight se déplace sur `.btn-ai` (bouton "Nouvelle campagne IA")
   - Tooltip se met à jour :
     - Icône `bi-plus-circle`
     - Titre "Créer une Campagne"
     - Contenu explicatif
     - Progress dots : 2ème actif, 1er complété (vert)

**Validation :**
- [ ] Spotlight transition fluide vers `.btn-ai`
- [ ] Tooltip repositionné automatiquement
- [ ] Progress dots : 2/4 actif, 1/4 complété
- [ ] Bouton "Suivant" cliquable

---

### Test 5 : Tour Guidé - Étape 3/4 (Sidebar Navigation)

**Étapes :**
1. Tour guidé étape 2 actif (Test 4)
2. Cliquer sur "Suivant"
3. **Résultat attendu :**
   - Spotlight sur `.nav-section:first-child`
   - Tooltip icône `bi-compass`
   - Progress dots : 3/4 actif, 2/4 complétés

**Validation :**
- [ ] Spotlight sur sidebar navigation
- [ ] Tooltip repositionné (position adaptée)
- [ ] Progress dots : 3/4 actif

---

### Test 6 : Tour Guidé - Étape 4/4 (Cards Campagnes)

**Étapes :**
1. Tour guidé étape 3 actif (Test 5)
2. Cliquer sur "Suivant"
3. **Résultat attendu :**
   - Spotlight sur `.card:first-child`
   - Tooltip icône `bi-folder-fill`
   - Progress dots : 4/4 actif, 3/4 complétés
   - Bouton "Suivant" remplacé par "Terminer"

**Validation :**
- [ ] Spotlight sur première card campagne
- [ ] Bouton "Terminer" affiché (au lieu de "Suivant")

---

### Test 7 : Fin de Tour (Completion)

**Objectif :** Vérifier la complétion du tour.

**Étapes :**
1. Tour guidé étape 4 actif (Test 6)
2. Cliquer sur "Terminer"
3. **Résultat attendu :**
   - Overlay disparaît (fade out)
   - Spotlight disparaît
   - Tooltip disparaît
   - Toast de félicitation apparaît en bas à droite :
     - Icône check vert
     - Titre "Tour terminé !"
     - Message "Vous êtes prêt à créer votre première campagne marketing."
   - Toast disparaît après 5 secondes
   - LocalStorage `mycfia_onboarding_completed = true`

**Validation :**
- [ ] Console log : `[OnboardingDAP] Tour terminé avec succès`
- [ ] Overlay/Spotlight/Tooltip disparus
- [ ] Toast félicitation visible 5 secondes
- [ ] LocalStorage `mycfia_onboarding_completed: true`
- [ ] Recharger page → Modal ne s'affiche PAS

---

### Test 8 : Bouton "Passer le tour" (Mid-tour Skip)

**Objectif :** Vérifier le skip en plein milieu du tour.

**Étapes :**
1. Démarrer tour guidé (Test 3)
2. Avancer jusqu'à étape 2 ou 3
3. Cliquer sur "Passer le tour"
4. **Résultat attendu :**
   - Tour s'arrête immédiatement
   - Overlay/Spotlight/Tooltip disparus
   - LocalStorage `mycfia_onboarding_completed = true`
   - LocalStorage `mycfia_onboarding_skipped_at` = timestamp

**Validation :**
- [ ] Console log : `[OnboardingDAP] Tour sauté depuis: tooltip`
- [ ] Tout disparaît instantanément
- [ ] LocalStorage marqué comme sauté

---

### Test 9 : Help Button (Relance Tour)

**Objectif :** Vérifier que le help button relance le tour.

**Étapes :**
1. Compléter ou sauter tour (LocalStorage `completed = true`)
2. Recharger page → Modal ne s'affiche pas
3. **Vérifier Help Button :**
   - Bas gauche écran
   - Icône "?" blanche sur fond bleu
   - Animation wiggle après 2 secondes
4. Cliquer sur Help Button
5. **Résultat attendu :**
   - LocalStorage réinitialisé (`completed` supprimé)
   - Tour redémarre à étape 1 (Dashboard Overview)

**Validation :**
- [ ] Help button visible bas gauche
- [ ] Animation wiggle démarre après 2s
- [ ] Clic relance tour depuis étape 1
- [ ] Console log : `[OnboardingDAP] Help button cliqué - Relance du tour`

---

### Test 10 : Persistence LocalStorage

**Objectif :** Vérifier que l'état est sauvegardé.

**Étapes :**
1. Démarrer tour, avancer jusqu'à étape 2
2. Recharger page (pendant le tour)
3. **Résultat attendu :**
   - Modal ne s'affiche PAS (car `mycfia_onboarding_step = 2`)
   - Help button visible pour relancer

**Validation :**
- [ ] LocalStorage `mycfia_onboarding_step: 2`
- [ ] Page rechargée n'affiche pas modal
- [ ] Help button permet relance manuelle

---

## Tests Cross-Browser

### Chrome 90+
- [ ] Tous les tests 1-10 passent
- [ ] Animations fluides (60fps)
- [ ] Aucune erreur console

### Firefox 88+
- [ ] Tous les tests 1-10 passent
- [ ] Animations fluides
- [ ] Aucune erreur console

### Safari 14+
- [ ] Tous les tests 1-10 passent
- [ ] Animations fluides
- [ ] Vérifier compatibilité LocalStorage

### Edge 90+
- [ ] Tous les tests 1-10 passent
- [ ] Animations fluides

---

## Tests Responsive

### Desktop (1920x1080)
- [ ] Tooltip positionné correctement (ne déborde pas)
- [ ] Spotlight précis sur éléments cibles
- [ ] Modal centré

### Laptop (1366x768)
- [ ] Tooltip lisible (min-width: 280px)
- [ ] Spotlight adapté
- [ ] Modal centré

### Tablet (768x1024)
- [ ] Tooltip min-width réduit (240px)
- [ ] Contenu lisible
- [ ] Boutons cliquables

### Mobile (<480px)
- [ ] Tooltip fixe en bas écran (position: fixed)
- [ ] Transform: translateX(-50%)
- [ ] Flèche tooltip cachée
- [ ] Boutons accessibles

---

## Tests Thèmes

### Light Theme (default)
- [ ] Overlay : fond noir 70%
- [ ] Tooltip : fond blanc, texte noir
- [ ] Boutons : bleu primaire (#003080)
- [ ] Contraste correct (WCAG AA)

### Dark-Blue Theme
- [ ] Tooltip hérite `--bg-card` (fond sombre)
- [ ] Texte hérite `--text-primary` (clair)
- [ ] Boutons héritent `--color-primary`
- [ ] Contraste correct

### Dark-Red Theme
- [ ] Tooltip hérite `--bg-card`
- [ ] Texte hérite `--text-primary`
- [ ] Boutons héritent `--color-primary`
- [ ] Contraste correct

---

## Tests Accessibilité

### Navigation Clavier
- [ ] `Tab` : Navigue entre boutons tooltip
- [ ] `Enter` : Déclenche bouton "Suivant"/"Terminer"
- [ ] `Escape` : Ferme tour (à implémenter si besoin)
- [ ] Focus visible (outline 2px bleu)

### ARIA
- [ ] Modal : `aria-hidden="true"` quand fermé
- [ ] Help button : `aria-label="Aide - Relancer le tour guidé"`
- [ ] Boutons tooltip : labels clairs

### Contraste Couleurs
- [ ] Texte sur fond : ratio ≥4.5:1 (WCAG AA)
- [ ] Boutons : ratio ≥3:1
- [ ] Help button : icône blanche sur bleu (#003080) → ratio OK

### Prefers-Reduced-Motion
- [ ] Ajouter dans OS : "Reduce motion" activé
- [ ] Animations désactivées ou réduites
- [ ] Transitions instantanées (opacity 0.1s linear)

### Screen Reader (optionnel)
- [ ] NVDA/JAWS : Lit titre modal
- [ ] Lit contenu tooltip
- [ ] Annonce progression (1/4, 2/4, etc.)

---

## Métriques de Performance

### Temps de Chargement
- [ ] CSS chargé : < 50ms
- [ ] JS chargé : < 100ms
- [ ] Initialisation : < 10ms
- [ ] Modal apparition : 1000ms (delay intentionnel)

### Animations
- [ ] Overlay fade-in : 300ms smooth
- [ ] Spotlight transition : 400ms cubic-bezier
- [ ] Tooltip fade-in : 300ms
- [ ] Toutes animations 60fps (vérifier Timeline DevTools)

### Poids Fichiers
- [ ] `_onboarding-dap.css` : ~8-10 KB (non minifié)
- [ ] `onboarding-dap.js` : ~15-20 KB (non minifié)
- [ ] Total ajouté : ~25-30 KB

---

## Commandes Utiles Console

### Vérifier LocalStorage
```javascript
// Lire toutes les clés DAP
Object.keys(localStorage).filter(k => k.startsWith('mycfia_onboarding'))

// Lire valeur spécifique
localStorage.getItem('mycfia_onboarding_completed')

// Supprimer tout
localStorage.clear()

// Supprimer clé spécifique
localStorage.removeItem('mycfia_onboarding_completed')
```

### Relancer Tour Manuellement
```javascript
// Supprimer localStorage + recharger
localStorage.clear();
location.reload();

// Ou via Help Button
document.querySelector('.onboarding-help-button').click();
```

### Debug Étapes
```javascript
// Voir configuration étapes
console.table(ONBOARDING_STEPS);

// Voir étape actuelle
console.log(localStorage.getItem('mycfia_onboarding_step'));
```

---

## Bugs Connus / Limitations

### À Corriger
- [ ] Navigation clavier : Escape pour fermer tour (non implémenté)
- [ ] Tooltip responsive mobile : Tester sur vrais devices
- [ ] Gestion resize window pendant tour (à tester)

### Améliorations Futures
- [ ] Ajouter sound effects (optionnel)
- [ ] Ajouter confetti animation (fin de tour)
- [ ] Ajouter skip count analytics
- [ ] Optimiser spotlight sur éléments dynamiques

---

## Rapport de Bugs

### Template
```
**Bug ID :** DAP-001
**Sévérité :** Haute / Moyenne / Basse
**Navigateur :** Chrome 90 / Firefox 88 / etc.
**Description :** [Description détaillée]
**Steps to Reproduce :**
1. [Étape 1]
2. [Étape 2]
**Résultat Attendu :** [Ce qui devrait se passer]
**Résultat Actuel :** [Ce qui se passe réellement]
**Screenshot :** [Optionnel]
```

---

## Validation Finale

### Checklist Pré-Déploiement

- [ ] Tous tests fonctionnels passent (1-10)
- [ ] Tous navigateurs testés (Chrome, Firefox, Safari, Edge)
- [ ] Responsive testé (desktop, tablet, mobile)
- [ ] 3 thèmes testés (light, dark-blue, dark-red)
- [ ] Accessibilité validée (clavier, ARIA, contraste)
- [ ] Performance OK (<100ms init, 60fps animations)
- [ ] LocalStorage fonctionne
- [ ] Help button relance tour
- [ ] Aucun warning/error console
- [ ] Code review effectué
- [ ] Documentation à jour

---

**Note :** Ce guide doit être exécuté par un testeur humain. Les tests automatisés (Playwright/Cypress) peuvent être ajoutés en Phase 4.

**Durée estimée tests complets :** 30-45 minutes
