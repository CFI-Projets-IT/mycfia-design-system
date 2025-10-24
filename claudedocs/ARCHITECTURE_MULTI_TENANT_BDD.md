# Architecture Multi-Tenant Hiérarchique avec Stockage BDD

**Date** : 2025-01-24 (Révision 2025-01-30)
**Révision** : Architecture corrigée avec stockage BDD + intégration permissions globales
**Objectif** : Système hybride BDD + API pour multi-tenant hiérarchique performant et résilient

---

## 🎯 Décision Architecturale : Stockage BDD

### Pourquoi Stocker en BDD ?

| Critère | Sans BDD (API-Only) | Avec BDD (Hybride) |
|---------|---------------------|-------------------|
| **Performance** | ❌ Appel API à chaque lecture | ✅ Lecture BDD rapide |
| **Disponibilité** | ❌ Dépend de l'API CFI | ✅ Mode dégradé si API down |
| **Latence** | ❌ 100-500ms (réseau) | ✅ <10ms (BDD locale) |
| **Résilience** | ❌ App bloquée si API down | ✅ App fonctionne avec données BDD |
| **Charge API** | ❌ Beaucoup d'appels | ✅ Sync périodique uniquement |

**Décision** : ✅ **Stockage BDD avec synchronisation périodique depuis API CFI**

---

## 🔐 Permissions et Multi-Tenant

### Analyse : Permissions Globales vs Per-Tenant

**Question** : Les permissions de `/Utilisateurs/getDroitsUtilisateur` sont-elles **globales** ou **per-tenant** ?

**Réponse** : ✅ **GLOBALES** à l'utilisateur (pas liées aux divisions)

#### Preuves Techniques

| Élément | Analyse |
|---------|---------|
| **API getDroitsUtilisateur** | ❌ Aucun paramètre division<br>✅ Utilise uniquement le token user (header Jeton)<br>✅ Retourne permissions sans contexte tenant |
| **Stockage** | ✅ `User.permissions` (JSON global)<br>❌ Pas de colonne permissions dans `user_accessible_divisions` |
| **Documentation** | ✅ ENVIRONMENTS.md ligne 228-231 : "Les permissions sont héritées automatiquement vers le bas"<br>✅ Héritage concerne la HIÉRARCHIE des tenants, pas les permissions elles-mêmes |
| **Workflow** | ✅ Swagger ligne 239 : "Appeler au login" (une seule fois)<br>❌ Pas de rappel lors du switch de tenant |

#### Rôle de TenantDto.permissions

```php
// TenantDto.php ligne 22
public function __construct(
    public int $idCfi,
    public string $nom,
    public ?string $code = null,
    public bool $actif = true,
    public array $permissions = [], // ← Commodité, PAS stockage per-tenant
) {}
```

**Explication** :
- ✅ Commodité pour passer les permissions avec le contexte tenant
- ✅ Simplement une copie des `User.permissions` globales
- ❌ PAS un stockage per-tenant de permissions différentes
- ✅ Facilite l'accès dans les services (évite de récupérer User)

#### Workflow Permissions + Multi-Tenant

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. LOGIN                                                        │
│    ↓                                                            │
│ 2. CfiAuthenticator::authenticate()                            │
│    ↓                                                            │
│ 3. syncUserPermissions(User)                                    │
│    └─→ User.permissions = GLOBAL (25 permissions + quota)       │
│    ↓                                                            │
│ 4. syncUserDivisions(User)                                      │
│    └─→ user_accessible_divisions = [Division A, Division B]     │
│    ↓                                                            │
│ 5. initializeTenantFromUser()                                   │
│    └─→ Session tenant = Division A (contexte par défaut)        │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ SWITCH TENANT                                                   │
│    ↓                                                            │
│ CfiTenantService::switchTenant(Division B)                      │
│    ├─→ Validation : User a accès à Division B ? (BDD)          │
│    ├─→ Session tenant = Division B (NOUVEAU CONTEXTE)          │
│    └─→ Permissions INCHANGÉES (User.permissions reste global)   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ VÉRIFICATION PERMISSIONS                                        │
│    ↓                                                            │
│ PermissionService::hasPermission('factures_Visu')              │
│    └─→ Lit TOUJOURS User.permissions (global)                   │
│    └─→ PAS de recharge depuis API                              │
│    └─→ PAS de variation selon le tenant actif                  │
└─────────────────────────────────────────────────────────────────┘
```

### Conclusion : Contexte ≠ Permissions

| Aspect | Comportement |
|--------|-------------|
| **Permissions** | ✅ Globales à l'utilisateur CFI<br>✅ Stockées dans `User.permissions`<br>✅ Synchronisées au login uniquement<br>❌ NE CHANGENT PAS lors du switch tenant |
| **Contexte Tenant** | ✅ Change lors du switch (Session)<br>✅ Filtre les données affichées (factures, campagnes, etc.)<br>✅ Détermine la division active<br>❌ N'affecte PAS les permissions |
| **Héritage Hiérarchique** | ✅ Manager a accès à ses divisions enfants<br>✅ Mêmes permissions pour toutes les divisions accessibles<br>✅ Hiérarchie gérée par API CFI (getDivisions) |

**Implications** :
- ⚠️ Un utilisateur avec `factures_Visu = true` peut voir les factures de TOUTES ses divisions accessibles
- ⚠️ Les permissions sont les mêmes que l'utilisateur soit sur Division A ou Division B
- ✅ Le switch de tenant change UNIQUEMENT le périmètre des données, pas les droits d'accès
- ✅ Pas besoin de rappeler `getDroitsUtilisateur` lors du switch tenant

---

## 🏗️ Modèle de Données

### Option 1 : Table Pivot Simple (RECOMMANDÉE)

#### Structure BDD

```sql
-- Table division (existante, pas de modification)
CREATE TABLE division (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    id_division INT UNIQUE NOT NULL,  -- ID CFI
    nom_division VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    settings JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

-- ✅ NOUVELLE : Table pivot pour divisions accessibles
CREATE TABLE user_accessible_divisions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    division_id BIGINT NOT NULL,
    synced_at DATETIME NOT NULL,      -- Dernière sync API CFI

    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (division_id) REFERENCES division(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_division (user_id, division_id)
);

-- Index pour performance
CREATE INDEX idx_user_accessible_divisions_user ON user_accessible_divisions(user_id);
CREATE INDEX idx_user_accessible_divisions_synced ON user_accessible_divisions(synced_at);
```

#### Entité Doctrine

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_accessible_divisions')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_accessible_divisions_user')]
class UserAccessibleDivision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Division::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Division $division;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $syncedAt;

    // Getters/Setters...
}
```

#### Modification User.php

```php
namespace App\Entity;

class User implements UserInterface
{
    // Relation existante (division d'appartenance)
    #[ORM\ManyToOne(targetEntity: Division::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Division $division = null;

    // ✅ NOUVEAU : Divisions accessibles (hiérarchie)
    /**
     * @var Collection<int, UserAccessibleDivision>
     */
    #[ORM\OneToMany(
        targetEntity: UserAccessibleDivision::class,
        mappedBy: 'user',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $accessibleDivisions;

    public function __construct()
    {
        $this->accessibleDivisions = new ArrayCollection();
    }

    /**
     * Récupère les divisions accessibles à cet utilisateur.
     *
     * @return Collection<int, Division>
     */
    public function getAccessibleDivisions(): Collection
    {
        return $this->accessibleDivisions->map(
            fn(UserAccessibleDivision $uad) => $uad->getDivision()
        );
    }

    /**
     * Vérifie si l'utilisateur a accès à une division.
     */
    public function hasAccessToDivision(int $idDivision): bool
    {
        foreach ($this->accessibleDivisions as $uad) {
            if ($uad->getDivision()->getIdDivision() === $idDivision) {
                return true;
            }
        }

        return false;
    }
}
```

**Avantages** :
- ✅ Simple à comprendre et maintenir
- ✅ Pas de modification de la table `division` existante
- ✅ Relation Many-to-Many explicite
- ✅ Tracking de synchronisation par relation

**Inconvénients** :
- ❌ Pas de hiérarchie parent/enfant explicite en BDD (géré par logique métier)

---

### Option 2 : Hiérarchie Complète (COMPLEXE, non recommandée pour Sprint actuel)

```sql
ALTER TABLE division ADD COLUMN parent_id BIGINT NULL;
ALTER TABLE division ADD FOREIGN KEY (parent_id) REFERENCES division(id);
```

**Pourquoi ne PAS faire ça maintenant** :
- ⚠️ L'API CFI gère déjà la hiérarchie (source de vérité)
- ⚠️ Complexité : gestion arbre, cascades, migrations complexes
- ⚠️ Risque désynchronisation hiérarchie BDD vs API CFI
- ⚠️ YAGNI : On n'a pas besoin de requêter la hiérarchie en BDD

**Approche recommandée** : Stocker seulement les **relations user ↔ divisions accessibles**, pas toute la hiérarchie.

---

## 🔄 Stratégie de Synchronisation

### Quand Synchroniser ?

| Déclencheur | Fréquence | Méthode |
|-------------|-----------|---------|
| **Login utilisateur** | À chaque connexion | Sync automatique |
| **Changement droits** | Sur demande admin | Endpoint manuel `/api/sync-divisions` |
| **Cron périodique** | Tous les jours à 3h | `bin/console app:sync-divisions-all` |
| **Expiration cache** | Après 24h sans sync | Vérification auto |

### Workflow de Synchronisation

```
1. Login User
   ↓
2. CfiAuthenticator::authenticate()
   ↓
3. DivisionSyncService::syncUserDivisions(User $user)
   ↓
4. API CFI /Division/getDivisions → [Division A, Division B]
   ↓
5. Upsert divisions dans table `division` (si nouvelles)
   ↓
6. Clear anciennes relations user_accessible_divisions pour ce user
   ↓
7. Insert nouvelles relations user_accessible_divisions
   ↓
8. Timestamp syncedAt = NOW()
   ↓
9. Return divisions from BDD
```

---

## 📦 Services

### DivisionSyncService.php

```php
namespace App\Service\Cfi;

use App\Entity\Division;
use App\Entity\User;
use App\Entity\UserAccessibleDivision;
use App\Repository\DivisionRepository;
use App\Repository\UserAccessibleDivisionRepository;
use App\Service\Api\DivisionApiService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class DivisionSyncService
{
    public function __construct(
        private DivisionApiService $divisionApiService,
        private EntityManagerInterface $entityManager,
        private DivisionRepository $divisionRepository,
        private UserAccessibleDivisionRepository $accessibleDivisionRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * Synchronise les divisions accessibles d'un utilisateur depuis l'API CFI.
     *
     * Workflow :
     * 1. Appel API CFI /Division/getDivisions
     * 2. Upsert divisions dans table division
     * 3. Clear anciennes relations user_accessible_divisions
     * 4. Insert nouvelles relations
     * 5. Return divisions depuis BDD
     *
     * @param User $user Utilisateur à synchroniser
     * @param bool $forceSync Si true, ignore le cache et force l'appel API
     *
     * @return array<int, Division> Divisions accessibles depuis BDD
     */
    public function syncUserDivisions(User $user, bool $forceSync = false): array
    {
        $this->logger->info('Synchronisation divisions accessibles', [
            'userId' => $user->getId(),
            'idCfi' => $user->getIdCfi(),
            'forceSync' => $forceSync,
        ]);

        try {
            // 1. Récupérer divisions depuis API CFI
            $divisionsFromApi = $this->divisionApiService->getDivisions();

            if (empty($divisionsFromApi)) {
                $this->logger->warning('Aucune division accessible depuis API CFI', [
                    'userId' => $user->getId(),
                ]);

                // Fallback : retourner divisions BDD existantes si API vide
                return $this->getDivisionsFromDatabase($user);
            }

            // 2. Upsert divisions dans table division
            $divisions = [];
            foreach ($divisionsFromApi as $divisionData) {
                $division = $this->upsertDivision($divisionData);
                $divisions[] = $division;
            }

            // 3. Clear anciennes relations pour ce user
            $this->accessibleDivisionRepository->clearUserDivisions($user);

            // 4. Insert nouvelles relations
            $now = new \DateTimeImmutable();
            foreach ($divisions as $division) {
                $userAccessibleDivision = new UserAccessibleDivision();
                $userAccessibleDivision->setUser($user);
                $userAccessibleDivision->setDivision($division);
                $userAccessibleDivision->setSyncedAt($now);

                $this->entityManager->persist($userAccessibleDivision);
            }

            $this->entityManager->flush();

            $this->logger->info('Divisions synchronisées avec succès', [
                'userId' => $user->getId(),
                'nbDivisions' => count($divisions),
            ]);

            return $divisions;

        } catch (\Exception $e) {
            $this->logger->error('Erreur synchronisation divisions', [
                'userId' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            // Fallback : retourner divisions BDD existantes
            $this->logger->info('Fallback : utilisation divisions BDD existantes');
            return $this->getDivisionsFromDatabase($user);
        }
    }

    /**
     * Upsert une division dans la table division.
     */
    private function upsertDivision(array $divisionData): Division
    {
        $idDivision = (int) $divisionData['id'];
        $division = $this->divisionRepository->findByIdDivision($idDivision);

        if (!$division) {
            $division = new Division();
            $division->setIdDivision($idDivision);
        }

        $division->setNomDivision($divisionData['nom'] ?? 'Division #' . $idDivision);
        $this->entityManager->persist($division);

        return $division;
    }

    /**
     * Récupère les divisions accessibles depuis la BDD (fallback).
     */
    private function getDivisionsFromDatabase(User $user): array
    {
        return $this->accessibleDivisionRepository->findDivisionsByUser($user);
    }

    /**
     * Vérifie si la synchronisation est nécessaire.
     *
     * @param User $user
     * @param int $maxAgeHours Âge maximum en heures (défaut: 24h)
     */
    public function needsSync(User $user, int $maxAgeHours = 24): bool
    {
        $lastSync = $this->accessibleDivisionRepository->getLastSyncDate($user);

        if (null === $lastSync) {
            return true; // Jamais synchronisé
        }

        $maxAge = new \DateTimeImmutable("-{$maxAgeHours} hours");
        return $lastSync < $maxAge;
    }
}
```

---

### DivisionApiService.php (Simplifié)

```php
namespace App\Service\Api;

final readonly class DivisionApiService
{
    private const ENDPOINT_GET_DIVISIONS = '/Division/getDivisions';
    private const ENDPOINT_GET_UTILISATEURS = '/Division/getUtilisateurs';

    public function __construct(
        private CfiApiService $cfiApi,
        private LoggerInterface $logger,
    ) {}

    /**
     * Récupère les divisions enfants depuis l'API CFI.
     *
     * ⚠️ Cette méthode appelle directement l'API CFI (pas de cache).
     * Utiliser DivisionSyncService pour la logique métier avec BDD.
     *
     * @return array<int, array{id: int, nom: string}>
     * @throws CfiApiException Si l'API échoue
     */
    public function getDivisions(): array
    {
        $response = $this->cfiApi->post(self::ENDPOINT_GET_DIVISIONS, []);

        return array_map(
            fn(array $div) => [
                'id' => (int) $div['id'],
                'nom' => (string) ($div['nom'] ?? 'Division #' . $div['id'])
            ],
            $response
        );
    }

    /**
     * Récupère les utilisateurs enfants depuis l'API CFI.
     *
     * @return array<int, array{id: int, idDivision: int, ...}>
     * @throws CfiApiException Si l'API échoue
     */
    public function getUtilisateurs(): array
    {
        $response = $this->cfiApi->post(self::ENDPOINT_GET_UTILISATEURS, []);

        return array_map(
            fn(array $user) => [
                'id' => (int) $user['id'],
                'idDivision' => (int) $user['idDivision'],
                'nomDivision' => $user['nomDivision'] ?? null,
                'nom' => $user['nom'] ?? null,
                'prenom' => $user['prenom'] ?? null,
                'email' => $user['email'] ?? null,
            ],
            $response
        );
    }
}
```

---

### Repository UserAccessibleDivisionRepository.php

```php
namespace App\Repository;

use App\Entity\Division;
use App\Entity\User;
use App\Entity\UserAccessibleDivision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserAccessibleDivisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAccessibleDivision::class);
    }

    /**
     * Récupère toutes les divisions accessibles pour un utilisateur.
     *
     * @return array<int, Division>
     */
    public function findDivisionsByUser(User $user): array
    {
        $results = $this->createQueryBuilder('uad')
            ->select('d')
            ->join('uad.division', 'd')
            ->where('uad.user = :user')
            ->setParameter('user', $user)
            ->orderBy('d.nomDivision', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * Supprime toutes les divisions accessibles pour un utilisateur.
     */
    public function clearUserDivisions(User $user): void
    {
        $this->createQueryBuilder('uad')
            ->delete()
            ->where('uad.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère la date de dernière synchronisation pour un utilisateur.
     */
    public function getLastSyncDate(User $user): ?\DateTimeImmutable
    {
        $result = $this->createQueryBuilder('uad')
            ->select('MAX(uad.syncedAt)')
            ->where('uad.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? new \DateTimeImmutable($result) : null;
    }

    /**
     * Vérifie si un utilisateur a accès à une division.
     */
    public function hasAccess(User $user, int $idDivision): bool
    {
        $count = $this->createQueryBuilder('uad')
            ->select('COUNT(uad.id)')
            ->join('uad.division', 'd')
            ->where('uad.user = :user')
            ->andWhere('d.idDivision = :idDivision')
            ->setParameter('user', $user)
            ->setParameter('idDivision', $idDivision)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
```

---

## 🔌 Intégration CfiAuthenticator

### Modifications dans CfiAuthenticator::authenticate()

```php
public function authenticate(Request $request): Passport
{
    // ... code existant ...

    // Synchroniser User et Division en BDD (upsert)
    $user = $this->cfiUserSyncService->syncUserFromCfi($utilisateurDto);

    // Synchroniser les permissions utilisateur depuis l'API CFI
    $this->cfiUserSyncService->syncUserPermissions($user);

    // ✅ NOUVEAU : Synchroniser les divisions accessibles
    try {
        $this->divisionSyncService->syncUserDivisions($user);
        $this->logger->info('Divisions accessibles synchronisées au login', [
            'userId' => $user->getId(),
        ]);
    } catch (\Exception $e) {
        // Ne pas bloquer l'authentification si sync divisions échoue
        $this->logger->error('Erreur sync divisions au login (non bloquant)', [
            'userId' => $user->getId(),
            'error' => $e->getMessage(),
        ]);
    }

    // Stocker le token CFI en session
    // ...
}
```

---

## 🛡️ Mode Dégradé (Fallback)

### Scénarios de Fallback

| Scénario | Comportement |
|----------|-------------|
| **API CFI down au login** | ✅ Sync échoue → Utilise divisions BDD existantes |
| **API CFI down après login** | ✅ Lecture depuis BDD uniquement (performant) |
| **Premier login (BDD vide)** | ⚠️ Sync échoue → Aucune division accessible (message user) |
| **Données BDD obsolètes** | ⚠️ User voit anciennes divisions (max 24h) |

### Indicateur Mode Dégradé

```php
// Dans un Twig global ou service
public function isInDegradedMode(User $user): bool
{
    $lastSync = $this->accessibleDivisionRepository->getLastSyncDate($user);

    if (null === $lastSync) {
        return true; // Jamais synchronisé
    }

    $threshold = new \DateTimeImmutable('-1 hour');
    return $lastSync < $threshold;
}
```

**Affichage UI** :
```twig
{% if is_degraded_mode %}
    <div class="alert alert-warning">
        ⚠️ Mode dégradé : Les données affichées peuvent être obsolètes.
        <a href="{{ path('sync_divisions') }}">Synchroniser maintenant</a>
    </div>
{% endif %}
```

---

## 📊 Performance

### Comparaison Avant/Après

| Opération | Sans BDD (API-Only) | Avec BDD (Hybride) |
|-----------|---------------------|-------------------|
| **Login** | 3 appels API (auth + permissions + divisions) | 3 appels API (sync complète) |
| **Affichage sélecteur** | 1 appel API (100-500ms) | 1 lecture BDD (<10ms) ⚡ |
| **Validation switchTenant** | 1 appel API (100-500ms) | 1 lecture BDD (<10ms) ⚡ |
| **Liste divisions (10x/session)** | 10 appels API (1-5s total) | 10 lectures BDD (<100ms total) ⚡ |

**Gain** : **~95% réduction latence** pour opérations répétitives.

---

## 🚀 Plan d'Implémentation

### Phase 1 : Modèle de Données (1h)
1. ✅ Créer entité `UserAccessibleDivision`
2. ✅ Modifier entité `User` (relation accessibleDivisions)
3. ✅ Créer migration BDD
4. ✅ Créer repository `UserAccessibleDivisionRepository`

### Phase 2 : Services (1h30)
1. ✅ Créer `DivisionApiService` (appels API CFI)
2. ✅ Créer `DivisionSyncService` (logique sync BDD)
3. ✅ Créer DTOs (`DivisionDto`, `UtilisateurDto`)
4. ✅ Tests unitaires services

### Phase 3 : Intégration (1h)
1. ✅ Modifier `CfiAuthenticator` (sync au login)
2. ✅ Modifier `CfiTenantService::switchTenant()` (validation BDD)
3. ✅ Créer endpoint manuel `/api/sync-divisions`
4. ✅ Créer commande console `app:sync-divisions-all`

### Phase 4 : UI & Tests (1h)
1. ✅ Afficher sélecteur divisions (si > 0 divisions)
2. ✅ Indicateur mode dégradé
3. ✅ Tests fonctionnels complets
4. ✅ PHPStan + PHP-CS-Fixer

**Total** : ~4h30 (vs 3h initialement prévues)

---

## ✅ Checklist Finale

- [ ] Migration BDD créée et testée
- [ ] Entités `UserAccessibleDivision` et modifications `User`
- [ ] Repository avec méthodes optimisées (index)
- [ ] `DivisionSyncService` avec gestion erreurs
- [ ] `DivisionApiService` pour appels API CFI
- [ ] Sync au login dans `CfiAuthenticator`
- [ ] Validation `switchTenant()` avec BDD
- [ ] Endpoint manuel `/api/sync-divisions` (admin)
- [ ] Commande console `app:sync-divisions-all`
- [ ] UI sélecteur divisions + indicateur mode dégradé
- [ ] Tests unitaires + fonctionnels
- [ ] PHPStan niveau 6 ✅
- [ ] Documentation mise à jour

---

## 📝 Conclusion

**Architecture Hybride BDD + API CFI** :

1. **Performance** ⚡ : Lecture BDD <10ms vs appel API 100-500ms
2. **Résilience** 🛡️ : Mode dégradé si API CFI down
3. **Disponibilité** ✅ : Application fonctionne avec données BDD
4. **Synchronisation** 🔄 : Au login + périodique + manuel
5. **Fallback** 💪 : Données BDD utilisées si sync échoue

**Source de vérité** : API CFI (via sync périodique)
**Cache performant** : BDD locale avec relations optimisées
**Mode dégradé** : Fallback automatique sur BDD si API indisponible

**Permissions** : Globales à l'utilisateur (User.permissions), synchronisées au login uniquement
**Contexte tenant** : Change le périmètre des données (filtrage), pas les droits d'accès
**Aucune resynchronisation** : Pas besoin de rappeler getDroitsUtilisateur lors du switch tenant