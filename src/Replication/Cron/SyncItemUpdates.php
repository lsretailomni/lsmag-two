<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchyLeaf;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;

/**
 * Class SyncItemUpdates
 * @package Ls\Replication\Cron
 */
class SyncItemUpdates extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    public function execute()
    {
        if ($this->lsr->isLSR()) {
            $this->logger->debug('Running SyncItemUpdates Task ');
            $this->replicationHelper->updateConfigValue(
                $this->replicationHelper->getDateTime(),
                LSR::SC_ITEM_UPDATES_CONFIG_PATH_LAST_EXECUTE
            );
            $hierarchyCode = $this->lsr->getStoreConfig(LSR::SC_REPLICATION_HIERARCHY_CODE);
            if (!empty($hierarchyCode)) {
                $itemAssignmentCount = $this->caterItemAssignmentToCategories();
                $this->caterHierarchyLeafRemoval($hierarchyCode);

                if ($itemAssignmentCount == 0) {
                    $this->cronStatus = true;
                }
            } else {
                $this->logger->debug("Hierarchy Code not defined in the configuration.");
            }

            $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_ITEM_UPDATES);
            $this->logger->debug('End SyncItemUpdates Task ');
        }
    }

    /**
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function executeManually()
    {
        $this->execute();
        $itemsLeftToProcess = (int)$this->getRemainingRecords();
        return [$itemsLeftToProcess];
    }

    /**
     * @return mixed
     */
    public function caterItemAssignmentToCategories()
    {
        $assignProductToCategoryBatchSize = $this->replicationHelper->getProductCategoryAssignmentBatchSize();

        $filters = [
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
        ];

        $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            $assignProductToCategoryBatchSize
        );
        /** @var  $collection */
        $collection = $this->replHierarchyLeafCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'nav_id',
            'ls_replication_repl_item',
            'nav_id',
            true
        );
        $sku = "";
        if ($collection->getSize() > 0) {
            foreach ($collection as $hierarchyLeaf) {
                try {
                    $sku     = $hierarchyLeaf->getNavId();
                    $product = $this->productRepository->get($hierarchyLeaf->getNavId());
                    $this->assignProductToCategories($product);
                } catch (Exception $e) {
                    $this->logger->debug("Problem with sku: " . $sku . " in " . __METHOD__);
                    $this->logger->debug($e->getMessage());
                }
            }
            return (int)$this->getRemainingRecords();
        } else {
            return (int)$collection->getSize();
        }
    }

    /**
     * @param $hierarchyCode
     * @return mixed
     */
    public function caterHierarchyLeafRemoval($hierarchyCode)
    {
        $filters    = [['field' => 'main_table.HierarchyCode', 'value' => $hierarchyCode, 'condition_type' => 'eq']];
        $criteria   = $this->replicationHelper->buildCriteriaGetDeletedOnlyWithAlias($filters, 100);
        $collection = $this->replHierarchyLeafCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'nav_id',
            'catalog_product_entity',
            'sku'
        );
        $sku = "";
        /** @var ReplHierarchyLeaf $hierarchyLeaf */
        foreach ($collection as $hierarchyLeaf) {
            try {
                $sku               = $hierarchyLeaf->getNavId();
                $product           = $this->productRepository->get($sku);
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
            } catch (Exception $e) {
                $this->logger->debug('Problem with sku: ' . $sku . ' in ' . __METHOD__);
                $this->logger->debug($e->getMessage());
            }
            $hierarchyLeaf->setData('processed', 1);
            $hierarchyLeaf->setData('processed_at', $this->replicationHelper->getDateTime());
            $hierarchyLeaf->setData('IsDeleted', 0);
            $hierarchyLeaf->setData('is_updated', 0);
            // @codingStandardsIgnoreStart
            $this->replHierarchyLeafRepository->save($hierarchyLeaf);
            // @codingStandardsIgnoreEnd
        }
        return $collection->getSize();
    }

    /**
     * @param $nav_id
     * @return bool|\Magento\Framework\DataObject
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
     * @return int
     */
    public function getRemainingRecords()
    {
        if (!$this->remainingRecords) {
            $filters = [
                ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
            ];

            $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                $filters,
                -1
            );
            /** @var  $collection */
            $collection = $this->replHierarchyLeafCollectionFactory->create();
            $this->replicationHelper->setCollectionPropertiesPlusJoin(
                $collection,
                $criteria,
                'nav_id',
                'ls_replication_repl_item',
                'nav_id',
                true
            );
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }
}
