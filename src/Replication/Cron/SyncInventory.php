<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplInvStatus;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class SyncInventory
 * @package Ls\Replication\Cron
 */
class SyncInventory extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    public function execute($storeData = null)
    {
        if (!empty($storeData) && $storeData instanceof StoreInterface) {
            $stores = [$storeData];
        } else {
            /** @var StoreInterface[] $stores */
            $stores = $this->lsr->getAllStores();
        }
        if (!empty($stores)) {
            foreach ($stores as $store) {
                $this->lsr->setStoreId($store->getId());
                $this->store = $store;
                if ($this->lsr->isLSR($this->store->getId())) {
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_PRODUCT_INVENTORY_CONFIG_PATH_LAST_EXECUTE, $this->store->getId()
                    );
                    $this->logger->debug('Running SyncInventory Task for store ' . $this->store->getName());
                    $storeId                   = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE,
                        $this->store->getId());
                    $productInventoryBatchSize = $this->replicationHelper->getProductInventoryBatchSize();
                    $filters    = [
                        ['field' => 'main_table.StoreId', 'value' => $storeId, 'condition_type' => 'eq']
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
                        'catalog_product_entity',
                        'sku',
                        true,
                        true
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
                            $replInvStatus->setData('processed_at', $this->replicationHelper->getDateTime());
                            $this->replInvStatusRepository->save($replInvStatus);
                        }
                        $remainingItems = (int)$this->getRemainingRecords($this->store);
                        if ($remainingItems == 0) {
                            $this->cronStatus = true;
                        }
                    } else {
                        $this->cronStatus = true;
                    }

                    $this->replicationHelper->updateCronStatus($this->cronStatus,
                        LSR::SC_SUCCESS_CRON_PRODUCT_INVENTORY, $this->store->getId());
                    $this->logger->debug('End SyncInventory Task for store ' . $this->store->getName());
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * @param null $storeData
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    public function executeManually($storeData = null)
    {
        $this->execute($storeData);
        $itemsLeftToProcess = (int)$this->getRemainingRecords($storeData);
        return [$itemsLeftToProcess];
    }

    /**
     * @param $storeData
     * @return int
     */
    public function getRemainingRecords($storeData)
    {
        if (!$this->remainingRecords) {
            $storeId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE, $storeData->getId());
            $filters    = [
                ['field' => 'main_table.StoreId', 'value' => $storeId, 'condition_type' => 'eq']
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
                'catalog_product_entity',
                'sku',
                true,
                true
            );
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }
}
