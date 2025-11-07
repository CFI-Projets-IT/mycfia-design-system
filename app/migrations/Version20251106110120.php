<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106110120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE marketing_asset (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, asset_type VARCHAR(50) NOT NULL, channel VARCHAR(50) NOT NULL, content LONGTEXT NOT NULL, variations LONGTEXT DEFAULT NULL, status VARCHAR(255) NOT NULL, quality_score NUMERIC(3, 2) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_asset_project (project_id), INDEX idx_asset_status (status), INDEX idx_asset_type (asset_type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE marketing_competitor_analysis (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, competitors LONGTEXT NOT NULL, strengths LONGTEXT NOT NULL, weaknesses LONGTEXT NOT NULL, market_positioning LONGTEXT NOT NULL, differentiation_opportunities LONGTEXT NOT NULL, marketing_strategies LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8540C268166D1F9C (project_id), INDEX idx_competitor_project (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE marketing_persona (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, age INT NOT NULL, gender VARCHAR(50) NOT NULL, job VARCHAR(255) NOT NULL, interests LONGTEXT NOT NULL, behaviors LONGTEXT NOT NULL, motivations LONGTEXT NOT NULL, pains LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_persona_project (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE marketing_project (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, tenant_id BIGINT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, product_info LONGTEXT NOT NULL, goal_type VARCHAR(255) NOT NULL, budget NUMERIC(10, 2) NOT NULL, status VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_845E8B61A76ED395 (user_id), INDEX IDX_845E8B619033212A (tenant_id), INDEX idx_project_user_tenant (user_id, tenant_id), INDEX idx_project_status (status), INDEX idx_project_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE marketing_strategy (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, positioning LONGTEXT NOT NULL, key_messages LONGTEXT NOT NULL, recommended_channels LONGTEXT NOT NULL, timeline LONGTEXT NOT NULL, budget_allocation LONGTEXT NOT NULL, kpis LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_69EA3607166D1F9C (project_id), INDEX idx_strategy_project (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE marketing_asset ADD CONSTRAINT FK_679DA478166D1F9C FOREIGN KEY (project_id) REFERENCES marketing_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketing_competitor_analysis ADD CONSTRAINT FK_8540C268166D1F9C FOREIGN KEY (project_id) REFERENCES marketing_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketing_persona ADD CONSTRAINT FK_FA08ED14166D1F9C FOREIGN KEY (project_id) REFERENCES marketing_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketing_project ADD CONSTRAINT FK_845E8B61A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketing_project ADD CONSTRAINT FK_845E8B619033212A FOREIGN KEY (tenant_id) REFERENCES division (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketing_strategy ADD CONSTRAINT FK_69EA3607166D1F9C FOREIGN KEY (project_id) REFERENCES marketing_project (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketing_asset DROP FOREIGN KEY FK_679DA478166D1F9C');
        $this->addSql('ALTER TABLE marketing_competitor_analysis DROP FOREIGN KEY FK_8540C268166D1F9C');
        $this->addSql('ALTER TABLE marketing_persona DROP FOREIGN KEY FK_FA08ED14166D1F9C');
        $this->addSql('ALTER TABLE marketing_project DROP FOREIGN KEY FK_845E8B61A76ED395');
        $this->addSql('ALTER TABLE marketing_project DROP FOREIGN KEY FK_845E8B619033212A');
        $this->addSql('ALTER TABLE marketing_strategy DROP FOREIGN KEY FK_69EA3607166D1F9C');
        $this->addSql('DROP TABLE marketing_asset');
        $this->addSql('DROP TABLE marketing_competitor_analysis');
        $this->addSql('DROP TABLE marketing_persona');
        $this->addSql('DROP TABLE marketing_project');
        $this->addSql('DROP TABLE marketing_strategy');
    }
}
