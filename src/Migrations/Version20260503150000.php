<?php

declare(strict_types=1);

namespace Fusio\Impl\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds fusio_operation.response_headers: JSON-encoded map<string,string> of headers that, when set, force-override
 * same-named headers on the outgoing response (CORS / Cache-Control / etc.). NULL = feature inactive for this row.
 */
final class Version20260503150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fusio_operation.response_headers (JSON-encoded map<string,string>, nullable)';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('fusio_operation');
        if (!$table->hasColumn('response_headers')) {
            $table->addColumn('response_headers', 'text', ['notnull' => false, 'default' => null]);
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('fusio_operation');
        if ($table->hasColumn('response_headers')) {
            $table->dropColumn('response_headers');
        }
    }
}
