<?php


namespace Ls\Replication\Setup;

use Exception;
use \Ls\Replication\Logger\Logger;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\App\ResourceConnection;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var Logger
     */
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


    /** @var array List of all websiteScopeFields */
    public $websiteScopeFields = [
        "ls_mag/service/base_url",
        "ls_mag/service/ls_key",
        "ls_mag/service/selected_store",
        "ls_mag/service/replicate_hierarchy_code",
        "ls_mag/service/version",
        "ls_mag/service/ls_central_version"
    ];

    /** @var array List of all non websiteScopeFields */
    public $nonwebsiteScopeFields = [
        "ls_mag/replication/last_execute_repl_attribute",
        "ls_mag/replication/last_execute_repl_attribute_option_value",
        "ls_mag/replication/last_execute_repl_attribute_value",
        "ls_mag/replication/last_execute_repl_barcode",
        "ls_mag/replication/last_execute_repl_discount",
        "ls_mag/replication/last_execute_repl_extended_variant_value",
        "ls_mag/replication/last_execute_repl_hierarchy",
        "ls_mag/replication/last_execute_repl_hierarchy_leaf",
        "ls_mag/replication/last_execute_repl_hierarchy_node",
        "ls_mag/replication/last_execute_repl_image_link",
        "ls_mag/replication/last_execute_repl_inv_status",
        "ls_mag/replication/last_execute_repl_item",
        "ls_mag/replication/last_execute_repl_item_category",
        "ls_mag/replication/last_execute_repl_item_variant_registration",
        "ls_mag/replication/last_execute_repl_price",
        "ls_mag/replication/last_execute_repl_product_group",
        "ls_mag/replication/last_execute_repl_store",
        "ls_mag/replication/max_key_repl_attribute",
        "ls_mag/replication/max_key_repl_attribute_option_value",
        "ls_mag/replication/max_key_repl_attribute_value",
        "ls_mag/replication/max_key_repl_barcode",
        "ls_mag/replication/max_key_repl_discount",
        "ls_mag/replication/max_key_repl_extended_variant_value",
        "ls_mag/replication/max_key_repl_hierarchy",
        "ls_mag/replication/max_key_repl_hierarchy_leaf",
        "ls_mag/replication/max_key_repl_hierarchy_node",
        "ls_mag/replication/max_key_repl_image_link",
        "ls_mag/replication/max_key_repl_inv_status",
        "ls_mag/replication/max_key_repl_item",
        "ls_mag/replication/max_key_repl_item_category",
        "ls_mag/replication/max_key_repl_item_variant_registration",
        "ls_mag/replication/max_key_repl_price",
        "ls_mag/replication/max_key_repl_product_group",
        "ls_mag/replication/max_key_repl_store",
        "ls_mag/replication/repl_attribute",
        "ls_mag/replication/repl_attribute_option_value",
        "ls_mag/replication/repl_attribute_value",
        "ls_mag/replication/repl_barcode",
        "ls_mag/replication/repl_discount",
        "ls_mag/replication/repl_extended_variant_value",
        "ls_mag/replication/repl_hierarchy",
        "ls_mag/replication/repl_hierarchy_leaf",
        "ls_mag/replication/repl_hierarchy_node",
        "ls_mag/replication/repl_image_link",
        "ls_mag/replication/repl_inv_status",
        "ls_mag/replication/repl_item",
        "ls_mag/replication/repl_item_category",
        "ls_mag/replication/repl_item_variant_registration",
        "ls_mag/replication/repl_price",
        "ls_mag/replication/repl_product_group",
        "ls_mag/replication/repl_store",
        "ls_mag/replication/status_repl_attribute",
        "ls_mag/replication/status_repl_attribute_option_value",
        "ls_mag/replication/status_repl_attribute_value",
        "ls_mag/replication/status_repl_barcode",
        "ls_mag/replication/status_repl_discount",
        "ls_mag/replication/status_repl_extended_variant_value",
        "ls_mag/replication/status_repl_hierarchy",
        "ls_mag/replication/status_repl_hierarchy_leaf",
        "ls_mag/replication/status_repl_hierarchy_node",
        "ls_mag/replication/status_repl_image_link",
        "ls_mag/replication/status_repl_inv_status",
        "ls_mag/replication/status_repl_item",
        "ls_mag/replication/status_repl_item_category",
        "ls_mag/replication/status_repl_item_variant_registration",
        "ls_mag/replication/status_repl_price",
        "ls_mag/replication/status_repl_product_group",
        "ls_mag/replication/status_repl_store",
        "ls_mag/replication/success_repl_attribute",
        "ls_mag/replication/success_repl_attribute_variant",
        "ls_mag/replication/success_repl_category",
        "ls_mag/replication/success_repl_discount",
        "ls_mag/replication/success_repl_product",
        "ls_mag/replication/last_execute_repl_category",
        "ls_mag/replication/last_execute_repl_attributes",
        "ls_mag/replication/last_execute_repl_discount_create",
        "ls_mag/replication/last_execute_repl_products",
        "ls_mag/replication/success_sync_item_updates",
        "ls_mag/replication/success_sync_inventory",
        "ls_mag/replication/success_sync_item_images",
        "ls_mag/replication/success_sync_attributes_value",
        "ls_mag/replication/success_sync_price",
        "ls_mag/replication/last_execute_repl_item_updates_sync",
        "ls_mag/replication/last_execute_repl_inventory_sync",
        "ls_mag/replication/last_execute_repl_item_images_sync",
        "ls_mag/replication/last_execute_repl_attributes_value_sync",
        "ls_mag/replication/last_execute_repl_price_sync",
        "ls_mag/replication/last_execute_repl_discount_status_reset",
        "ls_mag/replication/last_execute_repl_inv_status_reset",
        "ls_mag/replication/last_execute_repl_price_status_reset"
    ];


    /**
     * UpgradeData constructor.
     * @param ResourceConnection $resource
     * @param Logger $logger
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $logger
    ) {
        $this->resource = $resource;
        $this->logger   = $logger;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.3.0', '<') ||
            version_compare($context->getVersion(), '1.3.1', '<')) {
            // only need to run  for existing clients who are using older version then 1.3.0
            $this->updateFlatTables();
            $this->updateConfigTable();
        }
    }

    /**
     * Update All flat tables for migration.
     */
    private function updateFlatTables()
    {
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        // Update all
        foreach ($this->lsTables as $lsTable) {
            $lsTableName = $connection->getTableName($lsTable);
            $lsQuery     = "UPDATE $lsTableName SET scope = 'stores', scope_id = 1";
            try {
                $connection->query($lsQuery);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Update All Core Config values.
     */
    private function updateConfigTable()
    {
        $connection   = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $lsTableName  = $connection->getTableName('core_config_data');
        $websiteQuery = "UPDATE $lsTableName set scope = 'websites', scope_id = 1 WHERE path IN ('" . implode("','", $this->websiteScopeFields) . "')";
        $storeQuery   = "UPDATE $lsTableName set scope = 'stores', scope_id = 1 WHERE path IN ('" . implode("','", $this->nonwebsiteScopeFields) . "')";
        try {
            $connection->query($websiteQuery);
            $connection->query($storeQuery);

        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
