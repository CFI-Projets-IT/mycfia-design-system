#!/bin/bash
# Script de surveillance des logs d'enrichissement Marketing AI Bundle
# Utilisation : ./scripts/watch-enrichment-logs.sh

echo "🔍 Surveillance des logs d'enrichissement en temps réel"
echo "========================================================"
echo ""
echo "📋 Fichiers surveillés :"
echo "  - project_enrichment (Agent IA)"
echo "  - brand_style (Analyse URL)"
echo "  - project_context (Contexte projet)"
echo "  - firecrawl (Scraping web)"
echo "  - task_execution (Exécution tâches)"
echo ""
echo "👉 Lancez un enrichissement depuis l'interface web maintenant..."
echo ""

# Obtenir la date du jour pour les fichiers de log avec suffixe
TODAY=$(date +%Y-%m-%d)

# Chemins des fichiers de log
PROJECT_ENRICHMENT="app/var/log/marketing/agents/project_enrichment-${TODAY}.log"
BRAND_STYLE="app/var/log/marketing/tools/brand_style-${TODAY}.log"
PROJECT_CONTEXT="app/var/log/marketing/tools/project_context-${TODAY}.log"
FIRECRAWL="app/var/log/marketing/clients/firecrawl-${TODAY}.log"
TASK_EXECUTION="app/var/log/marketing/tasks/task-${TODAY}.log"

# Créer les fichiers s'ils n'existent pas (pour éviter erreur tail)
touch "$PROJECT_ENRICHMENT" "$BRAND_STYLE" "$PROJECT_CONTEXT" "$FIRECRAWL" "$TASK_EXECUTION"

# Surveiller tous les fichiers en temps réel avec couleurs
tail -f \
  "$PROJECT_ENRICHMENT" \
  "$BRAND_STYLE" \
  "$PROJECT_CONTEXT" \
  "$FIRECRAWL" \
  "$TASK_EXECUTION" \
  2>/dev/null | while IFS= read -r line; do
    # Coloriser selon le type de log
    if [[ "$line" == *"project_enrichment"* ]]; then
        echo -e "\033[1;34m[PROJECT_ENRICHMENT]\033[0m $line"
    elif [[ "$line" == *"brand_style"* ]]; then
        echo -e "\033[1;32m[BRAND_STYLE]\033[0m $line"
    elif [[ "$line" == *"project_context"* ]]; then
        echo -e "\033[1;33m[PROJECT_CONTEXT]\033[0m $line"
    elif [[ "$line" == *"firecrawl"* ]]; then
        echo -e "\033[1;35m[FIRECRAWL]\033[0m $line"
    elif [[ "$line" == *"task"* ]]; then
        echo -e "\033[1;36m[TASK]\033[0m $line"
    else
        echo "$line"
    fi
done
