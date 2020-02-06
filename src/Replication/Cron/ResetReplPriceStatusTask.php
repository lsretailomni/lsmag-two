<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;

/**
 * Class ResetReplPriceStatusTask
 * @package Ls\Replication\Cron
 */
class ResetReplPriceStatusTask
{

    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_price_status_reset';

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /** @var Logger */
    public $logger;

    /**
     * ResetReplPriceStatusTask constructor.
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param Logger $logger
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        Logger $logger
    ) {
        $this->replicationHelper = $replicationHelper;
        $this->lsr = $LSR;
        $this->logger = $logger;
    }

    /**
     * Reset the Inventory Status
     */
    public function execute()
    {
        if ($this->lsr->isLSR()) {
            $this->replicationHelper->updateConfigValue(
                $this->replicationHelper->getDateTime(),
                self::CONFIG_PATH_LAST_EXECUTE
            );
            $this->replicationHelper->updateCronStatus(false, ReplEcommPricesTask::CONFIG_PATH_STATUS);
            $this->replicationHelper->updateCronStatus(false, ReplEcommPricesTask::CONFIG_PATH);
        }
    }

    /**
     * @return array
     */
    public function executeManually()
    {
        $this->execute();
        return [0];
    }
}
