<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use Psr\Log\LoggerInterface;

/**
 * Class ResetReplPriceStatusTask
 * @package Ls\Replication\Cron
 */
class ResetReplPriceStatusTask
{
    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /** @var LoggerInterface */
    public $logger;

    /**
     * ResetReplPriceStatusTask constructor.
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param LoggerInterface $logger
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        LoggerInterface $logger
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