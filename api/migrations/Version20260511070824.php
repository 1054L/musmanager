<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260511070824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user ADD nickname VARCHAR(100) DEFAULT NULL, ADD phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE province RENAME INDEX uniq_province_code TO UNIQ_4ADAD40B77153098');
        $this->addSql('ALTER TABLE tournament RENAME INDEX idx_tournament_province TO IDX_BD5FB8D9E946114A');
        $this->addSql('ALTER TABLE tournament RENAME INDEX idx_tournament_town TO IDX_BD5FB8D975E23604');
        $this->addSql('ALTER TABLE town RENAME INDEX idx_town_province TO IDX_4CE6C7A4E946114A');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `app_user` DROP nickname, DROP phone');
        $this->addSql('ALTER TABLE province RENAME INDEX uniq_4adad40b77153098 TO UNIQ_PROVINCE_CODE');
        $this->addSql('ALTER TABLE tournament RENAME INDEX idx_bd5fb8d975e23604 TO IDX_TOURNAMENT_TOWN');
        $this->addSql('ALTER TABLE tournament RENAME INDEX idx_bd5fb8d9e946114a TO IDX_TOURNAMENT_PROVINCE');
        $this->addSql('ALTER TABLE town RENAME INDEX idx_4ce6c7a4e946114a TO IDX_TOWN_PROVINCE');
    }
}
