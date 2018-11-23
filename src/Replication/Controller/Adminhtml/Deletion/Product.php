<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Class Product Deletion
 */
class Product extends Action

{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ResourceConnection */
    protected $_resource;

    /** @var array List of all the Catalog Product tables */
    protected $catalog_products_tables = array(
        "catalog_product_attribute_cl",
        "catalog_product_bundle_option",
        "catalog_product_bundle_option_value",
        "catalog_product_bundle_price_index",
        "catalog_product_bundle_selection",
        "catalog_product_bundle_selection_price",
        "catalog_product_bundle_stock_index",
        "catalog_product_category_cl",
        "catalog_product_entity",
        "catalog_product_entity_datetime",
        "catalog_product_entity_decimal",
        "catalog_product_entity_gallery",
        "catalog_product_entity_int",
        "catalog_product_entity_media_gallery",
        "catalog_product_entity_media_gallery_value",
        "catalog_product_entity_media_gallery_value_to_entity",
        "catalog_product_entity_media_gallery_value_video",
        "catalog_product_entity_text",
        "catalog_product_entity_tier_price",
        "catalog_product_entity_varchar",
        "catalog_product_frontend_action",
        "catalog_product_index_eav",
        "catalog_product_index_eav_decimal",
        "catalog_product_index_eav_decimal_idx",
        "catalog_product_index_eav_decimal_replica",
        "catalog_product_index_eav_decimal_tmp",
        "catalog_product_index_eav_idx",
        "catalog_product_index_eav_replica",
        "catalog_product_index_eav_tmp",
        "catalog_product_index_price",
        "catalog_product_index_price_bundle_idx",
        "catalog_product_index_price_bundle_opt_idx",
        "catalog_product_index_price_bundle_opt_tmp",
        "catalog_product_index_price_bundle_sel_idx",
        "catalog_product_index_price_bundle_sel_tmp",
        "catalog_product_index_price_bundle_tmp",
        "catalog_product_index_price_cfg_opt_agr_idx",
        "catalog_product_index_price_cfg_opt_agr_tmp",
        "catalog_product_index_price_cfg_opt_idx",
        "catalog_product_index_price_cfg_opt_tmp",
        "catalog_product_index_price_downlod_idx",
        "catalog_product_index_price_downlod_tmp",
        "catalog_product_index_price_final_idx",
        "catalog_product_index_price_final_tmp",
        "catalog_product_index_price_idx",
        "catalog_product_index_price_opt_agr_idx",
        "catalog_product_index_price_opt_agr_tmp",
        "catalog_product_index_price_opt_idx",
        "catalog_product_index_price_opt_tmp",
        "catalog_product_index_price_replica",
        "catalog_product_index_price_tmp",
        "catalog_product_index_tier_price",
        "catalog_product_index_website",
        "catalog_product_link",
        "catalog_product_link_attribute",
        "catalog_product_link_attribute_decimal",
        "catalog_product_link_attribute_int",
        "catalog_product_link_attribute_varchar",
        "catalog_product_link_type",
        "catalog_product_option",
        "catalog_product_option_price",
        "catalog_product_option_title",
        "catalog_product_option_type_price",
        "catalog_product_option_type_title",
        "catalog_product_option_type_value",
        "catalog_product_price_cl",
        "catalog_product_relation",
        "catalog_product_super_attribute",
        "catalog_product_super_attribute_label",
        "catalog_product_super_link",
        "catalog_product_website",
        "catalog_category_product",
        "catalog_category_product_index",
        "catalog_url_rewrite_product_category",
        "cataloginventory_stock_item",
        "cataloginventory_stock_status",
        "cataloginventory_stock_status_idx",
        "cataloginventory_stock_status_tmp",
        "catalog_category_product",
        "catalog_category_product_index",
        "catalog_category_product_index_tmp",
        "catalog_compare_item",
        "catalog_url_rewrite_product_category",
        "downloadable_link",
        "downloadable_link_price",
        "downloadable_link_purchased",
        "downloadable_link_purchased_item",
        "downloadable_link_title",
        "downloadable_sample",
        "downloadable_sample_title",
        "product_alert_price",
        "product_alert_stock",
        "report_compared_product_index",
        "report_viewed_product_aggregated_daily",
        "report_viewed_product_aggregated_monthly",
        "report_viewed_product_aggregated_yearly",
        "report_viewed_product_index"
    );

    /** @var array  */
    protected $_publicActions = ['product'];

    /**
     * Product Deletion constructor.
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger,
        Context $context
    )
    {
        $this->_resource = $resource;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Remove products
     *
     * @return void
     */
    public function execute()
    {
        $connection = $this->_resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        foreach ($this->catalog_products_tables as $catalogTable) {
            $tableName = $connection->getTableName($catalogTable);
            $query = "TRUNCATE TABLE " . $tableName;
            try {
                $connection->query($query);
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        $tableURLRewrite = $connection->getTableName('url_rewrite');
        $connection->query("DELETE FROM " . $tableURLRewrite . " WHERE entity_type = 'product';");
        $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
        $this->messageManager->addSuccessMessage(__('Products deleted successfully.'));
        $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }

}
