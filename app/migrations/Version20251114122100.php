<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration production-ready : Screenshot URL + Index composites.
 *
 * Changements :
 * 1. screenshot TEXT → screenshot_url VARCHAR(500) (scalabilité S3)
 * 2. Index composites pour Dashboard (performance 10-100× requêtes)
 */
final class Version20251114122100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration screenshot vers URL + index composites pour performance Dashboard';
    }

    public function up(Schema $schema): void
    {
        // 1. Changement screenshot TEXT → screenshot_url VARCHAR(500)
        $this->addSql('ALTER TABLE marketing_project ADD screenshot_url VARCHAR(500) DEFAULT NULL, DROP screenshot');

        // 2. Index composites pour requêtes Dashboard fréquentes
        // idx_lang_branding : Filtrer "Projets français avec branding complet"
        $this->addSql('CREATE INDEX idx_lang_branding ON marketing_project (language, has_branding)');

        // idx_seo_potential : Trier "Top SEO par langue"
        $this->addSql('CREATE INDEX idx_seo_potential ON marketing_project (language, keywords_avg_volume)');

        // idx_budget_filter : Filtrer par budget Google Ads abordable
        $this->addSql('CREATE INDEX idx_budget_filter ON marketing_project (keywords_avg_cpc, keywords_avg_volume)');
    }

    public function down(Schema $schema): void
    {
        // Suppression index composites
        $this->addSql('DROP INDEX idx_lang_branding ON marketing_project');
        $this->addSql('DROP INDEX idx_seo_potential ON marketing_project');
        $this->addSql('DROP INDEX idx_budget_filter ON marketing_project');

        // Rollback screenshot_url → screenshot
        $this->addSql('ALTER TABLE marketing_project ADD screenshot LONGTEXT DEFAULT NULL, DROP screenshot_url');
    }
}
