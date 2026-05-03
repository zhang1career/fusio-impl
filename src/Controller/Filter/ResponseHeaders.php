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
use PSX\Http\FilterChainInterface;
use PSX\Http\FilterInterface;
use PSX\Http\RequestInterface;
use PSX\Http\ResponseInterface;

/**
 * ResponseHeaders
 *
 * Generic per-Operation response-header override. Reads the active OperationRow from
 * {@see \Fusio\Impl\Framework\Loader\Context} (populated by {@see OperationLoader}), parses the JSON-encoded
 * map<string,string> stored in the {@code response_headers} column, and force-applies each non-empty entry to the
 * outgoing response via {@see ResponseInterface::setHeader} (override semantics).
 *
 * Wraps {@code $filterChain->handle()} in a {@code try}/{@code finally} so the override executes on:
 *   - the OPTIONS short-circuit (Filter\Operation `return`s without an exception),
 *   - normal 200 responses, and
 *   - exception paths (Authentication 401, action-layer throws, etc.).
 *
 * Not CORS-specific: any header name works; CORS / Cache-Control are common use cases. When the operation has no
 * configured headers (NULL or empty JSON), this filter is a no-op.
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class ResponseHeaders implements FilterInterface
{
    private ContextFactory $contextFactory;

    public function __construct(ContextFactory $contextFactory)
    {
        $this->contextFactory = $contextFactory;
    }

    public function handle(RequestInterface $request, ResponseInterface $response, FilterChainInterface $filterChain): void
    {
        try {
            $filterChain->handle($request, $response);
        } finally {
            $this->applyConfiguredHeaders($response);
        }
    }

    private function applyConfiguredHeaders(ResponseInterface $response): void
    {
        $operation = $this->contextFactory->getActive()->getOperation();

        $raw = $operation->getResponseHeaders();
        if ($raw === null || $raw === '') {
            return;
        }

        $headers = json_decode($raw, true);
        if (!is_array($headers)) {
            return;
        }

        foreach ($headers as $name => $value) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            if (!is_string($value) || $value === '') {
                continue;
            }
            $response->setHeader($name, $value);
        }
    }
}
