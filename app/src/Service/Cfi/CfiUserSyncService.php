<?php

declare(strict_types=1);

namespace App\Service\Cfi;

use App\DTO\Cfi\UtilisateurGorilliasDto;
use App\Entity\Division;
use App\Entity\User;
use App\Repository\DivisionRepository;
use App\Repository\UserRepository;
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
     */
    private function upsertUser(UtilisateurGorilliasDto $dto, Division $division): User
    {
        $user = $this->userRepository->findByIdCfi($dto->id);

        if (! $user) {
            $this->logger->info('Création nouvel utilisateur', [
                'idCfi' => $dto->id,
                'email' => $dto->email,
            ]);

            $user = new User();
            $user->setIdCfi($dto->id);
            // createdAt et updatedAt gérés automatiquement par Gedmo
        } else {
            $this->logger->debug('Mise à jour utilisateur existant', [
                'idCfi' => $dto->id,
                'userId' => $user->getId(),
            ]);
        }

        // Mise à jour des données utilisateur depuis CFI
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
}
