<?php

namespace Ls\Webhooks\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 * @package Ls\Webhooks\Logger
 */
class Handler extends Base
{
    /**
     * Logging level
     * @var int
     */
    public $loggerType = Logger::DEBUG;

    /**
     * File name
     * @var string
     */
    public $fileName = '/var/log/webhookstatus.log';
}
