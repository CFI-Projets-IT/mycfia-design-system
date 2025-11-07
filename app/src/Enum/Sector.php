<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Enum des secteurs d'activité supportés par le Marketing AI Bundle.
 *
 * Les 7 secteurs définis permettent aux agents IA d'adapter leur vocabulaire,
 * benchmarks et recommandations selon les spécificités sectorielles.
 */
enum Sector: string
{
    case TECH_B2B_SAAS = 'Tech B2B SaaS';
    case ECOMMERCE = 'E-commerce';
    case FINTECH = 'Fintech';
    case HEALTHCARE = 'Healthcare';
    case RETAIL = 'Retail';
    case EDUCATION = 'Education';
    case OTHER = 'Autre';

    /**
     * Retourne le label français du secteur.
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Retourne tous les secteurs disponibles sous forme de tableau.
     *
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return [
            self::TECH_B2B_SAAS->value => self::TECH_B2B_SAAS->value,
            self::ECOMMERCE->value => self::ECOMMERCE->value,
            self::FINTECH->value => self::FINTECH->value,
            self::HEALTHCARE->value => self::HEALTHCARE->value,
            self::RETAIL->value => self::RETAIL->value,
            self::EDUCATION->value => self::EDUCATION->value,
            self::OTHER->value => self::OTHER->value,
        ];
    }
}
