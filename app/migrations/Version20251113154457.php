<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113154457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs scrapedContent et projectContext pour Bundle v3.10.0+ et v3.11.0+';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketing_project ADD scraped_content JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD project_context JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketing_project DROP scraped_content, DROP project_context');
    }
}
