<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Creates Province and Town tables and updates Tournament to use relations.
 */
final class Version20260509144500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates Province and Town tables and updates Tournament to use relations.';
    }

    public function up(Schema $schema): void
    {
        // Province table
        $this->addSql('CREATE TABLE province (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL, code VARCHAR(10) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROVINCE_CODE ON province (code)');

        // Town table
        $this->addSql('CREATE TABLE town (id SERIAL PRIMARY KEY, province_id INT NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(10) NOT NULL)');
        $this->addSql('CREATE INDEX IDX_TOWN_PROVINCE ON town (province_id)');
        $this->addSql('ALTER TABLE town ADD CONSTRAINT FK_TOWN_PROVINCE FOREIGN KEY (province_id) REFERENCES province (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Update Tournament table: remove old string columns and add relation columns
        $this->addSql('ALTER TABLE tournament DROP province');
        $this->addSql('ALTER TABLE tournament DROP town');
        $this->addSql('ALTER TABLE tournament ADD province_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tournament ADD town_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_TOURNAMENT_PROVINCE ON tournament (province_id)');
        $this->addSql('CREATE INDEX IDX_TOURNAMENT_TOWN ON tournament (town_id)');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_TOURNAMENT_PROVINCE FOREIGN KEY (province_id) REFERENCES province (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_TOURNAMENT_TOWN FOREIGN KEY (town_id) REFERENCES town (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament DROP CONSTRAINT FK_TOURNAMENT_PROVINCE');
        $this->addSql('ALTER TABLE tournament DROP CONSTRAINT FK_TOURNAMENT_TOWN');
        $this->addSql('DROP TABLE town');
        $this->addSql('DROP TABLE province');
        $this->addSql('ALTER TABLE tournament DROP province_id');
        $this->addSql('ALTER TABLE tournament DROP town_id');
        $this->addSql('ALTER TABLE tournament ADD province VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tournament ADD town VARCHAR(255) DEFAULT NULL');
    }
}
