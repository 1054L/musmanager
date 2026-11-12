<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pending_managers JSON column to tournament table for co-admin email invitations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament ADD pending_managers JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament DROP pending_managers');
    }
}

