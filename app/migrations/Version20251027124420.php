<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251027124420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chat_conversation (id INT AUTO_INCREMENT NOT NULL, user_id BIGINT NOT NULL, tenant_id BIGINT NOT NULL, context VARCHAR(20) NOT NULL, title VARCHAR(255) NOT NULL, is_favorite TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_74654F68A76ED395 (user_id), INDEX IDX_74654F689033212A (tenant_id), INDEX idx_chat_conv_user_tenant (user_id, tenant_id), INDEX idx_chat_conv_created (created_at), INDEX idx_chat_conv_favorite (is_favorite), INDEX idx_chat_conv_context (context), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE chat_message (id INT AUTO_INCREMENT NOT NULL, conversation_id INT NOT NULL, role VARCHAR(20) NOT NULL, content LONGTEXT NOT NULL, type VARCHAR(20) DEFAULT NULL, data JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_chat_msg_conversation (conversation_id), INDEX idx_chat_msg_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chat_conversation ADD CONSTRAINT FK_74654F68A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_conversation ADD CONSTRAINT FK_74654F689033212A FOREIGN KEY (tenant_id) REFERENCES division (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC169AC0396 FOREIGN KEY (conversation_id) REFERENCES chat_conversation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_conversation DROP FOREIGN KEY FK_74654F68A76ED395');
        $this->addSql('ALTER TABLE chat_conversation DROP FOREIGN KEY FK_74654F689033212A');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC169AC0396');
        $this->addSql('DROP TABLE chat_conversation');
        $this->addSql('DROP TABLE chat_message');
    }
}
