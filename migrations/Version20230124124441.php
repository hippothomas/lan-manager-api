<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230124124441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE information_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE information (id INT NOT NULL, title VARCHAR(255) NOT NULL, content TEXT DEFAULT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE information_user (information_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(information_id, user_id))');
        $this->addSql('CREATE INDEX IDX_AC3BDF542EF03101 ON information_user (information_id)');
        $this->addSql('CREATE INDEX IDX_AC3BDF54A76ED395 ON information_user (user_id)');
        $this->addSql('ALTER TABLE information_user ADD CONSTRAINT FK_AC3BDF542EF03101 FOREIGN KEY (information_id) REFERENCES information (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE information_user ADD CONSTRAINT FK_AC3BDF54A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE information_id_seq CASCADE');
        $this->addSql('ALTER TABLE information_user DROP CONSTRAINT FK_AC3BDF542EF03101');
        $this->addSql('ALTER TABLE information_user DROP CONSTRAINT FK_AC3BDF54A76ED395');
        $this->addSql('DROP TABLE information');
        $this->addSql('DROP TABLE information_user');
    }
}
