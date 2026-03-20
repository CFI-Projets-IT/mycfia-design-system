# Nouvelle Étape BUDGET - Spécifications

**Version:** 1.0
**Date:** 2026-02-11
**Auteur:** Analyse des besoins utilisateur

---

## 🎯 Vue d'Ensemble

### Objectif
Ajouter une nouvelle étape **BUDGET** entre l'étape **STEP 8 : ASSETS** (validation des contenus marketing) et **STEP 9 : PLANNING** (planification des diffusions).

### Rôle de l'Étape BUDGET
- **Afficher le coût de la campagne** selon les tarifs CFIA
- **Intégrer le paiement Stripe** comme validation obligatoire
- **Bloquer l'accès à l'étape Planning** si le paiement n'est pas validé
- **Adapter l'affichage** selon le parcours utilisateur (Standard vs Avancé)

---

## 📋 Contexte Workflow Actuel

### Structure Actuelle (8 étapes)
1. **STEP 1 : PROJET** → Définition projet + enrichissement IA
2. **STEP 2 : CONCURRENTS** → Sélection concurrents
3. **STEP 3 : PERSONAS** → Sélection personas + Profilia (optionnel)
4. **STEP 4 : UPLOAD CONTACTS** → Import base contacts (optionnel)
5. **STEP 5 : STRATÉGIE** → Configuration stratégie marketing
6. **STEP 6 : CANAUX** → Sélection canaux de diffusion
7. **STEP 7 : ASSETS** → Génération et validation contenus
8. **STEP 8 : PLANNING** (`step9_schedule_light.html`) → Planification diffusions

### Structure Proposée (9 étapes)
1. **STEP 1 : PROJET**
2. **STEP 2 : CONCURRENTS**
3. **STEP 3 : PERSONAS**
4. **STEP 4 : UPLOAD CONTACTS**
5. **STEP 5 : STRATÉGIE**
6. **STEP 6 : CANAUX**
7. **STEP 7 : ASSETS**
8. **STEP 8 : BUDGET** ← **NOUVELLE ÉTAPE**
9. **STEP 9 : PLANNING** (ancien step8)

---

## 🔀 Deux Parcours Utilisateur

### Parcours A : "Avancé" (avec import Avanci)

**Critère de détection :**
- À la **Step 4 (Upload Contacts)**, l'utilisateur a choisi :
  - Option **"Avanci"** (leads qualifiés Avanci uniquement)
  - OU Option **"Upload + Avanci"** (combinaison fichier propre + Avanci)

**Comportement Step 8 BUDGET :**
- ✅ **Afficher directement le paiement Stripe**
- ❌ **Ne PAS afficher le tableau des tarifs CFIA** (skip)
- 💡 **Hypothèse** : Les tarifs sont inclus dans l'offre Avanci (forfait ou pricing différent)

**Workflow :**
```
STEP 7 (Assets validés)
    ↓
STEP 8 BUDGET (Paiement Stripe direct - pas de détail tarifs)
    ↓
STEP 9 PLANNING (si paiement validé)
```

---

### Parcours B : "Standard" (sans Avanci)

**Critère de détection :**
- À la **Step 4 (Upload Contacts)**, l'utilisateur a choisi :
  - Option **"Upload Fichier Contact"** (fichier CSV/Excel uniquement)
  - OU Option **"Passer directement à la Stratégie"** (pas de contacts, uniquement ads digitaux)

**Comportement Step 8 BUDGET :**
- ✅ **Afficher le tableau des tarifs CFIA** détaillé
- ✅ **Calculer automatiquement le coût total** selon volume établi
- ✅ **Afficher le paiement Stripe** après validation tarifs

**Workflow :**
```
STEP 7 (Assets validés)
    ↓
STEP 8 BUDGET
    ├─ Tableau tarifs CFIA (par support)
    ├─ Calcul volume × tarif unitaire = total
    └─ Paiement Stripe
    ↓
STEP 9 PLANNING (si paiement validé)
```

---

## 💰 Tableau Tarifs CFIA (Parcours Standard)

### Structure du Tableau

**Colonnes proposées :**
| Support | Volume Établi | Tarif Unitaire CFIA | Total |
|---------|---------------|---------------------|-------|
| Email Marketing | 10 000 emails | 0,05 € / email | 500 € |
| SMS | 5 000 SMS | 0,10 € / SMS | 500 € |
| Courrier Postal | 2 000 lettres | 0,80 € / lettre | 1 600 € |
| LinkedIn Ads | 500 000 impressions | 8,00 € / 1000 imp | 4 000 € |
| Google Ads | 1 000 000 impressions | 5,00 € / 1000 imp | 5 000 € |
| Facebook Ads | 800 000 impressions | 4,00 € / 1000 imp | 3 200 € |
| Display / Programmatique | 1 500 000 impressions | 3,00 € / 1000 imp | 4 500 € |

**Total Campagne Estimé** : **19 300 €**

### Organisation Visuelle
- **Section par support** : chaque canal sélectionné à la Step 6
- **Ligne récapitulative** : Total général en bas du tableau
- **Design** : Card Bootstrap avec tableau responsive
- **Éditable ?** : Volume éditable par user pour ajuster calcul ?

---

## 💳 Intégration Paiement Stripe

### Prérequis Technique
- **Stripe API** : clé publique/privée configurées
- **Webhook Stripe** : validation paiement côté backend
- **Session Stripe Checkout** : redirection vers page paiement sécurisée

### Workflow Paiement

#### 1. Affichage du Montant
- **Parcours Avancé** : Montant calculé côté backend (API call)
- **Parcours Standard** : Montant = Total tableau tarifs CFIA

#### 2. Bouton "Procéder au Paiement"
- Clic → Appel API backend :
  ```
  POST /api/campaigns/{id}/create-payment-intent
  {
    "campaign_id": "uuid",
    "amount": 19300,
    "currency": "EUR"
  }
  ```
- Réponse : `client_secret` Stripe
- Redirection vers Stripe Checkout (modal ou page externe)

#### 3. Validation Paiement
- **Succès** : Webhook Stripe notifie backend → `payment_status = validated`
- **Échec** : Retour Step 8 avec message erreur
- **Annulation** : Retour Step 8, possibilité réessayer

#### 4. Déblocage Planning
- **Si paiement validé** : Bouton "Continuer vers Planning" activé
- **Si paiement non validé** : Bouton désactivé avec tooltip "Paiement requis"

---

## 🚫 Blocage Accès Planning

### Règle de Validation
**Condition d'accès Step 9 (Planning) :**
```javascript
if (campaign.payment_status !== 'validated') {
  // Bloquer accès + afficher message
  showAlert("Paiement requis pour accéder à la planification");
  redirectTo('step8_budget_light.html');
}
```

### Scénarios de Blocage

#### Scénario 1 : Tentative d'Accès Direct
- User essaie d'accéder à `/campaign_generation/step9_schedule_light.html` directement
- Vérification backend : `payment_status`
- Si non validé → Redirection forcée vers Step 8

#### Scénario 2 : Clic "Valider et Continuer" (Speed Dial)
- Clic sur bouton principal Speed Dial à la Step 8
- JavaScript vérifie `payment_status` avant navigation
- Si non validé → Modal d'alerte + blocage navigation

---

## 🎨 Interface Utilisateur Proposée

### Page Step 8 : Budget (`step8_budget_light.html`)

#### Header
```html
<div class="campaign-stepper">
  <!-- Stepper avec Step 8 active -->
  <div class="stepper-step active">
    <div class="stepper-circle">8</div>
    <div class="stepper-label">Budget</div>
  </div>
</div>
```

#### Content - Parcours Standard

```html
<div class="container mt-5">
  <!-- Alert Introduction -->
  <div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <strong>Récapitulatif des Coûts</strong>
    Voici le détail des tarifs CFIA selon les canaux sélectionnés et les volumes estimés.
  </div>

  <!-- Section Tableau Tarifs -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Détail des Tarifs par Support</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Support</th>
              <th>Volume Établi</th>
              <th>Tarif Unitaire CFIA</th>
              <th class="text-end">Total</th>
            </tr>
          </thead>
          <tbody>
            <!-- Lignes dynamiques par canal -->
            <tr>
              <td><i class="bi bi-envelope"></i> Email Marketing</td>
              <td>10 000 emails</td>
              <td>0,05 € / email</td>
              <td class="text-end"><strong>500,00 €</strong></td>
            </tr>
            <!-- ... autres canaux ... -->
          </tbody>
          <tfoot>
            <tr class="table-primary">
              <td colspan="3" class="text-end"><strong>Total Campagne :</strong></td>
              <td class="text-end"><strong class="fs-5">19 300,00 €</strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Section Paiement Stripe -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Paiement Sécurisé</h5>
    </div>
    <div class="card-body">
      <p>Pour lancer votre campagne, veuillez procéder au paiement sécurisé via Stripe.</p>

      <!-- Stripe Payment Element -->
      <div id="payment-element">
        <!-- Stripe injectera le formulaire ici -->
      </div>

      <button id="btnPayment" class="btn btn-primary btn-lg w-100 mt-3">
        <i class="bi bi-credit-card"></i> Procéder au Paiement (19 300,00 €)
      </button>

      <!-- Statut paiement -->
      <div id="paymentStatus" class="mt-3"></div>
    </div>
  </div>
</div>
```

#### Content - Parcours Avancé

```html
<div class="container mt-5">
  <!-- Alert Introduction -->
  <div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <strong>Paiement Avanci</strong>
    Votre campagne utilise les contacts Avanci. Le paiement inclut les frais de campagne et l'accès aux leads qualifiés.
  </div>

  <!-- Section Paiement Direct (sans tableau) -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Paiement Sécurisé</h5>
    </div>
    <div class="card-body">
      <p>Montant total de votre campagne :</p>
      <h2 class="text-primary mb-4">12 500,00 € <small class="text-muted">(Forfait Avanci inclus)</small></h2>

      <!-- Stripe Payment Element -->
      <div id="payment-element"></div>

      <button id="btnPayment" class="btn btn-primary btn-lg w-100 mt-3">
        <i class="bi bi-credit-card"></i> Procéder au Paiement (12 500,00 €)
      </button>
    </div>
  </div>
</div>
```

#### Speed Dial

```html
<div class="speed-dial-container">
  <!-- Action secondaire : Retour -->
  <div class="speed-dial-action">
    <span class="speed-dial-label">Retour aux Assets</span>
    <button class="speed-dial-btn speed-dial-cancel" onclick="window.location.href='step8_validate_light.html'">
      <i class="bi bi-arrow-left"></i>
    </button>
  </div>

  <!-- Action principale : Continuer (désactivée si pas de paiement) -->
  <div class="speed-dial-action">
    <span class="speed-dial-label">Continuer vers Planning</span>
    <button id="btnContinue" class="speed-dial-btn speed-dial-primary" disabled>
      <i class="bi bi-check-lg"></i>
    </button>
  </div>
</div>
```

---

## 📂 Fichiers à Créer/Modifier

### Nouveaux Fichiers

1. **`campaign_generation/step8_budget_light.html`**
   - Page principale étape Budget
   - Intègre Stripe.js
   - Affiche tableau tarifs (si Standard) ou montant direct (si Avancé)

2. **`assets/css/components/_campaign-budget.css`**
   - Styles spécifiques tableau tarifs
   - Styles section paiement Stripe
   - Animations validation paiement

3. **`assets/js/components/step8-budget.js`**
   - Logique affichage tableau tarifs
   - Intégration Stripe Payment Element
   - Vérification statut paiement
   - Activation/désactivation bouton "Continuer"

### Fichiers à Modifier

1. **`campaign_generation/step8_validate_light.html`** (ancienne Step 8 Assets)
   - Modifier lien Speed Dial "Valider et continuer" :
     - Avant : `href="step9_schedule_light.html"`
     - Après : `href="step8_budget_light.html"`

2. **`campaign_generation/step9_schedule_light.html`** (ancien step8 Planning)
   - Renommer en `step9_schedule_light.html` (déjà le cas)
   - Ajouter vérification `payment_status` au chargement page

3. **`assets/css/components/_campaign-stepper.css`**
   - Ajouter Step 9 dans le stepper (actuellement 8 steps)

4. **`docs/WORKFLOW_GENERATION_CAMPAGNE.md`**
   - Mettre à jour avec nouvelle Step 8 : BUDGET
   - Déplacer ancien Step 8 (Planning) vers Step 9

---

## ❓ Questions & Clarifications Requises

### 1. Tarifs CFIA - Source des Données

**Question :** Où se trouvent les tarifs CFIA ?
- **Option A** : Barème fixe hardcodé dans le frontend/backend
- **Option B** : API externe CFIA à interroger
- **Option C** : Table base de données configurable

**Impact :** Architecture récupération données + possibilité mise à jour tarifs

---

### 2. Calcul du Volume Établi

**Question :** Comment est déterminé le volume pour chaque support ?
- **Email/SMS/Courrier** : Nombre de contacts importés (Step 4) ?
- **Ads digitaux (LinkedIn, Google, Facebook, Display)** : Impressions estimées selon budget alloué (Step 6) ?

**Exemple :**
- Budget LinkedIn = 4 000 €
- CPC moyen = 8 € / 1000 impressions
- Volume = 4 000 / 8 × 1000 = 500 000 impressions

**Impact :** Logique calcul backend + affichage frontend

---

### 3. Service Avanci - Définition

**Question :** Qu'est-ce qu'Avanci exactement ?
- Service enrichissement contacts ?
- Base de leads qualifiés à acheter ?
- Partenariat tiers ?

**Question :** Pourquoi le parcours Avancé skip le tableau tarifs ?
- Les tarifs sont inclus dans un forfait Avanci ?
- Le pricing est différent et calculé autrement ?

**Impact :** Logique conditionnelle affichage + workflow paiement

---

### 4. Montant Paiement Stripe

**Question :** Le montant payé via Stripe correspond à quoi ?
- **Option A** : Total campagne calculé (100% du budget)
- **Option B** : Commission/marge CFIA uniquement (ex: 15% du budget)
- **Option C** : Forfait fixe + variable selon canaux

**Exemple Budget Step 1 vs Budget Step 8 :**
- Step 1 : User déclare "Budget total : 25 000 €"
- Step 8 : Calcul tarifs CFIA = 19 300 €
- Cohérence ? Le budget Step 1 est-il indicatif ou contraignant ?

**Impact :** Calcul montant Stripe + cohérence avec budget initial

---

### 5. Workflow Stripe - Modalités

**Question :** Quel type de paiement Stripe ?
- **Option A** : Payment Intent (paiement immédiat)
- **Option B** : Checkout Session (redirection page Stripe)
- **Option C** : Setup Intent (autorisation carte, paiement ultérieur)

**Question :** Gestion des webhooks Stripe ?
- Endpoint backend configuré : `/api/stripe/webhook` ?
- Événements écoutés : `payment_intent.succeeded`, `checkout.session.completed` ?

**Impact :** Implémentation frontend/backend + sécurité

---

### 6. Paiement Unique ou Échelonné ?

**Question :** Le paiement est-il :
- **Unique** : Paiement total avant lancement campagne
- **Échelonné** : Acompte maintenant, solde après diffusion
- **Par palier** : Paiement selon phases (ex: 50% avant, 50% après)

**Impact :** UX paiement + logique backend

---

### 7. Édition du Volume par User

**Question :** L'utilisateur peut-il modifier le volume dans le tableau ?
- **Éditable** : Input modifiable → recalcul total dynamique
- **Non éditable** : Affichage lecture seule

**Impact :** Complexité frontend (calcul dynamique) + validation backend

---

### 8. Design UI - Préférences

**Question :** Préférences visuelles pour le tableau tarifs ?
- Tableau Bootstrap classique ?
- Cards par canal (plus visuel) ?
- Accordion sections (si beaucoup de canaux) ?

**Question :** Paiement Stripe :
- Modal overlay ?
- Page dédiée plein écran ?
- Intégré directement dans Step 8 (Stripe Payment Element) ?

**Impact :** Design + expérience utilisateur

---

### 9. Gestion Erreurs Paiement

**Question :** Comportement si paiement échoue ?
- Message erreur + possibilité réessayer ?
- Enregistrement campagne en brouillon ?
- Email/notification au support ?

**Question :** Timeout paiement ?
- Durée validité session Stripe (ex: 30 minutes) ?
- Que se passe-t-il si user abandonne paiement ?

**Impact :** Robustesse + UX erreur

---

### 10. Coherence Budget Step 1 vs Step 8

**Question :** Le budget déclaré à la Step 1 ("Budget Total") est-il :
- **Indicatif** : Simple estimation, calcul réel fait Step 8
- **Contraignant** : Le calcul Step 8 ne doit pas dépasser ce montant
- **Ignoré** : Pas de lien entre les deux

**Impact :** Validation + alertes si dépassement

---

## 🎯 Récapitulatif de ma Compréhension

### Ce que j'ai compris

✅ **Nouvelle étape Budget entre Assets et Planning**
- Étape 8 actuelle (Assets) reste Step 8
- Nouvelle Step 9 : Budget
- Ancien Step 8 (Planning) devient Step 10

✅ **Paiement Stripe obligatoire**
- Sans paiement validé → blocage accès Planning
- Stripe intégré avec Payment Element ou Checkout Session

✅ **Deux parcours utilisateur**
- **Avancé (Avanci)** : Paiement direct, pas de détail tarifs
- **Standard (Upload)** : Tableau tarifs CFIA détaillé, puis paiement

✅ **Tableau tarifs CFIA (Parcours Standard)**
- Organisation par support (Email, SMS, Courrier, Ads)
- Calcul : Volume × Tarif Unitaire = Total
- Total général affiché en pied de tableau

✅ **Détection parcours basée sur Step 4**
- Choix "Avanci" → Parcours Avancé
- Choix "Upload Fichier" → Parcours Standard

### Ce que je dois clarifier

❓ **Tarifs CFIA** : Source des données (API, BDD, hardcodé) ?
❓ **Volume établi** : Comment est-il calculé pour chaque canal ?
❓ **Avanci** : Définition exacte + raison du skip tarifs ?
❓ **Montant Stripe** : Total campagne ou commission CFIA ?
❓ **Type paiement** : Intent, Checkout, Setup ?
❓ **Paiement échelonné** : Unique ou par palier ?
❓ **Édition volume** : User peut modifier dans tableau ?
❓ **Design UI** : Tableau, cards, modal Stripe ?
❓ **Erreurs paiement** : Comportement si échec ?
❓ **Cohérence budgets** : Lien entre budget Step 1 et calcul Step 8 ?

---

## 🚀 Prochaines Étapes Recommandées

### Phase 1 : Clarification (Avant Implémentation)
1. Répondre aux 10 questions ci-dessus
2. Définir spécifications API backend (endpoints paiement)
3. Obtenir credentials Stripe (test + production)

### Phase 2 : Design UI/UX
1. Maquetter page `step8_budget_light.html` (Parcours Standard)
2. Maquetter page `step8_budget_light.html` (Parcours Avancé)
3. Concevoir modal/page erreur paiement
4. Valider design avec utilisateurs/stakeholders

### Phase 3 : Implémentation Frontend
1. Créer `step8_budget_light.html`
2. Créer `_campaign-budget.css`
3. Créer `step8-budget.js` (intégration Stripe.js)
4. Modifier stepper (ajouter Step 9)
5. Modifier liens navigation steps

### Phase 4 : Implémentation Backend
1. Créer endpoint `/api/campaigns/{id}/calculate-budget`
2. Créer endpoint `/api/campaigns/{id}/create-payment-intent`
3. Créer endpoint `/api/stripe/webhook`
4. Ajouter champ `payment_status` table campagnes
5. Implémenter middleware blocage accès Planning

### Phase 5 : Tests
1. Tests unitaires calcul tarifs
2. Tests intégration Stripe (sandbox)
3. Tests E2E workflow complet (Parcours Standard + Avancé)
4. Tests erreurs/edge cases paiement

### Phase 6 : Documentation
1. Mettre à jour `WORKFLOW_GENERATION_CAMPAGNE.md`
2. Documenter API endpoints
3. Guide troubleshooting Stripe
4. Guide utilisateur final (si nécessaire)

---

**Fin du Document**

_Ce document sera complété une fois les clarifications obtenues._
