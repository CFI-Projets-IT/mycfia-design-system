# Contexte projet — MyCfiA Design System

> Fichier de référence pour reprendre le travail entre sessions ClaudeCode.
> Mis à jour : 2026-03-20

---

## Objectif du projet

Conception et prototypage interactif HTML/CSS/JS du design system de l'application **MyCfiA** (client : Gorillias).
Les mockups n'avaient pas été livrés par le client — ce projet couvre leur conception complète.

Ce projet couvre **uniquement la phase mockup**. L'implémentation Symfony est un projet séparé.

---

## Structure du projet

```
myCfia-designSystem/
├── index.html                  → page de navigation vers toutes les vues
├── assets/                     → CSS/JS partagés (design system, DAP, thèmes)
├── campaign_generation/        → workflow complet de génération de campagne (9 étapes)
├── settings_index/             → module Settings
├── profile/                    → module Profil utilisateur
├── docs/                       → documentation technique
├── IMPLEMENTATION_DAP/         → guide d'implémentation du système DAP
├── DEVIS_MYCFIA_DESIGN_SYSTEM.md → devis client (à saisir sur facture.net)
└── _template_*.html            → templates de base (light, dark-blue, dark-red)
```

**3 thèmes disponibles** : `light`, `dark-blue`, `dark-red`

---

## Workflow Génération de Campagne (9 étapes)

| Step | Fichiers | Description |
|------|----------|-------------|
| step1 | `step1_create`, `step1_loading`, `step1_review` | Création campagne + enrichissement IA |
| step2 | `step2_loading`, `step2_select`, `step2_validate` | Sélection personas |
| step3 | `step3_loading`, `step3_select`, `step3_validate` | Stratégie marketing |
| step4 | `step4_loading`, `step4_result`, `step4_recap` | Résultat stratégie |
| step5 | `step5_loading`, `step5_select`, `step5_config`, `step5_validate` | Sélection canaux |
| step6 | `step6_upload_*` (7 états) | Upload et mapping contacts |
| step8 | `step8_loading`, `step8_config`, `step8_validate` | Validation assets (éditeur + enrichissement IA) |
| step9 | `step9_schedule` | Planification publication |
| — | `campaign_show` | Vue d'ensemble campagne (dashboard) |

**Note** : step7 = `contact_upload_*` (ancienne numérotation), intégré dans step6 après réorganisation.

---

## Système DAP (Digital Adoption Platform)

Tour guidé interactif implémenté sur toutes les vues.

**Composants core** (dans `assets/`) :
- Moteur de tour guidé (étapes, navigation)
- Spotlight (mise en surbrillance des éléments)
- Tooltips contextuels positionnés dynamiquement
- Speed Dial d'aide
- Gestion scroll + repositionnement dynamique

**Instrumentation complète** sur : dashboard, step1→step9, campaign_show, contact_upload (7 états).

Guide d'implémentation : `IMPLEMENTATION_DAP/`

---

## État du devis (DEVIS_MYCFIA_DESIGN_SYSTEM.md)

Devis à saisir sur **facture.net** (pas de génération automatique — saisie manuelle par l'utilisateur).

| # | Prestation | Jours | Montant HT |
|---|-----------|-------|-----------|
| 1 | Analyse et design system | 1 j | 380 € |
| 2 | Maquette — Génération de Campagne | 3 j | 1 140 € |
| 3 | Maquette — Settings et Profil | 1 j | 380 € |
| 4 | Maquette — Planification et Dashboard | 1 j | 380 € |
| 5 | Itérations — Réorganisation workflow | 1,5 j | 570 € |
| 6 | Itérations — Corrections UX | 1,5 j | 570 € |
| 7 | DAP — Architecture et composants core | 2 j | 760 € |
| 8 | DAP — Instrumentation 10 étapes | 3 j | 1 140 € |
| 9 | Maquette — Vue Budget (grille tarifaire + DAP) | 1,5 j | 570 € |
| 10 | DAP — Corrections et finitions | 1 j | 380 € |
| 11 | Module Upload Contacts | 1 j | 380 € |
| | **TOTAL** | **17,5 j** | **6 650 €** |

Taux journalier : **380 €/jour**

---

## Vue Budget — À DÉVELOPPER

Prochaine tâche identifiée lors de la dernière session (19 mars 2026) :

**Concevoir et développer la vue Budget** affichant la grille tarifaire par canal.
- Canaux : email, courrier, SMS
- Source de données : fichier Excel à structure prédéfinie
- Intégration dans le workflow campagne
- Déclinaison multi-thèmes (light, dark-blue, dark-red)
- Instrumentation DAP incluse
- Déjà intégré dans le devis (ligne 9 — 1,5 jour)

---

## Conventions techniques

- HTML5 sémantique + Bootstrap 5
- CSS modulaire par vue + variables CSS pour les thèmes
- JS vanilla (pas de framework)
- Chaque vue est autonome (pas de dépendance serveur)
- Nomenclature fichiers : `{step/module}_{état}_{thème}.html`
