<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour l'authentification par identifiant/mot de passe via API CFI.
 *
 * Endpoint : POST /Utilisateurs/getUtilisateurMyCFiA
 *
 * Le mot de passe doit être hashé en SHA-512 avec la clé de salage
 * 'u_UYCFI_TECH~#sS' préfixée avant envoi à l'API.
 */
class GetUtilisateurByLoginMDP
{
    /**
     * Identifiant de l'utilisateur (email ou login CFI).
     */
    #[Assert\NotBlank(message: 'L\'identifiant est obligatoire')]
    #[Assert\Length(max: 255, maxMessage: 'L\'identifiant ne peut pas dépasser {{ limit }} caractères')]
    public ?string $identifiant = null;

    /**
     * Mot de passe hashé en SHA-512 (128 caractères hexadécimaux).
     *
     * Format attendu : hash('sha512', 'u_UYCFI_TECH~#sS' . $password)
     */
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire')]
    #[Assert\Length(min: 128, max: 128, exactMessage: 'Le hash SHA-512 doit faire exactement {{ limit }} caractères')]
    #[Assert\Regex(pattern: '/^[a-f0-9]{128}$/', message: 'Le mot de passe doit être un hash SHA-512 valide (128 caractères hexadécimaux)')]
    public ?string $mdp = null;

    /**
     * Clé API CFI (obligatoire selon tests API réels).
     */
    #[Assert\NotBlank(message: 'La clé API est obligatoire')]
    public ?string $clefApi = null;
}
