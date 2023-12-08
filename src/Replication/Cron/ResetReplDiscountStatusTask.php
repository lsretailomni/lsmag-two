<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ResetReplPriceStatusTask
 * @package Ls\Replication\Cron
 */
class ResetReplDiscountStatusTask
{

    /** @var string */
    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_discount_status_reset';

    /** @var string */
    const DISCOUNT_TABLE_NAME = 'ls_replication_repl_discount';

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

    /**
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param Logger $logger
     * @param ResourceConnection $resource
     * @param CatalogRuleRepositoryInterface $catalogRuleRepository
     * @param RuleCollectionFactory $ruleCollectionFactory
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        Logger $logger,
        ResourceConnection $resource
    ) {
        $this->replicationHelper     = $replicationHelper;
        $this->lsr                   = $LSR;
        $this->logger                = $logger;
        $this->resource              = $resource;
    }

    /**
     * Entry point for cron jobs
     *
     * @param null $storeData
     * @throws NoSuchEntityException
     */
    public function execute($storeData = null)
    {
        if (!empty($storeData) && $storeData instanceof WebsiteInterface) {
            $stores = [$storeData];
        } else {
            $stores = $this->lsr->getAllWebsites();
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($store->getId(), $this->defaultScope)) {
                    $this->logger->debug('Running ResetReplDiscountStatusTask Task ');

                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        self::CONFIG_PATH_LAST_EXECUTE,
                        $store->getId(),
                        $this->defaultScope
                    );
                    // resetting the flag back to false
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommDiscountsTask::CONFIG_PATH_STATUS,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommDiscountsTask::CONFIG_PATH,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );
                    $this->replicationHelper->updateCronStatus(
                        false,
                        ReplEcommDiscountsTask::CONFIG_PATH_MAX_KEY,
                        $store->getId(),
                        false,
                        $this->defaultScope
                    );

                    $websiteId  = $this->store->getWebsiteId();
                    // deleting the catalog rules data and delete flat table discount data
                    try {
                        $childCollection  = $this->replicationHelper->getCatalogRulesCollectionGivenWebsiteId($websiteId);
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
                    } catch (\Exception $e) {
                        $this->logger->debug('Something wrong while truncating the discount table');
                        $this->logger->debug($e->getMessage());
                    }
                    // reset the status for cron status job
                    $this->replicationHelper->updateCronStatus(
                        false,
                        LSR::SC_SUCCESS_CRON_DISCOUNT,
                        $store->getId()
                    );
                    $this->logger->debug('End ResetReplDiscountStatusTask task');
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
}
