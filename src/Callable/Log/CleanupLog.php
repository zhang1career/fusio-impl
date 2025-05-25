<?php

namespace Fusio\Impl\Callable\Log;

use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use Fusio\Impl\Callable\BaseCall;
use Fusio\Impl\Service\ConfigService;
use Fusio\Impl\Service\Log;

class CleanupLog extends BaseCall
{
    private Log $logService;

    public function __construct(Log $logService)
    {
        $this->logService = $logService;
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context): mixed
    {
        $logExpire = ConfigService::enval('LOG_EXPIRE_IN_DAYS', 30);
        $this->logService->cleanup($logExpire);
        return [
            'success' => true,
            'message' => 'Log entries older than ' . $logExpire . ' days have been removed',
        ];
    }
}