<?php

namespace Ls\Replication\Controller\Adminhtml\Deletion;

use Exception;
use \Ls\Core\Model\LSR;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class CategoryDeletion
 */
class Category extends AbstractReset
{
    public const DEPENDENT_CRONS = [
        LSR::SC_SUCCESS_CRON_CATEGORY,
        LSR::SC_SUCCESS_CRON_ITEM_UPDATES,
        LSR::SC_SUCCESS_CRON_DATA_TRANSLATION_TO_MAGENTO
    ];

    /** @var array List of magento tables required in categories */
    public const MAGENTO_CATEGORY_TABLES = [
        'catalog_category_product',
        'catalog_category_product_index',
        'catalog_category_product_index_tmp'
    ];

    /**
     * Remove categories tree
     *
     * @return ResponseInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $scopeId = $this->_request->getParam('store');
        $where   = [];

        if ($scopeId != '') {
            $stores = [$this->replicationHelper->storeManager->getStore($scopeId)];
            $this->deleteAllStoresCategoriesUnderRootCategory($stores);
            $where = ['scope_id = ?' => $scopeId];
        } else {
            $stores = $this->replicationHelper->lsr->getAllStores();
            $this->deleteAllStoresCategoriesUnderRootCategory($stores);
            $this->truncateAllGivenTables(self::MAGENTO_CATEGORY_TABLES);
            $this->clearRequiredMediaDirectories();
        }

        // Update dependent ls tables to not processed
        $this->updateAllGivenTablesToUnprocessed(self::LS_CATEGORY_RELATED_TABLES, $where);
        // Update translation ls tables to not processed for hierarchy
        $where['TranslationId = ?'] = LSR::SC_TRANSLATION_ID_HIERARCHY_NODE;
        $this->updateDataTranslationTables($where);
        // remove the url keys from url_rewrite table.
        $this->replicationHelper->resetUrlRewriteByType('category', $scopeId);
        $this->replicationHelper->updateAllGivenCronsWithGivenStatus(self::DEPENDENT_CRONS, $scopeId, false);
        $this->messageManager->addSuccessMessage(__('Categories deleted successfully.'));

        return $this->_redirect('adminhtml/system_config/edit/section/ls_mag', ['store' => $scopeId]);
    }

    /**
     * Delete root categories
     *
     * @param $stores
     * @return void
     */
    public function deleteAllStoresCategoriesUnderRootCategory($stores)
    {
        try {
            if (!empty($stores)) {
                foreach ($stores as $store) {
                    $rootCategory = $this->replicationHelper->getCategoryGivenId($store->getRootCategoryId());
                    $this->replicationHelper->deleteChildrenGivenCategory($rootCategory);
                }
            }
        } catch (Exception $e) {
            $this->replicationHelper->_logger->debug($e->getMessage());
        }
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
            '%scatalog%scategory%s',
            $mediaDirectory,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
        $mediaTmpDirectory     = sprintf(
            '%stmp%scatalog%scategory%s',
            $mediaDirectory,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR
        );
        $this->replicationHelper->removeDirectory($catalogMediaDirectory);
        $this->replicationHelper->removeDirectory($mediaTmpDirectory);
    }
}
