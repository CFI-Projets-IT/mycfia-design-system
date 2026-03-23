# 📊 Workflow de Génération de Campagne myCFiA

**Version:** 1.0
**Date:** 2026-01-19
**Auteur:** Analyse workflow design system

---

## 🎯 Vue d'Ensemble

### Objectif Global
Le workflow permet de **créer une campagne marketing complète assistée par IA** en 8 étapes structurées, de la définition du projet jusqu'à la planification des diffusions.

### Rôle du Workflow
- **Guider l'utilisateur** à travers un processus complexe de création de campagne
- **Enrichir les données** via l'IA à chaque étape (analyse, génération, recommandations)
- **Valider et affiner** les propositions de l'IA avant de passer à l'étape suivante
- **Générer automatiquement** contenus marketing (assets) adaptés aux personas et canaux

### Utilisation
Destiné aux **responsables marketing** pour créer des campagnes multi-canaux avec assistance IA, en réduisant le temps de conception et en optimisant le ciblage.

---

## 📍 Les 8 Étapes du Workflow

### STEP 1 : PROJET 🏁

**Rôle :** Définir les fondations de la campagne
**Objectif :** Collecter infos projet + enrichir via IA

#### Fichiers
- `step1_create_light.html` - Formulaire initial
- `step1_loading_light.html` - Analyse IA en cours
- `step1_review_light.html` - Validation enrichissement

#### Processus
1. **Création** - L'utilisateur remplit le formulaire :
   - Informations de base (nom projet, entreprise, secteur, site web)
   - Objectifs marketing (objectif principal, objectifs SMART)
   - Budget et Timeline (budget total, dates début/fin, description)

2. **Analyse IA** - L'IA analyse :
   - Le site web fourni
   - Le secteur d'activité
   - Les objectifs déclarés

3. **Review Enrichissement** - L'utilisateur valide :
   - **Nom de campagne** : choix entre nom original ou 3 propositions IA
   - **Identité de marque** :
     - Nom de marque détecté
     - Site web analysé
     - Style de design
     - Palette de couleurs (4 couleurs extraites)
     - Typographie principale
   - **Intelligence business** :
     - Proposition de valeur
     - Marché géographique
     - Audience cible principale
     - Offre principale
   - **Mots-clés Google Ads** :
     - 42 mots-clés détectés
     - Volume moyen/mois
     - CPC moyen
     - Niveau de concurrence
     - Top 5 mots-clés avec métriques détaillées
   - **Recommandations IA** :
     - Ciblage multi-générationnel
     - Canaux prioritaires (ex: LinkedIn 70%, Google Ads 20%, Email 10%)
     - Contenus différenciés
     - Stratégie saisonnière
     - Différenciation concurrentielle

#### Actions Utilisateur
- **Speed Dial** :
  - "Régénérer l'analyse" → retour step1_loading
  - "Valider et continuer" → passage STEP 2

---

### STEP 2 : CONCURRENTS 🏢

**Rôle :** Identifier et analyser la concurrence
**Objectif :** Sélectionner concurrents pertinents pour stratégie de différenciation

#### Fichiers
- `step2_loading_light.html` - Détection IA des concurrents
- `step2_validate_light.html` - Sélection concurrents (fichier = step3_select_light)

#### Processus
1. **Détection IA** - L'IA identifie les concurrents automatiquement

2. **Sélection** - L'utilisateur choisit parmi :
   - **Concurrents directs et importants** (4 détectés)
     - Badge rouge "Concurrent Direct" (score >85%)
     - Badge orange "Concurrent Important" (score 75-85%)
   - **Concurrents indirects** (3 détectés)
     - Badge bleu "Concurrent Indirect" (score <75%)

#### Données par Concurrent
- **Score d'alignement** : pourcentage de similarité
- **Overlap offre** : % de chevauchement des services
- **Overlap marché** : % de chevauchement audience
- **URL** : site web concurrent
- **Description** : positionnement et spécificités
- **Tags** : caractéristiques clés (ex: National, B2B, E-learning, Google Ads actif)

#### Fonctionnalités
- **Ajout manuel** : formulaire pour ajouter un concurrent non détecté
  - Nom concurrent (requis)
  - URL site web (requis)
  - Type : Direct / Indirect (requis)

#### Actions Utilisateur
- **Speed Dial** :
  - "Régénérer les concurrents" → retour step2_loading
  - "Valider et continuer" → passage STEP 3

---

### STEP 3 : PERSONAS 👥

**Rôle :** Définir les audiences cibles
**Objectif :** Sélectionner personas + estimer reach potentiel

#### Fichiers
- `step3_loading_light.html` - Génération IA des personas
- `step3_select_light.html` - Sélection personas + Profilia

#### Processus
1. **Génération IA** - L'IA crée 5 personas basés sur :
   - Informations projet (Step 1)
   - Concurrents sélectionnés (Step 2)
   - Analyse marché

2. **Sélection Personas** - 5 personas générés (exemple secteur formation) :
   - **Sophie Martin** (28 ans) - Jeune Professionnelle
     - Score: 92%
     - Contrôleuse de gestion junior
     - Motivations: Évolution carrière, Certifications, Formation digitale

   - **Marc Dubois** (45 ans) - Cadre Confirmé
     - Score: 89%
     - Directeur financier
     - Motivations: Mise à jour compétences, Formation intensive, Budget entreprise

   - **Claire Rousseau** (35 ans) - Reconversion Pro
     - Score: 85%
     - En reconversion
     - Motivations: Reconversion, Formation diplômante, Financement CPF

   - **Thomas Bernard** (52 ans) - Chef d'Entreprise
     - Score: 78%
     - Dirigeant PME
     - Motivations: Formation équipes, Sur-mesure, Budget élevé

   - **Julie Leroy** (38 ans) - RH / Formation
     - Score: 74%
     - Responsable RH
     - Motivations: ROI important, Taux de réussite, Reporting détaillé

3. **Enrichissement Profilia (Optionnel)** - Sidebar droite
   - **État Initial** : Invitation à confronter aux segments Profilia
     - 48M profils
     - 150+ critères
     - Certification RGPD
     - Bouton "Analyser avec Profilia" (min 1 persona sélectionné)

   - **État Loading** : Progression en 4 étapes
     1. Connexion Profilia
     2. Extraction critères
     3. Matching segments
     4. Calcul reach

   - **État Résultats** : Affichage données
     - **Segments matchés** avec scores :
       - Cadres Finance 25-35 : 94% → 1.2M contacts
       - Dirigeants PME/ETI : 82% → 450K contacts
       - Décideurs RH : 78% → 280K contacts
     - **Reach potentiel** :
       - Contacts : 1.93M
       - Emails : 1.45M
       - Téléphones : 890K

#### Modal "Source des Contacts"
Apparaît après validation personas :
- **Upload Fichier Contact** (actif) → Step 4
- **Avanci** (bientôt disponible)
- **Upload + Avanci** (bientôt disponible)
- **Option** : "Passer directement à la Stratégie" → Step 5

#### Actions Utilisateur
- **Speed Dial** :
  - "Régénérer les personas" → retour step3_loading
  - "Valider et continuer" → ouverture modal choix source

---

### STEP 4 : UPLOAD CONTACTS 📤

**Rôle :** Alimenter la campagne en contacts cibles
**Objectif :** Importer et valider base contacts
**Note :** Requis uniquement pour canaux SMS, Email, Courrier postal

#### Fichiers (Workflow Complet)
1. `contact_upload_empty_light.html` - Zone drop fichier
2. `contact_upload_analyzing_light.html` - Analyse structure
3. `contact_upload_mapping_light.html` - Mapping colonnes
4. `contact_upload_suggestions_light.html` - Suggestions IA
5. `contact_upload_validating_light.html` - Validation données
6. `contact_upload_preview_light.html` - Preview finale
7. `contact_upload_errors_light.html` - Gestion erreurs

#### Processus Détaillé

##### 1. Empty State
- Zone drag & drop
- Formats acceptés : CSV, Excel (XLSX, XLS)
- Bouton "Parcourir fichiers"

##### 2. Analyzing
- Lecture structure fichier
- Détection encodage
- Comptage lignes/colonnes
- Identification séparateurs

##### 3. Mapping
- **Mapping automatique** des colonnes détectées :
  - Nom / Prénom
  - Email (validation format)
  - Téléphone (validation format international)
  - Entreprise
  - Poste
  - Adresse postale complète
  - Champs personnalisés
- **Mapping manuel** si détection échoue
- Preview données (5 premières lignes)

##### 4. Suggestions IA
- **Corrections automatiques** :
  - Emails malformés
  - Numéros téléphone non standardisés
  - Casse noms/prénoms
  - Accents et caractères spéciaux
- **Enrichissements proposés** :
  - Complétion adresses via API postale
  - Détection civilité (M./Mme)
  - Normalisation entreprises
- Choix : "Accepter tout" / "Accepter sélectivement" / "Ignorer"

##### 5. Validating
- **Dédoublonnage** :
  - Détection doublons email
  - Détection doublons téléphone
  - Fusion intelligente des données
- **Validation RGPD** :
  - Vérification consentement (si colonne présente)
  - Alerte si champs sensibles détectés
  - Check liste Robinson (opt-out)
- **Validation qualité** :
  - Emails bounced connus
  - Numéros invalides
  - Adresses incomplètes

##### 6. Preview
- **Récapitulatif import** :
  - X contacts valides
  - Y contacts rejetés (+ raisons)
  - Z contacts enrichis
- **Aperçu tableau** (10 premières lignes)
- **Statistiques** :
  - % emails valides
  - % téléphones mobiles vs fixes
  - Répartition géographique
  - Répartition par entreprise

##### 7. Errors (si erreurs bloquantes)
- **Liste erreurs par ligne** :
  - Numéro ligne
  - Champ concerné
  - Type erreur
  - Valeur erronée
  - Suggestion correction
- **Actions** :
  - "Télécharger rapport erreurs" (CSV)
  - "Retour mapping" → step3
  - "Ignorer lignes en erreur et continuer"

#### Actions Utilisateur
- **Après preview** : "Confirmer import" → passage STEP 5
- **Erreurs** : "Corriger et réimporter" ou "Continuer sans ces lignes"

---

### STEP 5 : STRATÉGIE 🎯

**Rôle :** Définir la stratégie marketing globale
**Objectif :** Configurer approche, messages clés et positionnement

#### Fichiers
- `step5_loading_light.html` - Génération stratégie IA
- `step5_config_light.html` - Configuration stratégie
- `step5_validate_light.html` - Validation stratégie
- `step5_recap_light.html` - Récapitulatif avant assets

#### Processus
1. **Génération IA** - Stratégie basée sur :
   - Projet + Objectifs (Step 1)
   - Concurrents sélectionnés (Step 2)
   - Personas cibles (Step 3)
   - Base contacts (Step 4, si applicable)

2. **Configuration** - Paramétrage stratégie
   *(Détails à explorer dans fichier réel)*

3. **Validation** - Review propositions IA
   *(Détails à explorer dans fichier réel)*

4. **Récapitulatif** - Vue synthétique avant génération :
   - Résumé projet
   - Personas sélectionnés
   - Concurrents retenus
   - Stratégie validée
   - Prochaine étape : "Passer à la génération" → STEP 6

#### Actions Utilisateur
- **Speed Dial** :
  - "Régénérer stratégie" → retour step5_loading
  - "Valider et continuer" → passage STEP 6

---

### STEP 6 : CANAUX 📡

**Rôle :** Choisir les canaux de diffusion
**Objectif :** Sélectionner mix canaux optimal + répartition budget

#### Fichiers
- `step6_select_light.html` - Sélection canaux

#### Canaux Disponibles
- **LinkedIn Ads** (Sponsorisé BtoB)
- **Google Ads** (Search + Display)
- **Facebook Ads** (Social Ads)
- **Instagram Ads** (Visual Social)
- **Email Marketing** (Campagnes email)
- **SMS** (Messages courts)
- **Courrier postal** (Direct mail)
- **Display / Programmatique** (Bannières web)

#### Fonctionnalités
- **Sélection multi-canaux** : checkbox par canal
- **Répartition budget** : slider % par canal
  - Total doit = 100%
  - Recommandations IA affichées
- **Prévisions** par canal :
  - Reach estimé
  - CPC / CPM moyen
  - Taux conversion estimé
  - ROI prévisionnel

#### Actions Utilisateur
- **Speed Dial** :
  - "Modifier stratégie" → retour STEP 5
  - "Valider canaux" → passage STEP 7

---

### STEP 7 : ASSETS 🎨

**Rôle :** Générer les contenus marketing
**Objectif :** Créer assets adaptés à chaque canal sélectionné

#### Fichiers
- `step7_loading_light.html` - Génération IA assets
- `step7_config_light.html` - Configuration assets
- `step7_validate_light.html` - Validation assets

#### Processus
1. **Génération IA** - Assets créés pour chaque canal :
   - **LinkedIn Ads** :
     - Textes publicitaires (headline + description)
     - Visuels carrousel
     - CTA personnalisés

   - **Google Ads** :
     - Annonces textuelles (3 headlines, 2 descriptions)
     - Extensions d'annonce
     - Keywords négatifs

   - **Facebook/Instagram Ads** :
     - Créatifs visuels (formats multiples)
     - Copies publicitaires
     - Stories ads

   - **Email Marketing** :
     - Templates HTML responsive
     - Objets email (5 variantes A/B test)
     - Préheaders
     - Corps email
     - CTA buttons

   - **SMS** :
     - Messages courts (<160 caractères)
     - Variantes avec/sans émoji
     - Liens trackés raccourcis

   - **Courrier postal** :
     - Template lettre
     - Design enveloppe
     - Carte postale recto/verso

   - **Display** :
     - Bannières multi-formats (300x250, 728x90, 160x600, etc.)
     - Animations HTML5
     - Visuels statiques

2. **Configuration** - Personnalisation par asset :
   - Édition textes
   - Upload visuels custom
   - Remplacement propositions IA
   - Variables dynamiques (merge tags)

3. **Validation** - Review finale :
   - **Preview multi-format** : desktop / mobile / tablette
   - **Preview par canal** : affichage réaliste
   - **Tests A/B** : création variantes
   - **Checklist qualité** :
     - Orthographe
     - Liens fonctionnels
     - CTA clair
     - Branding cohérent
     - Conformité légale (mentions, RGPD)

#### Actions Utilisateur
- **Speed Dial** :
  - "Régénérer assets" → retour step7_loading
  - "Modifier canaux" → retour STEP 6
  - "Valider assets" → passage STEP 8

---

### STEP 8 : PLANNING 📅

**Rôle :** Planifier les diffusions dans le temps
**Objectif :** Scheduler campagne multi-canaux

#### Fichiers
- `step8_schedule_light.html` - Calendrier interactif

#### Technologies
- **FullCalendar** : librairie calendrier JavaScript
- **Drag & Drop** : déplacement événements
- **Vues multiples** : Mois / Semaine / Jour / Timeline

#### Fonctionnalités

##### Calendrier Interactif
- **Visualisation** :
  - Vue mensuelle (défaut)
  - Vue hebdomadaire (détails horaires)
  - Vue journalière (planning précis)
  - Vue timeline (Gantt-like)
- **Événements** :
  - Couleur par canal
  - Icône par type asset
  - Tooltip détails au survol

##### Programmation Diffusions
Pour chaque canal :
- **Dates de lancement** :
  - Date début campagne
  - Date fin campagne
  - Dates jalons intermédiaires

- **Fréquences d'envoi** :
  - Une fois (one-shot)
  - Quotidien
  - Hebdomadaire (choix jours)
  - Mensuel
  - Personnalisé (cron-like)

- **Horaires optimaux** :
  - Suggestions IA par canal :
    - **Email** : 10h-11h et 15h-16h
    - **LinkedIn** : 8h-9h et 17h-18h
    - **SMS** : 11h-13h et 18h-20h
  - Timezone gestion
  - Évitement week-ends/jours fériés

##### Règles Avancées
- **Budget pacing** : répartition dépenses dans le temps
- **Frequency capping** : limite impressions/user
- **Dayparting** : activation/désactivation par plage horaire
- **Conditions météo** (si pertinent)

##### Validation Finale
- **Checklist pré-lancement** :
  - ✓ Tous assets validés
  - ✓ Budgets alloués
  - ✓ Calendrier complet
  - ✓ Tracking configuré
  - ✓ Landing pages prêtes
  - ✓ Équipe notifiée

#### Actions Utilisateur
- **Speed Dial** :
  - "Modifier assets" → retour STEP 7
  - "Enregistrer brouillon" → sauvegarde sans lancer
  - **"Lancer la campagne"** → Activation effective + redirection Dashboard

---

## 🎉 Fin du Workflow

Après lancement :
- Redirection vers **Dashboard Campagnes**
- Campagne visible avec statut "En cours"
- Accès analytics temps réel
- Possibilité éditer/pause/stop campagne

---

## 🔑 Concepts Clés

### Pattern Récurrent par Étape

La majorité des steps suivent ce modèle en 3 phases :

```
┌─────────────┐      ┌──────────────┐      ┌─────────────┐
│   LOADING   │  →   │ SELECT/CONFIG│  →   │  VALIDATE   │
│             │      │              │      │   REVIEW    │
│ IA génère   │      │ User choisit │      │ User valide │
│ propositions│      │ configure    │      │ avant next  │
└─────────────┘      └──────────────┘      └─────────────┘
```

**Exemple Step 1 :**
1. **Loading** → IA analyse site web + génère enrichissements
2. **Review** → User choisit nom, valide branding, keywords
3. **Validate** → Action "Valider et continuer" → Step 2

### Enrichissements IA Progressifs

L'IA construit la campagne **par couches successives** :

| Step | Enrichissement IA | Données Générées |
|------|-------------------|------------------|
| 1 | Analyse projet | Branding, keywords, recommandations |
| 2 | Détection concurrence | Concurrents + scores alignement |
| 3 | Génération personas | 5 personas détaillés avec motivations |
| 5 | Stratégie marketing | Approche globale, positionnement |
| 7 | Création assets | Contenus multi-canaux personnalisés |

### Validation Utilisateur Systématique

**Principe :** L'IA propose, l'humain dispose.

- Chaque proposition IA **nécessite validation explicite**
- Possibilité **régénérer** à chaque étape
- Possibilité **modifier** ou **ajouter manuellement**
- **Aucune action automatique** sans consentement user

### Optionnalités Intelligentes

Le workflow s'adapte au contexte :

| Élément | Condition | Impact |
|---------|-----------|--------|
| **Profilia** (Step 3) | Optionnel | Enrichit reach, pas bloquant |
| **Upload Contacts** (Step 4) | Requis si canaux Email/SMS/Courrier | Peut skip si seulement ads digitaux |
| **Nombre canaux** (Step 6) | Libre (1 à N) | Moins de canaux = moins d'assets |

### Progressive Disclosure

Le workflow révèle la complexité **graduellement** :

```
┌──────────────────────────────────────────────────────┐
│  FONDATIONS (Steps 1-3)                              │
│  Questions : Qui sommes-nous ? Qui ciblons-nous ?    │
│  Complexité : ★☆☆☆☆                                 │
└──────────────────────────────────────────────────────┘
                        ↓
┌──────────────────────────────────────────────────────┐
│  PRÉPARATION (Steps 4-5)                             │
│  Questions : Quelles données ? Quelle stratégie ?    │
│  Complexité : ★★★☆☆                                 │
└──────────────────────────────────────────────────────┘
                        ↓
┌──────────────────────────────────────────────────────┐
│  EXÉCUTION (Steps 6-8)                               │
│  Questions : Quels canaux ? Quels contenus ? Quand ? │
│  Complexité : ★★★★★                                 │
└──────────────────────────────────────────────────────┘
```

---

## 🎨 Composants Visuels Récurrents

### Campaign Stepper

**Localisation :** Haut de page, sous breadcrumb
**Présence :** Toutes les pages du workflow
**Fichier CSS :** `assets/css/components/_campaign-stepper.css`

**Structure :**
```html
<div class="campaign-stepper">
  <div class="stepper-container">
    <div class="stepper-line">
      <div class="stepper-progress"></div>
    </div>

    <!-- 8 steps -->
    <div class="stepper-step [active|completed]">
      <div class="stepper-circle">[1-8 ou ✓]</div>
      <div class="stepper-label">[Nom step]</div>
    </div>
  </div>
</div>
```

**États :**
- **Pending** : cercle gris, numéro gris clair
- **Active** : cercle bleu, numéro blanc, label en gras
- **Completed** : cercle vert, icône ✓, label normal

**Barre progression :**
- Animation fluide entre steps
- Largeur calculée : `(step_actuel - 1) / 7 * 100%`

### Speed Dial FAB

**Localisation :** Bas droite de l'écran, position fixed
**Présence :** Toutes les pages sauf loading
**Fichier CSS :** `assets/css/components/_speed-dial.css` (probablement)

**Structure :**
```html
<div class="speed-dial-container">
  <!-- Action secondaire (ex: régénérer) -->
  <div class="speed-dial-action">
    <span class="speed-dial-label">Régénérer</span>
    <button class="speed-dial-btn speed-dial-cancel">
      <i class="bi bi-arrow-clockwise"></i>
    </button>
  </div>

  <!-- Action principale (ex: valider) -->
  <div class="speed-dial-action">
    <span class="speed-dial-label">Valider et continuer</span>
    <button class="speed-dial-btn speed-dial-primary">
      <i class="bi bi-check-lg"></i>
    </button>
  </div>

  <!-- Bouton principal (trigger) -->
  <div class="speed-dial-btn speed-dial-main">
    <i class="bi bi-three-dots-vertical"></i>
  </div>
</div>
```

**Comportement :**
- Clic sur `speed-dial-main` → déploie actions
- Actions apparaissent vers le haut
- Labels apparaissent à gauche au hover
- Fermeture : clic extérieur ou re-clic main

### FAB Retour

**Localisation :** Bas gauche de l'écran, position fixed
**Présence :** Toutes les pages sauf step1_create (première étape)

**Structure :**
```html
<a href="[previous_step].html" class="fab-back">
  <i class="bi bi-arrow-left"></i>
  <span class="fab-tooltip">Retour à [étape précédente]</span>
</a>
```

**Comportement :**
- Tooltip apparaît au hover
- Navigation vers étape N-1
- Conserve données formulaire (localStorage ou session)

### Cards de Sélection

**Localisation :** Steps 2, 3, 7 (sélection concurrents, personas, assets)
**Fichier CSS :** `assets/css/components/_asset-card.css`

**Structure :**
```html
<div class="asset-card [selected]">
  <div class="asset-card-check">
    <i class="bi bi-check-lg"></i>
  </div>

  <!-- Contenu spécifique (concurrent, persona, asset) -->
  <div class="row">
    <div class="col-md-8">
      <h5>[Titre]</h5>
      <p>[Description]</p>
      <div class="d-flex gap-2">
        <span class="badge">[Tag]</span>
      </div>
    </div>
    <div class="col-md-4">
      <div class="[type]-score">[Score]</div>
    </div>
  </div>
</div>
```

**États :**
- **Non sélectionné** : border gris, fond blanc
- **Sélectionné** : border bleu, fond bleu léger, checkbox visible
- **Hover** : border plus foncé, cursor pointer

**Interaction :**
- Clic n'importe où sur card → toggle sélection
- Compteur "X/Y sélectionnés" mis à jour en temps réel

### Alert Succès

**Localisation :** Haut du content, sous stepper
**Présence :** Pages "review" / "validate" après génération IA

**Structure :**
```html
<div class="alert alert-success alert-dismissible fade show [custom-class]">
  <i class="bi bi-check-circle-fill [custom-icon-class]"></i>
  <div>
    <strong>[Titre succès]</strong><br>
    [Message détaillé]
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
```

**Variantes :**
- `ai-enrichment-alert` (Step 1)
- `competitors-detected-alert` (Step 2)
- `personas-generated-alert` (Step 3)

### Form Section

**Localisation :** Toutes les pages avec formulaires
**Fichier CSS :** `assets/css/components/_campaign-forms.css`

**Structure :**
```html
<div class="form-section [form-section-delay-1]">
  <div class="form-section-header">
    <div class="form-section-icon">
      <i class="bi bi-[icon]"></i>
    </div>
    <h2 class="form-section-title">[Titre section]</h2>
    <p class="form-section-subtitle">[Sous-titre optionnel]</p>
  </div>

  <!-- Contenu formulaire -->
  <div class="row g-4">
    <div class="col-md-6">
      <label class="form-label">
        [Label] <span class="required">*</span>
      </label>
      <input type="text" class="form-control" placeholder="...">
    </div>
  </div>
</div>
```

**Animation :**
- Classes `form-section-delay-1`, `form-section-delay-2`, `form-section-delay-3`
- Apparition séquentielle avec fade-in + slide-up
- Délai 0.1s entre chaque section

---

## 💡 Insights Business & UX

### 1. Réduction de la Friction Cognitive

**Problème :** Créer une campagne marketing complète = tâche écrasante
**Solution :** Découpage en 8 micro-étapes avec objectif clair par step

**Bénéfices :**
- User jamais perdu (stepper toujours visible)
- Progression tangible (barre avancement)
- Sentiment d'accomplissement à chaque étape

### 2. Assistance IA Contextuelle

**Principe :** L'IA est un **copilote**, pas un autopilote

**Implémentation :**
- IA génère des **propositions**, jamais de décisions finales
- User **valide explicitement** à chaque étape
- Possibilité **régénérer** si insatisfait
- Possibilité **modifier manuellement** toute proposition

**Valeur :**
- Gain temps (80% du travail automatisé)
- Contrôle total user (pas de "boîte noire")
- Learning progressif (user comprend les choix IA)

### 3. Data-Driven Decisions

**Données quantitatives omniprésentes :**
- **Step 1** : Volume keywords, CPC, concurrence
- **Step 2** : Scores alignement, overlap offre/marché
- **Step 3** : Scores personas, reach Profilia
- **Step 6** : Reach estimé, CPC/CPM, ROI prévisionnel

**Impact :**
- Décisions **justifiées** (pas au feeling)
- Comparaisons **objectives** entre options
- KPIs **trackables** dès conception

### 4. Compliance by Design

**RGPD intégré nativement :**
- **Step 4 Upload** : validation consentement, check liste Robinson
- **Alertes automatiques** si champs sensibles détectés
- **Mentions légales** ajoutées automatiquement aux assets

**Avantages :**
- Risques juridiques minimisés
- Confiance utilisateur renforcée
- Audit trail complet (traçabilité actions)

### 5. Flexibilité Adaptative

**Le workflow s'adapte au contexte :**

| Scénario | Adaptation Workflow |
|----------|---------------------|
| Campagne LinkedIn uniquement | Skip Step 4 (pas d'upload contacts) |
| Pas de concurrents détectés | Ajout manuel obligatoire Step 2 |
| Budget limité | Restriction nombre canaux Step 6 |
| Assets déjà existants | Upload custom Step 7, pas de génération |

### 6. Prévention Erreurs

**Multiples garde-fous :**
- **Validation formulaires** : champs requis, formats email/tél
- **Alertes intelligentes** : "Attention, budget insuffisant pour ces canaux"
- **Preview systématique** : voir résultat avant valider
- **Checklist pré-lancement** : impossible lancer si incomplet

### 7. Progressive Disclosure Maitrisée

**Complexité révélée graduellement :**

**Steps 1-3 (Fondations) :**
- Formulaires simples
- Validations assistées IA
- Concepts familiers (projet, concurrents, personas)

**Steps 4-5 (Préparation) :**
- Upload contacts (optionnel)
- Stratégie (abstraite mais guidée)

**Steps 6-8 (Exécution) :**
- Canaux multiples (choix complexes)
- Assets par canal (multiplication combinatoire)
- Planning multi-dimensionnel (temps × canaux × assets)

**Bénéfice :** User monte en compétence progressivement, jamais submergé

---

## 🔧 Aspects Techniques

### Stack Technologies Identifiées

- **Frontend Framework :** Vanilla HTML/CSS/JS (pas de framework détecté)
- **UI Library :** Bootstrap 5.3.8
- **Icons :** Bootstrap Icons 1.11.3
- **Fonts :** Google Fonts (Poppins)
- **Calendar :** FullCalendar 6.1.11 (Step 8)
- **Design System :** Custom myCFiA (`assets/css/main.css`)

### Architecture CSS Modulaire

```
assets/css/
├── main.css                      # Core design system
├── themes/
│   └── light.css                 # Thème clair
└── components/
    ├── _campaign-stepper.css     # Stepper workflow
    ├── _campaign-forms.css       # Formulaires
    ├── _campaign-schedule.css    # Planning Step 8
    ├── _campaign-loader.css      # Animations loading
    ├── _step1-create.css         # Styles spécifiques Step 1
    ├── _step1-review.css
    ├── _step2-select.css
    ├── _step3-validate.css
    ├── _step4-recap.css
    ├── _step5-config.css
    ├── _step5-validate.css
    └── ...
```

**Pattern :** Un fichier CSS par composant/step pour maintenabilité

### Architecture JavaScript

```
assets/js/
├── main.js                       # Entry point (module)
└── components/
    ├── campaign-stepper.js       # Logique stepper
    ├── step1-asset-selector.js   # Sélection assets Step 1
    ├── step2-competitor-selector.js
    ├── step5-asset-selector.js
    ├── step5-loading-config.js
    ├── step5-validate-data.js
    ├── step6-channel-selector.js
    └── ...
```

**Pattern :** Composants découplés, chargement module ES6

### Gestion État (Hypothèses)

**Stockage données entre steps :**
- **LocalStorage** : sauvegarde brouillon campagne
- **SessionStorage** : données temporaires session
- **Backend API** : sauvegarde définitive après chaque step validé

**Structure données (probable) :**
```json
{
  "campaign_id": "uuid",
  "step_current": 3,
  "step1_data": {
    "project_name": "...",
    "company": "...",
    "selected_campaign_name": "...",
    "branding": { ... },
    "keywords": [ ... ]
  },
  "step2_data": {
    "selected_competitors": [ ... ]
  },
  "step3_data": {
    "selected_personas": [ ... ],
    "profilia_results": { ... }
  },
  ...
}
```

### Points d'Intégration Backend (API)

**Endpoints probables :**

| Step | Endpoint | Méthode | Données |
|------|----------|---------|---------|
| 1 | `/api/campaigns/analyze-website` | POST | URL site → branding, keywords |
| 1 | `/api/campaigns/create` | POST | Données projet → campaign_id |
| 2 | `/api/campaigns/{id}/detect-competitors` | POST | Projet → concurrents |
| 3 | `/api/campaigns/{id}/generate-personas` | POST | Projet + concurrents → personas |
| 3 | `/api/profilia/analyze` | POST | Personas → segments + reach |
| 4 | `/api/contacts/upload` | POST (multipart) | Fichier CSV/Excel → validation |
| 4 | `/api/contacts/validate` | POST | Contacts → validation RGPD |
| 5 | `/api/campaigns/{id}/generate-strategy` | POST | Toutes données → stratégie |
| 7 | `/api/campaigns/{id}/generate-assets` | POST | Stratégie + canaux → assets |
| 8 | `/api/campaigns/{id}/schedule` | POST | Planning → activation campagne |

### Optimisations Performances

**Techniques identifiées :**
- **Lazy loading images** : assets chargés progressivement
- **CSS animations** : transitions fluides sans JS
- **Debouncing** : validation formulaires (pas de validation à chaque keypress)
- **Pagination** : listes concurrents/personas/assets si > 20 items
- **Compression** : fichiers CSS/JS minifiés en production

---

## 📊 Métriques & KPIs

### Métriques UX à Tracker

| Métrique | Description | Objectif |
|----------|-------------|----------|
| **Completion Rate** | % users atteignant Step 8 | >75% |
| **Drop-off par step** | % abandon à chaque étape | Identifier frictions |
| **Time per step** | Temps moyen par étape | Optimiser steps lents |
| **Regeneration Rate** | % users régénérant propositions IA | <20% (qualité IA) |
| **Manual Edit Rate** | % users modifiant propositions IA | 30-50% (normal) |
| **Profilia Usage** | % users utilisant enrichissement | >40% |
| **Contact Upload Success** | % uploads réussis 1er coup | >80% |

### Métriques Business

| Métrique | Description | Impact |
|----------|-------------|--------|
| **Campagnes créées/mois** | Volume utilisation | Adoption produit |
| **Temps création campagne** | De Step 1 à Step 8 | Efficacité (objectif <30min) |
| **ROI campagnes générées** | Performance réelle vs prévisions | Qualité IA |
| **Canaux préférés** | Distribution choix Step 6 | Product roadmap |
| **Taux réussite lancement** | % campagnes lancées vs brouillons | Taux conversion |

---

## 🚀 Améliorations Possibles

### Court Terme (Quick Wins)

1. **Save & Resume**
   - Bouton "Enregistrer brouillon" sur chaque step
   - Reprendre plus tard sans perdre données
   - Email reminder si brouillon non finalisé sous 7j

2. **Keyboard Shortcuts**
   - `Ctrl+Enter` : Valider et continuer
   - `Ctrl+←` : Retour step précédent
   - `Ctrl+S` : Sauvegarder brouillon

3. **Preview Mode**
   - Bouton "Aperçu final" accessible dès Step 5
   - Vue synthétique campagne complète
   - Navigation rapide vers steps pour éditer

4. **Smart Defaults**
   - Pré-remplir budget moyen selon secteur
   - Pré-sélectionner canaux selon objectifs
   - Pré-configurer horaires selon timezone

### Moyen Terme (Product Roadmap)

1. **Templates Campagne**
   - Bibliothèque templates par industrie
   - "Lancement produit SaaS", "Black Friday eCommerce", etc.
   - Démarrer à partir template (skip Steps 1-2)

2. **Collaboration**
   - Inviter collègues commenter campagne
   - Workflow approbation (manager valide avant Step 8)
   - Historique modifications (qui a changé quoi)

3. **A/B Testing Intégré**
   - Créer variantes campagne (ex: 2 stratégies différentes)
   - Split traffic automatiquement
   - Dashboards comparaison performances

4. **Profilia++ Enrichment**
   - Enrichissement automatique base contacts (Step 4)
   - Scoring qualité leads
   - Suppression automatique contacts invalides/dangereux

5. **Assets Multilingues**
   - Génération assets dans plusieurs langues (Step 7)
   - Traduction automatique via IA
   - Adaptation culturelle (pas juste traduction littérale)

### Long Terme (Vision)

1. **Auto-Optimization**
   - IA ajuste campagne en temps réel selon performances
   - Réallocation budget vers canaux performants
   - Pause automatique assets sous-performants

2. **Predictive Analytics**
   - Prévision ROI avec intervalle confiance
   - Alerte si objectifs inatteignables
   - Suggestions optimisation budget

3. **Cross-Campaign Insights**
   - Analyse toutes campagnes user
   - Recommandations basées sur historique
   - "Votre meilleur canal est LinkedIn, allouez +20% budget"

4. **Ecosystem Integrations**
   - Sync bi-directionnel CRM (Salesforce, HubSpot)
   - Push assets directs vers plateformes (LinkedIn Ads API, Google Ads API)
   - Import automatique résultats pour analytics

---

## 📚 Ressources & Références

### Fichiers Principaux

**Workflow Steps :**
- `campaign_generation/step[1-8]_*_light.html`

**Components CSS :**
- `assets/css/components/_campaign-*.css`

**Components JS :**
- `assets/js/components/step*.js`
- `assets/js/components/campaign-*.js`

**Upload Workflow :**
- `campaign_generation/contact_upload_*_light.html`

### Documentation Externe

- **Bootstrap 5.3** : https://getbootstrap.com/docs/5.3/
- **Bootstrap Icons** : https://icons.getbootstrap.com/
- **FullCalendar** : https://fullcalendar.io/docs
- **Google Fonts (Poppins)** : https://fonts.google.com/specimen/Poppins

### Design System

- **Fichier principal** : `assets/css/main.css`
- **Thème** : `assets/css/themes/light.css`
- **Index** : `index.html` (documentation composants)

---

## 🏁 Conclusion

Le workflow de génération de campagne myCFiA représente un **équilibre sophistiqué** entre :

✅ **Automatisation IA** (gain temps 80%)
✅ **Contrôle utilisateur** (validation explicite chaque step)
✅ **Complexité maîtrisée** (progressive disclosure)
✅ **Flexibilité** (adaptatif selon contexte)
✅ **Data-driven** (décisions basées métriques)
✅ **Compliance** (RGPD by design)

**Forces principales :**
1. Découpage intelligent en micro-étapes
2. IA copilote (pas autopilote)
3. Visualisation progression (stepper + barre)
4. Validation systématique avant next step
5. Possibilité régénération/modification

**Points vigilance :**
- Longueur workflow (8 steps) peut décourager
- Dépendance qualité IA (si mauvaise, frustration)
- Complexité Step 7 (assets multi-canaux)
- Risque abandon si steps longs (monitorer métriques)

**Recommandations :**
- Implémenter Save & Resume rapidement
- A/B tester ordre certains steps (ex: Upload avant ou après Personas?)
- Créer templates pour réduire friction initiale
- Investir qualité IA (c'est le cœur de la valeur)

---

**Dernière mise à jour :** 2026-01-19
**Prochaine review :** Après analyse métriques production
