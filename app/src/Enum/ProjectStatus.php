<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts possibles d'un projet marketing IA.
 * Représente le workflow de génération : brief → personas → stratégie → assets → publication.
 */
enum ProjectStatus: string
{
    case DRAFT = 'draft';
    case PERSONA_GENERATED = 'persona_generated';
    case STRATEGY_GENERATED = 'strategy_generated';
    case ASSETS_GENERATING = 'assets_generating';
    case ASSETS_GENERATED = 'assets_generated';
    case READY_TO_PUBLISH = 'ready_to_publish';
    case PUBLISHED = 'published';
}
