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
        $dsn = trim((string) $config->get('ext_user_center_sd_redis_dsn'));
        if ($dsn === '') {
            return new NullRedisStringClient();
        }

        return new PredisRedisStringClient(new Client($dsn));
    }
}
