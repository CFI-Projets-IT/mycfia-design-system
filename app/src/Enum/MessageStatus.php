<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts possibles d'un message envoyé.
 */
enum MessageStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case BOUNCED = 'bounced';
}
