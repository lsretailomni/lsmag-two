<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplLoyVendorItemMapping;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Setup\Exception;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class SyncVendorAttributesValue
 * @package Ls\Replication\Cron
 */
class SyncVendorAttributesValue extends ProductCreateTask
{

    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /**
     * @param null $storeData
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
                    $cronAttributeCheck = $this->lsr->getStoreConfig(
                        LSR::SC_SUCCESS_CRON_VENDOR,
                        $this->store->getId()
                    );
                    if ($cronAttributeCheck == 1) {
                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::LAST_EXECUTE_REPL_SYNC_VENDOR_ATTRIBUTES,
                            $this->store->getId()
                        );
                        $this->logger->debug('Running Sync Vendor Task for store ' . $this->store->getName());
                        $this->processVendorAttributesValue();
                        $remainingItems = (int)$this->getRemainingRecords($this->store);
                        if ($remainingItems == 0) {
                            $this->cronStatus = true;
                        }
                    } else {
                        $this->cronStatus = false;
                    }
                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_VENDOR_ATTRIBUTE,
                        $this->store->getId()
                    );
                    $this->logger->debug('End Sync Vendor Task for store ' . $this->store->getName());
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * @param null $storeData
     * @return array
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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function processVendorAttributesValue()
    {
        /** Get list of only those Attribute Value whose items are already processed */
        $filters            = [];
        $attributeBatchSize = $this->replicationHelper->getProductAttributeBatchSize();
        $criteria           = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            $attributeBatchSize,
            1
        );
        $collection         = $this->replItemVendorCollectionFactory->create();

        $this->replicationHelper->setCollectionPropertiesPlusJoinsForVendor(
            $collection,
            $criteria
        );

        if ($collection->getSize() > 0) {
            /** @var ReplLoyVendorItemMapping $attributeValue */
            foreach ($collection as $attributeValue) {
                $itemId = $attributeValue->getNavProductId();
                try {
                    $vendorName = $attributeValue->getData('name');
                    $product    = $this->productRepository->get($itemId);
                    $value      = $this->_getOptionIDByCode(LSR::LS_VENDOR_ATTRIBUTE, $vendorName);
                    $product->setData(LSR::LS_VENDOR_ATTRIBUTE, $value);
                    $product->getResource()->saveAttribute($product, LSR::LS_VENDOR_ATTRIBUTE);
                    $product->setData(LSR::LS_ITEM_VENDOR_ATTRIBUTE, $attributeValue->getNavManufacturerItemId());
                    $product->getResource()->saveAttribute($product, LSR::LS_ITEM_VENDOR_ATTRIBUTE);
                } catch (Exception $e) {
                    $this->logger->debug('Problem with sku: ' . $itemId . ' in ' . __METHOD__);
                    $this->logger->debug($e->getMessage());
                    $attributeValue->setData('is_failed', 1);
                }
                $attributeValue->setData('processed_at', $this->replicationHelper->getDateTime());
                $attributeValue->setData('processed', 1);
                $attributeValue->setData('is_updated', 0);
                // @codingStandardsIgnoreLine
                $this->replVendorItemMappingRepositoryInterface->save($attributeValue);
            }
        }
    }

    /**
     * @param $storeData
     * @return int
     */
    public function getRemainingRecords($storeData)
    {
        if (!$this->remainingRecords) {
            /** Get list of only those attribute value whose items are already processed */
            $filters    = [];
            $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                $filters
            );
            $collection = $this->replItemVendorCollectionFactory->create();
            $this->replicationHelper->setCollectionPropertiesPlusJoinSku(
                $collection,
                $criteria,
                'NavProductId',
                null,
                'catalog_product_entity',
                'sku'
            );
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }
}
