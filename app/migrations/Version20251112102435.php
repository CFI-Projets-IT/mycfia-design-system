<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112102435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactoring Persona entity: ajoute description et raw_data, supprime les anciens champs JSON (interests, behaviors, motivations, pains)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketing_persona ADD description LONGTEXT DEFAULT NULL, ADD raw_data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', DROP interests, DROP behaviors, DROP motivations, DROP pains');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketing_persona ADD interests LONGTEXT NOT NULL, ADD behaviors LONGTEXT NOT NULL, ADD motivations LONGTEXT NOT NULL, ADD pains LONGTEXT NOT NULL, DROP description, DROP raw_data');
    }
}
