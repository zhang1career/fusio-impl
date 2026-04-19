<?php

declare(strict_types=1);

namespace Fusio\Impl\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds fusio_operation.usability: 0 = internal (Fusio token), 1 = external (user center JWT).
 */
final class Version20260419120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fusio_operation.usability (0=internal, 1=external)';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('fusio_operation');
        if (!$table->hasColumn('usability')) {
            $table->addColumn('usability', 'integer', ['notnull' => true, 'default' => 0]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('fusio_operation');
        if ($table->hasColumn('usability')) {
            $table->dropColumn('usability');
        }
    }
}
