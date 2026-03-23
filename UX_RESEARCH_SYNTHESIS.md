# Synthèse Recherche UX : Mapping Colonnes CSV
**Date** : 2025-12-30
**Objectif** : Identifier les meilleures pratiques UX pour l'interface de mapping de colonnes CSV

---

## 🔍 Sources analysées

### Articles de référence
- [5 Best Practices for Building a CSV Uploader](https://www.oneschema.co/blog/building-a-csv-uploader)
- [Building a Seamless CSV Import Experience with Flatfile](https://flatfile.com/blog/optimizing-csv-import-experiences-flatfile-portal/)
- [Best UI patterns for file uploads - CSVBox Blog](https://blog.csvbox.io/file-upload-patterns/)
- [Designing An Attractive And Usable Data Importer For Your App — Smashing Magazine](https://www.smashingmagazine.com/2020/12/designing-attractive-usable-data-importer-app/)
- [How To Design Bulk Import UX (+ Figma Prototypes) — Smart Interface Design Patterns](https://smart-interface-design-patterns.com/articles/bulk-ux/)
- [Introducing Smart Mapping: Revolutionizing CSV Imports - Flatirons](https://flatirons.com/blog/introducing-smart-mapping/)

---

## 🎨 Pattern UI dominant : Layout Deux Colonnes

### Structure visuelle
```
┌─────────────────────────────────────────────────────────────┐
│  Colonnes du fichier (gauche)    Champs système (droite)   │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐             ┌─────────────────┐        │
│  │ E-mail          │ ─────────→  │ mail (requis)   │  ✓     │
│  └─────────────────┘             └─────────────────┘        │
│                                                              │
│  ┌─────────────────┐             ┌─────────────────┐        │
│  │ Nom complet     │ ─────────→  │ nom             │  ✓     │
│  └─────────────────┘             └─────────────────┘        │
│                                                              │
│  ┌─────────────────┐             ┌─────────────────┐        │
│  │ Tél             │ ─────────→  │ téléphone       │  ⚠     │
│  └─────────────────┘             └─────────────────┘        │
└─────────────────────────────────────────────────────────────┘
```

**Exemple référence** : ClickUp Data Importer (Smashing Magazine)
- Colonnes identifiées à gauche
- Informations de matching à droite
- Connexion visuelle claire entre les deux

---

## 🤖 Détection Automatique IA

### Technologie de matching
1. **Fuzzy Matching 95%** (Flatfile)
   - Machine-learning pour correspondance automatique
   - Apprentissage au fil du temps
   - Amélioration continue de la précision

2. **OpenAI Mapping** (Flatirons Fuse)
   - Utilisation de l'IA pour éliminer le mapping manuel
   - Sauvegarde des mappings réussis
   - Application automatique pour futurs CSV similaires

3. **Détection de structure** (CamelAI)
   - Analyse automatique de la structure CSV
   - Détection des types de données
   - Optimisation pour requêtes

### UX de la suggestion automatique
- **Affichage immédiat** : Suggestions visibles dès l'upload
- **Indicateur de confiance** : Niveau de certitude du matching (optionnel)
- **CTA clair** : Bouton "Accepter toutes les suggestions" + possibilité d'ajuster
- **Apprentissage** : Mémorisation des choix utilisateur pour futures importations

---

## 🎨 Système de Couleurs et États Visuels

### Code couleur standardisé

#### ✅ Vert = Validation/Succès
- Matching confirmé et correct
- Champ validé
- Boutons d'action affirmative
- **Effet psychologique** : Conditionne l'utilisateur à reconnaître rapidement les matches corrects

#### ⚠️ Orange = Avertissement
- Problème potentiel avec les valeurs
- Validation partielle
- Nécessite attention utilisateur

#### ❌ Rouge = Erreur/Requis
- Champ requis non mappé
- Donnée invalide/manquante
- Action bloquante

#### ⚪ Gris/Neutre = Non mappé
- Colonne non assignée
- Champ optionnel vide
- Peut être ignoré

### Exemple visuel
```
✓ E-mail → mail          [Vert - Match confirmé]
⚠ Téléphone → phone      [Orange - Format à vérifier]
✗ Nom → [Non mappé]      [Rouge - Requis manquant]
○ Notes → [Ignoré]       [Gris - Optionnel]
```

---

## 📊 Interface de Mapping : Dropdowns vs Drag & Drop

### Option A : Dropdowns (Recommandé pour ce projet)

**Avantages** :
- ✅ Simple et familier pour tous les utilisateurs
- ✅ Fonctionne parfaitement sur mobile/tablette
- ✅ Facile à implémenter en mockup statique
- ✅ Moins de risque d'erreur utilisateur
- ✅ Accessible (ARIA support natif)

**Structure** :
```
┌────────────────────────────────────────────┐
│ Colonne fichier : E-mail                   │
│                                            │
│ Mapper vers :  ┌────────────────────────┐ │
│                │ mail (requis)        ▼ │ │
│                └────────────────────────┘ │
│                                            │
│ ✓ Mapping validé                           │
└────────────────────────────────────────────┘
```

**Exemple** : Utilisé par la majorité des outils SaaS (Salesforce, HubSpot, etc.)

### Option B : Drag & Drop

**Avantages** :
- ✅ Visuel et intuitif
- ✅ Sensation de contrôle direct
- ✅ Moderne et engageant

**Inconvénients** :
- ❌ Difficile sur mobile
- ❌ Problèmes d'accessibilité
- ❌ Complexe à simuler en mockup statique
- ❌ Nécessite fallback pour touch devices

**Conclusion** : Privilégier les dropdowns pour ce projet (mobile-first, accessibilité, simplicité de mockup)

---

## ✅ Validation et Gestion des Erreurs

### 1. Validation en temps réel
- Vérification immédiate lors du mapping
- Feedback visuel instantané
- Messages d'erreur contextuels

### 2. Toggle "Afficher seulement les problèmes"
- Permet de se concentrer sur les erreurs
- Réduit la surcharge cognitive
- Accélère la résolution

### 3. Correction directe
- Édition inline des valeurs problématiques
- Suggestions de correction automatique
- Undo/Redo pour les modifications

### 4. Résumé de validation
```
┌─────────────────────────────────────────┐
│ 📊 Résumé de la validation              │
├─────────────────────────────────────────┤
│ ✓ 150 lignes valides                    │
│ ⚠ 5 lignes avec avertissements          │
│ ✗ 2 lignes avec erreurs                 │
│                                         │
│ [Corriger les erreurs] [Continuer ›]   │
└─────────────────────────────────────────┘
```

---

## 📋 Prévisualisation des Données

### Bonnes pratiques

#### Nombre de lignes
- **5-10 lignes** : Optimal pour aperçu rapide
- Toggle "Afficher plus" pour voir davantage
- Pagination si fichier volumineux

#### Colonnes affichées
- Uniquement les colonnes **mappées**
- Option "Afficher colonnes ignorées" (toggle)
- Scroll horizontal si nombreuses colonnes

#### Format tableau
```
┌──────────┬──────────┬──────────────────┬─────────────┐
│ nom      │ prénom   │ mail             │ téléphone   │
├──────────┼──────────┼──────────────────┼─────────────┤
│ Dupont   │ Jean     │ j.d@mail.com     │ 0601020304  │
│ Martin   │ Sophie   │ s.m@mail.com     │ 0605060708  │
│ Bernard  │ Luc      │ l.b@mail.com     │ 0609101112  │
│ ✗ Erreur │ Marie    │ invalide         │ ---         │
│ Petit    │ Paul     │ p.p@mail.com     │ 0613141516  │
└──────────┴──────────┴──────────────────┴─────────────┘
       [Afficher plus]  [Tout afficher]
```

#### Indicateurs visuels
- ✓ Ligne verte = Toutes données valides
- ⚠ Ligne orange = Avertissement
- ✗ Ligne rouge = Erreur bloquante
- Tooltip au survol pour détails

---

## 🚀 Workflow Optimal (Étapes UX)

### Étape 1 : Upload
- Zone drag & drop visible
- Formats acceptés affichés (.csv, .xlsx)
- Taille max indiquée (si limite)
- Bouton "Parcourir" alternatif

### Étape 2 : Analyse Automatique
- Loader avec message "Analyse en cours..."
- Temps estimé si fichier volumineux
- Possibilité d'annuler

### Étape 3 : Suggestions IA
- Affichage des suggestions automatiques
- Score de confiance (optionnel)
- CTA "Accepter toutes les suggestions"
- Possibilité de modifier individuellement

### Étape 4 : Mapping Manuel (si nécessaire)
- Interface claire colonnes fichier ↔ champs système
- Champs requis marqués visuellement
- Validation en temps réel
- Résumé du mapping (X/Y champs mappés)

### Étape 5 : Validation Données
- Prévisualisation tableau (5-10 lignes)
- Résumé validation (lignes OK/Warning/Erreur)
- Toggle "Afficher seulement les problèmes"
- Possibilité de corriger directement

### Étape 6 : Confirmation
- Résumé final du mapping
- Nombre de contacts à importer
- Bouton "Importer" bien visible
- Option "Sauvegarder ce mapping pour futurs imports"

---

## 🎯 Recommandations pour le Mockup

### Pattern UI choisi : **Dropdowns avec suggestions IA**

**Justification** :
- ✅ Mobile-friendly (responsive essentiel)
- ✅ Accessible (ARIA natif)
- ✅ Facile à mocker en HTML/CSS statique
- ✅ Familier pour utilisateurs
- ✅ Permet suggestions IA claires

### États à créer (6 mockups × 3 thèmes)

1. **État Upload vide**
   - Zone drag & drop
   - Instructions claires
   - Exemples de formats

2. **État Analyse IA**
   - Fichier uploadé affiché (nom, taille, lignes)
   - Loader "Analyse en cours..."
   - Possibilité d'annuler

3. **État Suggestions IA**
   - Mappings suggérés affichés
   - Score de confiance (badges)
   - Bouton "Accepter suggestions" + "Modifier"

4. **État Mapping manuel**
   - Liste colonnes fichier
   - Dropdowns champs CFI
   - Indicateurs visuels (vert/orange/rouge)
   - Compteur "5/8 champs mappés"

5. **État Prévisualisation**
   - Tableau 5-10 lignes
   - Résumé validation
   - Toggle "Problèmes uniquement"

6. **État Erreurs**
   - Messages d'erreur contextuels
   - Lignes problématiques mises en évidence
   - Actions de correction suggérées

### Système de couleurs (à adapter aux thèmes)

**Thème Clair** :
- Vert : `#28a745` (success)
- Orange : `#ffc107` (warning)
- Rouge : `#dc3545` (danger)
- Gris : `#6c757d` (neutral)

**Thème Sombre Bleu** :
- Vert : `#4ade80` (success-dark)
- Orange : `#fbbf24` (warning-dark)
- Rouge : `#f87171` (danger-dark)
- Gris : `#9ca3af` (neutral-dark)

**Thème Sombre Rouge** :
- Vert : `#4ade80` (success-dark)
- Orange : `#fbbf24` (warning-dark)
- Rouge : `#fca5a5` (danger-dark-red)
- Gris : `#9ca3af` (neutral-dark)

---

## 📚 Features Avancées (Nice-to-Have)

### Pour grande quantité de données (20+ colonnes)
- Recherche/filtre de colonnes
- Groupement de champs (Identité, Localisation, Contact, etc.)
- Sauvegarde session (reprise possible)
- Export/import de mapping

### Pour utilisateurs avancés
- Mode "Vue compacte" (plus de lignes visibles)
- Raccourcis clavier (Tab pour navigation, Enter pour valider)
- Historique des mappings
- Templates de mapping prédéfinis

### Pour optimisation IA
- Feedback utilisateur sur suggestions (👍/👎)
- Apprentissage des corrections manuelles
- Suggestion de transformations (format téléphone, email, etc.)

**Note** : Ces features avancées ne sont **pas prioritaires** pour le mockup initial. À discuter avec l'équipe si nécessaire.

---

## 🎬 Décision Finale pour le Mockup

### Pattern UI : Dropdowns avec détection IA

**Structure visuelle** :
```
┌─────────────────────────────────────────────────────────┐
│  Upload fichier contacts.csv                            │
│  150 lignes détectées                                   │
│                                                          │
│  🤖 Suggestions IA (95% confiance)                      │
│  [Accepter toutes les suggestions] [Ajuster]            │
└─────────────────────────────────────────────────────────┘

┌──────────────────────────┬──────────────────────────────┐
│ Colonnes du fichier      │ Champs CFI                   │
├──────────────────────────┼──────────────────────────────┤
│ E-mail                   │ ┌─────────────────────────┐  │
│ (150 valeurs)            │ │ mail (requis)        ▼ │  │
│                          │ └─────────────────────────┘  │
│                          │ ✓ Match validé               │
├──────────────────────────┼──────────────────────────────┤
│ Nom complet              │ ┌─────────────────────────┐  │
│ (150 valeurs)            │ │ nom                  ▼ │  │
│                          │ └─────────────────────────┘  │
│                          │ ⚠ Suggéré : prénom + nom     │
├──────────────────────────┼──────────────────────────────┤
│ Tél                      │ ┌─────────────────────────┐  │
│ (148 valeurs, 2 vides)   │ │ téléphone            ▼ │  │
│                          │ └─────────────────────────┘  │
│                          │ ⚠ 2 valeurs manquantes       │
└──────────────────────────┴──────────────────────────────┘

[Prévisualiser les données]
```

### Composants Bootstrap à utiliser
- `.card` : Conteneur principal
- `.form-select` : Dropdowns de mapping
- `.table` : Prévisualisation données
- `.badge` : Indicateurs (requis, optionnel, confiance IA)
- `.alert` : Messages d'erreur/succès
- `.btn` : Boutons d'action
- `.spinner-border` : Loader analyse

### Icônes Bootstrap Icons
- `.bi-upload` : Upload
- `.bi-file-earmark-text` : Fichier CSV
- `.bi-check-circle-fill` : Match validé (vert)
- `.bi-exclamation-triangle-fill` : Avertissement (orange)
- `.bi-x-circle-fill` : Erreur (rouge)
- `.bi-magic` : IA/Suggestions automatiques
- `.bi-eye` : Prévisualisation

---

## ✅ Prochaine Étape

**Phase 2 : Conception des 6 états × 3 thèmes**

Maintenant que le pattern UX est défini, commencer la création des mockups HTML avec :
1. Agent `twig-template-expert` pour structure HTML
2. Respect design system existant
3. Composants Bootstrap conformes
4. 3 thèmes (Clair, Sombre Bleu, Sombre Rouge)

---

**Sources** :
- [5 Best Practices for Building a CSV Uploader](https://www.oneschema.co/blog/building-a-csv-uploader)
- [Building a Seamless CSV Import Experience with Flatfile](https://flatfile.com/blog/optimizing-csv-import-experiences-flatfile-portal/)
- [Best UI patterns for file uploads - CSVBox Blog](https://blog.csvbox.io/file-upload-patterns/)
- [Designing An Attractive And Usable Data Importer For Your App — Smashing Magazine](https://www.smashingmagazine.com/2020/12/designing-attractive-usable-data-importer-app/)
- [How To Design Bulk Import UX (+ Figma Prototypes) — Smart Interface Design Patterns](https://smart-interface-design-patterns.com/articles/bulk-ux/)
- [Introducing Smart Mapping: Revolutionizing CSV Imports - Flatirons](https://flatirons.com/blog/introducing-smart-mapping/)