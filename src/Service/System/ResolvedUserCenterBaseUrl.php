<?php

declare(strict_types=1);

namespace Fusio\Impl\Service\System;

use Fusio\Impl\Exception\InvalidConfigurationException;
use JsonException;
use Paganini\Memo\CacheKeyGenerator;
use Paganini\Memo\Memoizer;
use Paganini\ServiceDiscovery\Contracts\ServiceUriResolverInterface;
use Paganini\ServiceDiscovery\ServiceUrlSpecifier;
use PSX\Framework\Config\ConfigInterface;

/**
 * Resolves {@see FrameworkConfig::getUserCenterBaseUrl()} from {@see ConfigInterface} key {@code ext_user_center_url}
 * (env {@code EXT_USER_CENTER_URL}). When the value contains Fusio-style service discovery {@code ://{{service_key}}},
 * the host segment is resolved via Redis using paganini (same pattern as mall-agg / {@code API_GATEWAY_BASE_URL}).
 *
 * Memoization avoids hitting Redis on every request (TTL from {@code ext_user_center_sd_memo_ttl_seconds}).
 */
final class ResolvedUserCenterBaseUrl
{
    public function __construct(
        private readonly ConfigInterface             $config,
        private readonly Memoizer                    $memoizer,
        private readonly ServiceUriResolverInterface $serviceUriResolver)
    {
    }

    /**
     * Trimmed user-center base URL, or empty string if unset.
     *
     * @throws InvalidConfigurationException
     * @throws JsonException
     */
    public function resolve(): string
    {
        $raw = (string)$this->config->get('ext_user_center_url');
        if ($raw === '') {
            return '';
        }
        if (!str_contains($raw, '://{{')) {
            return rtrim($raw, '/');
        }

        $host = trim((string)$this->config->get('redis_host'));
        if ($host === '') {
            throw new InvalidConfigurationException(
                'ext_user_center_url contains service-discovery placeholders (`://{{...}}`) but REDIS_HOST (redis_host) is not set.'
            );
        }

        $ttl = (int)$this->config->get('ext_user_center_sd_memo_ttl_seconds');
        if ($ttl < 0) {
            $ttl = 0;
        }

        $cacheKey = 'fusio_impl:user_center_base:' . CacheKeyGenerator::fromAssociativeArray(['u' => $raw]);

        return rtrim(
            (string)$this->memoizer->getOrCompute(
                $cacheKey,
                $ttl,
                fn(): string => ServiceUrlSpecifier::specifyHost($raw, $this->serviceUriResolver)
            ),
            '/'
        );
    }
}
