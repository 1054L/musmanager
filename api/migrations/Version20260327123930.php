<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260327123930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "app_user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, player_id INTEGER DEFAULT NULL, CONSTRAINT FK_88BDF3E999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E999E6F5DF ON "app_user" (player_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "app_user" (email)');
        $this->addSql('CREATE TABLE mus_match (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, score_team1 INTEGER NOT NULL, score_team2 INTEGER NOT NULL, stage VARCHAR(100) NOT NULL, tournament_id INTEGER NOT NULL, team1_id INTEGER NOT NULL, team2_id INTEGER NOT NULL, winner_id INTEGER DEFAULT NULL, CONSTRAINT FK_33B4AF2533D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_33B4AF25E72BCFA4 FOREIGN KEY (team1_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_33B4AF25F59E604A FOREIGN KEY (team2_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_33B4AF255DFCD4B8 FOREIGN KEY (winner_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_33B4AF2533D1A3E7 ON mus_match (tournament_id)');
        $this->addSql('CREATE INDEX IDX_33B4AF25E72BCFA4 ON mus_match (team1_id)');
        $this->addSql('CREATE INDEX IDX_33B4AF25F59E604A ON mus_match (team2_id)');
        $this->addSql('CREATE INDEX IDX_33B4AF255DFCD4B8 ON mus_match (winner_id)');
        $this->addSql('CREATE TABLE player (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_by_id INTEGER DEFAULT NULL, CONSTRAINT FK_98197A65B03A8386 FOREIGN KEY (created_by_id) REFERENCES "app_user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65E7927C74 ON player (email)');
        $this->addSql('CREATE INDEX IDX_98197A65B03A8386 ON player (created_by_id)');
        $this->addSql('CREATE TABLE team (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_by_id INTEGER DEFAULT NULL, CONSTRAINT FK_C4E0A61FB03A8386 FOREIGN KEY (created_by_id) REFERENCES "app_user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_C4E0A61FB03A8386 ON team (created_by_id)');
        $this->addSql('CREATE TABLE team_player (team_id INTEGER NOT NULL, player_id INTEGER NOT NULL, PRIMARY KEY (team_id, player_id), CONSTRAINT FK_EE023DBC296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_EE023DBC99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_EE023DBC296CD8AE ON team_player (team_id)');
        $this->addSql('CREATE INDEX IDX_EE023DBC99E6F5DF ON team_player (player_id)');
        $this->addSql('CREATE TABLE tournament (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, uuid_access_token VARCHAR(36) NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, poster_path VARCHAR(255) DEFAULT NULL, status_description CLOB DEFAULT NULL, rule_kings INTEGER DEFAULT 8 NOT NULL, rule_points INTEGER DEFAULT 40 NOT NULL, rule_games INTEGER DEFAULT 3 NOT NULL, tables_count INTEGER DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, created_by_id INTEGER DEFAULT NULL, CONSTRAINT FK_BD5FB8D9B03A8386 FOREIGN KEY (created_by_id) REFERENCES "app_user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BD5FB8D9BE7A16B0 ON tournament (uuid_access_token)');
        $this->addSql('CREATE INDEX IDX_BD5FB8D9B03A8386 ON tournament (created_by_id)');
        $this->addSql('CREATE TABLE tournament_managers (tournament_id INTEGER NOT NULL, user_id INTEGER NOT NULL, PRIMARY KEY (tournament_id, user_id), CONSTRAINT FK_49F706E333D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_49F706E3A76ED395 FOREIGN KEY (user_id) REFERENCES "app_user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_49F706E333D1A3E7 ON tournament_managers (tournament_id)');
        $this->addSql('CREATE INDEX IDX_49F706E3A76ED395 ON tournament_managers (user_id)');
        $this->addSql('CREATE TABLE tournament_team (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, group_name VARCHAR(50) DEFAULT NULL, points INTEGER DEFAULT 0 NOT NULL, games_won INTEGER DEFAULT 0 NOT NULL, games_lost INTEGER DEFAULT 0 NOT NULL, matches_played INTEGER DEFAULT 0 NOT NULL, tournament_id INTEGER NOT NULL, team_id INTEGER NOT NULL, CONSTRAINT FK_F36D142133D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F36D1421296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F36D142133D1A3E7 ON tournament_team (tournament_id)');
        $this->addSql('CREATE INDEX IDX_F36D1421296CD8AE ON tournament_team (team_id)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE "app_user"');
        $this->addSql('DROP TABLE mus_match');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_player');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('DROP TABLE tournament_managers');
        $this->addSql('DROP TABLE tournament_team');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
