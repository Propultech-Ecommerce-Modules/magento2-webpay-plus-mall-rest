<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Logger;

use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{
    public function logInfo(string $message, array $context = []): void
    {
        $this->info($message, $context);
    }

    public function logError(string $message, array $context = []): void
    {
        $this->error($message, $context);
    }
}
