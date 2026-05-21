<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260515180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add match_day to mus_match';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mus_match ADD match_day INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mus_match DROP match_day');
    }
}
