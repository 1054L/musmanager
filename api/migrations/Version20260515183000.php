<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260515183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure all columns exist for League support - Smart Version';
    }

    public function up(Schema $schema): void
    {
        $tableTournament = $schema->getTable('tournament');
        if (!$tableTournament->hasColumn('type')) {
            $this->addSql("ALTER TABLE tournament ADD type VARCHAR(50) DEFAULT 'eliminatory'");
        }
        if (!$tableTournament->hasColumn('has_third_place')) {
            $this->addSql("ALTER TABLE tournament ADD has_third_place TINYINT(1) DEFAULT 0");
        }

        $tableMusMatch = $schema->getTable('mus_match');
        if (!$tableMusMatch->hasColumn('match_day')) {
            $this->addSql("ALTER TABLE mus_match ADD match_day INT DEFAULT NULL");
        }
        if (!$tableMusMatch->hasColumn('mesa')) {
            $this->addSql("ALTER TABLE mus_match ADD mesa VARCHAR(50) DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
    }
}
