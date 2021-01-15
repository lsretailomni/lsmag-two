<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Lstables
 * for truncating all flat tables
 */
class Lstables extends Action
{
    /** @var Logger */
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
        "ls_replication_repl_data_translation_lang_code",
        "ls_replication_repl_discount",
        "ls_replication_repl_discount_validation",
        "ls_replication_repl_extended_variant_value",
        "ls_replication_repl_hierarchy",
        "ls_replication_repl_hierarchy_hosp_deal",
        "ls_replication_repl_hierarchy_hosp_deal_line",
        "ls_replication_repl_hierarchy_leaf",
        "ls_replication_repl_hierarchy_node",
        "ls_replication_repl_image",
        "ls_replication_repl_image_link",
        "ls_replication_repl_inv_status",
        "ls_replication_repl_item",
        "ls_replication_repl_item_category",
        "ls_replication_repl_item_modifier",
        "ls_replication_repl_item_recipe",
        "ls_replication_repl_item_unit_of_measure",
        "ls_replication_repl_item_variant_registration",
        "ls_replication_repl_loy_vendor_item_mapping",
        "ls_replication_repl_price",
        "ls_replication_repl_product_group",
        "ls_replication_repl_shipping_agent",
        "ls_replication_repl_store",
        "ls_replication_repl_store_tender_type",
        "ls_replication_repl_tax_setup",
        "ls_replication_repl_unit_of_measure",
        "ls_replication_repl_vendor"
    ];

    /** @var LSR */
    public $lsr;

    /** @var ReplicationHelper */
    public $replHelper;

    // @codingStandardsIgnoreStart
    /** @var array */
    protected $_publicActions = ['ls_tables'];
    // @codingStandardsIgnoreEnd

    /**
     * Lstables constructor.
     * @param ResourceConnection $resource
     * @param Logger $logger
     * @param LSR $LSR
     * @param Context $context
     * @param ReplicationHelper $repHelper
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $logger,
        LSR $LSR,
        Context $context,
        ReplicationHelper $repHelper
    ) {
        $this->resource   = $resource;
        $this->logger     = $logger;
        $this->lsr        = $LSR;
        $this->replHelper = $repHelper;
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
        $jobName = $this->_request->getParam('jobname');
        if ($jobName != "") {
            $tableName = 'ls_replication_' . $jobName;
            $tableName = $this->resource->getTableName($tableName);
            try {
                $connection->truncateTable($tableName);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
            $coreConfigTableName = $this->resource->getTableName('core_config_data');
            $connection->query('DELETE FROM ' . $coreConfigTableName .
                ' WHERE path = "ls_mag/replication/' . $jobName . '"');
            $connection->query('DELETE FROM ' . $coreConfigTableName . '
            WHERE path = "ls_mag/replication/last_execute_' . $jobName . '"');
            $connection->query('DELETE FROM ' . $coreConfigTableName . '
            WHERE path = "ls_mag/replication/status_' . $jobName . '"');
            $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
            $this->replHelper->flushByTypeCode('config');
            // @codingStandardsIgnoreEnd
            $this->messageManager->addSuccessMessage(__('%1 table truncated successfully.', $jobName));
            $this->_redirect('ls_repl/cron/grid/');
        } else {
            foreach ($this->lsTables as $lsTables) {
                $tableName = $this->resource->getTableName($lsTables);
                try {
                    $connection->truncateTable($tableName);
                } catch (Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
            $coreConfigTableName = $this->resource->getTableName('core_config_data');
            $connection->query('DELETE FROM ' . $coreConfigTableName . ' WHERE path LIKE "ls_mag/replication/%";');
            $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
            $this->replHelper->flushByTypeCode('config');
            // @codingStandardsIgnoreEnd
            $this->messageManager->addSuccessMessage(__('All ls_ tables truncated successfully.'));
            $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
        }
    }
}
