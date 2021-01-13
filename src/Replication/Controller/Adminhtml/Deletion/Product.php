<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Helper\ReplicationHelper;
use \Ls\Replication\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Product Deletion
 */
class Product extends Action
{
    /** @var Logger */
    public $logger;

    /** @var ResourceConnection */
    public $resource;

    /** @var LSR */
    public $lsr;

    /** @var ReplicationHelper */
    public $replicationHelper;

    /**
     * @var AttributeSetRepositoryInterface
     */
    public $attributeSetRepository;

    /** @var array List of ls tables required in products */
    public $ls_tables = [
        "ls_replication_repl_item",
        "ls_replication_repl_item_variant_registration",
        "ls_replication_repl_price",
        "ls_replication_repl_barcode",
        "ls_replication_repl_inv_status",
        "ls_replication_repl_hierarchy_leaf",
        "ls_replication_repl_attribute_value",
        "ls_replication_repl_image_link",
        "ls_replication_repl_item_unit_of_measure",
        "ls_replication_repl_loy_vendor_item_mapping",
        "ls_replication_repl_item_modifier",
        "ls_replication_repl_item_recipe",
        "ls_replication_repl_hierarchy_hosp_deal",
        "ls_replication_repl_hierarchy_hosp_deal_line"
    ];

    /** @var array List of all the Catalog Product tables */
    public $catalog_products_tables = [
        "catalog_product_bundle_option",
        "catalog_product_bundle_option_value",
        "catalog_product_bundle_price_index",
        "catalog_product_bundle_selection",
        "catalog_product_bundle_selection_price",
        "catalog_product_bundle_stock_index",
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
        "report_viewed_product_index",
        "sequence_product"
    ];

    // @codingStandardsIgnoreStart
    /** @var array */
    protected $_publicActions = ['product'];
    // @codingStandardsIgnoreEnd

    /**
     * Product constructor.
     * @param ResourceConnection $resource
     * @param Logger $logger
     * @param Context $context
     * @param LSR $LSR
     * @param ReplicationHelper $replicationHelper
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        ResourceConnection $resource,
        Logger $logger,
        Context $context,
        LSR $LSR,
        ReplicationHelper $replicationHelper,
        AttributeSetRepositoryInterface $attributeSetRepository
    ) {
        $this->resource               = $resource;
        $this->logger                 = $logger;
        $this->lsr                    = $LSR;
        $this->replicationHelper      = $replicationHelper;
        $this->attributeSetRepository = $attributeSetRepository;
        parent::__construct($context);
    }

    /**
     * Remove products
     *
     * @return void
     */
    public function execute()
    {
        // @codingStandardsIgnoreStart
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $connection->query('SET FOREIGN_KEY_CHECKS = 0;');
        foreach ($this->catalog_products_tables as $catalogTable) {
            $tableName = $this->resource->getTableName($catalogTable);
            try {
                if ($connection->isTableExists($tableName)) {
                    $connection->truncateTable($tableName);
                }
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }
        // Remove the url keys from url_rewrite table
        $this->replicationHelper->resetUrlRewriteByType('product');
        $connection->query('SET FOREIGN_KEY_CHECKS = 1;');
        $mediaDirectory        = $this->replicationHelper->getMediaPathtoStore();
        $catalogMediaDirectory = $mediaDirectory . "catalog" . DIRECTORY_SEPARATOR . "product" . DIRECTORY_SEPARATOR;
        $mediaTmpDirectory     = $mediaDirectory . "tmp". DIRECTORY_SEPARATOR. "catalog" . DIRECTORY_SEPARATOR . "product" . DIRECTORY_SEPARATOR;
        try {
            $this->replicationHelper->removeDirectory($catalogMediaDirectory);
            $this->replicationHelper->removeDirectory($mediaTmpDirectory);
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }

        $filters      = [
            ['field' => 'attribute_set_name', 'value' => 'ls_%', 'condition_type' => 'like'],
            ['field' => 'entity_type_id', 'value' => 4, 'condition_type' => 'eq']
        ];
        $criteria     = $this->replicationHelper->buildCriteriaForDirect($filters, -1, false);
        $searchResult = $this->attributeSetRepository->getList($criteria);
        foreach ($searchResult->getItems() as $attributeSet) {
            try {
                $this->attributeSetRepository->deleteById($attributeSet->getAttributeSetId());
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        // Update all dependent ls tables to not processed
        foreach ($this->ls_tables as $lsTable) {
            $lsTableName = $this->resource->getTableName($lsTable);
            $lsQuery     = 'UPDATE ' . $lsTableName . ' SET processed = 0, is_updated = 0, is_failed = 0, processed_at = NULL;';
            try {
                $connection->query($lsQuery);
            } catch (Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        // @codingStandardsIgnoreEnd
        // Reset Data Translation Table for product name
        $lsTableName = $this->resource->getTableName("ls_replication_repl_data_translation");
        $lsQuery     = 'UPDATE ' . $lsTableName . ' SET processed = 0, is_updated = 0, is_failed = 0,
            processed_at = NULL WHERE TranslationId ="' . LSR::SC_TRANSLATION_ID_ITEM_DESCRIPTION . '"';
        try {
            $connection->query($lsQuery);
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }

        $this->replicationHelper->updateCronStatusForAllStores(
            false,
            LSR::SC_SUCCESS_CRON_PRODUCT
        );
        $this->replicationHelper->updateCronStatusForAllStores(
            false,
            LSR::SC_SUCCESS_CRON_PRODUCT_INVENTORY
        );
        $this->replicationHelper->updateCronStatusForAllStores(
            false,
            LSR::SC_SUCCESS_CRON_PRODUCT_PRICE
        );
        $this->replicationHelper->updateCronStatusForAllStores(
            false,
            LSR::SC_SUCCESS_CRON_ITEM_IMAGES
        );
        $this->replicationHelper->updateCronStatusForAllStores(
            false,
            LSR::SC_SUCCESS_CRON_ITEM_UPDATES
        );
        $this->replicationHelper->updateCronStatusForAllStores(
            false,
            LSR::SC_SUCCESS_CRON_ATTRIBUTES_VALUE
        );
        $this->replicationHelper->updateCronStatusForAllStores(
            false,
            LSR::SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO
        );
        $this->replicationHelper->flushByTypeCode('config');
        $this->messageManager->addSuccessMessage(__('Products deleted successfully.'));
        $this->_redirect('adminhtml/system_config/edit/section/ls_mag');
    }
}
