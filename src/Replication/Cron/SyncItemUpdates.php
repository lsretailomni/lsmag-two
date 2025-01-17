<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\HierarchyLeafType;
use \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchyLeaf;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Cron responsible to add products in categories
 */
class SyncItemUpdates extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /**
     * Entry point for cron
     *
     * @param mixed $storeData
     * @return void
     * @throws LocalizedException
     */
    public function execute($storeData = null)
    {
        if (!$this->lsr->isSSM()) {
            if (!empty($storeData) && $storeData instanceof StoreInterface) {
                $stores = [$storeData];
            } else {
                $stores = $this->lsr->getAllStores();
            }
        } else {
            $stores = [$this->lsr->getAdminStore()];
        }

        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $cronCategoryCheck = $this->lsr->getConfigValueFromDb(
                        LSR::SC_SUCCESS_CRON_CATEGORY,
                        ScopeInterface::SCOPE_STORES,
                        $store->getId()
                    );

                    if ($cronCategoryCheck == 1) {
                        $this->logger->debug('Running SyncItemUpdates Task for store ' . $this->store->getName());
                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::SC_ITEM_UPDATES_CONFIG_PATH_LAST_EXECUTE,
                            $this->store->getId(),
                            ScopeInterface::SCOPE_STORES
                        );
                        $hierarchyCode = $this->lsr->getStoreConfig(
                            LSR::SC_REPLICATION_HIERARCHY_CODE,
                            $this->store->getId()
                        );
                        if (!empty($hierarchyCode)) {
                            $itemAssignmentCount = $this->caterItemAssignmentToCategories();
                            $this->caterHierarchyLeafRemoval($hierarchyCode);
                            if ($itemAssignmentCount == 0) {
                                $this->cronStatus = true;
                            }
                        } else {
                            $this->logger->debug('Hierarchy Code not defined in the configuration.');
                        }

                        $this->replicationHelper->updateCronStatus(
                            $this->cronStatus,
                            LSR::SC_SUCCESS_CRON_ITEM_UPDATES,
                            $this->store->getId(),
                            false,
                            ScopeInterface::SCOPE_STORES
                        );
                        $this->logger->debug('End SyncItemUpdates Task for store ' . $this->store->getName());
                    }
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * Execute manually
     *
     * @param mixed $storeData
     * @return int[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        $itemsLeftToProcess = (int)$this->getRemainingRecords($storeData);
        return [$itemsLeftToProcess];
    }

    /**
     * Cater Item assignment to categories
     *
     * @return int
     * @throws LocalizedException
     */
    public function caterItemAssignmentToCategories()
    {
        $assignProductToCategoryBatchSize = $this->replicationHelper->getProductCategoryAssignmentBatchSize();

        $filters = [
            ['field' => 'Type', 'value' => 'Deal', 'condition_type' => 'neq'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
        ];

        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            $assignProductToCategoryBatchSize
        );
        /** @var  $collection */
        $collection = $this->replHierarchyLeafCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoinSku(
            $collection,
            $criteria,
            'nav_id',
            null,
            ['repl_hierarchy_leaf_id'],
            true
        );
        $websiteId = $this->store->getWebsiteId();
        $this->replicationHelper->applyProductWebsiteJoin($collection, $websiteId);
        $sku = '';
        foreach ($collection as $hierarchyLeaf) {
            try {
                $sku = $hierarchyLeaf->getNavId();
                if ($hierarchyLeaf->getType() == HierarchyLeafType::ITEM_CATEGORY) {
                    $products = $this->replicationHelper->getProductsByItemCategory($hierarchyLeaf->getNavId(),
                        $this->store->getId());
                    foreach ($products as $product) {
                        if ($product) {
                            $this->replicationHelper->assignProductToCategories($product, $this->store, true);
                        }
                    }
                } else {
                    $product = $this->replicationHelper->getProductDataByIdentificationAttributes(
                        $hierarchyLeaf->getNavId()
                    );
                    if ($product) {
                        $this->replicationHelper->assignProductToCategories($product, $this->store);
                    }
                }
            } catch (Exception $e) {
                $this->logger->debug(
                    sprintf(
                        'Exception happened in %s for store: %s, item id: %s',
                        __METHOD__,
                        $this->store->getName(),
                        $sku
                    )
                );
                $this->logger->debug($e->getMessage());
            }
        }
        return (int)$this->getRemainingRecords($this->store);
    }

    /**
     * Cater hierarchy leaf removal
     *
     * @param string $hierarchyCode
     * @return int
     * @throws LocalizedException
     */
    public function caterHierarchyLeafRemoval($hierarchyCode)
    {
        $filters    = [
            ['field' => 'main_table.HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq'],
            ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
        ];
        $criteria   = $this->replicationHelper->buildCriteriaGetDeletedOnlyWithAlias($filters, 100);
        $collection = $this->replHierarchyLeafCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoinSku(
            $collection,
            $criteria,
            'nav_id',
            null,
            ['repl_hierarchy_leaf_id'],
            true
        );
        $sku = '';
        /** @var ReplHierarchyLeaf $hierarchyLeaf */
        foreach ($collection as $hierarchyLeaf) {
            try {
                $sku = $hierarchyLeaf->getNavId();
                if ($hierarchyLeaf->getType() == HierarchyLeafType::ITEM_CATEGORY) {
                    $products = $this->replicationHelper->getProductsByItemCategory($hierarchyLeaf->getNavId(),
                        $this->store->getId());
                    foreach ($products as $product) {
                        $sku = $product->getSku();
                        $this->categoryProductLinkRemoval($hierarchyLeaf, $product, $sku);
                    }
                } else {
                    $product = $this->productRepository->get($sku);
                    $this->categoryProductLinkRemoval($hierarchyLeaf, $product, $sku);
                }
            } catch (Exception $e) {
                $this->logger->debug(
                    sprintf(
                        'Exception happened in %s for store: %s, item id: %s',
                        __METHOD__,
                        $this->store->getName(),
                        $sku
                    )
                );
                $this->logger->debug($e->getMessage());
            }
            $hierarchyLeaf->setData('processed', 1);
            $hierarchyLeaf->setData('processed_at', $this->replicationHelper->getDateTime());
            $hierarchyLeaf->setData('is_updated', 0);
            // @codingStandardsIgnoreStart
            $this->replHierarchyLeafRepository->save($hierarchyLeaf);
            // @codingStandardsIgnoreEnd
        }
        return $collection->getSize();
    }

    /**
     * Is category exist
     *
     * @param string $nav_id
     * @return false|DataObject
     * @throws LocalizedException
     */
    public function isCategoryExist($nav_id)
    {
        $collection = $this->collectionFactory->create()
            ->addAttributeToFilter('nav_id', $nav_id)
            ->setPageSize(1);
        if ($collection->getSize()) {
            // @codingStandardsIgnoreStart
            return $collection->getFirstItem();
            // @codingStandardsIgnoreEnd
        }
        return false;
    }

    /**
     * For removing product from certain categories
     *
     * @param $hierarchyLeaf
     * @param $product
     * @param $sku
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function categoryProductLinkRemoval($hierarchyLeaf, $product, $sku)
    {
        $categories        = null;
        $categories        = $product->getCategoryIds();
        $categoryExistData = $this->isCategoryExist($hierarchyLeaf->getNodeId());
        if (!empty($categoryExistData)) {
            $categoryId       = $categoryExistData->getEntityId();
            $parentCategoryId = $categoryExistData->getParentId();
            if (in_array($categoryId, $categories)) {
                $this->categoryLinkRepositoryInterface->deleteByIds($categoryId, $sku);
                $catIndex = array_search($categoryId, $categories);
                if ($catIndex !== false) {
                    unset($categories[$catIndex]);
                }
            }
            if (in_array($parentCategoryId, $categories)) {
                $childCategories = $this->categoryRepository->get($parentCategoryId)->getChildren();
                $childCat        = explode(",", $childCategories);
                if (count(array_intersect($childCat, $categories)) == 0) {
                    $this->categoryLinkRepositoryInterface->deleteByIds($parentCategoryId, $sku);
                }
            }
        }
    }


    /**
     * Get remaining records
     *
     * @param mixed $storeData
     * @return int
     * @throws LocalizedException
     */
    public function getRemainingRecords($storeData)
    {
        if (!$this->remainingRecords) {
            $filters  = [
                ['field' => 'Type', 'value' => 'Deal', 'condition_type' => 'neq'],
                ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
            ];
            $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                $filters,
                -1
            );
            /** @var  $collection */
            $collection = $this->replHierarchyLeafCollectionFactory->create();
            $this->replicationHelper->setCollectionPropertiesPlusJoinSku(
                $collection,
                $criteria,
                'nav_id',
                null,
                ['repl_hierarchy_leaf_id']
            );
            $websiteId = $this->store->getWebsiteId();
            $this->replicationHelper->applyProductWebsiteJoin($collection, $websiteId);
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }
}
