# Guide d'Analyse des Logs KPI - myCfia

## 📊 Vue d'Ensemble

Ce guide explique comment analyser les **KPIs de performance** (temps de réponse) pour les endpoints API CFI et les outils IA de myCfia.

### Métadonnées Disponibles

Tous les logs contiennent maintenant :
- **`duration_ms`** : Temps de réponse en millisecondes
- **`cache_status`** : État du cache (`HIT` ou `MISS`)
- **Contexte métier** : `id_division`, `nb_operations`, `tool_name`, etc.

### Canaux de Logs

Les KPIs sont disponibles dans ces canaux Monolog :
- **`api_services`** : Services API CFI (Facturation, Opérations, Stocks, États)
- **`tools`** : Outils IA (get_factures, get_operations, get_stocks, etc.)

---

## 🔍 Analyses Rapides avec grep/awk

### 1. Temps de Réponse Moyen par Service

#### ApiServices - FacturationApiService

```bash
# Moyenne des temps de réponse (tous statuts cache)
grep "FacturationApiService" app/var/log/symfony/api_services-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Moyenne:", sum/count, "ms"}'

# Exemple de sortie :
# Moyenne: 245.8 ms
```

#### ApiServices - OperationApiService

```bash
grep "OperationApiService" app/var/log/symfony/api_services-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Moyenne:", sum/count, "ms"}'
```

#### ApiServices - StockApiService

```bash
grep "StockApiService" app/var/log/symfony/api_services-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Moyenne:", sum/count, "ms"}'
```

#### ApiServices - EtatOperationApiService

```bash
grep "EtatOperationApiService" app/var/log/symfony/api_services-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Moyenne:", sum/count, "ms"}'
```

### 2. Impact du Cache (HIT vs MISS)

#### Cache MISS - Temps avec appel API CFI

```bash
# FacturationApiService - Temps moyen en cache MISS
grep "FacturationApiService" app/var/log/symfony/api_services-*.log | \
  grep "cache_status\":\"MISS" | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Cache MISS - Moyenne:", sum/count, "ms"}'
```

#### Cache HIT - Temps sans appel API

```bash
# FacturationApiService - Temps moyen en cache HIT
grep "FacturationApiService" app/var/log/symfony/api_services-*.log | \
  grep "cache_status\":\"HIT" | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Cache HIT - Moyenne:", sum/count, "ms"}'
```

#### Taux de Cache HIT (%)

```bash
# Calculer le taux de cache HIT pour FacturationApiService
TOTAL=$(grep "FacturationApiService" app/var/log/symfony/api_services-*.log | grep "cache_status" | wc -l)
HITS=$(grep "FacturationApiService" app/var/log/symfony/api_services-*.log | grep "cache_status\":\"HIT" | wc -l)

echo "Total requêtes: $TOTAL"
echo "Cache HIT: $HITS"
echo "Taux de cache HIT: $(awk "BEGIN {print ($HITS/$TOTAL)*100}")%"

# Exemple de sortie :
# Total requêtes: 150
# Cache HIT: 120
# Taux de cache HIT: 80%
```

### 3. Analyse des Outils IA

#### GetFacturesTool - Temps de Réponse

```bash
# Temps moyen du tool get_factures
grep "get_factures" app/var/log/symfony/tools-*.log | \
  grep "Tool executed successfully" | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Moyenne get_factures:", sum/count, "ms"}'
```

#### GetFacturesTool - Mode LISTE vs DÉTAIL

```bash
# Temps moyen en MODE LISTE
grep "get_factures" app/var/log/symfony/tools-*.log | \
  grep "mode\":\"LISTE" | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Mode LISTE - Moyenne:", sum/count, "ms"}'

# Temps moyen en MODE DÉTAIL
grep "get_factures" app/var/log/symfony/tools-*.log | \
  grep "mode\":\"DETAIL" | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Mode DÉTAIL - Moyenne:", sum/count, "ms"}'
```

### 4. Top 10 des Requêtes les Plus Lentes

#### ApiServices

```bash
# Top 10 requêtes les plus lentes (tous services API)
grep "duration_ms" app/var/log/symfony/api_services-*.log | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{print $1}' | \
  sort -nr | \
  head -10

# Exemple de sortie :
# 1245.3
# 987.6
# 845.2
# ...
```

#### Outils IA

```bash
# Top 10 tools les plus lents
grep "Tool executed successfully" app/var/log/symfony/tools-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{print $1}' | \
  sort -nr | \
  head -10
```

### 5. Analyse par Période (Aujourd'hui)

```bash
# Logs d'aujourd'hui uniquement
TODAY=$(date +%Y-%m-%d)

# Temps moyen FacturationApiService aujourd'hui
grep "$TODAY" app/var/log/symfony/api_services-*.log | \
  grep "FacturationApiService" | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Moyenne aujourd'\''hui:", sum/count, "ms"}'
```

### 6. Analyse des Erreurs avec Temps de Réponse

```bash
# Erreurs avec leur temps de réponse
grep "Tool execution failed" app/var/log/symfony/tools-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Temps moyen des erreurs:", sum/count, "ms"}'
```

---

## 📈 Rapports Consolidés

### Rapport Complet : Performances par Service

Créez un script `analyse_kpi.sh` :

```bash
#!/bin/bash

echo "=== RAPPORT KPI myCfia ==="
echo "Date: $(date)"
echo ""

echo "--- ApiServices ---"

echo "FacturationApiService:"
MISS=$(grep "FacturationApiService" app/var/log/symfony/api_services-*.log | grep "cache_status\":\"MISS" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
HIT=$(grep "FacturationApiService" app/var/log/symfony/api_services-*.log | grep "cache_status\":\"HIT" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
echo "  Cache MISS: ${MISS} ms"
echo "  Cache HIT: ${HIT} ms"
echo ""

echo "OperationApiService:"
MISS=$(grep "OperationApiService" app/var/log/symfony/api_services-*.log | grep "cache_status\":\"MISS" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
HIT=$(grep "OperationApiService" app/var/log/symfony/api_services-*.log | grep "cache_status\":\"HIT" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
echo "  Cache MISS: ${MISS} ms"
echo "  Cache HIT: ${HIT} ms"
echo ""

echo "StockApiService:"
MISS=$(grep "StockApiService" app/var/log/symfony/api_services-*.log | grep "cache_status\":\"MISS" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
HIT=$(grep "StockApiService" app/var/log/symfony/api_services-*.log | grep "cache_status\":\"HIT" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
echo "  Cache MISS: ${MISS} ms"
echo "  Cache HIT: ${HIT} ms"
echo ""

echo "EtatOperationApiService:"
MISS=$(grep "EtatOperationApiService" app/var/log/symfony/api_services-*.log | grep "cache_status\":\"MISS" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
HIT=$(grep "EtatOperationApiService" app/var/log/symfony/api_services-*.log | grep "cache_status\":\"HIT" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
echo "  Cache MISS: ${MISS} ms"
echo "  Cache HIT: ${HIT} ms"
echo ""

echo "--- Outils IA ---"
echo "GetFacturesTool (MODE LISTE):"
LISTE=$(grep "get_factures" app/var/log/symfony/tools-*.log | grep "mode\":\"LISTE" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
echo "  Temps moyen: ${LISTE} ms"
echo ""

echo "GetFacturesTool (MODE DÉTAIL):"
DETAIL=$(grep "get_factures" app/var/log/symfony/tools-*.log | grep "mode\":\"DETAIL" | grep "duration_ms" | awk -F'"duration_ms":' '{print $2}' | awk -F',' '{sum+=$1; count++} END {print sum/count}')
echo "  Temps moyen: ${DETAIL} ms"
echo ""

echo "=== FIN RAPPORT ==="
```

Utilisation :

```bash
chmod +x analyse_kpi.sh
./analyse_kpi.sh
```

---

## 🔬 Analyses Avancées

### 1. Distribution des Temps de Réponse (Histogramme Texte)

```bash
# Distribution par tranche de 100ms
grep "FacturationApiService" app/var/log/symfony/api_services-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{
    bucket = int($1/100)*100;
    count[bucket]++;
  }
  END {
    for (b in count) {
      printf "%d-%dms: ", b, b+99;
      for (i=0; i<count[b]; i++) printf "█";
      printf " (%d)\n", count[b];
    }
  }' | sort -n

# Exemple de sortie :
# 0-99ms: ███████ (7)
# 100-199ms: ███████████████ (15)
# 200-299ms: ████████ (8)
# 300-399ms: ██ (2)
```

### 2. Percentiles (P50, P90, P95, P99)

```bash
# Calculer les percentiles pour FacturationApiService
grep "FacturationApiService" app/var/log/symfony/api_services-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{print $1}' | \
  sort -n > /tmp/durations.txt

TOTAL=$(wc -l < /tmp/durations.txt)
P50=$(awk -v total=$TOTAL 'NR==int(total*0.50)+1' /tmp/durations.txt)
P90=$(awk -v total=$TOTAL 'NR==int(total*0.90)+1' /tmp/durations.txt)
P95=$(awk -v total=$TOTAL 'NR==int(total*0.95)+1' /tmp/durations.txt)
P99=$(awk -v total=$TOTAL 'NR==int(total*0.99)+1' /tmp/durations.txt)

echo "P50 (médiane): $P50 ms"
echo "P90: $P90 ms"
echo "P95: $P95 ms"
echo "P99: $P99 ms"

rm /tmp/durations.txt
```

### 3. Tendances Temporelles (Par Heure)

```bash
# Temps moyen par heure pour aujourd'hui
TODAY=$(date +%Y-%m-%d)

for HOUR in {00..23}; do
  AVG=$(grep "$TODAY $HOUR:" app/var/log/symfony/api_services-*.log | \
    grep "FacturationApiService" | \
    grep "duration_ms" | \
    awk -F'"duration_ms":' '{print $2}' | \
    awk -F',' '{sum+=$1; count++} END {if(count>0) print sum/count; else print 0}')

  if [ "$AVG" != "0" ]; then
    printf "%s:00 - Moyenne: %.2f ms\n" "$HOUR" "$AVG"
  fi
done
```

---

## 🚨 Alertes et Seuils

### Seuils Recommandés

| Métrique | Seuil Acceptable | Seuil Critique |
|----------|------------------|----------------|
| **ApiService Cache HIT** | < 50 ms | > 100 ms |
| **ApiService Cache MISS** | < 500 ms | > 1000 ms |
| **Tool IA** | < 600 ms | > 1200 ms |
| **Taux Cache HIT** | > 60% | < 40% |

### Script de Détection d'Anomalies

```bash
#!/bin/bash

# Seuils
THRESHOLD_MISS=1000  # ms
THRESHOLD_HIT=100    # ms

echo "=== DÉTECTION D'ANOMALIES ==="

# Vérifier les requêtes trop lentes (Cache MISS)
SLOW_MISS=$(grep "cache_status\":\"MISS" app/var/log/symfony/api_services-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' -v threshold=$THRESHOLD_MISS '{if($1>threshold) count++} END {print count+0}')

echo "⚠️  Requêtes MISS > ${THRESHOLD_MISS}ms: $SLOW_MISS"

# Vérifier les requêtes trop lentes (Cache HIT)
SLOW_HIT=$(grep "cache_status\":\"HIT" app/var/log/symfony/api_services-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' -v threshold=$THRESHOLD_HIT '{if($1>threshold) count++} END {print count+0}')

echo "⚠️  Requêtes HIT > ${THRESHOLD_HIT}ms: $SLOW_HIT"

# Vérifier taux de cache HIT
TOTAL=$(grep "cache_status" app/var/log/symfony/api_services-*.log | wc -l)
HITS=$(grep "cache_status\":\"HIT" app/var/log/symfony/api_services-*.log | wc -l)

if [ $TOTAL -gt 0 ]; then
  HIT_RATE=$(awk "BEGIN {print ($HITS/$TOTAL)*100}")
  echo "📊 Taux de cache HIT: ${HIT_RATE}%"

  if (( $(awk "BEGIN {print ($HIT_RATE < 60)}") )); then
    echo "🚨 ALERTE: Taux de cache HIT faible (< 60%)"
  fi
fi
```

---

## 📊 Métriques Clés à Surveiller

### 1. Performance Globale
- **Temps moyen de réponse** par service
- **Distribution des temps** (P50, P90, P95, P99)
- **Requêtes lentes** (> seuils)

### 2. Efficacité du Cache
- **Taux de cache HIT** (objectif : > 70%)
- **Gain de performance** (HIT vs MISS)
- **Fréquence des invalidations**

### 3. Volumétrie
- **Nombre de requêtes** par service
- **Nombre d'appels IA** par outil
- **Erreurs** par service

### 4. Tendances
- **Évolution temporelle** (par heure, par jour)
- **Pics d'activité**
- **Corrélation charge/performance**

---

## 🔧 Intégration Future (Post-Sprint S1)

### Option 2 : PerformanceMonitoringService
- Service centralisé pour enregistrer et analyser les métriques
- Agrégation automatique en temps réel
- API REST pour dashboard

### Option 3 : APM Externe (Grafana OSS)
- **Grafana OSS** : Visualisation gratuite et open-source
- **Prometheus** : Collecte des métriques
- **Loki** : Agrégation de logs
- Dashboards temps réel avec alertes

---

## 💡 Exemples de Cas d'Usage

### Cas 1 : Identifier un Service Lent

```bash
# Comparer tous les services
for service in FacturationApiService OperationApiService StockApiService EtatOperationApiService; do
  AVG=$(grep "$service" app/var/log/symfony/api_services-*.log | \
    grep "cache_status\":\"MISS" | \
    grep "duration_ms" | \
    awk -F'"duration_ms":' '{print $2}' | \
    awk -F',' '{sum+=$1; count++} END {print sum/count}')
  echo "$service (MISS): $AVG ms"
done | sort -t: -k2 -nr
```

### Cas 2 : Vérifier l'Impact d'une Optimisation

```bash
# Avant optimisation
grep "2025-01-30" app/var/log/symfony/api_services-*.log | \
  grep "FacturationApiService" | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Avant:", sum/count, "ms"}'

# Après optimisation
grep "2025-01-31" app/var/log/symfony/api_services-*.log | \
  grep "FacturationApiService" | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++} END {print "Après:", sum/count, "ms"}'
```

### Cas 3 : Analyser un Pic de Charge

```bash
# Analyser une période spécifique (ex: 14h-15h)
grep "2025-01-30 14:" app/var/log/symfony/api_services-*.log | \
  grep "duration_ms" | \
  awk -F'"duration_ms":' '{print $2}' | \
  awk -F',' '{sum+=$1; count++; if($1>max) max=$1} END {
    print "Requêtes:", count;
    print "Moyenne:", sum/count, "ms";
    print "Max:", max, "ms"
  }'
```

---

## 📁 Emplacements des Logs

### Logs Symfony
```
app/var/log/symfony/
├── api_services-YYYY-MM-DD.log   # ApiServices (Facturation, Opérations, Stocks, États)
├── tools-YYYY-MM-DD.log          # Outils IA (get_factures, get_operations, etc.)
├── dev-YYYY-MM-DD.log            # Logs généraux développement
└── prod-YYYY-MM-DD.log           # Logs généraux production
```

### Rotation des Logs
- **1 fichier par jour** (automatique via Monolog)
- **Rétention** : 30 jours par défaut
- **Format** : JSON structuré

---

## ✅ Checklist de Monitoring Régulier

- [ ] **Quotidien** : Temps moyens par service (rapport consolidé)
- [ ] **Quotidien** : Taux de cache HIT global
- [ ] **Quotidien** : Top 10 requêtes lentes
- [ ] **Hebdomadaire** : Distribution des temps (percentiles)
- [ ] **Hebdomadaire** : Tendances temporelles (pics, creux)
- [ ] **Mensuel** : Évolution des performances (comparaison mois à mois)
- [ ] **Avant/Après déploiement** : Impact des optimisations

---

## 🆘 Dépannage

### Pas de Logs KPI ?

```bash
# Vérifier que les logs sont bien générés
ls -lh app/var/log/symfony/

# Vérifier les permissions
docker compose exec frankenphp ls -lh var/log/symfony/

# Vérifier la configuration Monolog
docker compose exec frankenphp php bin/console debug:config monolog
```

### Résultats Vides avec grep ?

```bash
# Vérifier le format JSON des logs
tail -20 app/var/log/symfony/api_services-$(date +%Y-%m-%d).log

# Adapter les patterns grep si besoin
grep -E "duration_ms|cache_status" app/var/log/symfony/api_services-*.log
```

---

**Créé le** : 2025-01-30
**Auteur** : Context Engineering
**Version** : 1.0.0 - Sprint S1
