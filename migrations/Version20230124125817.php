<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230124125817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE information ADD lan_party_id INT NOT NULL');
        $this->addSql('ALTER TABLE information ADD CONSTRAINT FK_29791883E10CDB4A FOREIGN KEY (lan_party_id) REFERENCES lanparty (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_29791883E10CDB4A ON information (lan_party_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE information DROP CONSTRAINT FK_29791883E10CDB4A');
        $this->addSql('DROP INDEX IDX_29791883E10CDB4A');
        $this->addSql('ALTER TABLE information DROP lan_party_id');
    }
}
