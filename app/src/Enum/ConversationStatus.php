<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts possibles d'une conversation IA.
 */
enum ConversationStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}
