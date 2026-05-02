<?php

declare(strict_types=1);

namespace Fusio\Impl\Infrastructure\ServiceDiscovery;

use Paganini\ServiceDiscovery\Contracts\RedisStringClient;

/**
 * Placeholder when no Redis DSN is configured; must not be used for real lookups.
 */
final class NullRedisStringClient implements RedisStringClient
{
    public function get(string $key): string|false
    {
        return false;
    }

    /**
     * @param list<string> $keys
     *
     * @return list<string|false>
     */
    public function mget(array $keys): array
    {
        return array_fill(0, count($keys), false);
    }
}
