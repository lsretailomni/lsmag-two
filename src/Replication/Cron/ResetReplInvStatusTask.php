<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use Ls\Replication\Model\ReplInvStatusRepository;
use Psr\Log\LoggerInterface;

/**
 * Class ResetReplInvStatusTask
 * @package Ls\Replication\Cron
 */
class ResetReplInvStatusTask
{
    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /** @var LoggerInterface */
    public $logger;

    /**
     * ResetReplInvStatusTask constructor
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
            $this->replicationHelper->updateCronStatus(false, ReplEcommInventoryStatusTask::CONFIG_PATH_STATUS);
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