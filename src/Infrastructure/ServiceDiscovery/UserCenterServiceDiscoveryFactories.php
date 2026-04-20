<?php

declare(strict_types=1);

namespace Fusio\Impl\Infrastructure\ServiceDiscovery;

use Paganini\Memo\ApcuMemoStore;
use Paganini\Memo\ArrayMemoStore;
use Paganini\Memo\Memoizer;
use Paganini\ServiceDiscovery\RedisServiceUriResolver;
use PSX\Framework\Config\ConfigInterface;
use function function_exists;

/**
 * Symfony DI factories (named static methods; anonymous closures are not supported by the PSX loader).
 */
final class UserCenterServiceDiscoveryFactories
{
    public static function createMemoizer(): Memoizer
    {
        $store = function_exists('apcu_fetch')
            ? new ApcuMemoStore('fusio_impl.ext_user_center_sd')
            : new ArrayMemoStore();

        return new Memoizer($store);
    }

    public static function createRedisServiceUriResolver(
        ConfigInterface $config,
        RedisStringClientFactory $redisFactory,
    ): RedisServiceUriResolver {
        return new RedisServiceUriResolver(
            $redisFactory($config),
            (string) $config->get('ext_user_center_sd_key_prefix')
        );
    }
}
