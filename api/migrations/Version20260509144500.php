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
        $province = $schema->createTable('province');
        $province->addColumn('id', 'integer', ['autoincrement' => true]);
        $province->addColumn('name', 'string', ['length' => 255]);
        $province->addColumn('code', 'string', ['length' => 10]);
        $province->setPrimaryKey(['id']);
        $province->addUniqueIndex(['code'], 'UNIQ_PROVINCE_CODE');

        // Town table
        $town = $schema->createTable('town');
        $town->addColumn('id', 'integer', ['autoincrement' => true]);
        $town->addColumn('province_id', 'integer');
        $town->addColumn('name', 'string', ['length' => 255]);
        $town->addColumn('code', 'string', ['length' => 10]);
        $town->setPrimaryKey(['id']);
        $town->addIndex(['province_id'], 'IDX_TOWN_PROVINCE');
        $town->addForeignKeyConstraint('province', ['province_id'], ['id'], [], 'FK_TOWN_PROVINCE');

        // Update Tournament table
        $tournament = $schema->getTable('tournament');
        $tournament->dropColumn('province');
        $tournament->dropColumn('town');
        $tournament->addColumn('province_id', 'integer', ['notnull' => false]);
        $tournament->addColumn('town_id', 'integer', ['notnull' => false]);
        $tournament->addIndex(['province_id'], 'IDX_TOURNAMENT_PROVINCE');
        $tournament->addIndex(['town_id'], 'IDX_TOURNAMENT_TOWN');
        $tournament->addForeignKeyConstraint('province', ['province_id'], ['id'], [], 'FK_TOURNAMENT_PROVINCE');
        $tournament->addForeignKeyConstraint('town', ['town_id'], ['id'], [], 'FK_TOURNAMENT_TOWN');
    }

    public function down(Schema $schema): void
    {
        $tournament = $schema->getTable('tournament');
        $tournament->removeForeignKey('FK_TOURNAMENT_PROVINCE');
        $tournament->removeForeignKey('FK_TOURNAMENT_TOWN');
        $tournament->dropIndex('IDX_TOURNAMENT_PROVINCE');
        $tournament->dropIndex('IDX_TOURNAMENT_TOWN');
        $tournament->dropColumn('province_id');
        $tournament->dropColumn('town_id');
        $tournament->addColumn('province', 'string', ['length' => 255, 'notnull' => false]);
        $tournament->addColumn('town', 'string', ['length' => 255, 'notnull' => false]);

        $schema->dropTable('town');
        $schema->dropTable('province');
    }
}
