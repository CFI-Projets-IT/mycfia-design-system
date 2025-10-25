<?php

declare(strict_types=1);

namespace App\Service\Cfi;

use App\DTO\Cfi\UtilisateurGorilliasDto;
use App\Entity\Division;
use App\Entity\User;
use App\Repository\DivisionRepository;
use App\Repository\UserRepository;
use App\Service\Api\UtilisateurApiService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de synchronisation des données CFI vers la base de données locale.
 *
 * Ce service gère l'upsert (create or update) des entités User et Division
 * à partir des données reçues de l'API CFI lors de l'authentification.
 */
readonly class CfiUserSyncService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DivisionRepository $divisionRepository,
        private UserRepository $userRepository,
        private UtilisateurApiService $utilisateurApiService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Synchronise un utilisateur CFI vers la base de données locale.
     *
     * Cette méthode :
     * 1. Upsert Division (create si inexistante, update sinon)
     * 2. Upsert User (create si inexistant, update sinon)
     * 3. Link User → Division
     * 4. Flush EntityManager
     * 5. Retourne l'entité User Doctrine
     *
     * @param UtilisateurGorilliasDto $dto Données utilisateur depuis API CFI
     *
     * @return User Entité User Doctrine synchronisée
     */
    public function syncUserFromCfi(UtilisateurGorilliasDto $dto): User
    {
        $this->logger->info('Synchronisation utilisateur CFI vers BDD', [
            'idCfi' => $dto->id,
            'idDivision' => $dto->idDivision,
            'email' => $dto->email,
        ]);

        // 1. Upsert Division
        $division = $this->upsertDivision($dto);

        // 2. Upsert User
        $user = $this->upsertUser($dto, $division);

        // 3. Flush
        $this->entityManager->flush();

        $this->logger->info('Utilisateur synchronisé avec succès', [
            'userId' => $user->getId(),
            'idCfi' => $user->getIdCfi(),
            'divisionId' => $division->getId(),
        ]);

        return $user;
    }

    /**
     * Upsert Division depuis les données CFI.
     */
    private function upsertDivision(UtilisateurGorilliasDto $dto): Division
    {
        $division = $this->divisionRepository->findByIdDivision($dto->idDivision);

        if (! $division) {
            $this->logger->info('Création nouvelle division', [
                'idDivision' => $dto->idDivision,
                'nomDivision' => $dto->nomDivision,
            ]);

            $division = new Division();
            $division->setIdDivision($dto->idDivision);
            // createdAt, updatedAt et slug gérés automatiquement par Gedmo
        } else {
            $this->logger->debug('Mise à jour division existante', [
                'idDivision' => $dto->idDivision,
            ]);
        }

        // Mise à jour des données de la division
        $nomDivision = $dto->nomDivision ?? 'Division #'.$dto->idDivision;
        $division->setNomDivision($nomDivision);
        // slug et updatedAt gérés automatiquement par Gedmo lors du flush

        $this->entityManager->persist($division);

        return $division;
    }

    /**
     * Upsert User depuis les données CFI.
     *
     * Logique simplifiée :
     * 1. Chercher User par email (contrainte unique)
     * 2. Si trouvé : mettre à jour toutes les données (y compris idCfi et Division si changement)
     * 3. Sinon : créer nouveau User
     *
     * Note: Un utilisateur peut changer de Division au fil du temps.
     * La dernière connexion détermine la Division active.
     */
    private function upsertUser(UtilisateurGorilliasDto $dto, Division $division): User
    {
        // 1. Chercher par email (unique)
        $user = $this->userRepository->findByEmail($dto->email ?? '');

        // 2. Création ou mise à jour
        if (! $user) {
            $this->logger->info('Création nouvel utilisateur', [
                'idCfi' => $dto->id,
                'email' => $dto->email,
            ]);

            $user = new User();
            // createdAt et updatedAt gérés automatiquement par Gedmo
        } else {
            // Détection changement de Division
            $oldDivision = $user->getDivision();
            if ($oldDivision && $oldDivision->getIdDivision() !== $division->getIdDivision()) {
                $this->logger->info('Changement de Division détecté', [
                    'userId' => $user->getId(),
                    'email' => $dto->email,
                    'oldIdCfi' => $user->getIdCfi(),
                    'newIdCfi' => $dto->id,
                    'oldDivision' => $oldDivision->getNomDivision(),
                    'newDivision' => $division->getNomDivision(),
                ]);
            } else {
                $this->logger->debug('Mise à jour utilisateur existant', [
                    'idCfi' => $dto->id,
                    'userId' => $user->getId(),
                ]);
            }
        }

        // 3. Mise à jour de toutes les données utilisateur depuis CFI
        $user->setIdCfi($dto->id);
        $user->setEmail($dto->email ?? 'user-'.$dto->id.'@cfi.local');
        $user->setNom($dto->nom);
        $user->setPrenom($dto->prenom);
        $user->setTypeOptionGA($dto->type_d_option_GA);
        $user->setDivision($division);
        // updatedAt géré automatiquement par Gedmo lors du flush

        $this->entityManager->persist($user);

        return $user;
    }

    /**
     * Met à jour le tracking de connexion d'un utilisateur.
     *
     * Cette méthode est appelée après une authentification réussie.
     */
    public function updateLoginTracking(User $user): void
    {
        $this->logger->debug('Mise à jour tracking connexion', [
            'userId' => $user->getId(),
            'idCfi' => $user->getIdCfi(),
            'loginCountBefore' => $user->getLoginCount(),
        ]);

        $user->updateLoginTracking();
        $this->entityManager->flush();

        $this->logger->info('Tracking connexion mis à jour', [
            'userId' => $user->getId(),
            'loginCount' => $user->getLoginCount(),
            'lastLoginAt' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Synchronise les permissions de l'utilisateur depuis l'API CFI.
     *
     * Cette méthode :
     * 1. Appelle l'endpoint /Utilisateurs/getDroitsUtilisateur
     * 2. Met à jour le champ permissions en base de données
     * 3. Flush l'EntityManager
     *
     * Appelée après l'authentification pour récupérer les 25 permissions + quota HD.
     *
     * @param User $user Utilisateur à synchroniser
     */
    public function syncUserPermissions(User $user): void
    {
        $this->logger->info('Synchronisation permissions utilisateur', [
            'userId' => $user->getId(),
            'idCfi' => $user->getIdCfi(),
        ]);

        try {
            // Récupérer les droits depuis l'API CFI
            $permissions = $this->utilisateurApiService->getDroitsUtilisateur($user->getIdCfi());

            if (empty($permissions)) {
                $this->logger->warning('Aucune permission récupérée depuis API CFI', [
                    'userId' => $user->getId(),
                    'idCfi' => $user->getIdCfi(),
                ]);

                return;
            }

            // Mettre à jour le champ permissions de l'utilisateur
            $user->setPermissions($permissions);
            $this->entityManager->flush();

            $this->logger->info('Permissions utilisateur synchronisées avec succès', [
                'userId' => $user->getId(),
                'idCfi' => $user->getIdCfi(),
                'nb_permissions' => count($permissions),
                'administrateur' => $permissions['administrateur'] ?? false,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la synchronisation des permissions', [
                'userId' => $user->getId(),
                'idCfi' => $user->getIdCfi(),
                'error' => $e->getMessage(),
            ]);

            // Ne pas bloquer l'authentification si la sync permissions échoue
            // Les permissions resteront null et ne seront pas affichées
        }
    }
}
