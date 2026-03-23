# Quick Start - Test DAP Step 2 Validate

**Date :** 2026-01-26
**Page :** `step2_validate_light.html`
**Durée test :** ~5 minutes

---

## Test Rapide (5 min)

### 1. Ouvrir la page

```bash
# Option 1 : Serveur local
cd /home/krystdev/Bureau/KrystdevCom/Clients/Gorillias/myCfia-designSystem
python3 -m http.server 8000
# Puis ouvrir : http://localhost:8000/campaign_generation/step2_validate_light.html

# Option 2 : Directement dans le navigateur
open campaign_generation/step2_validate_light.html
```

### 2. Réinitialiser LocalStorage

Ouvrir la console développeur (`F12`) et exécuter :

```javascript
// Supprimer toutes les données onboarding Step 2
localStorage.removeItem('mycfia_onboarding_step2_completed');
localStorage.removeItem('mycfia_onboarding_step2_step');
localStorage.removeItem('mycfia_onboarding_step2_skipped_at');

// Ou tout supprimer
localStorage.clear();

// Recharger la page
location.reload();
```

### 3. Tester le tour guidé

1. ✅ **Modal s'affiche** après 1 seconde
2. ✅ **Cliquer "Faire le tour guidé"**
3. ✅ **Naviguer les 6 étapes** avec bouton "Suivant" :
   - Étape 1 : Stepper progression
   - Étape 2 : Alerte 7 concurrents détectés
   - Étape 3 : Première card concurrent direct
   - Étape 4 : Score d'alignement
   - Étape 5 : Formulaire ajout manuel
   - Étape 6 : Speed Dial FAB (s'ouvre automatiquement)
4. ✅ **Cliquer "Terminer"**
5. ✅ **Toast de félicitation** s'affiche 5 secondes

### 4. Tester le Help Button

1. Recharger la page (modal ne s'affiche plus)
2. Cliquer sur le bouton "?" en haut à droite du breadcrumb
3. Le tour redémarre immédiatement

---

## Vérifications Rapides

### Console Logs

Ouvrir la console (`F12` → Console), vous devriez voir :

```
[main] Initialisation de l'onboarding DAP Step 2...
[OnboardingDAPStep2] Initialisation...
[OnboardingDAPStep2] Initialisé avec succès
```

### LocalStorage

Après avoir terminé le tour :

```javascript
// Vérifier l'état
localStorage.getItem('mycfia_onboarding_step2_completed')  // "true"
localStorage.getItem('mycfia_onboarding_step2_step')       // "6"
```

### Éléments HTML

Vérifier la présence des éléments clés :

```javascript
// Modal
document.getElementById('onboardingWelcomeModalStep2')  // Existe

// Help Button
document.getElementById('helpButtonInlineStep2')  // Existe

// Speed Dial
document.querySelector('.speed-dial-container')  // Existe
```

---

## Tests Détaillés (Optionnel)

### Test 1 : Navigation Étapes

**Objectif :** Vérifier que toutes les étapes s'affichent correctement

**Étapes :**
1. Réinitialiser LocalStorage
2. Lancer le tour
3. Pour chaque étape :
   - ✅ Spotlight positionné correctement
   - ✅ Tooltip visible avec titre et contenu
   - ✅ Progress dots affichent étape courante
   - ✅ Icône Bootstrap Icons visible
   - ✅ Bouton "Suivant" (ou "Terminer" pour dernière)

### Test 2 : Speed Dial Auto-Open

**Objectif :** Vérifier l'ouverture automatique du Speed Dial

**Étapes :**
1. Lancer le tour
2. Naviguer jusqu'à l'étape 6
3. ✅ Vérifier que le Speed Dial s'ouvre automatiquement
4. Terminer le tour
5. ✅ Vérifier que le Speed Dial se ferme

### Test 3 : Skip Tour

**Objectif :** Vérifier le comportement de "Passer"

**Variante A - Depuis modal :**
1. Réinitialiser LocalStorage
2. Attendre modal
3. Cliquer "Passer"
4. ✅ Modal se ferme
5. ✅ Tour ne démarre pas
6. ✅ LocalStorage `completed` = true

**Variante B - Pendant tour :**
1. Lancer le tour
2. Cliquer "Passer le tour" sur n'importe quelle étape
3. ✅ Overlay disparaît
4. ✅ Spotlight disparaît
5. ✅ Tooltip disparaît
6. ✅ LocalStorage `completed` = true

### Test 4 : Responsive

**Desktop (>1200px) :**
- ✅ Tooltip positionné intelligemment
- ✅ Spotlight précis autour des éléments

**Tablet (768-1199px) :**
- ✅ Tooltip adapté (min-width: 240px)
- ✅ Navigation fluide

**Mobile (<768px) :**
- ✅ Tooltip fixe en bas de l'écran (fallback)
- ⚠️ Tour peut être désactivé (optionnel)

### Test 5 : Thèmes

**Light :**
- ✅ Contraste suffisant overlay/spotlight
- ✅ Tooltip lisible

**Dark-Blue :**
- ✅ Variables héritées correctement
- ✅ Texte visible

**Dark-Red :**
- ✅ Variables héritées correctement
- ✅ Texte visible

---

## Problèmes Potentiels

### Modal ne s'affiche pas

**Cause :** LocalStorage `completed` = true

**Solution :**
```javascript
localStorage.removeItem('mycfia_onboarding_step2_completed');
location.reload();
```

### Spotlight mal positionné

**Cause :** Élément cible introuvable ou invisible

**Vérifier :**
```javascript
// Stepper
document.querySelector('.campaign-stepper')  // Doit exister

// Alerte
document.querySelector('.competitors-detected-alert')  // Doit exister

// Première card
document.querySelector('.col-12:has(.competitor-card.selected):first-child')  // Doit exister

// Score
document.querySelector('.competitor-score')  // Doit exister

// Formulaire
document.querySelector('.card.border-0.shadow-sm')  // Doit exister

// Speed Dial
document.querySelector('.speed-dial-container')  // Doit exister
```

### Speed Dial ne s'ouvre pas

**Cause :** Classe `.speed-dial-container` introuvable ou script incompatible

**Vérifier :**
```javascript
const container = document.querySelector('.speed-dial-container');
console.log(container);  // Doit exister
console.log(container.classList);  // Vérifier classes
```

### Tooltip hors écran

**Cause :** Espace insuffisant, algorithme de positionnement

**Solution :**
- Tester sur écran >1200px
- Vérifier que viewport est suffisamment large
- Algorithme adaptatif devrait gérer automatiquement

---

## Support

### Logs Console

Activer les logs détaillés :

```javascript
// Dans onboarding-dap-step2.js, décommenter tous les console.log
```

### État Complet

Afficher l'état complet du système :

```javascript
// LocalStorage
Object.keys(localStorage)
    .filter(key => key.includes('mycfia_onboarding_step2'))
    .forEach(key => console.log(key, localStorage.getItem(key)));

// DOM
console.log('Modal:', document.getElementById('onboardingWelcomeModalStep2'));
console.log('Help Button:', document.getElementById('helpButtonInlineStep2'));
console.log('Speed Dial:', document.querySelector('.speed-dial-container'));
```

---

## Prochaines Étapes

### Après tests manuels réussis :

1. ✅ Valider avec stakeholders
2. ✅ Documenter bugs éventuels
3. ✅ Appliquer correctifs si nécessaire
4. ✅ Tester cross-browser (Chrome, Firefox, Safari, Edge)
5. ✅ Déployer en production
6. ⏳ Implémenter Step 3 (Personas validation)

---

## Commandes Utiles

### Réinitialiser tout

```javascript
localStorage.clear();
location.reload();
```

### Afficher état actuel

```javascript
console.log('Completed:', localStorage.getItem('mycfia_onboarding_step2_completed'));
console.log('Step:', localStorage.getItem('mycfia_onboarding_step2_step'));
console.log('Skipped At:', localStorage.getItem('mycfia_onboarding_step2_skipped_at'));
```

### Forcer relance tour

```javascript
localStorage.removeItem('mycfia_onboarding_step2_completed');
document.getElementById('helpButtonInlineStep2').click();
```

### Inspecter module

```javascript
// Dans la console, après chargement
console.log(window.onboardingDAPStep2);  // Si exposé (debug)
```

---

**Dernière mise à jour :** 2026-01-26
**Version :** 1.0
**Durée test rapide :** ~5 minutes
**Durée test complet :** ~15 minutes
