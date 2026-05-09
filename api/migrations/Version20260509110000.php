<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Adds province, town and rulesPath to tournament table
 */
final class Version20260509110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds province, town and rulesPath columns to tournament table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament ADD province VARCHAR(255) DEFAULT NULL, ADD town VARCHAR(255) DEFAULT NULL, ADD rules_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament DROP province, DROP town, DROP rules_path');
    }
}
