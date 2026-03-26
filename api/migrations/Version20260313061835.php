<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313061835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tournament_managers (tournament_id INTEGER NOT NULL, user_id INTEGER NOT NULL, PRIMARY KEY (tournament_id, user_id), CONSTRAINT FK_49F706E333D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_49F706E3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_49F706E333D1A3E7 ON tournament_managers (tournament_id)');
        $this->addSql('CREATE INDEX IDX_49F706E3A76ED395 ON tournament_managers (user_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__player AS SELECT id, name, email FROM player');
        $this->addSql('DROP TABLE player');
        $this->addSql('CREATE TABLE player (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_by_id INTEGER DEFAULT NULL, CONSTRAINT FK_98197A65B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO player (id, name, email) SELECT id, name, email FROM __temp__player');
        $this->addSql('DROP TABLE __temp__player');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65E7927C74 ON player (email)');
        $this->addSql('CREATE INDEX IDX_98197A65B03A8386 ON player (created_by_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tournament AS SELECT id, name, status, type, uuid_access_token FROM tournament');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('CREATE TABLE tournament (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, uuid_access_token VARCHAR(36) NOT NULL, created_by_id INTEGER DEFAULT NULL, CONSTRAINT FK_BD5FB8D9B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO tournament (id, name, status, type, uuid_access_token) SELECT id, name, status, type, uuid_access_token FROM __temp__tournament');
        $this->addSql('DROP TABLE __temp__tournament');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BD5FB8D9BE7A16B0 ON tournament (uuid_access_token)');
        $this->addSql('CREATE INDEX IDX_BD5FB8D9B03A8386 ON tournament (created_by_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, player_id INTEGER DEFAULT NULL, CONSTRAINT FK_8D93D64999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO user (id, email, roles, password) SELECT id, email, roles, password FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64999E6F5DF ON user (player_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE tournament_managers');
        $this->addSql('CREATE TEMPORARY TABLE __temp__player AS SELECT id, name, email FROM player');
        $this->addSql('DROP TABLE player');
        $this->addSql('CREATE TABLE player (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO player (id, name, email) SELECT id, name, email FROM __temp__player');
        $this->addSql('DROP TABLE __temp__player');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65E7927C74 ON player (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tournament AS SELECT id, name, status, type, uuid_access_token FROM tournament');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('CREATE TABLE tournament (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, uuid_access_token VARCHAR(36) NOT NULL)');
        $this->addSql('INSERT INTO tournament (id, name, status, type, uuid_access_token) SELECT id, name, status, type, uuid_access_token FROM __temp__tournament');
        $this->addSql('DROP TABLE __temp__tournament');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BD5FB8D9BE7A16B0 ON tournament (uuid_access_token)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password) SELECT id, email, roles, password FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
    }
}
