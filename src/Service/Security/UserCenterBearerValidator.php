<?php

declare(strict_types=1);

namespace Fusio\Impl\Service\Security;

use Fusio\Engine\Model\AppAnonymous;
use Fusio\Engine\Model\TokenAnonymous;
use Fusio\Engine\Model\UserAnonymous;
use Fusio\Engine\Request\BearerAccessToken;
use Fusio\Engine\Request\HttpRequestHeaderConstant;
use Fusio\Impl\Framework\Loader\Context;
use Fusio\Impl\Service\System\FrameworkConfig;
use PSX\Http\Exception\ServiceUnavailableException;
use PSX\Http\Exception\UnauthorizedException;
use PSX\Http\RequestInterface;

/**
 * Validates upstream Authorization Bearer JWT against the configured user center (GET validate or profile path).
 * Calls user center with X-User-Access-Token (raw JWT); then clears Authorization and sets the same header for downstream.
 */
final class UserCenterBearerValidator
{
    public function __construct(
        private FrameworkConfig $frameworkConfig,
    ) {
    }

    /**
     * Verifies the bearer token, then strips spoofed gateway identity headers and sets trusted X-User-* headers.
     * Sets Fusio context app/user/token to anonymous (user center identities are not Fusio users).
     */
    public function assertAndDecorateRequest(RequestInterface $request, Context $context): void
    {
        $baseUrl = $this->frameworkConfig->getUserCenterBaseUrl();
        if ($baseUrl === '') {
            throw new ServiceUnavailableException('User center is not configured (ext_user_center_url / EXT_USER_CENTER_URL)');
        }

        $authorization = $request->getHeader('Authorization');
        $params = ['realm' => 'Fusio'];

        if ($authorization === null || trim($authorization) === '') {
            throw new UnauthorizedException('Missing authorization header', 'Bearer', $params);
        }

        $accessToken = BearerAccessToken::rawFromAuthorizationHeader($authorization);
        if ($accessToken === null) {
            throw new UnauthorizedException('Invalid authorization type', 'Bearer', $params);
        }
        if ($accessToken === '') {
            throw new UnauthorizedException('No authorization token was provided', 'Bearer', $params);
        }

        $path = $this->frameworkConfig->getUserCenterMePath();
        $url = $baseUrl . $path;

        $raw = $this->httpGetJson($url, $accessToken);
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            throw new UnauthorizedException('Invalid user center response', 'Bearer', $params);
        }

        if (($payload['errorCode'] ?? -1) !== 0) {
            throw new UnauthorizedException($payload['message'] ?? 'Invalid or expired token', 'Bearer', $params);
        }

        $user = $payload['data'] ?? null;
        if (!is_array($user)) {
            throw new UnauthorizedException('Invalid user center user payload', 'Bearer', $params);
        }

        // GET /me/validate returns JwtValidateResult (user_id); GET /me returns full User (id)
        $userId = $user['user_id'] ?? $user['id'] ?? null;
        if ($userId === null || (!is_int($userId) && !is_string($userId))) {
            throw new UnauthorizedException('Invalid user center user payload', 'Bearer', $params);
        }

        foreach (['X-User-Id', 'X-User-Name', 'X-User-Email', HttpRequestHeaderConstant::X_USER_ACCESS_TOKEN] as $headerName) {
            $request->removeHeader($headerName);
        }

        $request->removeHeader('Authorization');
        $request->setHeader(HttpRequestHeaderConstant::X_USER_ACCESS_TOKEN, $accessToken);

        $request->setHeader('X-User-Id', (string) $userId);
        if (isset($user['username']) && is_string($user['username']) && $user['username'] !== '') {
            $request->setHeader('X-User-Name', $user['username']);
        }
        if (isset($user['email']) && is_string($user['email']) && $user['email'] !== '') {
            $request->setHeader('X-User-Email', $user['email']);
        }

        $context->setApp(new AppAnonymous());
        $context->setUser(new UserAnonymous());
        $context->setToken(new TokenAnonymous());
    }

    private function httpGetJson(string $url, string $rawAccessToken): string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new ServiceUnavailableException('Could not initialize HTTP client');
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => [
                    HttpRequestHeaderConstant::X_USER_ACCESS_TOKEN . ': ' . $rawAccessToken,
                    'Accept: application/json',
                ],
            ]);

            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            if ($errno !== 0 || !is_string($body)) {
                throw new ServiceUnavailableException('User center request failed');
            }

            if ($status >= 500) {
                throw new ServiceUnavailableException('User center returned HTTP ' . $status);
            }

            return $body;
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => HttpRequestHeaderConstant::X_USER_ACCESS_TOKEN . ": {$rawAccessToken}\r\nAccept: application/json\r\n",
                'timeout' => 10.0,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if (!is_string($body)) {
            throw new ServiceUnavailableException('User center request failed');
        }

        return $body;
    }
}
