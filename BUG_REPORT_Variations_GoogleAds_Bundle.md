# 🐛 BUG REPORT - Incohérence Format Variations Google Ads

**Date** : 2025-12-03
**Bundle Version** : gorillias/marketing-ai-bundle v3.35.5
**Severity** : MEDIUM - Incohérence entre DTO et génération LLM
**Reporter** : Application cliente myCfia

---

## 📊 Symptômes

Le DTO `GoogleAdsAssetDTO` déclare que la propriété `$variations` est de type `array<string>`, mais le LLM génère en réalité un `array<object>`.

### Comportement Observé

#### DTO Déclaré

**Fichier** : `src/StructuredOutput/Asset/GoogleAdsAssetDTO.php`

```php
/**
 * @param array<string> $variations 2-3 variations alternatives des headlines/descriptions
 */
public function __construct(
    public array $headlines,
    public array $descriptions,
    public string $final_url,
    public string $display_path,
    public array $keywords,
    public string $target_audience,
    public array $unique_selling_points,
    public array $variations,  // ← Déclare array<string>
) {}
```

**Format Attendu** : `["variation 1", "variation 2"]`

---

#### LLM Génération Réelle

**Log** : `/var/log/marketing/agents/content-2025-12-03.log` (ligne 18)

```json
{
  "content": {
    "headlines": [
      "MBA Juriste d'Entreprise - Bac+5 100% En Ligne",
      "Devenez Juriste Stratège avec un MBA Reconnu",
      "Formation Juriste : Maîtrisez Droit & Legaltech",
      "Boostez Votre Carrière Juridique avec un MBA",
      "Juriste Pro 360° : Le MBA Qui Transforme Votre Métier"
    ],
    "descriptions": [
      "Acquérez une expertise stratégique en droit des affaires...",
      "Pilotez la fonction juridique avec un MBA 100% en ligne...",
      "Transformez votre carrière juridique avec un MBA spécialisé...",
      "Devenez le juriste incontournable de votre entreprise...",
      "Un MBA pour juristes qui veulent évoluer..."
    ],
    "display_url": "www.studi.com/formation/juridique/mba-juriste",
    "final_url": "https://www.studi.com/fr/formation/juridique/mba-juriste-dentreprise"
  },
  "format": "google_ads",
  "metadata": {...},
  "variations": [
    {
      "headlines": [
        "MBA Juriste : La Formation Qui Change Tout",
        "Juriste d'Entreprise : Le MBA Qu'il Vous Faut",
        "Droit & Stratégie : Le MBA pour Juristes Ambition",
        "Legaltech & Droit : Maîtrisez l'Avenir Juridique",
        "Un MBA pour Devenir le Juriste de Demain"
      ],
      "descriptions": [
        "Formation en ligne Bac+5 pour juristes en quête d'excellence...",
        "Le MBA qui donne une dimension stratégique à votre carrière...",
        "Devenez un expert du droit des sociétés et des nouvelles technologies...",
        "Un programme complet pour juristes souhaitant évoluer...",
        "Anticipez les évolutions du droit et des legaltechs..."
      ]
    },
    {
      "headlines": [
        "Formation Juriste : Le MBA Qui Fait la Différence",
        "MBA Droit : Pour Juristes Stratèges et Innovants",
        "Devenez un Juriste 360° avec ce MBA en Ligne",
        "Droit des Affaires & Legaltech : Le MBA Ultime",
        "Un MBA pour Juristes en Quête d'Excellence"
      ],
      "descriptions": [
        "Acquérez les compétences clés pour piloter la fonction juridique...",
        "Formation Bac+5 pour juristes souhaitant maîtriser les enjeux...",
        "Le MBA qui vous prépare aux défis juridiques de demain...",
        "Un parcours complet pour devenir un juriste polyvalent...",
        "Transformez votre expertise juridique avec un MBA reconnu..."
      ]
    }
  ]
}
```

**Format Réel** : `[{"headlines": [...], "descriptions": [...]}, {...}]` ❌

---

## 🔍 ROOT CAUSE

### Incohérence DTO vs Prompt

Le DTO déclare `array<string>`, mais le **prompt template** ne force PAS ce format pour Google Ads.

**Prompt Template** : `templates/prompts/agents/content_creator_user.md.twig`

Le prompt demande probablement des variations structurées pour Google Ads (headlines + descriptions), ce qui est **logique métier** mais **incohérent avec le DTO**.

---

## 📊 Comparaison avec Autres Assets

| Asset Type | DTO Variations | LLM Génère | Conforme |
|------------|----------------|------------|----------|
| **LinkedIn Post** | `array<string>` | `["str1", "str2"]` | ✅ OUI |
| **Facebook Post** | `array<string>` | `["str1", "str2"]` | ✅ OUI |
| **Instagram Post** | `array<string>` | ? (non testé) | ? |
| **Google Ads** | `array<string>` | `[{obj1}, {obj2}]` | ❌ **NON** |
| **Bing Ads** | `array<string>` | ? (non testé) | ? |
| **Mail** | `array<string>` | ? (non testé) | ? |
| **IAB Banner** | `array<string>` | ? (non testé) | ? |

Seul **Google Ads** génère un format objet au lieu de strings.

---

## 💥 Impact

### Impact Technique

1. **Type Checking** : Le DTO ment sur le type réel
2. **Validation Symfony** : Si on ajoute `#[Assert\All([new Assert\Type('string')])]`, ça échouera
3. **Documentation** : Les développeurs se fient au DTO pour comprendre le format
4. **Maintenance** : Code client doit gérer 2 formats différents (strings ET objets)

### Impact sur l'Application Cliente

**Problème initial** : Les variations LinkedIn/Facebook ne s'affichaient pas dans l'UI.

**Root Cause UI** : Le template Twig supposait que **toutes** les variations étaient des objets (car Google Ads fonctionnait).

**Solution appliquée** : Modifier le template Twig pour gérer les 2 formats (strings ET objets).

```twig
{% if variation is iterable and variation is not string %}
    {# Format OBJET : Google Ads #}
    {% for key, value in variation %}
        <strong>{{ key }}:</strong> {{ value }}
    {% endfor %}
{% else %}
    {# Format STRING : LinkedIn, Facebook, etc. #}
    {{ variation }}
{% endif %}
```

**Inconvénient** : Complexité ajoutée dans l'UI pour gérer l'incohérence du bundle.

---

## 🛠️ Solutions Proposées

### Solution 1 : Corriger le DTO (RECOMMANDÉ)

**Refléter la réalité** : Changer le type du DTO pour correspondre à ce que le LLM génère.

**Fichier** : `src/StructuredOutput/Asset/GoogleAdsAssetDTO.php`

```php
/**
 * @param array<array{headlines: array<string>, descriptions: array<string>}> $variations 2-3 variations alternatives complètes (headlines + descriptions)
 */
public function __construct(
    public array $headlines,
    public array $descriptions,
    public string $final_url,
    public string $display_path,
    public array $keywords,
    public string $target_audience,
    public array $unique_selling_points,
    public array $variations,  // ← Type corrigé
) {}
```

**Avantages** :
- ✅ DTO reflète la réalité
- ✅ Type checking correct
- ✅ Documentation claire pour les développeurs
- ✅ Pas de changement du comportement LLM (déjà optimal)

**Inconvénient** :
- ⚠️ Google Ads reste le seul asset avec un format objet (mais c'est logique métier)

---

### Solution 2 : Uniformiser vers Strings

**Modifier le prompt** pour générer des strings au lieu d'objets.

**Fichier** : `templates/prompts/agents/content_creator_user.md.twig`

**Exemple de prompt Google Ads** :

```
"variations": [
  "Variation 1 : Headlines alternatifs + Descriptions alternatives (format texte concaténé)",
  "Variation 2 : Headlines alternatifs + Descriptions alternatives (format texte concaténé)"
]
```

**Avantages** :
- ✅ Cohérence avec tous les autres assets
- ✅ Pas de changement du DTO

**Inconvénients** :
- ❌ Perte de structure (headlines séparés des descriptions)
- ❌ Moins exploitable pour import direct dans Google Ads
- ❌ Dégradation de la qualité métier

**Verdict** : ❌ **NON RECOMMANDÉ** - Le format objet est meilleur pour Google Ads.

---

### Solution 3 : Ajouter Validation (COMPLÉMENTAIRE)

**Si Solution 1 adoptée**, ajouter validation Symfony pour garantir le format.

```php
use Symfony\Component\Validator\Constraints as Assert;

final readonly class GoogleAdsAssetDTO
{
    public function __construct(
        public array $headlines,
        public array $descriptions,
        public string $final_url,
        public string $display_path,
        public array $keywords,
        public string $target_audience,
        public array $unique_selling_points,
        #[Assert\All([
            new Assert\Collection([
                'headlines' => new Assert\All([new Assert\Type('string')]),
                'descriptions' => new Assert\All([new Assert\Type('string')]),
            ])
        ])]
        public array $variations,
    ) {}
}
```

**Avantages** :
- ✅ Détection précoce d'incohérences
- ✅ Validation automatique du format
- ✅ Meilleure robustesse

---

## 🧪 Test de Validation

```php
// Test unitaire suggéré
public function testGoogleAdsVariationsFormat(): void
{
    $agent = $this->createContentCreatorAgent();

    $result = $agent->createContent(
        assetType: 'google_ads',
        brief: [...],
        options: []
    );

    // Vérifier que variations existe
    $this->assertArrayHasKey('variations', $result);
    $this->assertIsArray($result['variations']);
    $this->assertCount(2, $result['variations']);

    // Vérifier le format OBJET (headlines + descriptions)
    foreach ($result['variations'] as $variation) {
        $this->assertIsArray($variation);
        $this->assertArrayHasKey('headlines', $variation);
        $this->assertArrayHasKey('descriptions', $variation);
        $this->assertIsArray($variation['headlines']);
        $this->assertIsArray($variation['descriptions']);
        $this->assertNotEmpty($variation['headlines']);
        $this->assertNotEmpty($variation['descriptions']);
    }
}
```

---

## 📊 Recommandations Finales

### Priorité HAUTE

1. ✅ **Corriger GoogleAdsAssetDTO** (Solution 1) : Refléter le format réel `array<object>`

### Priorité MOYENNE

2. ✅ **Tester BingAdsAssetDTO** : Vérifier si même problème (probable)
3. ✅ **Ajouter validation** (Solution 3) : Garantir le format avec Symfony Validator
4. ✅ **Documenter les formats** : Ajouter exemples JSON dans les PHPDoc

### Priorité BASSE

5. ✅ **Tester tous les autres DTOs** : Instagram, Mail, IAB (probablement OK)

---

## 📎 Fichiers de Référence

### Analyse Complète

Voir fichier joint : **`ANALYSE_VARIATIONS_ASSETS_BUNDLE.md`**

Contient :
- Analyse détaillée des 7 DTOs avec variations
- Logs de génération LLM réels
- Comparaison formats déclarés vs générés
- Impact sur l'UI
- Solutions détaillées

### Logs

**Source** : `/var/log/marketing/agents/content-2025-12-03.log`

Contient la réponse LLM brute pour :
- LinkedIn Post (ligne 4)
- Facebook Post (ligne 25)
- Google Ads (ligne 18) ← **Montre l'incohérence**

---

## 🔗 Contexte Technique

**Application** : myCfia - Plateforme marketing automatisé
**Bundle Version** : gorillias/marketing-ai-bundle v3.35.5
**Commit** : Intégration v3.35.1 (18bbbe2)
**Date Introduction** : v3.34.7 (migration vers DTOs asset-specific)

**Migration** :
- **Avant v3.34.7** : Tous les assets utilisaient `ContentAssetStructuredOutput` (format uniforme `array<string>`)
- **Depuis v3.34.7** : Chaque asset a son DTO spécifique → Google Ads génère maintenant un format objet

L'incohérence a probablement été introduite lors de la migration v3.34.7 sans mise à jour du type PHPDoc.

---

## 📞 Contact

**Reporter** : Application cliente myCfia (via Claude Code Assistant)
**Date Rapport** : 2025-12-03
**Bundle Version Testée** : v3.35.5

---

## ✅ Correctif Appliqué Côté Client

En attendant la correction du bundle, l'application cliente a modifié son template Twig pour gérer les 2 formats :

**Fichier** : `app/templates/marketing/project/show.html.twig`
**Lignes** : 1620-1644

Le template détecte maintenant si la variation est un **string** ou un **objet** et affiche en conséquence.

**Résultat** : Les variations LinkedIn, Facebook ET Google Ads s'affichent correctement.

---

**Merci de votre attention ! Ce bug n'est pas bloquant mais affecte la cohérence architecturale du bundle.**
