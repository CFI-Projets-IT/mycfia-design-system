<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201132932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE marketing_competitor (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, domain VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(255) DEFAULT NULL, alignment_score INT NOT NULL, reasoning LONGTEXT DEFAULT NULL, offering_overlap VARCHAR(50) DEFAULT NULL, market_overlap VARCHAR(50) DEFAULT NULL, has_ads TINYINT(1) NOT NULL, raw_data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', selected TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_competitor_project (project_id), INDEX idx_competitor_selected (selected), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE marketing_competitor ADD CONSTRAINT FK_8D487DE6166D1F9C FOREIGN KEY (project_id) REFERENCES marketing_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketing_competitor_analysis DROP FOREIGN KEY FK_8540C268166D1F9C');
        $this->addSql('DROP TABLE marketing_competitor_analysis');
        $this->addSql('ALTER TABLE marketing_project ADD competitive_market_overview LONGTEXT DEFAULT NULL, ADD competitive_threats JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD competitive_opportunities JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD competitive_recommendations JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD competitive_analysis_generated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE marketing_competitor_analysis (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, competitors LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, competitor_details LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Analyse détaillée PAR concurrent (JSON: {domain: {positioning, strengths[], weaknesses[]}})\', threats LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Menaces concurrentielles globales (JSON: [threat1, threat2])\', market_overview LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Vue d\'\'ensemble du marché (string narrative)\', opportunities LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Opportunités de marché identifiées (JSON: [opp1, opp2])\', recommendations LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Recommandations stratégiques (JSON: [rec1, rec2])\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_competitor_project (project_id), UNIQUE INDEX UNIQ_8540C268166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE marketing_competitor_analysis ADD CONSTRAINT FK_8540C268166D1F9C FOREIGN KEY (project_id) REFERENCES marketing_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketing_competitor DROP FOREIGN KEY FK_8D487DE6166D1F9C');
        $this->addSql('DROP TABLE marketing_competitor');
        $this->addSql('ALTER TABLE marketing_project DROP competitive_market_overview, DROP competitive_threats, DROP competitive_opportunities, DROP competitive_recommendations, DROP competitive_analysis_generated_at');
    }
}
