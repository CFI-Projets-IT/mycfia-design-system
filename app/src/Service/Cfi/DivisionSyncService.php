<?php

declare(strict_types=1);

namespace App\Service\Cfi;

use App\Entity\Division;
use App\Entity\User;
use App\Entity\UserAccessibleDivision;
use App\Repository\DivisionRepository;
use App\Repository\UserAccessibleDivisionRepository;
use App\Service\Api\DivisionApiService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de synchronisation des divisions accessibles pour le multi-tenant.
 *
 * Responsabilités :
 * - Synchroniser les divisions depuis l'API CFI vers la BDD (performance + fallback)
 * - Gérer la table pivot user_accessible_divisions
 * - Implémenter le mode dégradé (fallback BDD si API CFI down)
 * - Upsert des divisions dans la table division
 *
 * Architecture Hybride :
 * - Source de vérité : API CFI /Division/getDivisions
 * - Cache performant : BDD locale (lecture <10ms vs API 100-500ms)
 * - Résilience : Mode dégradé si API indisponible
 *
 * Synchronisation :
 * - Automatique au login (CfiAuthenticator)
 * - Manuelle via endpoint /api/sync-divisions
 * - Cron périodique (bin/console app:sync-divisions-all)
 */
final readonly class DivisionSyncService
{
    public function __construct(
        private DivisionApiService $divisionApiService,
        private EntityManagerInterface $entityManager,
        private DivisionRepository $divisionRepository,
        private UserAccessibleDivisionRepository $accessibleDivisionRepository,
        private LoggerInterface $logger,
    ) {
    }

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
     * Mode dégradé :
     * - Si API CFI échoue → retourne divisions BDD existantes (fallback)
     * - Logs d'erreur pour monitoring
     *
     * @param User $user      Utilisateur à synchroniser
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
            foreach ($divisionsFromApi as $divisionDto) {
                $division = $this->upsertDivision($divisionDto->id, $divisionDto->nom);
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
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback : retourner divisions BDD existantes
            $this->logger->info('Fallback : utilisation divisions BDD existantes');

            return $this->getDivisionsFromDatabase($user);
        }
    }

    /**
     * Upsert une division dans la table division.
     *
     * @param int         $idDivision ID CFI de la division
     * @param string|null $nom        Nom de la division
     */
    private function upsertDivision(int $idDivision, ?string $nom): Division
    {
        $division = $this->divisionRepository->findByIdDivision($idDivision);

        if (! $division) {
            $division = new Division();
            $division->setIdDivision($idDivision);
        }

        $division->setNomDivision($nom ?? 'Division #'.$idDivision);
        $this->entityManager->persist($division);

        return $division;
    }

    /**
     * Récupère les divisions accessibles depuis la BDD (fallback).
     *
     * @return array<int, Division>
     */
    private function getDivisionsFromDatabase(User $user): array
    {
        return $this->accessibleDivisionRepository->findDivisionsByUser($user);
    }

    /**
     * Vérifie si la synchronisation est nécessaire.
     *
     * @param User $user        Utilisateur à vérifier
     * @param int  $maxAgeHours Âge maximum en heures (défaut: 24h)
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

    /**
     * Récupère la date de dernière synchronisation pour un utilisateur.
     */
    public function getLastSyncDate(User $user): ?\DateTimeImmutable
    {
        return $this->accessibleDivisionRepository->getLastSyncDate($user);
    }

    /**
     * Synchronise les divisions pour tous les utilisateurs.
     *
     * Utilisé par la commande console app:sync-divisions-all (cron).
     * Utile pour maintenir les données BDD à jour.
     */
    public function syncAllUsers(): void
    {
        // TODO Phase 3 : Implémenter si nécessaire
        $this->logger->warning('syncAllUsers() pas encore implémenté');
    }
}
