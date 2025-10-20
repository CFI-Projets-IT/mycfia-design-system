<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rendre le champ email nullable dans la table user.
 *
 * Raison : Certains utilisateurs CFI s'authentifient avec un identifiant
 * (login CFI) plutôt qu'une adresse email.
 */
final class Version20251017152850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rendre le champ email nullable pour supporter authentification par identifiant CFI';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(180) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(180) NOT NULL');
    }
}
