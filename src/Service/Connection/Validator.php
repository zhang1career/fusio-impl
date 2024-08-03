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

namespace Fusio\Impl\Service\Connection;

use Fusio\Impl\Service\System\FrameworkConfig;
use Fusio\Impl\Service\Tenant\UsageLimiter;
use Fusio\Impl\Table;
use Fusio\Model\Backend\Connection;
use PSX\Http\Exception as StatusCode;

/**
 * Validator
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class Validator
{
    private Table\Connection $connectionTable;
    private FrameworkConfig $frameworkConfig;
    private UsageLimiter $usageLimiter;

    public function __construct(Table\Connection $connectionTable, FrameworkConfig $frameworkConfig, UsageLimiter $usageLimiter)
    {
        $this->connectionTable = $connectionTable;
        $this->frameworkConfig = $frameworkConfig;
        $this->usageLimiter = $usageLimiter;
    }

    public function assert(Connection $connection, ?string $tenantId, ?Table\Generated\ConnectionRow $existing = null): void
    {
        $this->usageLimiter->assertConnectionCount($tenantId);

        $this->assertExcluded($connection);

        $name = $connection->getName();
        if ($name !== null) {
            $this->assertName($name, $tenantId, $existing);
        } elseif ($existing === null) {
            throw new StatusCode\BadRequestException('Connection name must not be empty');
        }
    }

    private function assertName(string $name, ?string $tenantId, ?Table\Generated\ConnectionRow $existing = null): void
    {
        if (empty($name) || !preg_match('/^[a-zA-Z0-9\\-\\_]{3,255}$/', $name)) {
            throw new StatusCode\BadRequestException('Invalid connection name');
        }

        if (($existing === null || $name !== $existing->getName()) && $this->connectionTable->findOneByTenantAndName($tenantId, null, $name)) {
            throw new StatusCode\BadRequestException('Connection already exists');
        }
    }

    private function assertExcluded(Connection $record): void
    {
        $class = ltrim((string) $record->getClass(), '\\');

        $excluded = $this->frameworkConfig->getConnectionExclude();
        if (empty($excluded)) {
            return;
        }

        if (in_array($class, $excluded)) {
            throw new StatusCode\BadRequestException('The usage of this connection is disabled');
        }
    }
}
