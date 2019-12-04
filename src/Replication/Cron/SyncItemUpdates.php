<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
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
    /** @var string */
    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_item_updates_sync';

    /** @var bool */
    public $cronStatus = false;


    public function execute()
    {
        $this->replicationHelper->updateConfigValue(date('d M,Y h:i:s A'), self::CONFIG_PATH_LAST_EXECUTE);
        $assignProductToCategoryBatchSize = $this->replicationHelper->getProductCategoryAssignmentBatchSize();
        $this->logger->debug('Running SyncItemUpdates Task ');
        $filters = [
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
        ];

        $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            $assignProductToCategoryBatchSize
        );
        $collection = $this->replHierarchyLeafCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'nav_id',
            'ls_replication_repl_item',
            'nav_id',
            true
        );
        try {
            foreach ($collection as $hierarchyLeaf) {
                $product = $this->productRepository->get($hierarchyLeaf->getNavId());
                $this->assignProductToCategories($product);
            }
        } catch (Exception $e) {
            $this->logger->debug("Problem with sku: " . $product->getSku() . " in " . __METHOD__);
            $this->logger->debug($e->getMessage());
        }

        if (count($collection->getItems()) == 0) {
            $this->cronStatus = true;
        }
        $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_ITEM_UPDATES);
        $this->logger->debug('End SyncItemUpdates Task ');
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
        $filters = [
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
        ];

        $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters
        );
        $collection = $this->replHierarchyLeafCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'nav_id',
            'ls_replication_repl_item',
            'nav_id',
            true
        );
        $itemsLeftToProcess = $collection->getSize();
        return [$itemsLeftToProcess];
    }
}
