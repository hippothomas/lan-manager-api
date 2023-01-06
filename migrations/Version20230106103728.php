<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230106103728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE registration_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE registration (id INT NOT NULL, account_id INT NOT NULL, lan_party_id INT NOT NULL, roles TEXT NOT NULL, status VARCHAR(255) NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_62A8A7A79B6B5FBA ON registration (account_id)');
        $this->addSql('CREATE INDEX IDX_62A8A7A7E10CDB4A ON registration (lan_party_id)');
        $this->addSql('COMMENT ON COLUMN registration.roles IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A79B6B5FBA FOREIGN KEY (account_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A7E10CDB4A FOREIGN KEY (lan_party_id) REFERENCES lanparty (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE registration_id_seq CASCADE');
        $this->addSql('ALTER TABLE registration DROP CONSTRAINT FK_62A8A7A79B6B5FBA');
        $this->addSql('ALTER TABLE registration DROP CONSTRAINT FK_62A8A7A7E10CDB4A');
        $this->addSql('DROP TABLE registration');
    }
}
