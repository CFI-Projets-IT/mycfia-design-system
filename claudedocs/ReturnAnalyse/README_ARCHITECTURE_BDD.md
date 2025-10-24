# Documentation Architecture Base de Données CFI-MyCfia

**Date création** : 2025-10-22
**Projet** : myCfia - Plateforme d'automatisation marketing
**Contexte** : Décision architecturale BDD

---

## 📋 Vue d'Ensemble

Cette documentation présente l'analyse architecturale complète pour l'intégration des bases de données CFI et MyCfia, avec une recommandation finale pour une **Architecture Hybride** (Option 3).

---

## 📚 Documents Disponibles

### 1. 📊 Synthèse Exécutive (Pour Décideurs)

**Fichier** : [`SYNTHESE_EXECUTIVE_ARCHITECTURE_BDD.md`](SYNTHESE_EXECUTIVE_ARCHITECTURE_BDD.md)

**Contenu** :
- ✅ Recommandation finale (Option 3 - Architecture Hybride)
- 📊 Comparaison rapide des 5 options (matrice décisionnelle)
- 🔧 Responsabilités d'implémentation (CFI vs MyCfia)
- 📈 KPIs et indicateurs de succès
- 🚨 Risques et plans de contingence

**Durée lecture** : 10 minutes

---

### 2. 🔍 Analyse Architecturale Complète (Technique)

**Fichier** : [`ANALYSE_ARCHITECTURE_BDD_CFI_MYCFIA.md`](ANALYSE_ARCHITECTURE_BDD_CFI_MYCFIA.md)

**Contenu** :
- 🔍 Analyse détaillée des BDD existantes (CFI + MyCfia)
- 📋 Mapping complet des données CFI ↔ MyCfia
- 🏗️ Analyse comparative exhaustive des 5 options
- 🎯 Architecture Hybride détaillée (vues matérialisées, SFTP)
- 💻 Code d'implémentation Symfony (Entities, Repositories, Services, AI Tools)
- ⚠️ Risques identifiés et stratégies de mitigation
- 📊 KPIs et indicateurs de performance

**Durée lecture** : 45-60 minutes

**Sections principales** :
1. Contexte métier et workflow de génération de campagne
2. Analyse BDD CFI (Script.sql) et BDD MyCfia (Bundle Gorillas)
3. Mapping des données et flux (Lecture CFI → MyCfia, Écriture MyCfia → CFI)
4. Comparaison 5 options (API Pure, BDD Commune, Hybride, Event-Driven, SFTP)
5. Décision finale : Option 3 - Architecture Hybride (Score 88/100)
6. Implémentation technique complète (Doctrine, AI Tools, SFTP)
7. Risques et mitigations
8. Responsabilités CFI vs MyCfia

---

### 3. 📐 Schémas Architecture (Diagrammes Mermaid)

**Fichier** : [`SCHEMA_ARCHITECTURE_HYBRIDE.md`](SCHEMA_ARCHITECTURE_HYBRIDE.md)

**Contenu** :
- 📐 Vue d'ensemble système (BDD CFI, BDD Commune, MyCfia, SFTP)
- 🔄 Flux de lecture (CFI → MyCfia) avec séquence détaillée
- 📤 Flux d'écriture (MyCfia → CFI) avec séquence SFTP
- 🏗️ Architecture technique MyCfia (Multi-Database Doctrine)
- 🔐 Architecture sécurité & multi-tenancy (cascade descendante)
- 📊 Synchronisation BDD Commune CFI (vues matérialisées + jobs)
- 🛡️ Résilience & Fallback (scénarios normal, dégradé, critique)
- 📈 Matrice décisionnelle visuelle (comparaison options)
- 🔧 Diagramme responsabilités CFI vs MyCfia

**Durée lecture** : 15-20 minutes

**Format** : Diagrammes Mermaid (compatibles GitHub, GitLab, VS Code Preview)

---

## 🎯 Recommandation Finale

### Option Retenue : Architecture Hybride (Option 3)

**Score global** : **88/100** 🏆

#### Principe

```
BDD CFI (Production) ← Responsabilité CFI
      ↓ Sync auto (5-10min, responsabilité CFI)
BDD Commune CFI (Cache read-only) ← Responsabilité CFI
      ↓ Lecture rapide (10-50ms, responsabilité MyCfia)
MyCfia Backend (Symfony 7.3) ← Responsabilité MyCfia
      ↓ Écriture SFTP (async, responsabilité MyCfia)
SFTP CFI ← Responsabilité CFI
      ↓ Batch import (5min, responsabilité CFI)
BDD CFI (Production) ← Responsabilité CFI
```

#### Avantages Clés

✅ **Performance** : Lecture 10-50ms (vs 100-200ms API pure)
✅ **Scalabilité** : Indépendance totale MyCfia/CFI
✅ **Sécurité** : Isolation + lecture seule + cascade descendante
✅ **Résilience** : Fallback automatique API CFI
✅ **Couplage minimal** : Contrat d'interface clair (vues matérialisées)

---

## 🔧 Responsabilités d'Implémentation

### CFI (Infrastructure & BDD Commune)

- Créer BDD Commune CFI (SQL Server)
- Configurer vues matérialisées (stocks, opérations, factures, campagnes)
- Configurer synchronisation automatique (SQL Server Agent jobs)
- Créer compte `mycfia_readonly` avec permissions lecture seule
- Configurer serveur SFTP pour import campagnes
- Batch import SFTP (toutes les 5min)

### MyCfia (Intégration Applicative)

- Configurer Entity Managers Doctrine (mycfia + cfi_common)
- Créer Entities CfiCommon (StockReadonly, OperationReadonly, FactureReadonly, CampagneReadonly)
- Créer AI Tools (CfiStocksTool, CfiOperationsTool, CfiFacturesTool, CfiCampagnesTool)
- Créer CampaignExportService (export JSON/CSV vers SFTP CFI)
- Implémenter fallback automatique API CFI
- Tests unitaires + intégration + E2E

---

## 📊 Comparaison Options (Résumé)

| Option | Performance | Scalabilité | Sécurité | Couplage | Score | Recommandation |
|--------|-------------|-------------|----------|----------|-------|----------------|
| **1. API Pure** | 70/100 | 95/100 | 95/100 | 95/100 | **72/100** | ✓ Acceptable |
| **2. BDD Commune Unique** | 90/100 | 50/100 | 55/100 | 30/100 | **58/100** | ❌ Non recommandé |
| **3. Hybride** | 85/100 | 95/100 | 90/100 | 85/100 | **88/100** | ✅ **RECOMMANDÉ** |
| **4. Event-Driven** | 80/100 | 95/100 | 85/100 | 90/100 | **76/100** | ✓ Acceptable |
| **5. SFTP Pure** | 55/100 | 70/100 | 75/100 | 90/100 | **69/100** | ✓ Acceptable |

---

## 📈 KPIs de Succès

### Performance

- **Latence lecture BDD Commune** : < 50ms (cible)
- **Latence export SFTP** : < 5min (cible)
- **Taux succès export SFTP** : > 99% (cible)
- **Fraîcheur données** : < 5min (cible)

### Disponibilité

- **Uptime BDD Commune CFI** : > 99.5% (cible)
- **Uptime SFTP CFI** : > 99% (cible)
- **Taux succès fallback API CFI** : > 95% (cible)

### Satisfaction Utilisateur

- **Temps réponse Chat IA perçu** : < 1s (cible)
- **Taux succès export campagnes** : > 98% (cible)
- **NPS (Net Promoter Score)** : > 8/10 (cible)

---

## 🚨 Risques Principaux & Mitigations

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| **Panne BDD Commune CFI** | Faible (< 5%) | Moyen | Fallback automatique API CFI + Alertes monitoring + SLA 99.5% |
| **Échec Export SFTP** | Moyenne (5-10%) | Élevé | Retry automatique (3×) + Validation JSON Schema + Alertes admin |
| **Désynchronisation données** | Faible (< 2%) | Moyen | Monitoring latence sync + Affichage "Dernière MAJ" UI + Bouton "Rafraîchir" |
| **Complexité gestion 3 BDD** | Élevée | Moyen | Documentation claire + Scripts automatisés + Entity Managers séparés Doctrine |

---

## 🔗 Références Complémentaires

### Analyses Existantes

- [`Script.sql`](../Analyses/Script.sql) : Schéma BDD CFI (15 tables)
- [`SCHEMA_UML_BDD.md`](../Analyses/SCHEMA_UML_BDD.md) : Schéma BDD MyCfia Bundle Gorillas (13 tables)
- [`LISTING_TABLES_BDD.md`](../Analyses/LISTING_TABLES_BDD.md) : Détail tables Bundle Gorillas
- [`Règles Global ASSETS - MyCFiA.pdf`](../Analyses/Règles%20Global%20ASSETS%20-%20MyCFiA.pdf) : Expression besoin CFI (Assets)

### Standards Techniques

- **Doctrine ORM** : [doctrine-project.org](https://www.doctrine-project.org/)
- **Symfony Multi-Database** : [symfony.com/doc/current/doctrine/multiple_entity_managers.html](https://symfony.com/doc/current/doctrine/multiple_entity_managers.html)
- **Symfony AI Bundle** : [symfony.com/bundles/AIBundle](https://symfony.com/bundles/AIBundle)

---

## 💬 Questions Fréquentes (FAQ)

### 1. Pourquoi pas une BDD commune unique (Option 2) ?

**Réponse** : Couplage fort (score 30/100), scalabilité limitée (50/100), sécurité réduite (55/100). Score global **58/100** vs **88/100** pour l'Architecture Hybride. Le couplage entre les schémas rend les évolutions très difficiles et crée un point unique de défaillance.

### 2. Quelle est la latence acceptable pour la synchronisation BDD Commune ?

**Réponse** : **5-10 minutes maximum**. Pour des données de lecture (stocks, opérations, factures), cette latence est acceptable. L'UI affiche "Dernière MAJ il y a X min" pour transparence. Si données critiques temps réel nécessaires, fallback API CFI automatique.

### 3. Que se passe-t-il si la BDD Commune CFI tombe en panne ?

**Réponse** : **Fallback automatique vers API CFI** (responsabilité MyCfia). Le code détecte l'exception et bascule automatiquement sur l'API CFI Swagger. Latence passe de 40ms → 150ms (acceptable). Alertes monitoring déclenchées. SLA BDD Commune : 99.5% uptime, restauration sous 1h.

### 4. Comment garantir la sécurité multi-tenancy ?

**Réponse** : **Cascade descendante + Filtrage systématique** (responsabilité MyCfia). Toutes les requêtes incluent `WHERE tenant_id = ?`. Utilisateur niveau N voit uniquement N, N+1, N+2... (descendants). Compte `mycfia_readonly` a uniquement permissions SELECT (pas d'écriture directe BDD Commune).

### 5. Pourquoi SFTP pour l'export de campagnes ?

**Réponse** : **Standard CFI existant** (Avanci utilise déjà SFTP). Format fichiers JSON/CSV facilement validables. Retry automatique en cas d'échec. Traçabilité complète (fichiers archivés). Batch import CFI robuste (toutes les 5min).

### 6. Qui est responsable de quoi ?

**Réponse** :
- **CFI** : Création et maintenance de la BDD Commune (vues matérialisées, synchronisation, SFTP)
- **MyCfia** : Intégration applicative (Entity Managers, AI Tools, Services, fallback automatique)

---

## ✅ Prochaines Actions

### Validation Requise

**Décision à prendre** : Valider Option 3 - Architecture Hybride

**Contact** : System Architect (Claude Code)

### Implémentation par Phases

**Phase 1 : Infrastructure BDD Commune CFI (Responsabilité CFI)**
- Créer BDD Commune CFI (SQL Server)
- Configurer vues matérialisées
- Configurer SQL Server Agent jobs
- Créer compte `mycfia_readonly`
- Configurer SFTP server

**Phase 2 : Intégration Applicative MyCfia (Responsabilité MyCfia)**
- Configurer Entity Managers Doctrine
- Créer Entities CfiCommon
- Créer AI Tools
- Créer CampaignExportService
- Implémenter fallback automatique

**Phase 3 : Tests et Monitoring (Responsabilités Partagées)**
- Tests unitaires + intégration + E2E (MyCfia)
- Monitoring + Alertes (CFI + MyCfia)
- Documentation technique (MyCfia)

---

**Pour toute question** : Consulter les documents détaillés.

---

**Bonne lecture ! 📚**
