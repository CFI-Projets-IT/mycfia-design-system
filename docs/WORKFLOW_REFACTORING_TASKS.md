# Workflow Refactoring - Tâches à exécuter

> Refactoring du workflow Campaign Generation suite au logigramme client
> Version : light uniquement
> Date : 2026-01-14

---

## ÉTAT ACTUEL (2026-01-14 15:30)

**Phase 1 : TERMINÉE** - Tous les fichiers renommés
**Phase 2 : TERMINÉE** - Modifications de contenu effectuées
**Phase 3 : TERMINÉE** - Mise à jour des steppers (21 fichiers)
**Phase 4 : TERMINÉE** - Mise à jour de la navigation
**Phase 5 : TERMINÉE** - Mise à jour CSS

### Fichiers stepper mis à jour (21/21) :
- [x] step1_create_light.html
- [x] step1_loading_light.html
- [x] step1_review_light.html
- [x] step2_loading_light.html
- [x] step2_validate_light.html
- [x] step3_loading_light.html
- [x] step3_select_light.html
- [x] step6_select_light.html (créé avec bon stepper)
- [x] contact_upload_empty_light.html
- [x] contact_upload_validating_light.html
- [x] contact_upload_errors_light.html
- [x] contact_upload_analyzing_light.html
- [x] contact_upload_suggestions_light.html
- [x] contact_upload_mapping_light.html
- [x] contact_upload_preview_light.html
- [x] step5_loading_light.html
- [x] step5_recap_light.html
- [x] step5_result_light.html
- [x] step7_loading_light.html
- [x] step7_config_light.html
- [x] step7_validate_light.html

### Nouveau stepper à 7 étapes (template) :
```html
1. Projet
2. Concurrents
3. Personas
4. Upload
5. Stratégie
6. Canaux
7. Assets
```

---

---

## Contexte

Passage de 6 à 7 étapes avec réorganisation de l'ordre des étapes selon le flux client.

### Ancien ordre (6 étapes)
1. Création → 2. Upload → 3. Personas → 4. Concurrents → 5. Stratégie → 6. Assets

### Nouvel ordre (7 étapes)
1. Création → 2. Concurrents → 3. Personas → 4. Upload → 5. Stratégie → 6. Sélection Assets → 7. Génération Assets

---

## Phase 1 : Renommage des fichiers

> Ordre important pour éviter les conflits de noms

### 1.1 - Renommer step5_* → step7_* (Génération Assets)

- [x] `step5_loading_light.html` → `step7_loading_light.html`
- [x] `step5_config_light.html` → `step7_config_light.html`
- [x] `step5_validate_light.html` → `step7_validate_light.html`

### 1.2 - Renommer step4_* → step5_* (Stratégie)

- [x] `step4_loading_light.html` → `step5_loading_light.html`
- [x] `step4_recap_light.html` → `step5_recap_light.html`
- [x] `step4_result_light.html` → `step5_result_light.html`

### 1.3 - Renommer step3_* → TEMP_* (Concurrents - temporaire)

- [x] `step3_loading_light.html` → `TEMP_concurrents_loading_light.html`
- [x] `step3_validate_light.html` → `TEMP_concurrents_validate_light.html`

### 1.4 - Renommer step2_* → step3_* (Personas)

- [x] `step2_loading_light.html` → `step3_loading_light.html`
- [x] `step2_select_light.html` → `step3_select_light.html`

### 1.5 - Renommer TEMP_* → step2_* (Concurrents - final)

- [x] `TEMP_concurrents_loading_light.html` → `step2_loading_light.html`
- [x] `TEMP_concurrents_validate_light.html` → `step2_validate_light.html`

---

## Phase 2 : Modifications de contenu

### 2.1 - Modifier step1_create_light.html

- [x] Retirer la section "Sélection des canaux de diffusion"
- [x] Ajuster le layout si nécessaire
- [x] Vérifier que le formulaire reste cohérent

### 2.2 - Modifier step2_validate_light.html (Concurrents)

- [x] Ajouter formulaire d'ajout de concurrent :
  - Champ : Nom du concurrent (text, required)
  - Champ : URL du concurrent (url, required)
  - Bouton : Ajouter
- [x] Intégrer le formulaire dans le design existant

### 2.3 - Créer step6_select_light.html (Sélection Assets)

- [x] Créer la nouvelle page
- [x] Section 1 : "Assets recommandés par l'IA" (mise en avant)
  - Cards assets avec badge "Recommandé"
  - Checkboxes pour sélection
- [x] Section 2 : "Autres canaux disponibles"
  - Cards assets standard
  - Checkboxes pour sélection
- [x] Intégrer le stepper (étape 6 active)
- [x] Boutons navigation (Précédent / Continuer)

---

## Phase 3 : Mise à jour du Stepper

### 3.1 - Mettre à jour le composant stepper

- [ ] Passer de 6 à 7 étapes
- [ ] Nouveaux labels :
  1. Projet
  2. Concurrents
  3. Personas
  4. Upload
  5. Stratégie
  6. Canaux
  7. Assets

### 3.2 - Mettre à jour le stepper dans chaque fichier

- [ ] `step1_create_light.html` - Étape 1 active
- [ ] `step1_loading_light.html` - Étape 1 active
- [ ] `step1_review_light.html` - Étape 1 active
- [ ] `step2_loading_light.html` - Étape 2 active
- [ ] `step2_validate_light.html` - Étape 2 active
- [ ] `step3_loading_light.html` - Étape 3 active
- [ ] `step3_select_light.html` - Étape 3 active
- [ ] `contact_upload_empty_light.html` - Étape 4 active
- [ ] `contact_upload_validating_light.html` - Étape 4 active
- [ ] `contact_upload_errors_light.html` - Étape 4 active
- [ ] `contact_upload_analyzing_light.html` - Étape 4 active
- [ ] `contact_upload_suggestions_light.html` - Étape 4 active
- [ ] `contact_upload_mapping_light.html` - Étape 4 active
- [ ] `contact_upload_preview_light.html` - Étape 4 active
- [ ] `step5_loading_light.html` - Étape 5 active
- [ ] `step5_recap_light.html` - Étape 5 active
- [ ] `step5_result_light.html` - Étape 5 active
- [ ] `step6_select_light.html` - Étape 6 active
- [ ] `step7_loading_light.html` - Étape 7 active
- [ ] `step7_config_light.html` - Étape 7 active
- [ ] `step7_validate_light.html` - Étape 7 active

---

## Phase 4 : Mise à jour de la navigation

### 4.1 - Mettre à jour les liens de navigation

| Fichier | Lien "Précédent" | Lien "Suivant" |
|---------|------------------|----------------|
| `step1_create_light.html` | Dashboard | `step1_loading_light.html` |
| `step1_review_light.html` | — | `step2_loading_light.html` |
| `step2_loading_light.html` | — | `step2_validate_light.html` |
| `step2_validate_light.html` | `step1_review_light.html` | `step3_loading_light.html` |
| `step3_loading_light.html` | — | `step3_select_light.html` |
| `step3_select_light.html` | `step2_validate_light.html` | `contact_upload_empty_light.html` |
| `contact_upload_*` | `step3_select_light.html` | `step5_loading_light.html` |
| `step5_loading_light.html` | — | `step5_recap_light.html` |
| `step5_recap_light.html` | — | `step5_result_light.html` |
| `step5_result_light.html` | — | `step6_select_light.html` |
| `step6_select_light.html` | `step5_result_light.html` | `step7_loading_light.html` |
| `step7_loading_light.html` | — | `step7_config_light.html` |
| `step7_config_light.html` | `step6_select_light.html` | `step7_validate_light.html` |
| `step7_validate_light.html` | `step7_config_light.html` | `campaign_show_light.html` |

### 4.2 - Mettre à jour les redirections JS

- [ ] `assets/js/components/campaign-stepper.js` - URLs de navigation
- [ ] `assets/js/components/step2-persona-selector.js` → renommer `step3-persona-selector.js`
- [ ] `assets/js/components/step3-competitor-selector.js` → renommer `step2-competitor-selector.js`
- [ ] Autres fichiers JS concernés

---

## Phase 5 : Mise à jour CSS (si nécessaire)

- [ ] Vérifier `_campaign-stepper.css` pour 7 étapes
- [ ] Créer styles pour `step6_select_light.html` si nécessaire
- [ ] Vérifier cohérence visuelle globale

---

## Phase 6 : Tests et validation

- [ ] Tester le flux complet de bout en bout
- [ ] Vérifier tous les liens de navigation
- [ ] Vérifier le stepper sur chaque page
- [ ] Vérifier le formulaire ajout concurrent
- [ ] Vérifier la page sélection assets
- [ ] Vérifier la cohérence visuelle

---

## Fichiers impactés (récapitulatif)

### Fichiers à renommer (10 fichiers)

```
campaign_generation/
├── step5_loading_light.html    → step7_loading_light.html
├── step5_config_light.html     → step7_config_light.html
├── step5_validate_light.html   → step7_validate_light.html
├── step4_loading_light.html    → step5_loading_light.html
├── step4_recap_light.html      → step5_recap_light.html
├── step4_result_light.html     → step5_result_light.html
├── step3_loading_light.html    → step2_loading_light.html
├── step3_validate_light.html   → step2_validate_light.html
├── step2_loading_light.html    → step3_loading_light.html
└── step2_select_light.html     → step3_select_light.html
```

### Fichiers à modifier (3 fichiers)

```
campaign_generation/
├── step1_create_light.html     (retirer sélection canaux)
├── step2_validate_light.html   (ajouter formulaire concurrent)
└── step6_select_light.html     (CRÉATION - sélection assets)
```

### Fichiers à mettre à jour (22 fichiers)

Tous les fichiers du workflow pour :
- Stepper 7 étapes
- Liens de navigation

---

## Notes

- Version light uniquement pour cette phase
- Les versions dark-blue et dark-red seront traitées ultérieurement
- La planification existe déjà (`campaign_show_light.html` → `campaign_schedule_light.html`)
- L'intégration AVANCI est reportée (dépend échange client)

---

*Document créé le 2026-01-14*
