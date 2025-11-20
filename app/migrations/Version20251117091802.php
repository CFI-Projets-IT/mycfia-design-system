<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117091802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project_enrichment_draft (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, task_id VARCHAR(36) NOT NULL, enrichment_data JSON NOT NULL COMMENT \'(DC2Type:json)\', status VARCHAR(20) NOT NULL, enriched_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_18FA3DD1166D1F9C (project_id), INDEX idx_enrichment_draft_task_id (task_id), INDEX idx_enrichment_draft_status (status), INDEX idx_enrichment_draft_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_enrichment_draft ADD CONSTRAINT FK_18FA3DD1166D1F9C FOREIGN KEY (project_id) REFERENCES marketing_project (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_seo_potential ON marketing_project');
        $this->addSql('DROP INDEX idx_lang_branding ON marketing_project');
        $this->addSql('DROP INDEX idx_budget_filter ON marketing_project');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_enrichment_draft DROP FOREIGN KEY FK_18FA3DD1166D1F9C');
        $this->addSql('DROP TABLE project_enrichment_draft');
        $this->addSql('CREATE INDEX idx_seo_potential ON marketing_project (language, keywords_avg_volume)');
        $this->addSql('CREATE INDEX idx_lang_branding ON marketing_project (language, has_branding)');
        $this->addSql('CREATE INDEX idx_budget_filter ON marketing_project (keywords_avg_cpc, keywords_avg_volume)');
    }
}
