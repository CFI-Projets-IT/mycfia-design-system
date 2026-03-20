# Session de Travail — Step 9 Budget

**Date :** 2026-03-20
**Contexte :** Ajout de la nouvelle étape Budget dans le workflow de génération de campagne

---

## Décisions Confirmées

### Numérotation
- **Step 8** : Assets / Validation contenus (existant — `step8_validate_light.html`)
- **Step 9** : Budget ← **nouvelle étape** (`step9_budget_light.html`)
- **Step 10** : Planning (actuellement nommé `step9_schedule_light.html` → à mettre à jour)

### Thème
- Prototype **light uniquement** (comme les autres étapes récentes)

### Paiement
- **PayPal** (pas Stripe comme mentionné dans le doc initial `NOUVELLE_ETAPE_BUDGET.md`)
- Le bouton PayPal remplace le Payment Element Stripe dans l'UI

---

## Logique des Parcours — Décisions Confirmées

### Trois cas selon le choix à l'étape Upload (Step 4 / Step 6)

La distinction se fait **via le système de codification back-end** — le front-end affiche les sections correspondantes selon ce qui a été sélectionné.

| Choix à l'étape Upload | Vue affichée en Step 9 Budget |
|------------------------|-------------------------------|
| Upload fichier simple (CSV) | Parcours Standard uniquement |
| Avanci uniquement | Grille tarifaire Avanci uniquement |
| Upload + Avanci | Les deux sections combinées |

### Ce qui distingue les deux parcours

La différence est **minime visuellement** — c'est la codification article (back-end) qui pilote quelles lignes apparaissent :

- **Parcours Standard** : lignes `02_*` (location adresses) + `03_*` (canaux) + `04_*` (affranchissement)
- **Parcours Avanci** : ligne(s) `05_*` (contacts Avanci — codification à créer) + `03_*` canaux réseaux sociaux
- **Upload + Avanci** : les deux ensembles de lignes

### Un seul fichier HTML pour le prototype

Un seul fichier `step9_budget_light.html` avec les sections conditionnelles — pas de fichiers séparés.

---

## Données à Utiliser pour le Prototype

### Données réelles (fichier Excel fourni — `Grille de prix MyCFIA - IDCLIENT.xlsx`)

| Codification | Nommage | Unité | Prix de vente | Note |
|---|---|---|---|---|
| `01_mycfia_forfait` | MyCFiA forfait | Forfait Mensuel | 490 € | **NE PAS AFFICHER** — prix de l'abonnement myCFiA, hors calcul campagne |
| `02_mycfia_location_email` | Location adresse Email | Unité | 0,12 € | |
| `02_mycfia_location_sms` | Location adresse SMS | Unité | 0,14 € | |
| `02_mycfia_location_adresse_courrier` | Location adresse Courrier | Unité | 0,23 € | |
| `03_mycfia_canal_print_A4_R` | Lettre A4 Recto Seul + EP C5 | Unité | 0,30 € | laser+faco+msp |
| `03_mycfia_canal_email` | Email | Unité | 0,01 € | |
| `03_mycfia_canal_sms` | SMS | Unité | 0,055 € | |
| `03_mycfia_canal_reseaux_txt` | Réseaux sociaux textes | Unité | Compris dans forfait | |
| `03_mycfia_canal_reseaux_img` | Réseaux sociaux images | Unité | Compris dans forfait | |
| `04_mycfia_affr_premium_G4` | Affranchissement G4 Standard | Unité | 0,747 € | EP C6 et C5 |
| `04_mycfia_affr_premium_destineo_MD7` | Affranchissement Destineo MD7 | Unité | 0,388 € | EP C6 et C5 |

### Données imaginées pour Avanci (codification à créer)

Une ou plusieurs lignes `05_*` à ajouter dans la grille réelle :

| Codification (imaginée) | Nommage | Unité | Prix de vente |
|---|---|---|---|
| `05_mycfia_avanci_contact` | Contacts Avanci qualifiés | Unité | **PLACEHOLDER** — tarif à définir par le client |

**Note :** Les colonnes affichées dans la vue (Nommage, Unité, Prix de vente) proviennent du fichier Excel. La codification n'apparaît pas dans le front-end — usage back-end uniquement.

### Calcul des volumes

Le volume de chaque ligne tarifaire est déterminé par le **nombre de contacts uploadés à l'étape Upload (Step 4/6)**.

- Volume Email = nombre total de contacts uploadés
- Volume SMS = nombre total de contacts uploadés
- Volume Courrier = nombre total de contacts uploadés
- Volume Avanci = nombre de contacts Avanci ciblés (retourné par l'API Avanci)

Le volume est **en lecture seule** sur la page Budget — pas d'édition possible par l'utilisateur.

Pour le prototype : volumes mockés à titre illustratif.

### Affranchissement — Choix utilisateur sur la page

L'utilisateur sélectionne le type d'affranchissement **directement sur la page Step 9 Budget** (pas déterminé en amont).

Deux options disponibles :

| Codification | Nommage | Prix unitaire |
|---|---|---|
| `04_mycfia_affr_premium_G4` | Affranchissement G4 Standard | 0,747 € |
| `04_mycfia_affr_premium_destineo_MD7` | Affranchissement Destineo MD7 | 0,388 € |

**Comportement attendu dans la vue :**
- Selector (radio ou select) entre les deux options
- Le total se recalcule dynamiquement selon le choix
- Visible uniquement si le canal Courrier a été sélectionné en amont

---

## Contexte Technique (issu de NOUVELLE_ETAPE_BUDGET.md)

### Tableau Tarifs CFIA (Parcours Standard)
| Support | Volume Établi | Tarif Unitaire | Total |
|---------|--------------|----------------|-------|
| Email Marketing | 10 000 emails | 0,05 € / email | 500 € |
| SMS | 5 000 SMS | 0,10 € / SMS | 500 € |
| Courrier Postal | 2 000 lettres | 0,80 € / lettre | 1 600 € |
| LinkedIn Ads | 500 000 impressions | 8,00 € / 1000 imp | 4 000 € |
| Google Ads | 1 000 000 impressions | 5,00 € / 1000 imp | 5 000 € |
| Facebook Ads | 800 000 impressions | 4,00 € / 1000 imp | 3 200 € |
| Display / Programmatique | 1 500 000 impressions | 3,00 € / 1000 imp | 4 500 € |

**Total estimé : 19 300 €**

### Questions non résolues (du doc initial)
- Tarifs CFIA : hardcodés, API externe ou table BDD configurable ?
- Volume établi : comment calculé (depuis Step 4 pour email/SMS/courrier, depuis budget Step 6 pour les Ads) ?
- Montant PayPal : total campagne (100%) ou commission CFIA uniquement ?
- Paiement unique ou échelonné ?
- Volume éditable par l'utilisateur dans le tableau ?
- Comportement si paiement échoue (retry, brouillon, notification) ?
- Lien entre budget déclaré Step 1 et calcul Step 9 (indicatif ou contraignant) ?

---

## Fichiers Concernés

### À créer
- `campaign_generation/step9_budget_light.html` — page principale Budget
- (éventuellement) `campaign_generation/step9_budget_avanci_light.html` — variante Avanci

### À modifier
- `campaign_generation/step8_validate_light.html` — changer le lien "Continuer" vers step9_budget
- `campaign_generation/step9_schedule_light.html` — ajouter vérification paiement validé
- Stepper CSS — ajouter la 10e étape (Planning)
- `index.html` — mettre à jour le tableau de navigation si présent

---

## Prochaines Étapes

1. Confirmer : un fichier ou deux pour les deux parcours ?
2. Prototyper `step9_budget_light.html` (thème light)
3. Mettre à jour les liens de navigation dans step8_validate et step9_schedule
