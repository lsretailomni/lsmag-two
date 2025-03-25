<?php

namespace Ls\Replication\Logger;

use Magento\Framework\Logger\Handler\Base;

class FlatReplicationLoggerHandler extends Base
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
    public $fileName = '/var/log/flat_replication.log';
}
