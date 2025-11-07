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
}
