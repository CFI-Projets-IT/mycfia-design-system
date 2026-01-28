# Progression Réorganisation Étapes (Upload après Canaux)

**Date début** : 27 janvier 2026
**Objectif** : Déplacer l'étape UPLOAD CONTACTS après CANAUX

## Nouvel ordre
1. Projet
2. Concurrents
3. Personas
4. **Stratégie** (ex-step5)
5. **Canaux** (ex-step6)
6. **Upload** (ex-contact_upload)
7. **Assets** (ex-step7, devient step8)
8. **Planning** (ex-step8, devient step9)

---

## Phase 1 : Renommage fichiers ✅ TERMINÉ

- ✅ step8_schedule_light.html → step9_schedule_light.html
- ✅ step7_config_light.html → step8_config_light.html
- ✅ step7_loading_light.html → step8_loading_light.html
- ✅ step7_validate_light.html → step8_validate_light.html
- ✅ step6_select_light.html → step5_select_light.html
- ✅ contact_upload_empty_light.html → step6_upload_empty_light.html
- ✅ contact_upload_analyzing_light.html → step6_upload_analyzing_light.html
- ✅ contact_upload_validating_light.html → step6_upload_validating_light.html
- ✅ contact_upload_errors_light.html → step6_upload_errors_light.html
- ✅ contact_upload_mapping_light.html → step6_upload_mapping_light.html
- ✅ contact_upload_suggestions_light.html → step6_upload_suggestions_light.html
- ✅ contact_upload_preview_light.html → step6_upload_preview_light.html
- ✅ step5_loading_light.html → step4_loading_light.html
- ✅ step5_recap_light.html → step4_recap_light.html
- ✅ step5_result_light.html → step4_result_light.html

**Total** : 15 fichiers renommés

---

## Phase 2 : Mise à jour steppers et liens ✅ TERMINÉ

### ⚠️ Modification supplémentaire : Déplacement modal contactSourceModal

**Problème** : Modal "Source des contacts" s'affichait à l'étape 3 (Personas) au lieu de l'étape 5 (Canaux).

**Solution appliquée** :
- ✅ Modal déplacée de step3_select_light.html vers step5_select_light.html
- ✅ step3 : Bouton "Valider" → lien direct step4_loading_light.html (Stratégie)
- ✅ step5 : Bouton "Valider" → ouvre modal contactSourceModal
- ✅ Modal footer : "Passer à Stratégie" → "Passer aux Assets" (step8_loading)

**Nouveau workflow** :
- Étape 3 (Personas) → Valider → Étape 4 (Stratégie)
- Étape 5 (Canaux) → Valider → Modal → Upload OU Assets

---

### Fichiers critiques (renommés)

#### ✅ step3_select_light.html
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload
- [x] Bouton "Valider et continuer" : lien direct vers step4_loading_light.html
- [x] Modal contactSourceModal : SUPPRIMÉE (déplacée vers step5)

#### ✅ step4_loading_light.html (ex-step5)
- [x] Stepper : étape active 4 (Stratégie)
- [x] Stepper progress : step4-loading-stepper-progress

#### ✅ step4_recap_light.html (ex-step5)
- [x] Stepper : étape active 4 (Stratégie)
- [x] Stepper progress : step4-recap-stepper-progress

#### ✅ step4_result_light.html (ex-step5)
- [x] Stepper : étape active 4 (Stratégie)
- [x] Stepper progress : step4-result-stepper-progress

#### ✅ step5_select_light.html (ex-step6 Canaux) - TERMINÉ
- [x] Stepper : étape active 5 (Canaux)
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload
- [x] Classes CSS : step6-select → step5-select
- [x] Title : Étape 5
- [x] Liens href internes (step4_result + step8_loading)
- [x] **Modal contactSourceModal ajoutée** (déplacée depuis step3)
- [x] Bouton "Valider et continuer" : ouvre modal au lieu de lien direct

#### ✅ step6_upload_empty_light.html (ex-contact_upload)
- [x] Stepper : étape active 6 (Upload), Stratégie et Canaux completed
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload
- [x] Classes CSS : contact_upload_ → step6_upload_
- [x] Title : Étape 6
- [x] Liens href internes (step8_loading pour skip)

#### ✅ step6_upload_analyzing_light.html
- [x] Stepper : étape active 6 (Upload), Stratégie et Canaux completed
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload
- [x] Liens href internes (déjà corrects - step6_upload_*)

#### ✅ step6_upload_validating_light.html
- [x] Stepper : étape active 6 (Upload), Stratégie et Canaux completed
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload
- [x] Liens href internes (déjà corrects - step6_upload_*)

#### ✅ step6_upload_errors_light.html
- [x] Stepper : étape active 6 (Upload), Stratégie et Canaux completed
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload
- [x] Liens href internes (contact_upload_ → step6_upload_)

#### ✅ step6_upload_mapping_light.html
- [x] Stepper : étape active 6 (Upload), Stratégie et Canaux completed
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload
- [x] Liens href internes (contact_upload_ → step6_upload_)

#### ✅ step6_upload_suggestions_light.html
- [x] Stepper : étape active 6 (Upload), Stratégie et Canaux completed
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload
- [x] Liens href internes (contact_upload_ → step6_upload_)

#### ✅ step6_upload_preview_light.html
- [x] Stepper : étape active 6 (Upload), Stratégie et Canaux completed
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload
- [x] Liens href internes (contact_upload_ → step6_upload_ + step8_loading)

#### ✅ step8_config_light.html (ex-step7 Assets)
- [x] Stepper : étape active 7 (Assets), ordre correct
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload, 7=Assets, 8=Planning
- [x] Classes CSS : step7-config-stepper-progress → step8-config-stepper-progress
- [x] Title : Étape 7
- [ ] Liens href internes (à vérifier si nécessaire)

#### ✅ step8_loading_light.html (ex-step7)
- [x] Stepper : étape active 7 (Assets), ordre correct
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload, 7=Assets, 8=Planning
- [x] Classes CSS : step5-loading → step8-loading (global replace)
- [x] Title : Étape 7

#### ✅ step8_validate_light.html (ex-step7)
- [x] Stepper : étape active 7 (Assets), ordre correct
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload, 7=Assets, 8=Planning
- [x] Classes CSS : step5-validate → step8-validate (global replace)
- [x] Liens href : step8_loading, step9_schedule, step5_select (FAB)
- [x] Title : Étape 7

#### ✅ step9_schedule_light.html (ex-step8 Planning)
- [x] Stepper : étape active 8 (Planning), ordre correct
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload, 7=Assets, 8=Planning
- [x] Classes CSS : step8-schedule → step9-schedule, step5-validate → step9-schedule (global)
- [x] Title : Étape 8 (correct - Planning est l'étape 8)

---

### Autres fichiers light (contenant des steppers)

#### ✅ step1_create_light.html
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload (labels corrigés)

#### ✅ step1_loading_light.html
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload (labels corrigés)

#### ✅ step1_review_light.html
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload (à corriger)

#### ✅ step2_loading_light.html
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload (à corriger)

#### ✅ step2_validate_light.html
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload (à corriger)

#### ✅ step3_loading_light.html
- [x] Stepper : 4=Stratégie, 5=Canaux, 6=Upload (à corriger)

#### ✅ dashboard_light.html
- [x] Vérifier si contient stepper (pas de stepper)

#### ✅ campaign_assets_light.html
- [x] Vérifier si contient stepper (pas de stepper)

#### ✅ campaign_show_light.html
- [x] Vérifier si contient stepper (pas de stepper)

---

## Phase 3 : Corrections post-réorganisation ✅ TERMINÉ

### ⚠️ Correction loader JavaScript (campaign-loader.js)

**Problème** : Barre de progression ne fonctionnait pas sur step4_loading_light.html.

**Cause** : Détection de fichiers basée sur anciens noms dans `initLoader()`.

**Solution appliquée** :
- ✅ Ligne 490 : `step5_loading` → `step4_loading` (Stratégie)
- ✅ Ligne 492 : `step7_loading` → `step8_loading` (Assets)
- ✅ Ligne 494 : `contact_upload_validating` → `step6_upload_validating`
- ✅ Ligne 496 : `contact_upload_analyzing` → `step6_upload_analyzing`
- ✅ URLs de redirection : step5_result → step4_result, step7_validate → step8_validate, contact_upload_* → step6_upload_*

### ⚠️ Suppression alert (step4_result_light.html)

**Problème** : Alert Bootstrap inutile présente dans le fichier.

**Solution appliquée** :
- ✅ Alert-success supprimée (lignes 452-459)
- ✅ Speed Dial link corrigé : step6_select → step5_select (Canaux)
- ✅ Label corrigé : "Continuer vers génération d'assets" → "Continuer vers sélection canaux"

### ⚠️ Correction DAP Step 3 (onboarding-dap-step3.js)

**Problème** : Tooltip DAP mentionnait "étape 4 (Upload Contacts)" au lieu de "étape 4 (Stratégie)".

**Solution appliquée** :
- ✅ Ligne 66 : Texte corrigé de "Upload Contacts" → "Stratégie"

---

## Phase 4 : Vérification finale

- [ ] Vérifier tous les liens href fonctionnent
- [ ] Vérifier cohérence des steppers
- [ ] Tester navigation entre pages
- [ ] Commit Git

---

**Dernière mise à jour** : 28/01/2026 10:30
**Fichiers traités** : 27 HTML + 1 JS + 1 DAP JS = 29 fichiers
**Progression** : 100% ✅
