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

namespace Fusio\Impl\Controller\Filter;

use Fusio\Impl\Framework\Loader\ContextFactory;
use Fusio\Impl\Table;
use PSX\Http\Exception\InternalServerErrorException;
use PSX\Http\FilterChainInterface;
use PSX\Http\FilterInterface;
use PSX\Http\RequestInterface;
use PSX\Http\ResponseInterface;

/**
 * OperationLoader
 *
 * Resolves the active OperationRow from the routing source and stores it on the {@see \Fusio\Impl\Framework\Loader\Context}
 * exactly once per request. Downstream filters such as {@see Operation} and {@see ResponseHeaders} read the row from the
 * Context instead of issuing their own queries.
 *
 * Sits early in the pre-filter chain (after Tenant, before ResponseHeaders) so subsequent filters can rely on
 * {@see \Fusio\Impl\Framework\Loader\Context::getOperation()} being populated.
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class OperationLoader implements FilterInterface
{
    private Table\Operation $operationTable;
    private ContextFactory $contextFactory;

    public function __construct(Table\Operation $operationTable, ContextFactory $contextFactory)
    {
        $this->operationTable = $operationTable;
        $this->contextFactory = $contextFactory;
    }

    public function handle(RequestInterface $request, ResponseInterface $response, FilterChainInterface $filterChain): void
    {
        $context     = $this->contextFactory->getActive();
        $operationId = $context->getSource()[1] ?? null;

        $operation = $this->operationTable->find($operationId ?? 0);
        if (!$operation instanceof Table\Generated\OperationRow) {
            throw new InternalServerErrorException('Operation not found');
        }

        $context->setOperation($operation);

        $filterChain->handle($request, $response);
    }
}
