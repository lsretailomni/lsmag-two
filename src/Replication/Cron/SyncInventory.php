<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplInvStatus;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Cron responsible to update inventory for item and variants
 */
class SyncInventory extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /** @var array */
    public $processed = [];

    /** @var array */
    public $sourceItems = [];

    public $uomProducts = [];

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
                    $this->replicationHelper->updateConfigValue(
                        $this->replicationHelper->getDateTime(),
                        LSR::SC_PRODUCT_INVENTORY_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );
                    $this->logger->debug('Running SyncInventory Task for store ' . $this->store->getName());
                    $productInventoryBatchSize = $this->replicationHelper->getProductInventoryBatchSize();
                    $filters                   = [
                        ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
                    ];
                    $criteria                  = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                        $filters,
                        $productInventoryBatchSize,
                        1
                    );
                    $collection                = $this->replInvStatusCollectionFactory->create();
                    $this->replicationHelper->setCollectionPropertiesPlusJoinsForInventory($collection, $criteria);
                    $websiteId = $this->store->getWebsiteId();
                    $this->replicationHelper->applyProductWebsiteJoin($collection, $websiteId);
                    $defaultSourceCode = $this->replicationHelper->getDefaultSourceObject()->create()->getCode();
                    $sourceCode        = $this->replicationHelper->getSourceCodeFromWebsiteCode(
                        $defaultSourceCode,
                        $websiteId
                    );
                    $this->sourceItems = [];
                    $this->process($collection, $sourceCode, $defaultSourceCode);
                    $remainingItems = (int)$this->getRemainingRecords($this->store);
                    if ($remainingItems == 0) {
                        $this->cronStatus = true;
                    }

                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_PRODUCT_INVENTORY,
                        $this->store->getId(),
                        false,
                        ScopeInterface::SCOPE_STORES
                    );
                    $this->logger->debug('End SyncInventory Task for store ' . $this->store->getName());
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * Process inventory data
     *
     * @param object $collection
     * @param string $sourceCode
     * @param string $defaultSourceCode
     * @return void
     */
    public function process($collection, $sourceCode, $defaultSourceCode)
    {
        /** @var ReplInvStatus $replInvStatus */
        foreach ($collection as $replInvStatus) {
            try {
                $uomCodeStatus = false;
                $uomCodes      = $this->getUomCodesProcessed($replInvStatus->getItemId());
                if (!empty($uomCodes)) {
                    if (count($uomCodes[$replInvStatus->getItemId()]) > 1) {
                        $uomCodeStatus = true;
                        if (!isset($this->uomProducts[$replInvStatus->getItemId()])) {
                            $this->uomProducts[$replInvStatus->getItemId()] = $this->replicationHelper->
                            getProductDataByIdentificationAttributes(
                                $replInvStatus->getItemId(),
                                '',
                                '',
                                '',
                                true,
                                true,
                                true
                            );
                        }
                    }
                }
                if (!$uomCodeStatus) {
                    $this->updateInventory($replInvStatus, $sourceCode, $defaultSourceCode);
                } else {
                    foreach ($this->uomProducts[$replInvStatus->getItemId()] as $index => $uomProduct) {
                        if ($replInvStatus->getVariantId()) {
                            if ($replInvStatus->getVariantId() ==
                                $uomProduct->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE)
                            ) {
                                $this->updateInventory($replInvStatus, $sourceCode, $defaultSourceCode, $uomProduct);
                                unset($this->uomProducts[$replInvStatus->getItemId()][$index]);
                            }
                        } else {
                            if ($uomProduct->getTypeId() == Configurable::TYPE_CODE &&
                                $this->hasAttributesOtherThenUom($uomProduct)) {
                                $replInvStatus->setData('is_updated', 0);
                                $replInvStatus->setData('processed', 1);
                                $replInvStatus->setData(
                                    'processed_at',
                                    $this->replicationHelper->getDateTime()
                                );
                                $this->replInvStatusRepository->save($replInvStatus);
                                break;
                            }
                            $this->updateInventory($replInvStatus, $sourceCode, $defaultSourceCode, $uomProduct);
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logger->debug(
                    sprintf(
                        'Exception happened in %s for store: %s, item id: %s, variant id: %s',
                        __METHOD__,
                        $this->store->getName(),
                        $replInvStatus->getItemId(),
                        $replInvStatus->getVariantId()
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
        if (count($this->sourceItems) > 0) {
            $this->saveSourceItems();
        }
    }

    /**
     * Update inventory
     *
     * @param $replInvStatus
     * @param $sourceCode
     * @param $defaultSourceCode
     * @param $product
     * @return void
     */
    public function updateInventory($replInvStatus, $sourceCode, $defaultSourceCode, $product = null)
    {
        if (!in_array(
            $product ? $product->getId() : $replInvStatus->getEntityId(),
            $this->processed
        ) || $sourceCode != $defaultSourceCode
        ) {
            $this->sourceItems = $this->replicationHelper->updateInventory(
                $product,
                $replInvStatus,
                true,
                $this->sourceItems
            );
            $this->processed[] = $product ? $product->getId() : $replInvStatus->getEntityId();
        }
    }

    /**
     * Save source items and change stock status
     *
     * @return void
     */
    public function saveSourceItems()
    {
        try {
            $this->replicationHelper->getSourceItemsSaveObject()->execute(array_values($this->sourceItems));
            $this->replicationHelper->updateStockStatus($this->processed);
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
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
        $itemsLeftToProcess = $this->getRemainingRecords($storeData);
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
                ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq']
            ];
            $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                $filters,
                -1,
                1
            );
            $collection = $this->replInvStatusCollectionFactory->create();
            $this->replicationHelper->setCollectionPropertiesPlusJoinsForInventory($collection, $criteria);
            $websiteId = $this->store->getWebsiteId();
            $this->replicationHelper->applyProductWebsiteJoin($collection, $websiteId);
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }

    /**
     * Has attribute other then uom
     *
     * @param $product
     * @return bool
     */
    public function hasAttributesOtherThenUom($product)
    {
        $exists  = false;
        $options = $product->getTypeInstance()->getConfigurableAttributes($product);

        foreach ($options->getItems() as $attribute) {
            if ($attribute->getProductAttribute()->getAttributeCode() != 'lsr_uom') {
                $exists = true;
                break;
            }
        }

        return $exists;
    }
}
