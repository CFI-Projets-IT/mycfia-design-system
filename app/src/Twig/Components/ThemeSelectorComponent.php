<?php

namespace App\Twig\Components;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('ThemeSelector')]
final class ThemeSelectorComponent
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function getCurrentTheme(): string
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            return $user->getTheme();
        }

        return 'light';
    }

    /**
     * @return array<int, array{value: string, label: string, icon: string, color: string}>
     */
    public function getThemes(): array
    {
        return [
            [
                'value' => 'light',
                'label' => 'Light',
                'icon' => 'bi-sun',
                'color' => '#f5f5f7',
            ],
            [
                'value' => 'dark-blue',
                'label' => 'Dark Blue',
                'icon' => 'bi-moon-stars',
                'color' => '#0f1729',
            ],
            [
                'value' => 'dark-red',
                'label' => 'Dark Red',
                'icon' => 'bi-moon',
                'color' => '#1a0a0e',
            ],
        ];
    }
}
