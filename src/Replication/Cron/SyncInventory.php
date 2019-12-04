<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplInvStatus;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;

/**
 * Class SyncInventory
 * @package Ls\Replication\Cron
 */
class SyncInventory extends ProductCreateTask
{
    /** @var string */
    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_inventory_sync';

    /** @var bool */
    public $cronStatus = false;

    public function execute()
    {
        $this->replicationHelper->updateConfigValue(date('d M,Y h:i:s A'), self::CONFIG_PATH_LAST_EXECUTE);
        $this->logger->debug('Running SyncInventory Task');
        $storeId                   = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
        $productInventoryBatchSize = $this->replicationHelper->getProductInventoryBatchSize();

        /** Get list of only those Inventory whose items are already processed */
        $filters    = [
            ['field' => 'main_table.StoreId', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
        ];
        $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            $productInventoryBatchSize,
            1
        );
        $collection = $this->replInvStatusCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'ItemId',
            'ls_replication_repl_item',
            'nav_id'
        );
        if ($collection->getSize() > 0) {
            /** @var ReplInvStatus $replInvStatus */
            foreach ($collection as $replInvStatus) {
                try {
                    if (!$replInvStatus->getVariantId()) {
                        $sku = $replInvStatus->getItemId();
                    } else {
                        $sku = $replInvStatus->getItemId() . '-' . $replInvStatus->getVariantId();
                    }
                    $stockItem = $this->stockRegistry->getStockItemBySku($sku);
                    if (isset($stockItem)) {
                        // @codingStandardsIgnoreStart
                        $stockItem->setQty($replInvStatus->getQuantity());
                        $stockItem->setIsInStock(($replInvStatus->getQuantity() > 0) ? 1 : 0);
                        $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
                        // @codingStandardsIgnoreEnd
                    }
                } catch (Exception $e) {
                    $this->logger->debug('Problem with sku: ' . $sku . ' in ' . __METHOD__);
                    $this->logger->debug($e->getMessage());
                    $replInvStatus->setData('is_failed', 1);
                }
                $replInvStatus->setData('is_updated', 0);
                $replInvStatus->setData('processed', 1);
                $this->replInvStatusRepository->save($replInvStatus);
            }
        }
        if (count($collection->getItems()) == 0) {
            $this->cronStatus = true;
        }
        $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_PRODUCT_INVENTORY);
        $this->logger->debug('End SyncInventory Task');
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
        $storeId                   = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
        /** Get list of only those Inventory whose items are already processed */
        $filters    = [
            ['field' => 'main_table.StoreId', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
        ];
        $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            -1,
            1
        );
        $collection = $this->replInvStatusCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'ItemId',
            'ls_replication_repl_item',
            'nav_id'
        );
        $itemsLeftToProcess = $collection->getSize();
        return [$itemsLeftToProcess];
    }
}
