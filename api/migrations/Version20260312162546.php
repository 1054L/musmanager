<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312162546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__tournament AS SELECT id, name, status, type, uuid_access_token FROM tournament');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('CREATE TABLE tournament (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, uuid_access_token VARCHAR(36) NOT NULL)');
        $this->addSql('INSERT INTO tournament (id, name, status, type, uuid_access_token) SELECT id, name, status, type, uuid_access_token FROM __temp__tournament');
        $this->addSql('DROP TABLE __temp__tournament');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BD5FB8D9BE7A16B0 ON tournament (uuid_access_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__tournament AS SELECT id, name, status, type, uuid_access_token FROM tournament');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('CREATE TABLE tournament (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, uuid_access_token BLOB NOT NULL)');
        $this->addSql('INSERT INTO tournament (id, name, status, type, uuid_access_token) SELECT id, name, status, type, uuid_access_token FROM __temp__tournament');
        $this->addSql('DROP TABLE __temp__tournament');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BD5FB8D9BE7A16B0 ON tournament (uuid_access_token)');
    }
}
