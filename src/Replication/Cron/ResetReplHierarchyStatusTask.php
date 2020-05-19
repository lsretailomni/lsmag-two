<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class ResetReplHierarchyStatusTask
 * @package Ls\Replication\Cron
 */
class ResetReplHierarchyStatusTask
{
    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_hierarchy_status_reset';

    /**
     * @var ReplicationHelper
     */
    public $replicationHelper;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * ResetReplHierarchyStatusTask constructor.
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
        $this->lsr               = $LSR;
        $this->logger            = $logger;
    }

    /**
     * Reset the Repl Hierarchy Status
     * @param null $storeData
     */
    public function execute($storeData = null)
    {
        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            /** @var StoreInterface[] $stores */
            $stores = $this->lsr->getAllStores();
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                if ($this->lsr->isLSR($store->getId())) {
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        self::CONFIG_PATH_LAST_EXECUTE,
                        $store->getId()
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommHierarchyTask::CONFIG_PATH_STATUS,
                        $store->getId()
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommHierarchyTask::CONFIG_PATH,
                        $store->getId()
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommHierarchyTask::CONFIG_PATH_MAX_KEY,
                        $store->getId()
                    );
                }
            }
        }
    }

    /**
     * @param null $storeData
     * @return array
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        return [0];
    }
}
