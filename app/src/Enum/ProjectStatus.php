<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts possibles d'un projet marketing IA.
 * Représente le workflow de génération : brief → enrichissement → personas → stratégie → assets → publication.
 */
enum ProjectStatus: string
{
    case DRAFT = 'draft';
    case ENRICHED = 'enriched';
    case ENRICHED_PENDING = 'enriched_pending';
    case PERSONA_IN_PROGRESS = 'persona_in_progress';
    case PERSONA_GENERATED = 'persona_generated';
    case COMPETITOR_VALIDATED = 'competitor_validated';
    case STRATEGY_IN_PROGRESS = 'strategy_in_progress';
    case STRATEGY_GENERATED = 'strategy_generated';
    case ASSETS_IN_PROGRESS = 'assets_in_progress';
    case ASSETS_GENERATED = 'assets_generated';
    case READY_TO_PUBLISH = 'ready_to_publish';
    case PUBLISHED = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::ENRICHED => 'Enrichi par IA',
            self::ENRICHED_PENDING => 'En attente de validation',
            self::PERSONA_IN_PROGRESS => 'Génération personas...',
            self::PERSONA_GENERATED => 'Personas générées',
            self::COMPETITOR_VALIDATED => 'Concurrents validés',
            self::STRATEGY_IN_PROGRESS => 'Génération stratégie...',
            self::STRATEGY_GENERATED => 'Stratégie générée',
            self::ASSETS_IN_PROGRESS => 'Génération assets...',
            self::ASSETS_GENERATED => 'Assets générés',
            self::READY_TO_PUBLISH => 'Prêt à publier',
            self::PUBLISHED => 'Publié',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-secondary',
            self::ENRICHED => 'bg-info',
            self::ENRICHED_PENDING => 'bg-warning',
            self::PERSONA_IN_PROGRESS, self::STRATEGY_IN_PROGRESS, self::ASSETS_IN_PROGRESS => 'bg-warning',
            self::PERSONA_GENERATED, self::COMPETITOR_VALIDATED, self::STRATEGY_GENERATED, self::ASSETS_GENERATED => 'bg-primary',
            self::READY_TO_PUBLISH => 'bg-success',
            self::PUBLISHED => 'bg-dark',
        };
    }
}
