<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407091129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `app_user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, player_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_88BDF3E999E6F5DF (player_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mus_match (id INT AUTO_INCREMENT NOT NULL, score_team1 INT NOT NULL, score_team2 INT NOT NULL, stage VARCHAR(100) NOT NULL, tournament_id INT NOT NULL, team1_id INT NOT NULL, team2_id INT NOT NULL, winner_id INT DEFAULT NULL, INDEX IDX_33B4AF2533D1A3E7 (tournament_id), INDEX IDX_33B4AF25E72BCFA4 (team1_id), INDEX IDX_33B4AF25F59E604A (team2_id), INDEX IDX_33B4AF255DFCD4B8 (winner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE player (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_98197A65E7927C74 (email), INDEX IDX_98197A65B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_C4E0A61FB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team_player (team_id INT NOT NULL, player_id INT NOT NULL, INDEX IDX_EE023DBC296CD8AE (team_id), INDEX IDX_EE023DBC99E6F5DF (player_id), PRIMARY KEY (team_id, player_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tournament (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, uuid_access_token VARCHAR(36) NOT NULL, start_date DATETIME DEFAULT NULL, end_date DATETIME DEFAULT NULL, poster_path VARCHAR(255) DEFAULT NULL, status_description LONGTEXT DEFAULT NULL, rule_kings INT DEFAULT 8 NOT NULL, rule_points INT DEFAULT 40 NOT NULL, rule_games INT DEFAULT 3 NOT NULL, tables_count INT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, created_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_BD5FB8D9BE7A16B0 (uuid_access_token), INDEX IDX_BD5FB8D9B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tournament_managers (tournament_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_49F706E333D1A3E7 (tournament_id), INDEX IDX_49F706E3A76ED395 (user_id), PRIMARY KEY (tournament_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tournament_team (id INT AUTO_INCREMENT NOT NULL, group_name VARCHAR(50) DEFAULT NULL, points INT DEFAULT 0 NOT NULL, games_won INT DEFAULT 0 NOT NULL, games_lost INT DEFAULT 0 NOT NULL, matches_played INT DEFAULT 0 NOT NULL, tournament_id INT NOT NULL, team_id INT NOT NULL, INDEX IDX_F36D142133D1A3E7 (tournament_id), INDEX IDX_F36D1421296CD8AE (team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE `app_user` ADD CONSTRAINT FK_88BDF3E999E6F5DF FOREIGN KEY (player_id) REFERENCES player (id)');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT FK_33B4AF2533D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT FK_33B4AF25E72BCFA4 FOREIGN KEY (team1_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT FK_33B4AF25F59E604A FOREIGN KEY (team2_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT FK_33B4AF255DFCD4B8 FOREIGN KEY (winner_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65B03A8386 FOREIGN KEY (created_by_id) REFERENCES `app_user` (id)');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FB03A8386 FOREIGN KEY (created_by_id) REFERENCES `app_user` (id)');
        $this->addSql('ALTER TABLE team_player ADD CONSTRAINT FK_EE023DBC296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_player ADD CONSTRAINT FK_EE023DBC99E6F5DF FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_BD5FB8D9B03A8386 FOREIGN KEY (created_by_id) REFERENCES `app_user` (id)');
        $this->addSql('ALTER TABLE tournament_managers ADD CONSTRAINT FK_49F706E333D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_managers ADD CONSTRAINT FK_49F706E3A76ED395 FOREIGN KEY (user_id) REFERENCES `app_user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_team ADD CONSTRAINT FK_F36D142133D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)');
        $this->addSql('ALTER TABLE tournament_team ADD CONSTRAINT FK_F36D1421296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `app_user` DROP FOREIGN KEY FK_88BDF3E999E6F5DF');
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY FK_33B4AF2533D1A3E7');
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY FK_33B4AF25E72BCFA4');
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY FK_33B4AF25F59E604A');
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY FK_33B4AF255DFCD4B8');
        $this->addSql('ALTER TABLE player DROP FOREIGN KEY FK_98197A65B03A8386');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61FB03A8386');
        $this->addSql('ALTER TABLE team_player DROP FOREIGN KEY FK_EE023DBC296CD8AE');
        $this->addSql('ALTER TABLE team_player DROP FOREIGN KEY FK_EE023DBC99E6F5DF');
        $this->addSql('ALTER TABLE tournament DROP FOREIGN KEY FK_BD5FB8D9B03A8386');
        $this->addSql('ALTER TABLE tournament_managers DROP FOREIGN KEY FK_49F706E333D1A3E7');
        $this->addSql('ALTER TABLE tournament_managers DROP FOREIGN KEY FK_49F706E3A76ED395');
        $this->addSql('ALTER TABLE tournament_team DROP FOREIGN KEY FK_F36D142133D1A3E7');
        $this->addSql('ALTER TABLE tournament_team DROP FOREIGN KEY FK_F36D1421296CD8AE');
        $this->addSql('DROP TABLE `app_user`');
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
