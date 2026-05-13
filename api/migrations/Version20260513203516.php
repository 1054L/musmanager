<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513203516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY `FK_33B4AF255DFCD4B8`');
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY `FK_33B4AF25E72BCFA4`');
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY `FK_33B4AF25F59E604A`');
        $this->addSql('ALTER TABLE mus_match CHANGE team1_id team1_id INT DEFAULT NULL, CHANGE team2_id team2_id INT DEFAULT NULL, CHANGE winner_id winner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT FK_33B4AF255DFCD4B8 FOREIGN KEY (winner_id) REFERENCES tournament_team (id)');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT FK_33B4AF25E72BCFA4 FOREIGN KEY (team1_id) REFERENCES tournament_team (id)');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT FK_33B4AF25F59E604A FOREIGN KEY (team2_id) REFERENCES tournament_team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY FK_33B4AF25E72BCFA4');
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY FK_33B4AF25F59E604A');
        $this->addSql('ALTER TABLE mus_match DROP FOREIGN KEY FK_33B4AF255DFCD4B8');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT `FK_33B4AF25E72BCFA4` FOREIGN KEY (team1_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT `FK_33B4AF25F59E604A` FOREIGN KEY (team2_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE mus_match ADD CONSTRAINT `FK_33B4AF255DFCD4B8` FOREIGN KEY (winner_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
