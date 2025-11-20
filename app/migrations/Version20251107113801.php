<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251107113801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des 6 champs requis par Marketing AI Bundle v2.5.0 + correction relation Strategy (OneToOne → ManyToOne)';
    }

    public function up(Schema $schema): void
    {
        // Ajout des 6 nouveaux champs requis par le Marketing AI Bundle v2.5.0
        // Avec valeurs par défaut pour préserver les données existantes
        $this->addSql('ALTER TABLE marketing_project ADD company_name VARCHAR(255) DEFAULT "Entreprise à définir" NOT NULL');
        $this->addSql('ALTER TABLE marketing_project ADD sector VARCHAR(255) DEFAULT "Autre" NOT NULL');
        $this->addSql('ALTER TABLE marketing_project ADD detailed_objectives LONGTEXT DEFAULT "Objectifs marketing à définir" NOT NULL');
        $this->addSql('ALTER TABLE marketing_project ADD start_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE marketing_project ADD end_date DATETIME DEFAULT (DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY)) NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE marketing_project ADD website_url VARCHAR(255) DEFAULT NULL');

        // Correction de la relation Strategy : OneToOne → ManyToOne
        // Suppression de l'index UNIQUE sur project_id (permet plusieurs stratégies par projet)
        $this->addSql('DROP INDEX UNIQ_69EA3607166D1F9C ON marketing_strategy');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketing_project DROP company_name, DROP sector, DROP detailed_objectives, DROP start_date, DROP end_date, DROP website_url');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_69EA3607166D1F9C ON marketing_strategy (project_id)');
    }
}
