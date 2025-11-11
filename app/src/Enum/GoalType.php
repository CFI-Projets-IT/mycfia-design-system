<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Types d'objectifs marketing possibles pour un projet.
 * Détermine la stratégie et le ton des assets générés.
 */
enum GoalType: string
{
    case AWARENESS = 'awareness';
    case CONVERSION = 'conversion';
    case RETENTION = 'retention';

    /**
     * Retourne le libellé traduit de l'objectif.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::AWARENESS => 'Notoriété',
            self::CONVERSION => 'Conversion',
            self::RETENTION => 'Fidélisation',
        };
    }

    /**
     * Retourne la classe CSS du badge Bootstrap pour cet objectif.
     */
    public function getBadgeClass(): string
    {
        return match ($this) {
            self::AWARENESS => 'bg-info',
            self::CONVERSION => 'bg-success',
            self::RETENTION => 'bg-warning',
        };
    }
}
