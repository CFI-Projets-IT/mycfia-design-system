# 🔍 Outils de Qualité de Code

Guide complet pour l'utilisation de **PHPStan** et **PHP-CS-Fixer** dans le projet myCfia.

## 📋 Outils installés

### PHPStan - Analyse Statique
**Version** : 2.1.30
**Extensions** :
- `phpstan/phpstan` - Analyseur statique principal
- `phpstan/phpstan-symfony` - Extension pour Symfony
- `phpstan/phpstan-doctrine` - Extension pour Doctrine ORM
- `phpstan/extension-installer` - Gestionnaire d'extensions automatique

### PHP-CS-Fixer - Formatage de Code
**Version** : 3.88.2
**Configuration** : Standards Symfony + PSR-12

---

## 🚀 PHPStan - Analyse Statique

### Qu'est-ce que PHPStan ?

PHPStan est un outil d'analyse statique qui détecte les erreurs dans votre code PHP **sans l'exécuter**. Il trouve :
- Les erreurs de types
- Les appels de méthodes inexistantes
- Les propriétés non définies
- Les valeurs null non gérées
- Les erreurs de logique évidentes

### Configuration

Le fichier `phpstan.neon` à la racine du projet Symfony :

```neon
parameters:
    level: 6
    paths:
        - src
        - tests
    symfony:
        containerXmlPath: var/cache/dev/App_KernelDevDebugContainer.xml
    doctrine:
        repositoryClass: Doctrine\ORM\EntityRepository
    excludePaths:
        - var/
        - vendor/
```

**Niveau d'analyse** : 6 (sur une échelle de 0 à 10)
- **Niveau 0** : Vérifications basiques
- **Niveau 6** : Vérifications strictes (recommandé pour nouveaux projets)
- **Niveau 10** : Vérifications maximales

### Utilisation

#### Analyse complète du projet

```bash
# Dans le conteneur Docker
docker compose exec --user www-data frankenphp vendor/bin/phpstan analyse --memory-limit=1G

# Ou avec le script helper
./scripts/symfony.sh bash
vendor/bin/phpstan analyse --memory-limit=1G
```

#### Analyser un fichier spécifique

```bash
docker compose exec --user www-data frankenphp vendor/bin/phpstan analyse src/Controller/HomeController.php
```

#### Analyser un répertoire spécifique

```bash
docker compose exec --user www-data frankenphp vendor/bin/phpstan analyse src/Entity
```

#### Options utiles

```bash
# Analyse avec plus de détails
vendor/bin/phpstan analyse -v

# Analyse avec affichage du contexte
vendor/bin/phpstan analyse -vv

# Générer un rapport au format JSON
vendor/bin/phpstan analyse --error-format=json

# Afficher uniquement les erreurs (pas les warnings)
vendor/bin/phpstan analyse --no-progress
```

### Exemples d'erreurs détectées

#### Erreur de type

```php
// Code problématique
public function getUserName(User $user): string
{
    return $user->name; // Si $user->name peut être null
}

// PHPStan détecte : "Method getUserName() should return string but returns string|null"

// Solution
public function getUserName(User $user): string
{
    return $user->name ?? 'Anonyme';
}
```

#### Appel de méthode inexistante

```php
// Code problématique
$user = new User();
$user->getNom(); // La méthode n'existe pas

// PHPStan détecte : "Call to undefined method App\Entity\User::getNom()"

// Solution
$user->getName(); // Utiliser la bonne méthode
```

### Ignorer des erreurs spécifiques

Si vous devez temporairement ignorer une erreur :

```php
// Ignorer une ligne spécifique
/** @phpstan-ignore-next-line */
$result = $this->someComplexOperation();

// Ignorer un type d'erreur
/** @phpstan-ignore-line method.notFound */
$user->someMethod();
```

**⚠️ Attention** : N'ignorez les erreurs que si vous êtes absolument certain que le code est correct.

### Augmenter le niveau d'analyse

Quand votre code n'a plus d'erreurs au niveau 6, augmentez progressivement :

```neon
parameters:
    level: 7  # Puis 8, puis 9, etc.
```

---

## 🎨 PHP-CS-Fixer - Formatage de Code

### Qu'est-ce que PHP-CS-Fixer ?

PHP-CS-Fixer corrige automatiquement le style de votre code PHP pour respecter les standards de codage (PSR-12, Symfony, etc.).

### Configuration

Le fichier `.php-cs-fixer.dist.php` à la racine du projet Symfony :

```php
<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('vendor')
    ->exclude('public/bundles')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => true,
        // ... autres règles
    ])
    ->setFinder($finder)
;
```

### Utilisation

#### Vérifier les problèmes de style (dry-run)

```bash
# Voir les fichiers qui nécessitent des corrections
docker compose exec --user www-data frankenphp vendor/bin/php-cs-fixer fix --dry-run --diff

# Avec le script helper
./scripts/symfony.sh bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

#### Corriger automatiquement le style

```bash
# Corriger tous les fichiers
docker compose exec --user www-data frankenphp vendor/bin/php-cs-fixer fix

# Corriger un fichier spécifique
docker compose exec --user www-data frankenphp vendor/bin/php-cs-fixer fix src/Controller/HomeController.php

# Corriger un répertoire spécifique
docker compose exec --user www-data frankenphp vendor/bin/php-cs-fixer fix src/Entity
```

#### Options utiles

```bash
# Voir les modifications détaillées
vendor/bin/php-cs-fixer fix --diff

# Mode verbeux
vendor/bin/php-cs-fixer fix -v

# Utiliser plusieurs cœurs CPU (plus rapide)
vendor/bin/php-cs-fixer fix --using-cache=no --allow-risky=yes
```

### Exemples de corrections

#### Syntaxe des tableaux

```php
// Avant
$array = array('foo', 'bar');

// Après correction automatique
$array = ['foo', 'bar'];
```

#### Imports non utilisés

```php
// Avant
use App\Entity\User;
use App\Entity\Product;  // Non utilisé

class HomeController
{
    public function index(User $user) {}
}

// Après correction automatique
use App\Entity\User;

class HomeController
{
    public function index(User $user) {}
}
```

#### Ordre des imports

```php
// Avant
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

// Après correction automatique (ordre alphabétique)
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
```

#### Virgule finale dans les tableaux multi-lignes

```php
// Avant
$config = [
    'host' => 'localhost',
    'port' => 3306
];

// Après correction automatique
$config = [
    'host' => 'localhost',
    'port' => 3306,
];
```

### Ignorer des fichiers

Créer un fichier `.php-cs-fixer.ignore` :

```
/tests/fixtures/
/var/
/vendor/
```

---

## 🔄 Workflow recommandé

### Développement quotidien

1. **Écrire du code** normalement
2. **Avant chaque commit** :
   ```bash
   # 1. Corriger le style de code
   docker compose exec --user www-data frankenphp vendor/bin/php-cs-fixer fix

   # 2. Analyser le code avec PHPStan
   docker compose exec --user www-data frankenphp vendor/bin/phpstan analyse
   ```

3. **Si PHPStan détecte des erreurs** :
   - Corriger les erreurs détectées
   - Re-lancer l'analyse jusqu'à 0 erreur

4. **Committer le code propre** :
   ```bash
   git add .
   git commit -m "feat: nouvelle fonctionnalité"
   ```

### Intégration Continue (CI)

Ajouter dans votre pipeline CI/CD :

```yaml
# Exemple pour GitHub Actions
- name: PHPStan
  run: docker compose exec --user www-data frankenphp vendor/bin/phpstan analyse

- name: PHP-CS-Fixer
  run: docker compose exec --user www-data frankenphp vendor/bin/php-cs-fixer fix --dry-run --diff
```

### Pre-commit Hook (optionnel)

Créer `.git/hooks/pre-commit` :

```bash
#!/bin/bash

echo "🔍 Running PHPStan..."
docker compose exec --user www-data frankenphp vendor/bin/phpstan analyse

if [ $? -ne 0 ]; then
    echo "❌ PHPStan a détecté des erreurs. Commit annulé."
    exit 1
fi

echo "🎨 Running PHP-CS-Fixer..."
docker compose exec --user www-data frankenphp vendor/bin/php-cs-fixer fix

git add .

echo "✅ Code vérifié et formaté avec succès!"
exit 0
```

Rendre le hook exécutable :
```bash
chmod +x .git/hooks/pre-commit
```

---

## 📊 Commandes rapides

### PHPStan

| Commande | Description |
|----------|-------------|
| `vendor/bin/phpstan analyse` | Analyser tout le projet |
| `vendor/bin/phpstan analyse src/` | Analyser uniquement src/ |
| `vendor/bin/phpstan analyse --memory-limit=1G` | Augmenter la mémoire |
| `vendor/bin/phpstan analyse -v` | Mode verbeux |
| `vendor/bin/phpstan clear-result-cache` | Vider le cache |

### PHP-CS-Fixer

| Commande | Description |
|----------|-------------|
| `vendor/bin/php-cs-fixer fix` | Corriger tout le projet |
| `vendor/bin/php-cs-fixer fix --dry-run` | Voir les modifications sans appliquer |
| `vendor/bin/php-cs-fixer fix --diff` | Afficher les différences |
| `vendor/bin/php-cs-fixer fix src/` | Corriger uniquement src/ |
| `vendor/bin/php-cs-fixer list-files` | Lister les fichiers analysés |

---

## 🎯 Bonnes pratiques

### PHPStan

- ✅ Commencer avec le niveau 6, puis augmenter progressivement
- ✅ Corriger **toutes** les erreurs avant de committer
- ✅ Ne jamais ignorer les erreurs sans bonne raison
- ✅ Utiliser les annotations de type partout (`@param`, `@return`, `@var`)
- ✅ Activer PHPStan dans votre IDE (PHPStorm, VSCode)

### PHP-CS-Fixer

- ✅ Exécuter avant chaque commit
- ✅ Configurer votre IDE pour formater automatiquement
- ✅ Utiliser le même fichier de configuration dans toute l'équipe
- ✅ Committer le fichier `.php-cs-fixer.dist.php`
- ✅ Ajouter `.php-cs-fixer.cache` dans `.gitignore`

### Général

- ✅ Intégrer ces outils dans votre CI/CD
- ✅ Former toute l'équipe à leur utilisation
- ✅ Réviser régulièrement les configurations
- ✅ Documenter les exceptions et les règles ignorées

---

## 🔧 Dépannage

### PHPStan : Erreur de mémoire

```bash
# Augmenter la mémoire
vendor/bin/phpstan analyse --memory-limit=2G
```

### PHPStan : Cache corrompu

```bash
# Vider le cache
vendor/bin/phpstan clear-result-cache
```

### PHP-CS-Fixer : Fichiers non détectés

```bash
# Vérifier les fichiers analysés
vendor/bin/php-cs-fixer list-files
```

### PHP-CS-Fixer : Cache pose problème

```bash
# Désactiver le cache
vendor/bin/php-cs-fixer fix --using-cache=no
```

---

## 📚 Ressources

### Documentation officielle

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHPStan Rule Levels](https://phpstan.org/user-guide/rule-levels)
- [PHP-CS-Fixer Documentation](https://cs.symfony.com/)
- [PHP-CS-Fixer Rules](https://mlocati.github.io/php-cs-fixer-configurator/)

### Extensions IDE

- **PHPStorm** : Support natif PHPStan et PHP-CS-Fixer
- **VSCode** : Extensions `phpstan` et `php-cs-fixer`

---

## ✅ Checklist d'installation

- [x] PHPStan installé (version 2.1.30)
- [x] Extensions PHPStan Symfony et Doctrine installées
- [x] Fichier `phpstan.neon` configuré
- [x] PHP-CS-Fixer installé (version 3.88.2)
- [x] Fichier `.php-cs-fixer.dist.php` configuré
- [x] Tests effectués avec succès
- [x] Documentation créée

**Les outils de qualité de code sont prêts à l'emploi !** 🎉