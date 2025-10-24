# Structure des Prompts AI - Analyse Complète

**Date de création** : 2025-10-24
**Objectif** : Documenter l'architecture complète des prompts IA pour myCfia
**Contexte** : Correction du problème de tableaux Markdown générés par l'IA

---

## 📁 Architecture des Fichiers

### Localisation
```
app/templates/ai/prompts/
├── base.md.twig                    # Template parent réutilisable
├── partials/                       # Fragments réutilisables
│   ├── _rules.md.twig             # Règles absolues communes
│   ├── _format.md.twig            # Format de réponse standardisé
│   └── _security.md.twig          # Consignes de sécurité
└── Spécialisations/                # Templates enfants
    ├── chat_factures.md.twig      # Expert Factures
    ├── chat_commandes.md.twig     # Expert Commandes
    ├── chat_stocks.md.twig        # Expert Stocks
    ├── chat_general.md.twig       # Généraliste transverse
    └── chat_operations.md.twig    # Opérations Marketing (incomplet)
```

### Hiérarchie d'Héritage
```
base.md.twig (Template parent)
    ├── Partials (inclus automatiquement)
    │   ├── _rules.md.twig      → {% block rules %}
    │   ├── _format.md.twig     → {% block format %}
    │   └── _security.md.twig   → {% block security %}
    └── Spécialisations (surchargent les blocs)
        ├── chat_factures.md.twig
        ├── chat_commandes.md.twig
        ├── chat_stocks.md.twig
        ├── chat_operations.md.twig
        └── chat_general.md.twig
```

---

## 📄 Fichiers de Base

### `base.md.twig` - Template Parent
**Rôle** : Template réutilisable pour tous les agents IA

**Responsabilités** :
- Définir l'identité globale : "Tu es un assistant IA pour **myCfia**, plateforme d'automatisation marketing multi-canal"
- Fournir les blocs Twig hérités pour personnalisation
- Inclure automatiquement les partials communs

**Structure des Blocs** :
```twig
{% block rules %}       ← Règles absolues (inclusion _rules.md.twig)
{% block context %}     ← Contexte spécifique (surchargé par enfants)
{% block tools %}       ← Outils disponibles (surchargé par enfants)
{% block format %}      ← Format de réponse (inclusion _format.md.twig)
{% block security %}    ← Consignes sécurité (inclusion _security.md.twig)
```

**Lignes clés** :
- Ligne 4 : Identité globale myCfia
- Ligne 7 : Inclusion `_rules.md.twig`
- Ligne 19 : Inclusion `_format.md.twig`
- Ligne 23 : Inclusion `_security.md.twig`

---

## 📄 Partials (Fragments Réutilisables)

### `partials/_rules.md.twig` - Règles Absolues Communes
**Rôle** : Règles applicables à TOUS les agents sans exception

**4 Règles Absolues** :
1. **Données réelles uniquement** : Utiliser TOUJOURS les tools disponibles, ne jamais inventer de chiffres
2. **Sources obligatoires** : Inclure TOUJOURS les métadonnées (ID ressource, date MAJ, source API, lien)
3. **Transparence** : Si aucune donnée trouvée, le dire clairement avec explication
4. **UI/UX** : Interdiction d'utiliser des EMOJI

**Inclus par** : `base.md.twig` → Tous les agents héritent automatiquement

---

### `partials/_format.md.twig` - Format Standardisé
**Rôle** : Imposer une structure de réponse cohérente

**Format Imposé** :
```markdown
### 📊 [Titre court et clair]

**Résumé** : [Phrase de synthèse avec chiffres clés]

**Détails** : [Commenté dans le template - optionnel]
- Item 1 : valeur
- Item 2 : valeur

**Sources** : [Commenté dans le template - optionnel]
- API CFI : [metadata.endpoint]
- Date MAJ : [Y-m-d H:i:s]
- Durée : [metadata.duration_ms]
```

**Note** : La section **Détails** et **Sources** est COMMENTÉE dans le partial (lignes 10-20) car les spécialisations définissent leurs propres règles de formatage détaillé.

**Inclus par** : `base.md.twig` → Tous les agents utilisent ce format de base

---

### `partials/_security.md.twig` - Consignes Sécurité
**Rôle** : Règles de sécurité pour protéger les données

**4 Consignes** :
1. **Filtrage tenant** : Données automatiquement filtrées par division ({{ division.nom }})
2. **Lecture seule** : Aucune modification de données possible via chat
3. **Confidentialité** : Ne jamais afficher tokens, mots de passe, données sensibles
4. **Validation** : Tous les paramètres utilisateur sont validés avant appel API

**Inclus par** : `base.md.twig` → Tous les agents respectent ces consignes

---

## 📄 Spécialisations (Templates Enfants)

### `chat_factures.md.twig` - Expert Factures ✅
**Rôle** : Agent spécialisé FACTURES uniquement

**Outils disponibles** : `get_factures`

**Paramètres** :
- `id` (optionnel) : Numérique (ex: "12577") OU Alphanumérique (ex: "PO39982")
- `dateDebut` + `dateFin` (optionnels) : Format "YYYY-MM-DD"

**Workflow (4 étapes)** :
1. **ÉTAPE 1 - EXTRAIRE L'INFORMATION**
   - ID/commande spécifique ? → Extraire valeur BRUTE (ne PAS modifier)
   - Période ? → Extraire dateDebut et dateFin
   - Tout ? → Aucun paramètre

2. **ÉTAPE 2 - APPELER get_factures**
   - Recherche spécifique : `get_factures(id="VALEUR_BRUTE")`
   - Recherche par période : `get_factures(dateDebut="...", dateFin="...")`
   - Liste complète : `get_factures()`

3. **ÉTAPE 3 - VÉRIFICATION**
   - Si `success=false` : Afficher erreur + suggérer alternatives

4. **ÉTAPE 4 - FORMATAGE**
   - **MODE LISTE** (plusieurs factures) : Maximum 3 lignes de texte + "📊 Tableau interactif ci-dessous ⬇️"
   - **MODE DÉTAIL** (facture unique) : Afficher détails complets avec lignes de facturation

**🚨 RÈGLE CRITIQUE (Lignes 82-104)** :
```
⚠️ RÈGLE ABSOLUE DE FORMATAGE :

Ton rôle est UNIQUEMENT de fournir un résumé textuel court.
TU NE DOIS JAMAIS créer de tableau, ni en Markdown, ni en HTML, ni dans aucun autre format.

MODE LISTE (plusieurs factures) :
- Maximum 3 lignes de texte
- Indiquer le nombre de factures et le montant total
- INTERDICTION TOTALE de créer un tableau
- L'interface affichera automatiquement un tableau interactif

Exemple de réponse correcte pour MODE LISTE :
Voici 4 factures pour janvier 2024, représentant un total de 7 271,61 € HT (7 758,35 € TTC).

📊 Tableau interactif ci-dessous ⬇️

Exemple de réponse INCORRECTE (NE JAMAIS FAIRE) :
| ID | NOM | MONTANT |
|---|---|---|
| 11735 | ... | ... |
```

**Exemples** (Lignes 141-243) :
- Exemple 1 : Détail d'une facture par ID
- Exemple 2 : Recherche par commande
- Exemple 3 : Liste par période
- Exemple 4 : Aucun résultat

**⚠️ PROBLÈME IDENTIFIÉ** :
- Lignes 177-222 (Exemples 2 et 3) : Montrent des listes détaillées qui ressemblent à des tableaux textuels
- L'IA peut être induite en erreur et créer des tableaux Markdown malgré l'interdiction ligne 89
- **Solution** : Renforcer l'interdiction et simplifier les exemples MODE LISTE

---

### `chat_commandes.md.twig` - Expert Commandes
**Rôle** : Agent spécialisé COMMANDES CLIENTS uniquement

**Outils disponibles** : `get_operations`

**Paramètres** :
- `type` : Filtrer sur commandes (utiliser `type=all` puis filtrer)
- `dateDebut` + `dateFin` (optionnels)
- `statut` (optionnel) : en_cours, livree, annulee

**Workflow** :
1. Identifier paramètres (période, statut, client/référence)
2. Appeler `get_operations(type=all)` puis filtrer sur commandes
3. Vérifier 0 résultat → suggérer élargir recherche
4. Formater avec métadonnées complètes

**Interdictions** :
- ❌ Ne JAMAIS parler de stocks ou factures (hors contexte)
- ❌ Ne JAMAIS utiliser d'autres tools que `get_operations`

**Exemples** (Lignes 65-131) :
- Exemple 1 : Dernières commandes
- Exemple 2 : Commandes en cours
- Exemple 3 : Statistiques commandes

---

### `chat_stocks.md.twig` - Expert Stocks
**Rôle** : Agent spécialisé GESTION DES STOCKS uniquement

**Outils disponibles** :
- `get_stocks` : État stocks (paramètres : `reference`, `enAlerte`)
- `get_stock_alerts` : Stocks en alerte (paramètre : `limit`)

**Workflow** :
1. Identifier type de requête :
   - État global → `get_stocks()`
   - Référence spécifique → `get_stocks(reference=XXX)`
   - Alertes → `get_stock_alerts()`
2. Appeler le bon outil
3. Vérifier 0 résultat → suggérer ajuster critères
4. Formater avec métadonnées **RETOURNÉES PAR LE TOOL**

**🚨 RÈGLE IMPORTANTE (Lignes 52-56)** :
```
FORMATAGE :
Formate la réponse avec TOUTES les métadonnées RETOURNÉES PAR LE TOOL :
- OBLIGATOIRE : Utiliser metadata.endpoint (ex: "POST /Stocks/getStocks")
- OBLIGATOIRE : Utiliser metadata.duration_ms pour la durée
- OBLIGATOIRE : Utiliser metadata.division pour la division
- ❌ INTERDIT : Inventer des endpoints comme "GET /api/v2/stocks"
```

**Interdictions** :
- ❌ Ne JAMAIS parler de factures ou commandes
- ❌ Ne JAMAIS inventer des endpoints

**Exemples** (Lignes 73-170) :
- Exemple 1 : État des stocks
- Exemple 2 : Alertes de réapprovisionnement
- Exemple 3 : Recherche de référence spécifique
- Exemple 4 : Stocks critiques seulement

---

### `chat_general.md.twig` - Généraliste Transverse
**Rôle** : Assistant polyvalent pour questions touchant plusieurs domaines

**Outils disponibles (4 tools)** :
1. `get_operations` : Factures et Commandes
2. `get_stocks` : État stocks, recherche références
3. `get_stock_alerts` : Alertes réapprovisionnement
4. `get_operation_stats` : Statistiques agrégées

**Workflow** :
1. **Analyse & Routage** : Identifier domaine(s)
   - Factures → `get_operations(type=courrier|mail)`
   - Commandes → `get_operations(type=all)` puis filtrer
   - Stocks → `get_stocks()` ou `get_stock_alerts()`
   - Statistiques → `get_operation_stats()`
   - Transverse → Appeler plusieurs tools

2. **Appel Tool Obligatoire** :
   - 1 domaine = 1 tool call
   - Plusieurs domaines = Plusieurs tool calls (séquentiels ou parallèles)

3. Vérifier 0 résultat → suggérer ajuster critères
4. Formater avec métadonnées complètes

**Interdictions** :
- ❌ Ne JAMAIS répondre sans appeler au moins 1 tool
- ❌ Ne JAMAIS mélanger données de différents tools sans le préciser

**Exemples** (Lignes 94-235) :
- Exemple 1 : Question mono-domaine (Factures)
- Exemple 2 : Question mono-domaine (Stocks)
- Exemple 3 : Question transverse (Factures + Commandes)
- Exemple 4 : Question statistiques avancées
- Exemple 5 : Question transverse complexe (Stocks + Commandes)

---

### `chat_operations.md.twig` - Opérations Marketing ⚠️
**Rôle** : Opérations Marketing (SMS, Email, Courrier) et Stocks

**État** : Template minimal (24 lignes seulement)

**Contenu** :
- Contexte utilisateur (division, user, date/heure)
- Spécialisation : Données Opérations Marketing et Stocks
- Liste des tools disponibles (générée dynamiquement via `{% for tool in tools %}`)

**Note** : Probablement généré dynamiquement ou incomplet

---

## 📊 Tableau Récapitulatif

| Fichier | Type | Rôle | Outils | Lignes | Hérite de |
|---------|------|------|--------|--------|-----------|
| `base.md.twig` | Parent | Template global | - | 25 | - |
| `_rules.md.twig` | Partial | Règles absolues | - | 16 | base |
| `_format.md.twig` | Partial | Format réponse | - | 21 | base |
| `_security.md.twig` | Partial | Sécurité | - | 9 | base |
| `chat_factures.md.twig` | Enfant | Expert Factures | `get_factures` | 252 | base |
| `chat_commandes.md.twig` | Enfant | Expert Commandes | `get_operations` | 141 | base |
| `chat_stocks.md.twig` | Enfant | Expert Stocks | `get_stocks`, `get_stock_alerts` | 180 | base |
| `chat_general.md.twig` | Enfant | Généraliste | 4 tools | 245 | base |
| `chat_operations.md.twig` | Enfant | Opérations (incomplet) | dynamique | 24 | base |

---

## 🎯 Problème Actuel : Tableaux Markdown dans chat_factures.md.twig

### Symptôme
L'IA génère un tableau Markdown **EN PLUS** du DataTable interactif :
- ✅ Le DataTable s'affiche correctement (avec colonnes cliquables, Total row, styling)
- ❌ Un tableau Markdown textuel apparaît AVANT le DataTable

### Cause Identifiée

**Règle d'interdiction présente (Lignes 82-104)** :
- Ligne 89 : "**INTERDICTION TOTALE** de créer un tableau"
- Lignes 93-97 : Exemple correct sans tableau
- Lignes 99-104 : Exemple INCORRECTE à ne jamais faire

**MAIS** : Les exemples détaillés (Lignes 177-222) peuvent induire l'IA en erreur :
- Exemple 2 (Lignes 177-199) : Recherche par commande avec liste de lignes de facturation
- Exemple 3 (Lignes 201-222) : Liste par période (commenté mais structure visible)
- Ces listes détaillées ressemblent visuellement à des tableaux textuels

### Solution Proposée

1. **Renforcer l'interdiction** au début du workflow (ÉTAPE 4)
2. **Simplifier les exemples MODE LISTE** pour ne montrer QUE des résumés courts (3 lignes max)
3. **Ajouter un avertissement final** répétant l'interdiction avant les exemples
4. **Supprimer les détails formatés en liste** dans les Exemples 2 et 3

### Impact
- Fichier à modifier : `chat_factures.md.twig`
- Lignes à modifier : 82-222
- Autres fichiers : Aucun (problème spécifique aux factures)

---

## 📝 Recommandations Futures

### Pour tous les agents
1. **Cohérence des interdictions** : Vérifier que tous les agents ont des règles claires sur le formatage
2. **Simplification des exemples** : Éviter les listes détaillées qui ressemblent à des tableaux
3. **Testing systématique** : Tester chaque agent après modification de prompt

### Pour chat_general.md.twig
- Vérifier si les exemples transverses (lignes 134-235) peuvent induire en erreur
- S'assurer que l'IA ne crée pas de tableaux pour les statistiques

### Pour chat_operations.md.twig
- Compléter le template (actuellement 24 lignes seulement)
- Ajouter workflow, exemples, interdictions comme les autres agents

---

## 🔗 Liens Utiles

**Fichiers liés** :
- `app/src/Service/Tool/GetFacturesTool.php` : Génère `table_data` pour DataTable
- `app/src/Twig/Components/DataTable.php` : Composant DataTable
- `app/templates/components/DataTable.html.twig` : Template DataTable
- `app/assets/js/chat.js` : Rendu DataTable côté client (lignes 32-120)

**Documentation** :
- `claudedocs/DESIGN_SYSTEM_INDEX.md` : Design system complet
- `CONTEXT_ENGINEERING/BEST_PRACTICES/PLAN_FORMAT.md` : Format des plans

---

**Dernière mise à jour** : 2025-10-24
**Auteur** : Claude Code
**Statut** : Documentation complète - Prête pour correction de chat_factures.md.twig
