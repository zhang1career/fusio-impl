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

namespace Fusio\Impl\Service\System;

use DateInterval;
use Doctrine\DBAL;
use Exception;
use Fusio\Impl\Exception\InvalidConfigurationException;
use PSX\Framework\Config\ConfigInterface;

/**
 * Cleaner
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org
 */
class FrameworkConfig
{
    private ConfigInterface $config;
    private DBAL\Tools\DsnParser $parser;
    private ?ResolvedUserCenterBaseUrl $resolvedUserCenterBaseUrl;

    public function __construct(ConfigInterface $config, ?ResolvedUserCenterBaseUrl $resolvedUserCenterBaseUrl = null)
    {
        $this->config = $config;
        $this->parser = new DBAL\Tools\DsnParser();
        $this->resolvedUserCenterBaseUrl = $resolvedUserCenterBaseUrl;
    }

    /**
     * @throws Exception
     */
    public function getExpireTokenInterval(): DateInterval
    {
        return new DateInterval($this->config->get('fusio_expire_token'));
    }

    /**
     * @throws Exception
     */
    public function getExpireRefreshInterval(): DateInterval
    {
        return new DateInterval($this->config->get('fusio_expire_refresh') ?? 'P3D');
    }

    public function getTenantId(): ?string
    {
        $tenantId = $this->config->get('fusio_tenant_id');
        if (empty($tenantId)) {
            return null;
        }

        return $tenantId;
    }

    public function getProjectKey(): string
    {
        return $this->config->get('fusio_project_key');
    }

    public function getActionExclude(): ?array
    {
        return $this->config->get('fusio_action_exclude');
    }

    public function getConnectionExclude(): ?array
    {
        return $this->config->get('fusio_connection_exclude');
    }

    public function getProviderFile(): string
    {
        return $this->config->get('fusio_provider');
    }

    public function getMailSender(): ?string
    {
        return $this->config->get('fusio_mail_sender');
    }

    public function isDatabaseEnabled(): bool
    {
        return !!$this->config->get('fusio_database');
    }

    public function isMarketplaceEnabled(): bool
    {
        return !!$this->config->get('fusio_marketplace');
    }

    /**
     * Base URL for external (user center) JWT validation; empty string means not configured.
     * When {@see ResolvedUserCenterBaseUrl} is wired, `://{{service_key}}` in the configured URL is resolved via Redis (paganini), mirroring mall-agg / API_GATEWAY_BASE_URL.
     * @throws Exception
     */
    public function getUserCenterBaseUrl(): string
    {
        if ($this->resolvedUserCenterBaseUrl !== null) {
            return $this->resolvedUserCenterBaseUrl->resolve();
        }

        $url = $this->config->get('ext_user_center_url');
        if (!is_string($url) || $url === '') {
            return '';
        }

        return rtrim($url, '/');
    }

    /**
     * Path for GET current-user call (Bearer token), starting with /.
     *
     * Note: the default cannot live in configuration.php via env()->default('/api/user/me')
     * because Symfony DI forbids `/` in env processor chain names; fallback is applied here.
     */
    public function getUserCenterMePath(): string
    {
        $path = $this->config->get('ext_user_center_me_path');
        if (!is_string($path) || $path === '') {
            return '/api/user/me';
        }
        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    public function getAppsUrl(): string
    {
        return $this->config->get('fusio_apps_url');
    }

    public function getAppsDir(): string
    {
        return $this->config->get('fusio_apps_dir') ?: $this->getPathPublic();
    }

    public function getUrl(...$pathFragment): string
    {
        return $this->config->get('psx_url') . (count($pathFragment) > 0 ? '/' . implode('/', $pathFragment) : '');
    }

    public function getDispatchUrl(...$pathFragment): string
    {
        return $this->config->get('psx_url') . '/' . $this->config->get('psx_dispatch') . (count($pathFragment) > 0 ? implode('/', $pathFragment) : '');
    }

    public function getPathCache(...$directoryFragment): string
    {
        return $this->config->get('psx_path_cache') . (count($directoryFragment) > 0 ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $directoryFragment) : '');
    }

    public function getPathPublic(...$directoryFragment): string
    {
        return $this->config->get('psx_path_public') . (count($directoryFragment) > 0 ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $directoryFragment) : '');
    }

    public function getPathApp(): string
    {
        return $this->config->get('psx_path_app');
    }

    /**
     * @throws InvalidConfigurationException
     */
    public function getDoctrineConnectionParameters(): array
    {
        $connection = $this->config->get('psx_connection');
        if (is_string($connection)) {
            return $this->parser->parse($this->config->get('psx_connection'));
        } elseif (is_array($connection)) {
            return $connection;
        } else {
            throw new InvalidConfigurationException('The configured connection must contain a string or array');
        }
    }
}
