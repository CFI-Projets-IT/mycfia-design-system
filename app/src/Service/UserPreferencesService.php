<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service de gestion des préférences utilisateur.
 *
 * Gère les préférences personnalisables de l'utilisateur :
 * - Thème de l'interface (light/dark)
 * - Langue de l'interface (fr/en)
 *
 * Les préférences sont stockées directement dans l'entité User.
 * Le service valide les valeurs avant application et génère
 * des messages d'erreur traduits en cas de valeur invalide.
 */
class UserPreferencesService
{
    /**
     * Thèmes autorisés.
     *
     * @var array<string>
     */
    private const ALLOWED_THEMES = ['light', 'dark-blue', 'dark-red'];

    /**
     * Locales autorisées.
     *
     * @var array<string>
     */
    private const ALLOWED_LOCALES = ['fr', 'en'];

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Met à jour le thème de l'utilisateur.
     *
     * @throws \InvalidArgumentException Si le thème n'est pas valide
     */
    public function updateTheme(User $user, string $theme): void
    {
        if (! in_array($theme, self::ALLOWED_THEMES, true)) {
            throw new \InvalidArgumentException($this->translator->trans('settings.error.invalid_theme', ['%theme%' => $theme, '%allowed%' => implode(', ', self::ALLOWED_THEMES)], 'settings'));
        }

        $user->setTheme($theme);
    }

    /**
     * Met à jour la locale de l'utilisateur.
     *
     * @throws \InvalidArgumentException Si la locale n'est pas valide
     */
    public function updateLocale(User $user, string $locale): void
    {
        if (! in_array($locale, self::ALLOWED_LOCALES, true)) {
            throw new \InvalidArgumentException($this->translator->trans('settings.error.invalid_locale', ['%locale%' => $locale, '%allowed%' => implode(', ', self::ALLOWED_LOCALES)], 'settings'));
        }

        $user->setLocale($locale);
    }

    /**
     * Retourne les thèmes autorisés.
     *
     * @return array<string>
     */
    public function getAllowedThemes(): array
    {
        return self::ALLOWED_THEMES;
    }

    /**
     * Retourne les locales autorisées.
     *
     * @return array<string>
     */
    public function getAllowedLocales(): array
    {
        return self::ALLOWED_LOCALES;
    }
}
