<?php

namespace Ls\Replication\Cron;

use Magento\Framework\App\ResourceConnection;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use Psr\Log\LoggerInterface;

/**
 * Class ResetReplDiscountStatusTask
 * @package Ls\Replication\Cron
 */
class ResetReplDiscountStatusTask
{

    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_discount_status_reset';

    /** @var ResourceConnection */
    public $resource;

    /** @var array List of all the Discount tables */
    public $discount_tables = [
        "catalogrule",
        "catalogrule_customer_group",
        "catalogrule_group_website",
        "catalogrule_group_website_replica",
        "catalogrule_product_price",
        "catalogrule_product_price_replica",
        "catalogrule_product",
        "catalogrule_product_replica",
        "catalogrule_website",
        "ls_replication_repl_discount"
    ];

    /** @var ReplicationHelper */
    public $replicationHelper;

    /** @var LSR */
    public $lsr;

    /** @var LoggerInterface */
    public $logger;

    /**
     * ResetReplPriceStatusTask constructor.
     * @param ResourceConnection $resource
     * @param ReplicationHelper $replicationHelper
     * @param LSR $LSR
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        ReplicationHelper $replicationHelper,
        LSR $LSR,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->replicationHelper = $replicationHelper;
        $this->lsr = $LSR;
        $this->logger = $logger;
    }

    /**
     * Reset the Discount Status
     */
    public function execute()
    {
        if ($this->lsr->isLSR()) {
            $this->replicationHelper->updateConfigValue(date('d M,Y h:i:s A'), self::CONFIG_PATH_LAST_EXECUTE);
            $this->replicationHelper->updateCronStatus(false, ReplEcommDiscountsTask::CONFIG_PATH_STATUS);
            $this->replicationHelper->updateCronStatus(false, ReplEcommDiscountsTask::CONFIG_PATH);
            $this->replicationHelper->updateCronStatus(false, LSR::SC_SUCCESS_CRON_DISCOUNT);
            $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
            $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
            foreach ($this->discount_tables as $discountTable) {
                $tableName = $connection->getTableName($discountTable);
                try {
                    $connection->truncateTable($tableName);
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
            $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
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