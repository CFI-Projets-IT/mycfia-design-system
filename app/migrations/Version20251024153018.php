<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024153018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_accessible_divisions table for multi-tenant hierarchical system';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_accessible_divisions (id BIGINT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, division_id BIGINT NOT NULL, synced_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B7E9476741859289 (division_id), INDEX idx_user_accessible_divisions_user (user_id), INDEX idx_user_accessible_divisions_synced (synced_at), UNIQUE INDEX unique_user_division (user_id, division_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_accessible_divisions ADD CONSTRAINT FK_B7E94767A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_accessible_divisions ADD CONSTRAINT FK_B7E9476741859289 FOREIGN KEY (division_id) REFERENCES division (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_accessible_divisions DROP FOREIGN KEY FK_B7E94767A76ED395');
        $this->addSql('ALTER TABLE user_accessible_divisions DROP FOREIGN KEY FK_B7E9476741859289');
        $this->addSql('DROP TABLE user_accessible_divisions');
    }
}
