<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Types de messages envoyés dans les campagnes.
 */
enum MessageType: string
{
    case SMS = 'sms';
    case EMAIL = 'email';
    case MAIL = 'mail';
    case SOCIAL = 'social';
}
