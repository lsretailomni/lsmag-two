<?php

namespace Ls\Replication\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Omni log handler
 */
class OmniLoggerHandler extends Base
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
    public $fileName = '/var/log/omniclient.log';
}
