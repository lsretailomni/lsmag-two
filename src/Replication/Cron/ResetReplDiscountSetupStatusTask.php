<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Cron responsible to reset the relevant counters to do replication again
 */
class ResetReplDiscountSetupStatusTask
{

    /** @var string */
    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_discount_setup_status_reset';

    /** @var string */
    const CONFIG_PATH_LAST_EXECUTE_DV = 'ls_mag/replication/last_execute_repl_discount_validation_status_reset';

    /** @var string */
    const DISCOUNT_TABLE_NAME = 'ls_replication_repl_discount_setup';

    /** @var string */
    const DISCOUNT_VALIDATION_TABLE = 'ls_replication_repl_discount_validation';

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var ResourceConnection
     */
    public $resource;

    /** @var StoreInterface $store */
    public $store;

    /**
     * @var string
     */
    public $defaultScope = ScopeInterface::SCOPE_WEBSITES;

    /** @var StoreManagerInterface */
    public $storeManager;

    /**
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param Logger $logger
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        Logger $logger,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager
    ) {
        $this->replicationHelper     = $replicationHelper;
        $this->lsr                   = $LSR;
        $this->logger                = $logger;
        $this->resource              = $resource;
        $this->storeManager          = $storeManager;
        $this->setDefaultScope();
    }

    /**
     * Entry point for cron jobs
     *
     * @param null $storeData
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
        if (!$this->replicationHelper->isSSM()) {
            if (!empty($storeData) && $storeData instanceof WebsiteInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->lsr->getAllWebsites();
            }
        } else {
            $stores = [$this->lsr->getAdminStore()];
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($store->getId(), $this->defaultScope)) {
                    $this->logger->debug('Running ResetReplDiscountSetupStatusTask Task ');

                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        self::CONFIG_PATH_LAST_EXECUTE,
                        $store->getId(),
                        $this->defaultScope
                    );

                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        self::CONFIG_PATH_LAST_EXECUTE_DV,
                        $store->getId(),
                        $this->defaultScope
                    );

                    // resetting the flag back to false
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommDiscountSetupTask::CONFIG_PATH_STATUS,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommDiscountSetupTask::CONFIG_PATH,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommDiscountSetupTask::CONFIG_PATH_MAX_KEY,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommDiscountValidationsTask::CONFIG_PATH_STATUS,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommDiscountValidationsTask::CONFIG_PATH,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommDiscountValidationsTask::CONFIG_PATH_MAX_KEY,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $websiteId = $this->store->getWebsiteId();
                    // deleting the catalog rules data and delete flat table discount data
                    try {
                        $childCollection  = $this->replicationHelper->getCatalogRulesCollectionGivenWebsiteId(
                            !$this->replicationHelper->isSSM() ?
                                $websiteId :
                                $this->storeManager->getDefaultStoreView()->getWebsiteId()
                        );
                        $parentCollection = $this->replicationHelper->getGivenColumnsFromGivenCollection(
                            $childCollection,
                            ['rule_id']
                        );
                        $this->replicationHelper->deleteGivenTableDataGivenConditions(
                            $this->replicationHelper->getGivenTableName('catalogrule'),
                            ['rule_id IN (?)' => $parentCollection->getSelect()]
                        );
                        $this->replicationHelper->deleteGivenTableDataGivenConditions(
                            self::DISCOUNT_TABLE_NAME,
                            ['scope_id = ?' => $websiteId]
                        );
                        $this->replicationHelper->deleteGivenTableDataGivenConditions(
                            self::DISCOUNT_VALIDATION_TABLE,
                            ['scope_id = ?' => $websiteId]
                        );
                    } catch (\Exception $e) {
                        $this->logger->debug('Something wrong while truncating the discount table');
                        $this->logger->debug($e->getMessage());
                    }
                    // reset the status for cron status job
                    $this->replicationHelper->updateCronStatus(
                        false,
                        LSR::SC_SUCCESS_CRON_DISCOUNT_SETUP,
                        $store->getId()
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        LSR::SC_SUCCESS_CRON_DISCOUNT_VALIDATION,
                        $store->getId()
                    );
                    $this->logger->debug('End ResetReplDiscountSetupStatusTask task');
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * Entry point for manually run cron jobs
     *
     * @param null $storeData
     * @return array
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        return [0];
    }

    /**
     * Set default scope
     *
     */
    public function setDefaultScope()
    {
        if ($this->replicationHelper->isSSM()) {
            $this->defaultScope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
    }
}
