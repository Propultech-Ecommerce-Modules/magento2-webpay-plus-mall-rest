<?php

declare(strict_types=1);

namespace Propultech\WebpayPlusMallRest\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Logger;

class WebpayMallHandler extends BaseHandler
{
    /**
     * Log file path
     * @var string
     */
    protected $fileName = '/var/log/webpaymall.log';

    /**
     * Minimum level to log (DEBUG to capture everything)
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
