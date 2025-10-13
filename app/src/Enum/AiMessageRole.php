<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Rôles possibles dans une conversation IA.
 */
enum AiMessageRole: string
{
    case USER = 'user';
    case ASSISTANT = 'assistant';
    case SYSTEM = 'system';
}
