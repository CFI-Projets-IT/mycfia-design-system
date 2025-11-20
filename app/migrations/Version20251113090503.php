<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113090503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Augmente la précision de quality_score de DECIMAL(3,2) à DECIMAL(5,2) pour supporter échelle 0-100 (Marketing AI Bundle v3.8.5+)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketing_persona CHANGE quality_score quality_score NUMERIC(5, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketing_persona CHANGE quality_score quality_score NUMERIC(3, 2) DEFAULT NULL');
    }
}
