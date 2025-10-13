<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Types de campagnes marketing disponibles.
 */
enum CampaignType: string
{
    case SMS = 'sms';
    case EMAIL = 'email';
    case MAIL = 'mail';
    case SOCIAL = 'social';
}
