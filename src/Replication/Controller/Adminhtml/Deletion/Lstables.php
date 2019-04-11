<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Class Lstables
 */
class Lstables extends Action
{
    /** @var LoggerInterface */
    public $logger;

    /** @var ResourceConnection */
    public $resource;

    /** @var array List of all the ls_ tables */
    public $lsTables = [
        "ls_replication_loy_item",
        "ls_replication_repl_attribute",
        "ls_replication_repl_attribute_option_value",
        "ls_replication_repl_attribute_value",
        "ls_replication_repl_barcode",
        "ls_replication_repl_country_code",
        "ls_replication_repl_currency",
        "ls_replication_repl_currency_exch_rate",
        "ls_replication_repl_customer",
        "ls_replication_repl_data_translation",
        "ls_replication_repl_discount",
        "ls_replication_repl_discount_validation",
        "ls_replication_repl_hierarchy",
        "ls_replication_repl_hierarchy_leaf",
        "ls_replication_repl_hierarchy_node",
        "ls_replication_repl_extended_variant_value",
        "ls_replication_repl_image",
        "ls_replication_repl_image_link",
        "ls_replication_repl_inv_status",
        "ls_replication_repl_item",
        "ls_replication_repl_item_category",
        "ls_replication_repl_item_unit_of_measure",
        "ls_replication_repl_item_variant_registration",
        "ls_replication_repl_loy_vendor_item_mapping",
        "ls_replication_repl_price",
        "ls_replication_repl_product_group",
        "ls_replication_repl_shipping_agent",
        "ls_replication_repl_store",
        "ls_replication_repl_store_tender_type",
        "ls_replication_repl_unit_of_measure",
        "ls_replication_repl_vendor"
    ];

    // @codingStandardsIgnoreStart
    /** @var array */
    protected $_publicActions = ['ls_tables'];
    // @codingStandardsIgnoreEnd

    /**
     * Lstables constructor.
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger,
        Context $context
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Truncate ls_ Tables
     *
     * @return void
     */
    public function execute()
    {
        // @codingStandardsIgnoreStart
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        foreach ($this->lsTables as $lsTables) {
            $tableName = $connection->getTableName($lsTables);
            try {
                $connection->truncateTable($tableName);
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $coreConfigTableName = $connection->getTableName('core_config_data');
        $connection->query('DELETE FROM ' . $coreConfigTableName . ' WHERE path LIKE "ls_mag/replication/%";');
        $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
        // @codingStandardsIgnoreEnd
        $this->messageManager->addSuccessMessage(__('All ls_ tables truncated successfully.'));
        $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
