<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251114120204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketing_project ADD language VARCHAR(10) DEFAULT NULL, ADD brand_primary_color VARCHAR(7) DEFAULT NULL, ADD has_screenshot TINYINT(1) NOT NULL, ADD has_branding TINYINT(1) NOT NULL, ADD keywords_avg_volume INT DEFAULT NULL, ADD keywords_avg_cpc NUMERIC(6, 2) DEFAULT NULL, ADD brand_identity JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD business_intelligence JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD keywords_data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD ai_enrichment JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD screenshot LONGTEXT DEFAULT NULL, ADD enrichment_metrics JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', DROP brand_metadata, DROP project_context');
        $this->addSql('CREATE INDEX idx_project_language ON marketing_project (language)');
        $this->addSql('CREATE INDEX idx_project_keywords_volume ON marketing_project (keywords_avg_volume)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_project_language ON marketing_project');
        $this->addSql('DROP INDEX idx_project_keywords_volume ON marketing_project');
        $this->addSql('ALTER TABLE marketing_project ADD brand_metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD project_context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', DROP language, DROP brand_primary_color, DROP has_screenshot, DROP has_branding, DROP keywords_avg_volume, DROP keywords_avg_cpc, DROP brand_identity, DROP business_intelligence, DROP keywords_data, DROP ai_enrichment, DROP screenshot, DROP enrichment_metrics');
    }
}
