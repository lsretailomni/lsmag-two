<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Lstables for truncating all flat tables
 */
class Lstables extends Action
{
    /** @var Logger */
    public $logger;

    /** @var ResourceConnection */
    public $resource;

    /** @var array List of all the ls_ tables */
    public const LS_TABLES = [
        'ls_replication_loy_item',
        'ls_replication_repl_attribute',
        'ls_replication_repl_attribute_option_value',
        'ls_replication_repl_attribute_value',
        'ls_replication_repl_barcode',
        'ls_replication_repl_country_code',
        'ls_replication_repl_currency',
        'ls_replication_repl_currency_exch_rate',
        'ls_replication_repl_customer',
        'ls_replication_repl_data_translation',
        'ls_replication_repl_data_translation_lang_code',
        'ls_replication_repl_discount',
        'ls_replication_repl_discount_validation',
        'ls_replication_repl_extended_variant_value',
        'ls_replication_repl_hierarchy',
        'ls_replication_repl_hierarchy_hosp_deal',
        'ls_replication_repl_hierarchy_hosp_deal_line',
        'ls_replication_repl_hierarchy_leaf',
        'ls_replication_repl_hierarchy_node',
        'ls_replication_repl_image',
        'ls_replication_repl_image_link',
        'ls_replication_repl_inv_status',
        'ls_replication_repl_item',
        'ls_replication_repl_item_category',
        'ls_replication_repl_item_modifier',
        'ls_replication_repl_item_recipe',
        'ls_replication_repl_item_unit_of_measure',
        'ls_replication_repl_item_variant_registration',
        'ls_replication_repl_loy_vendor_item_mapping',
        'ls_replication_repl_price',
        'ls_replication_repl_product_group',
        'ls_replication_repl_shipping_agent',
        'ls_replication_repl_store',
        'ls_replication_repl_store_tender_type',
        'ls_replication_repl_tax_setup',
        'ls_replication_repl_unit_of_measure',
        'ls_replication_repl_vendor'
    ];

    public const TABLE_CONFIGS = [
        'ls_mag/replication/',
        'ls_mag/replication/last_execute_',
        'ls_mag/replication/status_',
        'ls_mag/replication/max_key_',
        'ls_mag/replication/app_id_'
    ];

    /**
     * @param ResourceConnection $resource
     * @param Logger $logger
     * @param Context $context
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $logger,
        Context $context
    ) {
        $this->resource = $resource;
        $this->logger   = $logger;
        parent::__construct($context);
    }

    /**
     * Truncate ls_ Tables
     *
     * @return void
     */
    public function execute()
    {
        $connection          = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $arguments           = [];
        $jobName             = $this->_request->getParam('jobname');
        $storeId             = $this->_request->getParam('store');
        $coreConfigTableName = $this->resource->getTableName('core_config_data');
        $connection->startSetup();

        if ($jobName != '' && $storeId != '') {
            $replicationTableName = 'ls_replication_' . $jobName;
            $replicationTableName = $this->resource->getTableName($replicationTableName);

            try {
                $connection->delete($replicationTableName, ['scope_id = ?' => $storeId]);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }

            foreach (self::TABLE_CONFIGS as $config) {
                $connection->delete(
                    $coreConfigTableName,
                    ['path = ?' => $config . $jobName, 'scope_id = ?' => $storeId]
                );
            }
            $message      = __('%1 table truncated successfully.', $jobName);
            $redirectPath = 'ls_repl/cron/grid/';
            $arguments    = ['store' => $storeId];
        } else {
            foreach (self::LS_TABLES as $lsTables) {
                $tableName = $this->resource->getTableName($lsTables);

                try {
                    $connection->truncateTable($tableName);
                } catch (Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
            $connection->delete($coreConfigTableName, ['path like ?' => 'ls_mag/replication/%']);
            $message      = __('All ls_ tables truncated successfully.');
            $redirectPath = 'adminhtml/system_config/edit/section/ls_mag';
        }

        $connection->endSetup();
        $this->messageManager->addSuccessMessage($message);
        $this->_redirect($redirectPath, $arguments);
    }
}
