# RÉPARTITION DES PROBLÈMES : BUNDLE vs APPLICATION CLIENTE

**Date** : 2025-11-19
**Source** : Synthèse analyse complète de la campagne HEC Digital Masters

---

## 1. Vue d'ensemble

| Responsabilité | Nombre de problèmes | Priorité moyenne |
|----------------|---------------------|------------------|
| **Bundle** | 9 | CRITIQUE à IMPORTANT |
| **Application Cliente** | 3 | CRITIQUE à IMPORTANT |
| **Partagée** | 1 | IMPORTANT |

---

## 2. Problèmes du BUNDLE (à corriger dans gorillias-marketing-bundle)

### 🔴 CRITIQUE

| # | Problème | Description | Fichiers concernés | Action |
|---|----------|-------------|-------------------|--------|
| 1 | **Contraintes caractères non respectées** | LLM génère headlines >30 chars et descriptions >90 chars | `templates/prompts/assets/*.md.twig` | Ajouter contraintes strictes dans templates |
| 2 | **JSON parsing failed (Bing Ads)** | Erreur de syntaxe JSON, variations inaccessibles | `src/Agent/ContentCreatorAgent.php` | Ajouter try-catch avec log du JSON brut |
| 3 | **Format JSON incompatible** | LLM retourne `content/metadata/variations` au lieu de `post_text/hook/cta` | `templates/prompts/assets/*.md.twig` | Aligner format de sortie avec DTOs attendus |

### 🟡 IMPORTANT

| # | Problème | Description | Fichiers concernés | Action |
|---|----------|-------------|-------------------|--------|
| 4 | **Longueur contenu excessive** | Captions 5-7× plus longues que les limites optimales | `templates/prompts/assets/*.md.twig` | Imposer contraintes longueur dans prompts |
| 5 | **Emojis dans le contenu** | LLM génère des emojis non acceptables | `templates/prompts/assets/*.md.twig` + `src/AssetBuilder/AbstractAssetBuilder.php` | Interdire dans prompts + filtrer post-génération |
| 6 | **Markdown non supporté** | `**gras**` ne rend pas sur plateformes sociales | `templates/prompts/assets/*.md.twig` | Interdire Markdown, utiliser MAJUSCULES |
| 7 | **Dates hardcodées** | "2024", "15 mars 2024" dans outputs | `templates/prompts/assets/*.md.twig` | Dynamiser avec variables Twig |
| 8 | **Placeholders non résolus** | `[Date]`, `[Lien]` dans contenu généré | `templates/prompts/assets/*.md.twig` | Interdire placeholders dans instructions |
| 9 | **Génération d'images manquante** | Assets visuels sans images générées | Nouveau : `src/Agent/ImageGeneratorAgent.php` | Créer ImageGeneratorAgent avec templates Twig |

### 🟢 MINEUR

| # | Problème | Description | Fichiers concernés | Action |
|---|----------|-------------|-------------------|--------|
| 10 | **Structured Output non utilisé** | Parsing JSON manuel moins fiable | `src/Agent/ContentCreatorAgent.php` | Activer Structured Output avec DTOs |
| 11 | **URLs fictives** | URLs non fonctionnelles dans outputs | `templates/prompts/assets/*.md.twig` | Utiliser placeholders ou variables |
| 12 | **Hashtags > max** | LinkedIn génère 8 hashtags vs 5 max | `templates/prompts/assets/linkedin_post.md.twig` | Limiter à 5 dans instructions |

---

## 3. Problèmes de l'APPLICATION CLIENTE (à corriger dans l'app qui consomme le bundle)

### 🔴 CRITIQUE

| # | Problème | Description | Correction requise | Action |
|---|----------|-------------|-------------------|--------|
| 1 | **Bug "mail" vs "email"** | L'application envoie `mail` au lieu de `email` | Code d'appel au bundle | Mapper `mail` → `email` avant envoi |

### 🟡 IMPORTANT

| # | Problème | Description | Correction requise | Action |
|---|----------|-------------|-------------------|--------|
| 2 | **Données projet incomplètes** | Certaines données optionnelles manquantes | Formulaire/validation projet | Enrichir les données envoyées au bundle |
| 3 | **Gestion des erreurs** | Pas de retry ou fallback en cas d'échec | Code d'orchestration | Implémenter retry logic et error handling |

---

## 4. Problèmes PARTAGÉS (coordination bundle + application cliente)

### 🟡 IMPORTANT

| # | Problème | Description | Responsabilité Bundle | Responsabilité App Cliente |
|---|----------|-------------|----------------------|---------------------------|
| 1 | **Target CPA non aligné** | 90€ arbitraire vs 101€ calculé par BudgetOptimizer | Propager CPL calculé dans contexte assets | Transmettre les données BudgetOptimizer aux étapes suivantes |

---

## 5. Détail des corrections BUNDLE

### Correction 1 : Contraintes caractères (CRITIQUE)

**Fichiers à modifier** : Tous les templates dans `templates/prompts/assets/`

**Exemple pour Google Ads** (`google_ads.md.twig`) :

```twig
## CONTRAINTES OBLIGATOIRES - À RESPECTER IMPÉRATIVEMENT

### Limites de caractères STRICTES

- **Headlines** : EXACTEMENT 30 caractères MAXIMUM par headline
  - Compter chaque caractère y compris espaces et ponctuation
  - Si un headline dépasse 30 caractères, le raccourcir
  - Exemples valides : "Formation HEC 89 jours" (22 chars) ✓
  - Exemples invalides : "Devenez expert digital en 89 jours avec HEC" (43 chars) ✗

- **Descriptions** : EXACTEMENT 90 caractères MAXIMUM par description
  - Compter chaque caractère y compris espaces et ponctuation
  - Si une description dépasse 90 caractères, la raccourcir

### Vérification obligatoire

Avant de générer le JSON final, VÉRIFIER que :
1. Chaque headline fait ≤ 30 caractères
2. Chaque description fait ≤ 90 caractères
3. Raccourcir tout élément qui dépasse
```

### Correction 2 : JSON parsing (CRITIQUE)

**Fichier** : `src/Agent/ContentCreatorAgent.php`

```php
protected function parseJsonResponse(string $content): array
{
    try {
        $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        return $parsed;
    } catch (\JsonException $e) {
        $this->logger->error('JSON parsing failed', [
            'error' => $e->getMessage(),
            'error_position' => $e->getCode(),
            'raw_json_start' => substr($content, 0, 500),
            'raw_json_end' => substr($content, -500),
        ]);

        // Tentative de nettoyage
        $cleaned = $this->cleanJsonString($content);

        try {
            return json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e2) {
            $this->logger->critical('JSON parsing failed after cleanup', [
                'original_error' => $e->getMessage(),
                'cleanup_error' => $e2->getMessage(),
            ]);

            // Retourner structure minimale
            return [
                'content' => $content,
                'parse_error' => true,
                'error_message' => $e->getMessage(),
            ];
        }
    }
}

private function cleanJsonString(string $json): string
{
    // Supprimer BOM et caractères invisibles
    $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);

    // Échapper les retours à la ligne dans les strings
    $json = preg_replace('/(?<!\\\\)\\n/', '\\n', $json);

    // Supprimer les virgules trailing
    $json = preg_replace('/,(\s*[}\]])/', '$1', $json);

    return $json;
}
```

### Correction 3 : Format JSON (CRITIQUE)

**Fichier** : `templates/prompts/assets/linkedin_post.md.twig`

```twig
## FORMAT DE SORTIE JSON

Retourner EXACTEMENT cette structure JSON (pas d'autre format) :

```json
{
  "post_text": "Le texte du post LinkedIn (150-300 caractères)",
  "hook": "La première phrase accrocheuse",
  "main_insight": "L'insight principal ou la statistique clé",
  "cta": "L'appel à l'action",
  "hashtags": ["hashtag1", "hashtag2", "hashtag3"],
  "thought_leadership_angle": "L'angle d'expertise",
  "target_audience": "L'audience ciblée",
  "discussion_prompt": "La question pour générer des commentaires",
  "variations": [
    {
      "post_text": "...",
      "tone": "urgent"
    }
  ]
}
```

NE PAS utiliser la structure content/metadata/variations.
```

### Correction 4 : Interdire emojis (IMPORTANT)

**Fichier** : `templates/prompts/partials/_format_rules.md.twig` (créer un partial réutilisable)

```twig
## RÈGLES DE FORMAT STRICTES

### Interdictions absolues

1. **PAS D'EMOJIS** : Ne jamais utiliser d'emojis (🚀, ✅, 💡, 📊, etc.)
   - Les emojis ne sont pas acceptables pour des assets marketing professionnels
   - Utiliser uniquement du texte et de la ponctuation standard

2. **PAS DE MARKDOWN** : Ne pas utiliser de syntaxe Markdown
   - Pas de `**gras**` ou `*italique*`
   - Utiliser des MAJUSCULES pour l'emphase si nécessaire

3. **PAS DE PLACEHOLDERS** : Ne pas utiliser de placeholders non résolus
   - Pas de `[Date]`, `[Lien]`, `[Nom]`
   - Utiliser les variables fournies ou générer des valeurs réalistes
```

**Fichier** : `src/AssetBuilder/AbstractAssetBuilder.php`

```php
protected function sanitizeContent(string $content): string
{
    // Supprimer tous les emojis
    $content = $this->removeEmojis($content);

    // Supprimer le Markdown
    $content = $this->removeMarkdown($content);

    return $content;
}

private function removeEmojis(string $content): string
{
    // Pattern pour tous les emojis Unicode
    $emojiPattern = '/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{1F900}-\x{1F9FF}]|[\x{1FA00}-\x{1FA6F}]/u';

    return preg_replace($emojiPattern, '', $content);
}

private function removeMarkdown(string $content): string
{
    // Supprimer **gras**
    $content = preg_replace('/\*\*([^*]+)\*\*/', '$1', $content);

    // Supprimer *italique*
    $content = preg_replace('/\*([^*]+)\*/', '$1', $content);

    return $content;
}
```

---

## 6. Détail des corrections APPLICATION CLIENTE

### Correction 1 : Bug "mail" vs "email" (CRITIQUE)

**Localisation** : Code qui appelle le bundle pour générer les assets

```php
// AVANT (problématique)
$assetTypes = ['google_ads', 'linkedin_post', 'mail', 'instagram_post'];

// APRÈS (corrigé)
$assetTypes = array_map(function($type) {
    return $type === 'mail' ? 'email' : $type;
}, $assetTypes);

// Ou utiliser une constante/enum
use Gorillias\MarketingBundle\Enum\AssetTypeEnum;

$assetTypes = [
    AssetTypeEnum::GOOGLE_ADS->value,
    AssetTypeEnum::LINKEDIN_POST->value,
    AssetTypeEnum::EMAIL->value,  // Utiliser 'email' pas 'mail'
    AssetTypeEnum::INSTAGRAM_POST->value,
];
```

### Correction 2 : Données projet incomplètes (IMPORTANT)

**Recommandation** : Enrichir les données envoyées au bundle

```php
// Structure projet recommandée
$project = [
    // Obligatoires
    'company_name' => 'HEC Executive Education',
    'sector' => 'Education',
    'goal_type' => 'conversion',
    'budget' => 50000,

    // Recommandés pour meilleure qualité
    'website_url' => 'https://executive.hec.edu',
    'campaign_start_date' => new \DateTime('+1 month'),
    'campaign_end_date' => new \DateTime('+3 months'),
    'target_locations' => ['France', 'Belgique', 'Suisse'],
    'language' => 'fr',

    // Pour personnalisation
    'brand_colors' => ['#002F5B', '#0069A9'],
    'brand_tone' => 'professional',
    'competitors' => ['ESSEC', 'INSEAD', 'emlyon'],
];
```

### Correction 3 : Gestion des erreurs (IMPORTANT)

```php
// Implémenter retry logic
$maxRetries = 3;
$retryDelay = 1000; // ms

for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
    try {
        $asset = $assetBuilder->build($strategy, $project);
        break; // Succès, sortir de la boucle
    } catch (JsonParseException $e) {
        $this->logger->warning("Tentative {$attempt}/{$maxRetries} échouée", [
            'error' => $e->getMessage(),
            'asset_type' => $assetType,
        ]);

        if ($attempt === $maxRetries) {
            // Fallback ou alerte
            $this->notifyAdmins("Échec génération asset après {$maxRetries} tentatives");
            throw $e;
        }

        usleep($retryDelay * 1000 * $attempt); // Backoff exponentiel
    }
}
```

---

## 7. Planning de correction recommandé

### Phase 1 : Corrections BUNDLE (Semaine 1-2)

| Jour | Action | Priorité | Effort |
|------|--------|----------|--------|
| J1 | Ajouter contraintes caractères dans tous les templates | 🔴 | 2h |
| J1 | Ajouter partial `_format_rules.md.twig` | 🟡 | 1h |
| J2 | Corriger JSON parsing avec cleanup | 🔴 | 3h |
| J2 | Ajouter sanitizeContent dans AbstractAssetBuilder | 🟡 | 2h |
| J3 | Aligner format JSON dans templates | 🔴 | 4h |
| J4 | Dynamiser dates et supprimer placeholders | 🟡 | 2h |
| J5 | Tests unitaires des corrections | - | 4h |

### Phase 2 : Corrections APPLICATION CLIENTE (Semaine 2)

| Jour | Action | Priorité | Effort |
|------|--------|----------|--------|
| J1 | Corriger mapping "mail" → "email" | 🔴 | 30min |
| J2 | Enrichir données projet | 🟡 | 2h |
| J3 | Implémenter retry logic | 🟡 | 3h |
| J4 | Tests d'intégration | - | 4h |

### Phase 3 : Nouvelles fonctionnalités (Semaine 3+)

| Action | Priorité | Effort |
|--------|----------|--------|
| Créer ImageGeneratorAgent | 🟡 | 8h |
| Templates Twig pour ImageGenerator | 🟡 | 4h |
| Intégration dans AssetBuilders | 🟡 | 4h |
| Tests | - | 4h |

---

## 8. Checklist de validation

### Bundle

- [ ] Tous les templates ont les contraintes caractères
- [ ] Partial `_format_rules.md.twig` inclus dans tous les templates assets
- [ ] JSON parsing avec try-catch et cleanup
- [ ] `sanitizeContent()` appelé dans tous les AssetBuilders
- [ ] Format JSON aligné avec DTOs attendus
- [ ] Dates dynamiques avec variables Twig
- [ ] Pas de placeholders `[...]` dans les instructions
- [ ] Tests unitaires passent

### Application Cliente

- [ ] Mapping "mail" → "email" en place
- [ ] Données projet complètes
- [ ] Retry logic implémenté
- [ ] Error handling avec notifications
- [ ] Tests d'intégration passent

---

## 9. Métriques de succès post-corrections

| Métrique | Avant | Après (cible) |
|----------|-------|---------------|
| Assets publiables | 17% | 95% |
| JSON parsing réussi | 83% | 100% |
| Conformité caractères | 0% | 100% |
| Emojis dans contenu | Présents | 0% |
| Placeholders résolus | 0% | 100% |

---

*Document généré le 2025-11-19*
*Basé sur l'analyse de la campagne HEC Digital Masters*

