<?php
/*
 * Fusio is an open source API management platform which helps to create innovative API solutions.
 * For the current version and information visit <https://www.fusio-project.org/>
 *
 * Copyright 2015-2023 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Fusio\Impl\Tests\Backend\Api\Generator;

use Doctrine\DBAL\Connection;
use Fusio\Adapter\Sql\Generator\SqlEntity;

/**
 * SqlEntityTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class SqlEntityTest extends ProviderTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::dropAppTables($this->connection);
    }

    protected function getProviderClass(): string
    {
        return SqlEntity::class;
    }

    protected function getProviderConfig(): array
    {
        $typeSchema = \json_decode(file_get_contents(__DIR__ . '/resource/typeschema.json'));

        return [
            'connection' => 1,
            'schema' => $typeSchema,
        ];
    }

    protected function getExpectChangelog(): string
    {
        return file_get_contents(__DIR__ . '/resource/changelog_sqlentity.json');
    }

    protected function getExpectSchema(): string
    {
        $data = $this->getExpectChangelog();
        $data = str_replace('schema:\/\/', 'schema:\/\/Provider_', $data);

        return $data;
    }

    protected function getExpectForm(): string
    {
        return <<<'JSON'
{
    "element": [
        {
            "element": "select",
            "name": "connection",
            "title": "Connection",
            "help": "The SQL connection which should be used",
            "options": [
                {
                    "key": "3",
                    "value": "Paypal"
                },
                {
                    "key": "1",
                    "value": "System"
                },
                {
                    "key": "2",
                    "value": "Test"
                }
            ]
        },
        {
            "element": "typeschema",
            "name": "schema",
            "title": "Schema",
            "help": "TypeSchema specification"
        }
    ]
}
JSON;
    }

    public static function dropAppTables(Connection $connection): void
    {
        $tableNames = [
            'app_human_0_location',
            'app_human_0_category',
            'app_human_0',
            'app_location_0',
            'app_category_0',
        ];

        $schemaManager = $connection->createSchemaManager();
        foreach ($tableNames as $tableName) {
            if ($schemaManager->tablesExist($tableName)) {
                $connection->executeQuery('DELETE FROM ' . $tableName . ' WHERE 1=1');
            }
        }

        foreach ($tableNames as $tableName) {
            if ($schemaManager->tablesExist($tableName)) {
                $schemaManager->dropTable($tableName);
            }
        }
    }
}
