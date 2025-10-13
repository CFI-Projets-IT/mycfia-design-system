<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Service de hashage des mots de passe pour l'API CFI.
 *
 * Utilise SHA-512 avec clé de salage préfixée selon les spécifications CFI.
 * Clé de salage : u_UYCFI_TECH~#sS (préfixée avant le mot de passe)
 */
class PasswordHasherService
{
    /**
     * Clé de salage CFI (à préfixer avant le mot de passe).
     */
    private const SALT_KEY = 'u_UYCFI_TECH~#sS';

    /**
     * Hash un mot de passe avec SHA-512 et clé de salage CFI.
     *
     * Format : SHA512(clé_salage + mot_de_passe)
     *
     * @param string $password Mot de passe en clair
     *
     * @return string Hash SHA-512 en hexadécimal (128 caractères)
     *
     * @throws \InvalidArgumentException Si le mot de passe est vide
     */
    public function hashPassword(string $password): string
    {
        if (empty($password)) {
            throw new \InvalidArgumentException('Le mot de passe ne peut pas être vide');
        }

        // Concaténer clé de salage + mot de passe
        $saltedPassword = self::SALT_KEY.$password;

        // Hasher avec SHA-512 (retourne 128 caractères hexadécimaux)
        $hash = hash('sha512', $saltedPassword);

        return $hash;
    }
}
