<?php

namespace Ls\Webhooks\Logger;

/**
 * Class Handler
 * @package Ls\Webhooks\Logger
 */
class Handler extends \Magento\Framework\Logger\Handler\Base
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
