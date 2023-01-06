<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230106082521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE lanparty_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE lanparty (id INT NOT NULL, name VARCHAR(255) NOT NULL, max_players INT NOT NULL, private BOOLEAN NOT NULL, registration_open BOOLEAN NOT NULL, location VARCHAR(255) NOT NULL, cover_image VARCHAR(255) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, cost DOUBLE PRECISION DEFAULT NULL, description TEXT DEFAULT NULL, date_sart TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_end TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE lanparty_id_seq CASCADE');
        $this->addSql('DROP TABLE lanparty');
    }
}
