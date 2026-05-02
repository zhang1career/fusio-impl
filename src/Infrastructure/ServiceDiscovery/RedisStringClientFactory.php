<?php

declare(strict_types=1);

namespace Fusio\Impl\Infrastructure\ServiceDiscovery;

use Paganini\ServiceDiscovery\Contracts\RedisStringClient;
use Predis\Client;
use PSX\Framework\Config\ConfigInterface;

final class RedisStringClientFactory
{
    public function __invoke(ConfigInterface $config): RedisStringClient
    {
        $host = trim((string) $config->get('redis_host'));
        if ($host === '') {
            return new NullRedisStringClient();
        }

        $scheme = trim((string) $config->get('redis_scheme'));
        if ($scheme === '') {
            $scheme = 'tcp';
        }

        $port = (int) $config->get('redis_port');
        if ($port <= 0) {
            $port = 6379;
        }

        return new PredisRedisStringClient(new Client([
            'scheme' => $scheme,
            'host'   => $host,
            'port'   => $port,
        ]));
    }
}
