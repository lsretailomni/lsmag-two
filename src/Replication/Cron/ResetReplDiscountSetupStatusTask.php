<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ResetReplPriceStatusTask
 * @package Ls\Replication\Cron
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

    /** @var array List of all the Discount tables */
    public $magento_discount_tables = [
        "catalogrule",
        "catalogrule_customer_group",
        "catalogrule_group_website",
        "catalogrule_group_website_replica",
        "catalogrule_product_price",
        "catalogrule_product_price_replica",
        "catalogrule_product",
        "catalogrule_product_replica",
        "catalogrule_website"
    ];

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

    /**
     * @var string
     */
    public $defaultScope = ScopeInterface::SCOPE_WEBSITES;

    /**
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param Logger $logger
     * @param ResourceConnection $resource
     */
    public function __construct(
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        Logger $logger,
        ResourceConnection $resource
    ) {
        $this->replicationHelper = $replicationHelper;
        $this->lsr               = $LSR;
        $this->logger            = $logger;
        $this->resource          = $resource;
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
                    // Process for Flat tables.
                    // truncating the discount table.
                    $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
                    $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
                    $tableName = $this->resource->getTableName(self::DISCOUNT_TABLE_NAME);
                    try {
                        $connection->truncateTable($tableName);
                    } catch (\Exception $e) {
                        $this->logger->debug('Something wrong while truncating the discount table');
                        $this->logger->debug($e->getMessage());
                    }
                    // truncating the discount validation table.
                    $tableName = $this->resource->getTableName(self::DISCOUNT_VALIDATION_TABLE);
                    try {
                        $connection->truncateTable($tableName);
                    } catch (\Exception $e) {
                        $this->logger->debug('Something wrong while truncating the discount validation table');
                        $this->logger->debug($e->getMessage());
                    }
                    // Process for Magento tables.
                    // deleting the catalog rules data
                    foreach ($this->magento_discount_tables as $discountTable) {
                        $tableName = $this->resource->getTableName($discountTable);
                        try {
                            $connection->truncateTable($tableName);
                        } catch (\Exception $e) {
                            $this->logger->debug('Something wrong while deleting the catalog rule');
                            $this->logger->debug($e->getMessage());
                        }
                    }
                    $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
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
