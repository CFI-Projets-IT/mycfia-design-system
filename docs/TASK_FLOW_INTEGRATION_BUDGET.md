# TASK — Intégration Step 9 Budget dans le flow complet

## Contexte

Une nouvelle étape **Budget (Step 9)** a été ajoutée entre Assets et Planification.
Le planning passe de Step 9 à **Step 10**.
Le fichier `step9_schedule_light.html` doit être renommé en `step10_schedule_light.html`.

**Périmètre : fichiers `_light.html` uniquement.**

---

## Nouveau flow de navigation

```
Step 1  → Projet         (step1_create / step1_loading / step1_review)
Step 2  → Concurrents    (step2_loading / step2_validate)
Step 3  → Personas       (step3_loading / step3_select)
Step 4  → Stratégie      (step4_loading / step4_recap / step4_result)
Step 5  → Canaux         (step5_select)
Step 6  → Upload         (step6_upload_*)
Step 7  → Assets         (step8_loading / step8_config / step8_validate)
Step 8  → Budget         (step9_budget_light.html)        ← NOUVEAU
Step 9  → Planning       (step10_schedule_light.html)     ← RENOMMÉ
```

---

## Tâches à réaliser

### 1. Renommer le fichier Planning

- `step9_schedule_light.html` → `step10_schedule_light.html`

### 2. Mettre à jour le CSS (`_campaign-stepper.css`)

Recalculer toutes les valeurs `width` sur la base de **10 étapes** (9 intervalles).
Formule : `(N-1) / 9 * 100%`

| Classe CSS | Ancienne valeur | Nouvelle valeur |
|---|---|---|
| `step1-create-stepper-progress` | 0% | **0%** |
| `step1-loading-stepper-progress` | 0% | **0%** |
| `step1-review-stepper-progress` | 0% | **0%** |
| `step2-loading-stepper-progress` | 7.14% | **5.56%** |
| `step2-validate-stepper-progress` | 14.29% | **11.11%** |
| `step3-loading-stepper-progress` | 21.43% | **16.67%** |
| `step3-select-stepper-progress` | 28.57% | **22.22%** |
| `step4-loading-stepper-progress` | 35.71% | **33.33%** |
| `step4-recap-stepper-progress` | 39.29% | **38.89%** |
| `step4-result-stepper-progress` | 42.86% | **44.44%** |
| `contact-upload-stepper-progress` | 42.86% | **44.44%** |
| `step5-select-stepper-progress` | 71.43% | **55.56%** |
| `step8-loading-stepper-progress` | 75% | **66.67%** |
| `step8-config-stepper-progress` | 78.57% | **72.22%** |
| `step8-validate-stepper-progress` | 85.71% | **77.78%** |
| `step9-budget-stepper-progress` | 87.5% | **88.89%** |
| `step9-schedule-stepper-progress` → renommer en `step10-schedule-stepper-progress` | 100% | **100%** |

Mettre à jour le commentaire de section : `/* STEPPER PROGRESS - 10 ÉTAPES */`

### 3. Mettre à jour le stepper HTML dans tous les fichiers _light.html

**Stepper cible (10 étapes) :**

```html
<div class="stepper-step [completed|active|'']">
    <div class="stepper-circle">[<i class="bi bi-check-lg"></i> | N]</div>
    <div class="stepper-label">Projet</div>
</div>
<!-- Concurrents -->
<!-- Personas -->
<!-- Stratégie -->
<!-- Canaux -->
<!-- Upload -->
<!-- Assets -->
<!-- Budget       ← À AJOUTER dans tous les fichiers sauf step9_budget -->
<!-- Planning     ← Numéro cercle passe de 8 à 10 dans les fichiers concernés -->
```

**Règles d'état par fichier :**

| Fichier | Budget | Planning |
|---|---|---|
| step1_* | vide (cercle 9) | vide (cercle 10) |
| step2_* | vide (cercle 9) | vide (cercle 10) |
| step3_* | vide (cercle 9) | vide (cercle 10) |
| step4_* | vide (cercle 9) | vide (cercle 10) |
| step5_* | vide (cercle 9) | vide (cercle 10) |
| step6_* | vide (cercle 9) | vide (cercle 10) |
| step8_loading | vide (cercle 9) | vide (cercle 10) |
| step8_config | vide (cercle 9) | vide (cercle 10) |
| step8_validate | vide (cercle 9) | vide (cercle 10) |
| step9_budget | **active** | vide (cercle 10) |
| step10_schedule | **completed** | **active** |

### 4. Mettre à jour les liens de navigation

| Fichier | Lien à corriger | Ancienne valeur | Nouvelle valeur |
|---|---|---|---|
| `step8_validate_light.html` | Speed dial "Continuer" | `step9_schedule_light.html` | `step9_budget_light.html` |
| `step9_budget_light.html` | "Continuer vers la planification" | `step9_schedule_light.html` | `step10_schedule_light.html` |
| `step10_schedule_light.html` | Speed dial "Retour" | `step7_validate_light.html` | `step9_budget_light.html` |
| `step10_schedule_light.html` | FAB retour | `step7_validate_light.html` | `step9_budget_light.html` |
| `step10_schedule_light.html` | Classe CSS progress | `step9-schedule-stepper-progress` | `step10-schedule-stepper-progress` |

### 5. Mettre à jour `step10_schedule_light.html` (ex-step9)

- Mettre à jour le titre de page : "Étape 9" → "Étape 10"
- Mettre à jour le fil d'Ariane si présent
- Vérifier le numéro dans le cercle actif du stepper (doit afficher `10`)

---

## Règles à respecter

### Architecture AssetMapper (NON-NÉGOCIABLE)
- Zéro `style=""` inline
- Zéro `<style>` dans les templates
- Zéro `onclick` / event handlers inline
- Zéro `<script>` dans les fichiers HTML

### Cohérence visuelle
- Conserver exactement la structure HTML du stepper existant (classes, balises)
- Ne modifier que ce qui est listé dans ce document
- Ne pas toucher aux fichiers dark-blue / dark-red
- Ne pas toucher aux fichiers hors `campaign_generation/`

### Ordre d'exécution recommandé
1. Renommer `step9_schedule_light.html` → `step10_schedule_light.html`
2. Mettre à jour `_campaign-stepper.css`
3. Mettre à jour les liens dans `step8_validate_light.html`
4. Mettre à jour `step9_budget_light.html` (lien continuer)
5. Mettre à jour `step10_schedule_light.html` (liens retour + stepper + titre)
6. Mettre à jour le stepper HTML dans tous les autres fichiers _light.html

---

## Fichiers concernés

### Renommage
- `campaign_generation/step9_schedule_light.html` → `step10_schedule_light.html`

### CSS
- `assets/css/components/_campaign-stepper.css`

### HTML à modifier (stepper + navigation)
- `campaign_generation/step8_validate_light.html`
- `campaign_generation/step9_budget_light.html`
- `campaign_generation/step10_schedule_light.html` (renommé)

### HTML à modifier (stepper uniquement — ajout étape Budget)
- `campaign_generation/step1_create_light.html`
- `campaign_generation/step1_loading_light.html`
- `campaign_generation/step1_review_light.html`
- `campaign_generation/step2_loading_light.html`
- `campaign_generation/step2_validate_light.html`
- `campaign_generation/step3_loading_light.html`
- `campaign_generation/step3_select_light.html`
- `campaign_generation/step4_loading_light.html`
- `campaign_generation/step4_recap_light.html`
- `campaign_generation/step4_result_light.html`
- `campaign_generation/step5_select_light.html`
- `campaign_generation/step6_upload_analyzing_light.html`
- `campaign_generation/step6_upload_empty_light.html`
- `campaign_generation/step6_upload_errors_light.html`
- `campaign_generation/step6_upload_mapping_light.html`
- `campaign_generation/step6_upload_preview_light.html`
- `campaign_generation/step6_upload_suggestions_light.html`
- `campaign_generation/step6_upload_validating_light.html`
- `campaign_generation/step8_config_light.html`
- `campaign_generation/step8_loading_light.html`
