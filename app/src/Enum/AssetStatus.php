<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts possibles d'un asset marketing généré.
 * Workflow de validation : brouillon → approuvé → publié (ou rejeté).
 */
enum AssetStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';
}
