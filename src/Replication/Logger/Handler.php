<?php

namespace Ls\Replication\Logger;

/**
 * Class Handler
 * @package Ls\Replication\Logger
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
    public $fileName = '/var/log/replication.log';
}
