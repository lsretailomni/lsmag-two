<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Core\Model\LSR;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Product Deletion
 */
class Product extends AbstractReset
{
    /** @var array List of all the Catalog Product tables */
    public const CATALOG_PRODUCT_TABLES = [
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

    public const DEPENDENT_CRONS = [
        LSR::SC_SUCCESS_CRON_PRODUCT,
        LSR::SC_SUCCESS_CRON_PRODUCT_INVENTORY,
        LSR::SC_SUCCESS_CRON_PRODUCT_PRICE,
        LSR::SC_SUCCESS_CRON_ITEM_IMAGES,
        LSR::SC_SUCCESS_CRON_ITEM_UPDATES,
        LSR::SC_SUCCESS_CRON_ATTRIBUTES_VALUE,
        LSR::SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO
    ];

    // @codingStandardsIgnoreStart
    /** @var array */
    protected $_publicActions = ['product'];
    // @codingStandardsIgnoreEnd

    /**
     * Remove products
     *
     * @return ResponseInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $scopeId = $this->_request->getParam('store');
        $where   = [];

        if ($scopeId != '') {
            $websiteId        = $this->replicationHelper->getWebsiteIdGivenStoreId($scopeId);
            $childCollection  = $this->replicationHelper->getProductCollectionGivenWebsiteId($websiteId);
            $parentCollection = $this->replicationHelper->getGivenColumnsFromGivenCollection($childCollection, ['sku']);
            $this->replicationHelper->deleteGivenTableDataGivenConditions(
                $this->replicationHelper->getGivenTableName('catalog_product_entity'),
                ['sku IN (?)' => $parentCollection->getSelect()]
            );
            $where = [
                'scope_id = ?' => $scopeId
            ];
        } else {
            $connection = $this->replicationHelper->getConnection();
            $connection->startSetup();
            foreach (self::CATALOG_PRODUCT_TABLES as $catalogTable) {
                $tableName = $this->replicationHelper->getGivenTableName($catalogTable);
                $this->replicationHelper->truncateGivenTable($tableName);
            }
            $connection->endSetup();
            $this->clearRequiredMediaDirectories();
            $this->deleteAllAttributeSets();
        }
        // Remove the url keys from url_rewrite table
        $this->replicationHelper->resetUrlRewriteByType('product', $scopeId);
        // Update all dependent ls tables to not processed
        $this->updateAllDependentLsTables($where);
        // Reset Data Translation Table for product name
        $this->updateDataTranslationTables($where);
        $this->replicationHelper->updateAllGivenCronStatus(self::DEPENDENT_CRONS, $scopeId);
        $this->messageManager->addSuccessMessage(__('Products deleted successfully.'));

        return $this->_redirect('adminhtml/system_config/edit/section/ls_mag', ['store' => $scopeId]);
    }

    /**
     * Clear required media directories
     *
     * @return void
     */
    public function clearRequiredMediaDirectories()
    {
        $mediaDirectory        = $this->replicationHelper->getMediaPathtoStore();
        $catalogMediaDirectory = sprintf(
            '%scatalog%sproduct%s',
            $mediaDirectory,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
        $mediaTmpDirectory     = sprintf(
            '%stmp%scatalog%sproduct%s',
            $mediaDirectory,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
        $this->replicationHelper->removeDirectory($catalogMediaDirectory);
        $this->replicationHelper->removeDirectory($mediaTmpDirectory);
    }

    /**
     * Delete All Attribute Sets
     *
     * @return void
     */
    public function deleteAllAttributeSets()
    {
        $filters      = [
            ['field' => 'attribute_set_name', 'value' => 'ls_%', 'condition_type' => 'like'],
            ['field' => 'entity_type_id', 'value' => 4, 'condition_type' => 'eq']
        ];
        $criteria     = $this->replicationHelper->buildCriteriaForDirect($filters, -1, false);
        $searchResult = $this->replicationHelper->attributeSetRepository->getList($criteria);
        foreach ($searchResult->getItems() as $attributeSet) {
            try {
                $this->replicationHelper->attributeSetRepository->deleteById($attributeSet->getAttributeSetId());
            } catch (Exception $e) {
                $this->replicationHelper->_logger->debug($e->getMessage());
            }
        }
    }

    /**
     * Update All dependent ls tables
     *
     * @param $where
     * @return void
     */
    public function updateAllDependentLsTables($where)
    {
        foreach (self::LS_ITEM_RELATED_TABLES as $lsTable) {
            $lsTableName = $this->replicationHelper->getGivenTableName($lsTable);
            $this->replicationHelper->updateGivenTableDataGivenConditions(
                $lsTableName,
                [
                    'processed' => 0,
                    'is_updated' => 0,
                    'is_failed' => 0,
                    'processed_at' => null
                ],
                $where
            );
        }
    }

    /**
     * Update data translation tables
     *
     * @param $where
     * @return void
     */
    public function updateDataTranslationTables($where)
    {
        $where['TranslationId = ?'] = LSR::SC_TRANSLATION_ID_ITEM_DESCRIPTION;
        $lsTableName                = $this->replicationHelper->getGivenTableName(
            'ls_replication_repl_data_translation'
        );
        $this->replicationHelper->updateGivenTableDataGivenConditions(
            $lsTableName,
            [
                'processed' => 0,
                'is_updated' => 0,
                'is_failed' => 0,
                'processed_at' => null
            ],
            $where
        );
    }
}
