<?php

declare(strict_types=1);

namespace Fusio\Impl\Infrastructure\ServiceDiscovery;

use Paganini\ServiceDiscovery\Contracts\RedisStringClient;
use Predis\Client;

/**
 * {@see RedisStringClient} backed by Predis (same stack as fusio/adapter-redis).
 */
final class PredisRedisStringClient implements RedisStringClient
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function get(string $key): string|false
    {
        $v = $this->client->get($key);
        if ($v === null) {
            return false;
        }

        return $v;
    }

    /**
     * @param list<string> $keys
     *
     * @return list<string|false>
     */
    public function mget(array $keys): array
    {
        if ($keys === []) {
            return [];
        }
        $values = $this->client->mget($keys);
        $out = [];
        foreach ($values as $v) {
            $out[] = $v === null ? false : (string) $v;
        }

        return $out;
    }
}
