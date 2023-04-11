<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplInvStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Cron responsible to update inventory for item and variants
 */
class SyncInventory extends ProductCreateTask
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
     * @throws NoSuchEntityException
     */
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
                        LSR::SC_PRODUCT_INVENTORY_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId()
                    );
                    $this->logger->debug('Running SyncInventory Task for store ' . $this->store->getName());
                    $productInventoryBatchSize = $this->replicationHelper->getProductInventoryBatchSize();
                    $filters                   = [
                        ['field' => 'main_table.scope_id', 'value' => $this->store->getId(), 'condition_type' => 'eq']
                    ];
                    $criteria                  = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                        $filters,
                        $productInventoryBatchSize,
                        1
                    );
                    $collection                = $this->replInvStatusCollectionFactory->create();
                    $this->replicationHelper->setCollectionPropertiesPlusJoinsForInventory($collection, $criteria);
                    /** @var ReplInvStatus $replInvStatus */
                    foreach ($collection as $replInvStatus) {
                        try {
                            $sku = '';
                            $uomCodeStatus = false;
                            $uomCodes      = $this->getUomCodesProcessed($replInvStatus->getItemId());
                            if (!empty($uomCodes)) {
                                if (count($uomCodes[$replInvStatus->getItemId()]) > 1) {
                                    $uomCodeStatus = true;

                                }
                            }
                            if (!$uomCodeStatus) {
                                $sku = $this->replicationHelper->getProductDataByIdentificationAttributes(
                                    $replInvStatus->getItemId(),
                                    $replInvStatus->getVariantId()
                                )->getSku();
                                $this->replicationHelper->updateInventory(
                                    $sku,
                                    $replInvStatus
                                );
                            }
                            if ($uomCodeStatus) {
                                foreach ($uomCodes[$replInvStatus->getItemId()] as $uomCode) {
                                    $sku = $this->replicationHelper->
                                    getProductDataByIdentificationAttributes(
                                        $replInvStatus->getItemId(),
                                        $replInvStatus->getVariantId(),
                                        $uomCode
                                    )->getSku();
                                    $this->replicationHelper->updateInventory(
                                        $sku,
                                        $replInvStatus
                                    );
                                }
                            }
                        } catch (Exception $e) {
                            $this->logger->debug(
                                sprintf(
                                    'Exception happened in %s for store: %, item id: %s',
                                    __METHOD__,
                                    $this->store->getName(),
                                    $sku
                                )
                            );
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

                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_PRODUCT_INVENTORY,
                        $this->store->getId()
                    );
                    $this->logger->debug('End SyncInventory Task for store ' . $this->store->getName());
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
     * Get remaining records
     *
     * @param mixed $storeData
     * @return int
     * @throws LocalizedException
     */
    public function getRemainingRecords($storeData)
    {
        if (!$this->remainingRecords) {
            $filters    = [
                ['field' => 'main_table.scope_id', 'value' => $this->store->getId(), 'condition_type' => 'eq']
            ];
            $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                $filters,
                -1,
                1
            );
            $collection = $this->replInvStatusCollectionFactory->create();
            $this->replicationHelper->setCollectionPropertiesPlusJoinsForInventory($collection, $criteria);
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }
}
