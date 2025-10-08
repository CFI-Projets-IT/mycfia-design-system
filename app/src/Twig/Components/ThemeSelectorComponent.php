<?php

namespace App\Twig\Components;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ThemeSelectorComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $currentTheme = 'light';

    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function mount(): void
    {
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $this->currentTheme = $user->getTheme();
        }
    }

    #[LiveAction]
    public function switchTheme(string $theme): void
    {
        // Valider le thème
        $allowedThemes = ['light', 'dark-blue', 'dark-red'];
        if (! in_array($theme, $allowedThemes, true)) {
            return;
        }

        $this->currentTheme = $theme;

        // Sauvegarder dans la BDD
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $user->setTheme($theme);
            $this->entityManager->flush();
        }
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
