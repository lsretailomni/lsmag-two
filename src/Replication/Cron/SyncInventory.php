<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplInvStatus;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Sync items Inventory
 */
class SyncInventory extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /**
     * @param null $storeData
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
                    if ($collection->getSize() > 0) {
                        /** @var ReplInvStatus $replInvStatus */
                        foreach ($collection as $replInvStatus) {
                            try {
                                $variants = null;
                                $checkIsNotVariant = true;
                                if (!$replInvStatus->getVariantId()) {
                                    $sku = $replInvStatus->getCustomItemId();
                                } else {
                                    $sku               = $replInvStatus->getItemId() . '-' . $replInvStatus->getVariantId();
                                    $checkIsNotVariant = false;
                                }
                                $this->replicationHelper->updateInventory($sku, $replInvStatus);
                                $uomCodes = $this->getUomCodesProcessed($replInvStatus->getCustomItemId());
                                if (!empty($uomCodes)) {
                                    if (count($uomCodes[$replInvStatus->getCustomItemId()]) > 1) {
                                        // @codingStandardsIgnoreLine
                                        $baseUnitOfMeasure = $uomCodes[$replInvStatus->getCustomItemId() . '-' . 'BaseUnitOfMeasure'];
                                        $variants          = $this->getProductVariants($sku);
                                        foreach ($uomCodes[$replInvStatus->getCustomItemId()] as $uomCode) {
                                            if (($checkIsNotVariant || $baseUnitOfMeasure != $uomCode) && empty($variants)) {
                                                $skuUom = $sku . "-" . $uomCode;
                                                $this->replicationHelper->updateInventory($skuUom, $replInvStatus);
                                            }
                                        }
                                    }
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
     * @param null $storeData
     * @return array|int[]
     * @throws NoSuchEntityException
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
