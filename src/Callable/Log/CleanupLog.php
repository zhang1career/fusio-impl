<?php

namespace Fusio\Impl\Callable\Log;

use Fusio\Impl\Service\Log;
use Fusio\Impl\Service\ConfigService;

class CleanupLog
{
    private Log $logService;

    public function __construct(Log $logService)
    {
        $this->logService = $logService;
    }

    public function invoke()
    {
        $logExpire = ConfigService::enval('LOG_EXPIRE_IN_DAYS', 30);
        $this->logService->cleanup($logExpire);
    }
}