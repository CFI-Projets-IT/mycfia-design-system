<?php

declare(strict_types=1);

namespace App\DTO\Cfi;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO Request pour l'authentification CFI via token Gorillias.
 *
 * Utilise pour l'endpoint POST /Utilisateurs/getUtilisateurGorillias
 */
class GetUtilisateurByClefDto
{
    /**
     * Token Gorillias de l'utilisateur (UUID format).
     * Duree de validite : 30 minutes.
     */
    #[Assert\NotBlank(message: 'cfi.jeton.not_blank')]
    #[Assert\Length(
        min: 36,
        max: 36,
        exactMessage: 'cfi.jeton.invalid_format'
    )]
    #[Assert\Uuid(message: 'cfi.jeton.invalid_format')]
    public ?string $jetonUtilisateur = null;

    /**
     * Cle API CFI pour authentifier l'application myCfia.
     * Fournie par CFI et stockee dans les variables d'environnement.
     */
    #[Assert\NotBlank(message: 'cfi.clef_api.not_blank')]
    #[Assert\Uuid(message: 'cfi.clef_api.invalid')]
    public ?string $clefApi = null;
}
