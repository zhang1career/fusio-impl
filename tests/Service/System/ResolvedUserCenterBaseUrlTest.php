<?php

declare(strict_types=1);

namespace Fusio\Impl\Tests\Service\System;

use Fusio\Impl\Exception\InvalidConfigurationException;
use Fusio\Impl\Service\System\ResolvedUserCenterBaseUrl;
use PHPUnit\Framework\TestCase;
use Paganini\Memo\ArrayMemoStore;
use Paganini\Memo\Memoizer;
use Paganini\ServiceDiscovery\Contracts\ServiceUriResolverInterface;
use PSX\Framework\Config\Config;

/**
 * @covers \Fusio\Impl\Service\System\ResolvedUserCenterBaseUrl
 */
class ResolvedUserCenterBaseUrlTest extends TestCase
{
    public function testResolvePlainUrlUnchanged(): void
    {
        $config = new Config([
            'ext_user_center_url' => 'https://user.example.com',
            'ext_user_center_sd_redis_dsn' => '',
            'ext_user_center_sd_memo_ttl_seconds' => 60,
        ]);
        $resolver = $this->createMock(ServiceUriResolverInterface::class);
        $resolver->expects($this->never())->method('resolve');

        $sut = new ResolvedUserCenterBaseUrl($config, new Memoizer(new ArrayMemoStore()), $resolver);

        $this->assertSame('https://user.example.com', $sut->resolve());
    }

    public function testResolveStripsTrailingSlashForPlain(): void
    {
        $config = new Config([
            'ext_user_center_url' => 'https://user.example.com/',
            'ext_user_center_sd_redis_dsn' => '',
            'ext_user_center_sd_memo_ttl_seconds' => 60,
        ]);
        $resolver = $this->createMock(ServiceUriResolverInterface::class);

        $sut = new ResolvedUserCenterBaseUrl($config, new Memoizer(new ArrayMemoStore()), $resolver);

        $this->assertSame('https://user.example.com', $sut->resolve());
    }

    public function testResolveServiceDiscoveryPlaceholder(): void
    {
        $config = new Config([
            'ext_user_center_url' => 'http://{{serv-fd}}/api/v1',
            'ext_user_center_sd_redis_dsn' => 'tcp://127.0.0.1:6379',
            'ext_user_center_sd_memo_ttl_seconds' => 60,
        ]);
        $resolver = $this->createMock(ServiceUriResolverInterface::class);
        $resolver->method('resolve')->with('serv-fd', null)->willReturn('serv-fd.internal:8000');

        $sut = new ResolvedUserCenterBaseUrl($config, new Memoizer(new ArrayMemoStore()), $resolver);

        $this->assertSame('http://serv-fd.internal:8000/api/v1', $sut->resolve());
    }

    public function testResolveThrowsWhenPlaceholderButNoRedisDsn(): void
    {
        $config = new Config([
            'ext_user_center_url' => 'http://{{serv-fd}}/api',
            'ext_user_center_sd_redis_dsn' => '',
            'ext_user_center_sd_memo_ttl_seconds' => 60,
        ]);
        $resolver = $this->createMock(ServiceUriResolverInterface::class);

        $sut = new ResolvedUserCenterBaseUrl($config, new Memoizer(new ArrayMemoStore()), $resolver);

        $this->expectException(InvalidConfigurationException::class);
        $sut->resolve();
    }

    public function testResolveEmptyReturnsEmpty(): void
    {
        $config = new Config([
            'ext_user_center_url' => '',
            'ext_user_center_sd_redis_dsn' => '',
            'ext_user_center_sd_memo_ttl_seconds' => 60,
        ]);
        $resolver = $this->createMock(ServiceUriResolverInterface::class);

        $sut = new ResolvedUserCenterBaseUrl($config, new Memoizer(new ArrayMemoStore()), $resolver);

        $this->assertSame('', $sut->resolve());
    }
}
