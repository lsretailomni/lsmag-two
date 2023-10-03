<?php

namespace Ls\Replication\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 * @package Ls\Replication\Logger
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
    public $fileName = '/var/log/replication.log';
}
