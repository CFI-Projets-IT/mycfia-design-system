# Corrections Critiques DAP - À Appliquer sur Toutes les Vues

**Date :** 2026-01-26
**Statut :** ✅ **TOUTES LES VUES CORRIGÉES**
- ✅ `step2_validate_light.html` (onboarding-dap-step2.js)
- ✅ `step1_create_light.html` (onboarding-dap-step1.js)
- ✅ `dashboard_light.html` (onboarding-dap.js)
- ✅ `step1_review_light.html` (onboarding-dap-step1-review.js)

---

## 🔴 Problèmes Identifiés

### 1. **Tooltips qui bougent pendant le scroll**
- **Symptôme** : Les tooltips ne restent pas attachés à leur élément cible pendant le scroll
- **Cause** : Utilisation incorrecte de `position: fixed` avec `getBoundingClientRect()`
- **Impact** : UX dégradée, tooltips qui "flottent" au lieu de suivre l'élément

### 2. **Pas de scroll automatique + Scroll manuel non bloqué**
- **Symptôme** : Quand l'élément ciblé n'est pas visible, le tooltip n'apparaît pas
- **Cause** : Aucun appel à `scrollIntoView()` dans le code
- **Impact** : Tooltips invisibles pour les éléments hors viewport
- **Problème UX supplémentaire** : L'utilisateur peut scroller manuellement pendant le DAP, ce qui désynchronise les tooltips et dégrade l'expérience

### 3. **Sélecteurs CSS fragiles**
- **Symptôme** : Certains sélecteurs utilisant `:has()` ne fonctionnent pas
- **Cause** : Support limité de `:has()` dans certains contextes
- **Impact** : Étapes du tour qui ne s'affichent pas

---

## ✅ Solutions Appliquées

### Solution 1 : Corriger le Positionnement (CSS + JS)

#### A. CSS - Vérifier `position: absolute`

**Fichier :** `assets/css/components/_onboarding-dap.css`

**Ligne ~156 :**
```css
.onboarding-tooltip {
    position: absolute;  /* ✅ DOIT être "absolute", PAS "fixed" */
    min-width: 280px;
    /* ... */
}
```

**Spotlight aussi :**
```css
.onboarding-spotlight {
    position: absolute;  /* ✅ DOIT être "absolute" */
    /* ... */
}
```

**⚠️ Important :** `position: fixed` ne fonctionne PAS car les tooltips doivent suivre l'élément pendant le scroll.

---

#### B. JavaScript - Ajouter `window.scrollY` / `scrollX`

**Fichier :** `assets/js/components/onboarding-dap-[page].js`

**Méthode `positionSpotlight()` :**
```javascript
positionSpotlight(targetEl) {
    const rect = targetEl.getBoundingClientRect();
    const padding = 8;

    // ✅ AJOUTER window.scrollY et window.scrollX
    this.spotlight.style.top = `${rect.top - padding + window.scrollY}px`;
    this.spotlight.style.left = `${rect.left - padding + window.scrollX}px`;
    this.spotlight.style.width = `${rect.width + padding * 2}px`;
    this.spotlight.style.height = `${rect.height + padding * 2}px`;
}
```

**Méthode `positionTooltip()` - TOUTES les positions :**

```javascript
positionTooltip(targetEl) {
    const targetRect = targetEl.getBoundingClientRect();
    const tooltipRect = this.tooltip.getBoundingClientRect();
    const padding = 16;

    let position = "bottom";
    let top, left;

    // ✅ Position BOTTOM
    if (spaceBelow >= tooltipRect.height + padding) {
        position = "bottom";
        top = targetRect.bottom + padding + window.scrollY;  // ✅ + window.scrollY
        left = targetRect.left + targetRect.width / 2 - tooltipRect.width / 2 + window.scrollX;  // ✅ + window.scrollX
    }

    // ✅ Position TOP
    else if (spaceAbove >= tooltipRect.height + padding) {
        position = "top";
        top = targetRect.top - tooltipRect.height - padding + window.scrollY;  // ✅ + window.scrollY
        left = targetRect.left + targetRect.width / 2 - tooltipRect.width / 2 + window.scrollX;  // ✅ + window.scrollX
    }

    // ✅ Position RIGHT
    else if (spaceRight >= tooltipRect.width + padding) {
        position = "right";
        top = targetRect.top + targetRect.height / 2 - tooltipRect.height / 2 + window.scrollY;  // ✅ + window.scrollY
        left = targetRect.right + padding + window.scrollX;  // ✅ + window.scrollX
    }

    // ✅ Position LEFT
    else if (spaceLeft >= tooltipRect.width + padding) {
        position = "left";
        top = targetRect.top + targetRect.height / 2 - tooltipRect.height / 2 + window.scrollY;  // ✅ + window.scrollY
        left = targetRect.left - tooltipRect.width - padding + window.scrollX;  // ✅ + window.scrollX
    }

    // ✅ Fallback centré
    else {
        position = "bottom";
        top = targetRect.bottom + padding + window.scrollY;  // ✅ + window.scrollY
        left = window.innerWidth / 2 - tooltipRect.width / 2 + window.scrollX;  // ✅ + window.scrollX
    }

    // Appliquer les positions
    this.tooltip.style.top = `${top}px`;
    this.tooltip.style.left = `${left}px`;
    // ...
}
```

**📊 Total : 12 occurrences de `+ window.scrollY` / `+ window.scrollX` à ajouter**

---

### Solution 2 : Ajouter Scroll Automatique

**Fichier :** `assets/js/components/onboarding-dap-[page].js`

**Méthode `showStep()` - Après avoir trouvé `targetEl` :**

```javascript
showStep(stepIndex) {
    // ... code existant ...

    const targetEl = document.querySelector(step.target);
    if (!targetEl) {
        console.warn(`Élément cible "${step.target}" introuvable`);
        this.showStep(stepIndex + 1);
        return;
    }

    // ✅ AJOUTER CE BLOC : Scroll automatique vers l'élément si pas visible
    const rect = targetEl.getBoundingClientRect();
    const isVisible = (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= window.innerHeight &&
        rect.right <= window.innerWidth
    );

    if (!isVisible) {
        targetEl.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
            inline: 'center'
        });

        // Attendre que le scroll se termine (500ms suffisant)
        setTimeout(() => {
            this.positionSpotlight(targetEl);
            this.createTooltip(step, targetEl);
        }, 500);
        return;
    }
    // ✅ FIN DU BLOC

    // Positionner le spotlight (si déjà visible)
    this.positionSpotlight(targetEl);

    // Créer et afficher le tooltip
    this.createTooltip(step, targetEl);

    // ... reste du code ...
}
```

**⚠️ Notes :**
- Le `setTimeout(500)` permet d'attendre la fin de l'animation smooth scroll
- Si l'élément est déjà visible, pas de scroll (performance)

---

### Solution 2B : Bloquer le Scroll Manuel de l'Utilisateur

**Problème** : L'utilisateur peut scroller manuellement pendant le tour guidé, ce qui désynchronise les tooltips et crée une mauvaise UX.

**Solution** : Bloquer le scroll du `<body>` quand le tour est actif. Seul le DAP contrôle le scroll via `scrollIntoView()`.

#### A. CSS - Bloquer le scroll du body

**Fichier :** `assets/css/components/_onboarding-dap.css`

**Après `.onboarding-overlay.active` (ligne ~43) :**
```css
/**
 * Bloquer le scroll du body pendant le tour guidé
 * Le scroll est géré automatiquement par scrollIntoView()
 * L'utilisateur ne peut pas scroller manuellement pour éviter les désynchronisations
 */
body.onboarding-active {
    overflow: hidden;
    position: fixed;
    width: 100%;
}
```

#### B. JavaScript - Ajouter/retirer la classe sur le body

**Fichier :** `assets/js/components/onboarding-dap-[page].js`

**Méthode `startTour()` - Ajouter après `this.currentStep = 1` :**
```javascript
// Sauvegarder la position de scroll actuelle
this._scrollPosition = window.scrollY;

// Bloquer le scroll du body (UX: seul le DAP contrôle le scroll)
document.body.classList.add('onboarding-active');
document.body.style.top = `-${this._scrollPosition}px`;
```

**Méthode `cleanup()` - Ajouter au tout début :**
```javascript
// Débloquer le scroll du body et restaurer la position
document.body.classList.remove('onboarding-active');
document.body.style.top = '';

// Restaurer la position de scroll sauvegardée
if (this._scrollPosition !== undefined) {
    window.scrollTo(0, this._scrollPosition);
    this._scrollPosition = undefined;
}
```

**✅ Bénéfices :**
- UX améliorée : pas de désynchronisation entre tooltips et éléments
- Contrôle total du scroll par le DAP
- Position de scroll restaurée après le tour (pas de "saut")
- L'utilisateur ne peut pas "casser" le tour en scrollant

---

### Solution 3 : Éviter les Sélecteurs `:has()`

**Problème :** Les sélecteurs CSS `:has()` ne sont pas supportés partout.

**❌ À ÉVITER :**
```javascript
target: ".competitor-section-header:has(.bi-info-circle-fill)"
target: ".col-12:has(.competitor-card.selected):first-child"
```

**✅ À UTILISER :**
```javascript
// Utiliser des classes parent uniques
target: ".mt-5.mb-4 .competitor-section-header"

// Utiliser nth-child ou first-child simple
target: ".row.g-4 .col-12:first-child"

// Utiliser des classes spécifiques
target: ".form-section-delay-1"
target: ".competitor-score"
```

**Patterns recommandés :**
- ✅ Classes spécifiques (`.my-class`)
- ✅ `:nth-child()` (`.card:nth-child(2)`)
- ✅ `:first-child` simple (`.col:first-child`)
- ✅ Combinaison de classes (`.mt-5.mb-4 .header`)
- ❌ `:has()` (support limité)

---

## 📋 Checklist Application sur Autres Vues

### Pour chaque vue DAP (`dashboard`, `step1_create`, `step1_review`)

#### JavaScript (`onboarding-dap-[page].js`)

- [ ] **Méthode `positionSpotlight()`**
  - [ ] Ligne `this.spotlight.style.top` : Ajouter `+ window.scrollY`
  - [ ] Ligne `this.spotlight.style.left` : Ajouter `+ window.scrollX`

- [ ] **Méthode `positionTooltip()`**
  - [ ] Position BOTTOM (2 lignes) : Ajouter `+ window.scrollY` et `+ window.scrollX`
  - [ ] Position TOP (2 lignes) : Ajouter `+ window.scrollY` et `+ window.scrollX`
  - [ ] Position RIGHT (2 lignes) : Ajouter `+ window.scrollY` et `+ window.scrollX`
  - [ ] Position LEFT (2 lignes) : Ajouter `+ window.scrollY` et `+ window.scrollX`
  - [ ] Fallback centré (2 lignes) : Ajouter `+ window.scrollY` et `+ window.scrollX`

- [ ] **Méthode `showStep()`**
  - [ ] Ajouter bloc scroll automatique avec `scrollIntoView()`
  - [ ] Ajouter vérification `isVisible` avant scroll
  - [ ] Ajouter `setTimeout(500)` pour attendre fin scroll

- [ ] **Méthode `startTour()`**
  - [ ] Sauvegarder position scroll actuelle : `this._scrollPosition = window.scrollY`
  - [ ] Bloquer scroll body : `document.body.classList.add('onboarding-active')`
  - [ ] Fixer position : `document.body.style.top = -${this._scrollPosition}px`

- [ ] **Méthode `cleanup()`**
  - [ ] Débloquer scroll : `document.body.classList.remove('onboarding-active')`
  - [ ] Nettoyer style : `document.body.style.top = ''`
  - [ ] Restaurer position : `window.scrollTo(0, this._scrollPosition)`

- [ ] **Configuration `ONBOARDING_STEPS_[PAGE]`**
  - [ ] Vérifier qu'aucun sélecteur n'utilise `:has()`
  - [ ] Remplacer par sélecteurs robustes si nécessaire

#### CSS (`_onboarding-dap.css`)

- [ ] **`.onboarding-tooltip`**
  - [ ] Vérifier `position: absolute` (ligne ~156)
  - [ ] NE PAS utiliser `position: fixed`

- [ ] **`.onboarding-spotlight`**
  - [ ] Vérifier `position: absolute`
  - [ ] NE PAS utiliser `position: fixed`

- [ ] **`body.onboarding-active`**
  - [ ] Ajouter règle après `.onboarding-overlay.active` (ligne ~43)
  - [ ] Propriétés : `overflow: hidden`, `position: fixed`, `width: 100%`

#### Tests de Validation

- [ ] **Lancer le tour guidé**
  - [ ] Toutes les étapes s'affichent
  - [ ] Tooltips visibles pour chaque étape
  - [ ] Scroll automatique fonctionne (éléments hors viewport)

- [ ] **Pendant le tour guidé (scroll automatique)**
  - [ ] Les tooltips suivent l'élément ciblé
  - [ ] Les tooltips ne "flottent" pas
  - [ ] Le spotlight reste autour de l'élément
  - [ ] **L'utilisateur ne peut PAS scroller manuellement (molette/trackpad bloqués)**
  - [ ] La position de scroll est restaurée après le tour

- [ ] **Navigation étapes**
  - [ ] Bouton "Suivant" fonctionne
  - [ ] Progress dots corrects
  - [ ] Dernière étape affiche "Terminer"

---

## 🎯 Patterns à Respecter pour Futures Implémentations

### 1. Toujours Utiliser `position: absolute`
```css
.onboarding-tooltip,
.onboarding-spotlight {
    position: absolute;  /* ✅ Obligatoire */
}
```

### 2. Toujours Ajouter `window.scrollY` / `scrollX`
```javascript
// ✅ Avec getBoundingClientRect() + position: absolute
top = rect.top + window.scrollY;
left = rect.left + window.scrollX;
```

### 3. Toujours Ajouter Scroll Automatique
```javascript
// ✅ Dans showStep(), après avoir trouvé targetEl
if (!isVisible) {
    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => { /* position tooltip */ }, 500);
}
```

### 4. Toujours Éviter `:has()`
```javascript
// ❌ NE PAS FAIRE
target: ".section:has(.icon)"

// ✅ FAIRE
target: ".parent-unique .section"
target: ".section:nth-child(2)"
```

---

## 📁 Fichiers Concernés

### ✅ Tous Corrigés (2026-01-26)
- ✅ `assets/js/components/onboarding-dap-step2.js` (step2_validate)
- ✅ `assets/js/components/onboarding-dap-step1.js` (step1_create)
- ✅ `assets/js/components/onboarding-dap.js` (dashboard)
- ✅ `assets/js/components/onboarding-dap-step1-review.js` (step1_review)

### CSS Partagé
- ✅ `assets/css/components/_onboarding-dap.css` (vérifié)

---

## 🔍 Validation Rapide

Pour vérifier qu'une vue DAP est correcte :

```bash
# 1. Vérifier CSS position: absolute
grep -n "position: fixed" assets/css/components/_onboarding-dap.css
# ✅ Doit retourner 0 résultat pour .onboarding-tooltip

# 2. Vérifier présence window.scrollY dans JS
grep -c "window.scrollY" assets/js/components/onboarding-dap-[page].js
# ✅ Doit retourner >= 6 (au moins 6 occurrences)

# 3. Vérifier présence scrollIntoView
grep -n "scrollIntoView" assets/js/components/onboarding-dap-[page].js
# ✅ Doit trouver au moins 1 occurrence

# 4. Vérifier absence de :has()
grep -n ":has(" assets/js/components/onboarding-dap-[page].js
# ✅ Doit retourner 0 résultat
```

---

## 📝 Exemple Complet - Step 2

**Commit de référence :** Corrections DAP Step 2 (2026-01-26)

**Fichiers modifiés :**
- `assets/js/components/onboarding-dap-step2.js` (+12 `window.scrollY/scrollX`, +1 bloc scroll automatique)
- Ordre des étapes corrigé (scores d'alignement entre directs/indirects)

**Tests validés :**
- ✅ Tooltips suivent l'élément pendant scroll
- ✅ Scroll automatique vers éléments hors viewport
- ✅ Toutes les 7 étapes fonctionnelles
- ✅ Sélecteurs CSS robustes (aucun `:has()`)

---

## ⚠️ Notes Importantes

1. **Cache navigateur** : Après modifications CSS, faire un hard refresh (`Ctrl+Shift+R`)
2. **Serveur HTTP** : Toujours tester avec `http://localhost` ou `python3 -m http.server`, JAMAIS avec `file://`
3. **Console logs** : Vérifier aucune erreur JavaScript dans la console
4. **LocalStorage** : Clear localStorage pour tester première visite (`localStorage.clear()`)

---

## 🎓 Pourquoi Ces Corrections ?

### `position: absolute` vs `position: fixed`

- **`fixed`** : Positionné relatif au **viewport** (fenêtre visible)
  - ❌ Ne suit PAS le scroll
  - ❌ Nécessite recalcul constant pendant scroll

- **`absolute`** : Positionné relatif au **document** (page complète)
  - ✅ Suit naturellement le scroll
  - ✅ Coordonnées stables = `getBoundingClientRect() + window.scrollY`

### `getBoundingClientRect()` + `window.scrollY`

`getBoundingClientRect()` retourne des coordonnées **relatives au viewport** :
- `rect.top = 100` signifie "100px du haut du viewport visible"

Avec `position: absolute`, on a besoin de coordonnées **relatives au document** :
- `document_top = rect.top + window.scrollY`
- Si `scrollY = 500` et `rect.top = 100`, alors position document = `600px`

**C'est pour ça que `window.scrollY` / `scrollX` sont OBLIGATOIRES avec `position: absolute`.**

---

**Document vivant** : Ce guide sera la référence pour toutes les corrections et nouvelles implémentations DAP.
