<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts possibles d'une campagne marketing.
 */
enum CampaignStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
}
