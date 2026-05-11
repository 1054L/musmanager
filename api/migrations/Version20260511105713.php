<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260511105713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mus_match_game (id INT AUTO_INCREMENT NOT NULL, game_number INT NOT NULL, points_team1 INT NOT NULL, points_team2 INT NOT NULL, mus_match_id INT NOT NULL, INDEX IDX_541BDB4CBD5496BB (mus_match_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE mus_match_game ADD CONSTRAINT FK_541BDB4CBD5496BB FOREIGN KEY (mus_match_id) REFERENCES mus_match (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mus_match_game DROP FOREIGN KEY FK_541BDB4CBD5496BB');
        $this->addSql('DROP TABLE mus_match_game');
    }
}
