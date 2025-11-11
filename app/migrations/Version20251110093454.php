<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251110093454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE marketing_task (id INT AUTO_INCREMENT NOT NULL, uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, status VARCHAR(50) NOT NULL, agent_class VARCHAR(255) NOT NULL, method_name VARCHAR(255) NOT NULL, arguments JSON NOT NULL COMMENT \'(DC2Type:json)\', context JSON NOT NULL COMMENT \'(DC2Type:json)\', result JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', tokens_input INT NOT NULL, tokens_output INT NOT NULL, tokens_total INT NOT NULL, cost NUMERIC(10, 4) NOT NULL, duration_ms INT NOT NULL, model_used VARCHAR(100) NOT NULL, error_message LONGTEXT DEFAULT NULL, error_trace LONGTEXT DEFAULT NULL, started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_D059397DD17F50A6 (uuid), INDEX idx_task_status_completed (status, completed_at), INDEX idx_task_agent_class (agent_class), INDEX idx_task_type (type), INDEX idx_task_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE marketing_project CHANGE company_name company_name VARCHAR(255) NOT NULL, CHANGE sector sector VARCHAR(255) NOT NULL, CHANGE detailed_objectives detailed_objectives LONGTEXT NOT NULL, CHANGE start_date start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE end_date end_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE marketing_task');
        $this->addSql('ALTER TABLE marketing_project CHANGE company_name company_name VARCHAR(255) DEFAULT \'Entreprise à définir\' NOT NULL, CHANGE sector sector VARCHAR(255) DEFAULT \'Autre\' NOT NULL, CHANGE detailed_objectives detailed_objectives LONGTEXT DEFAULT \'Objectifs marketing à définir\' NOT NULL, CHANGE start_date start_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE end_date end_date DATETIME DEFAULT \'(current_timestamp() + interval 30 day)\' NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
