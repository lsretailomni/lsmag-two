<?php

namespace Ls\Replication\Cron;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplAttributeValue;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Setup\Exception;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class SyncAttributesValue
 * @package Ls\Replication\Cron
 */
class SyncAttributesValue extends ProductCreateTask
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
                    $cronAttributeCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE,
                        $this->store->getId());
                    if ($cronAttributeCheck == 1) {
                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::LAST_EXECUTE_REPL_SYNC_ATTRIBUTES_VALUE, $this->store->getId()
                        );
                        $this->logger->debug('Running Sync Attributes Value Task for store ' . $this->store->getName());
                        $this->processAttributesValue();
                        $remainingItems = (int)$this->getRemainingRecords($this->store);
                        if ($remainingItems == 0) {
                            $this->cronStatus = true;
                        }
                    } else {
                        $this->cronStatus = false;
                    }
                    $this->replicationHelper->updateCronStatus($this->cronStatus,
                        LSR::SC_SUCCESS_CRON_ATTRIBUTES_VALUE, $this->store->getId());
                    $this->logger->debug('End Sync Attributes Value Task for store ' . $this->store->getName());
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
    public function processAttributesValue()
    {
        /** Get list of only those Attribute Value whose items are already processed */
        $filters            = [];
        $attributeBatchSize = $this->replicationHelper->getProductAttributeBatchSize();
        $criteria           = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            $attributeBatchSize,
            1
        );
        $collection         = $this->replAttributeValueCollectionFactory->create();

        $this->replicationHelper->setCollectionPropertiesPlusJoinSku(
            $collection,
            $criteria,
            'LinkField1',
            null,
            'catalog_product_entity',
            'sku'
        );


        if ($collection->getSize() > 0) {
            /** @var ReplAttributeValue $attributeValue */
            foreach ($collection as $attributeValue) {
                try {
                    $itemId        = $attributeValue->getLinkField1();
                    $product       = $this->productRepository->get($itemId);
                    $formattedCode = $this->replicationHelper->formatAttributeCode($attributeValue->getCode());
                    $attribute     = $this->eavConfig->getAttribute('catalog_product', $formattedCode);
                    if ($attribute->getFrontendInput() == 'multiselect') {
                        $value = $this->_getOptionIDByCode($formattedCode, $attributeValue->getValue());
                    } elseif ($attribute->getFrontendInput() == 'boolean') {
                        if (strtolower($attributeValue->getValue()) == 'yes') {
                            $value = 1;
                        } else {
                            $value = 0;
                        }
                    } else {
                        $value = $attributeValue->getValue();
                    }
                    $product->setData($formattedCode, $value);
                    $product->getResource()->saveAttribute($product, $formattedCode);
                } catch (Exception $e) {
                    $this->logger->debug('Problem with sku: ' . $itemId . ' in ' . __METHOD__);
                    $this->logger->debug($e->getMessage());
                    $attributeValue->setData('is_failed', 1);
                }
                $attributeValue->setData('processed_at', $this->replicationHelper->getDateTime());
                $attributeValue->setData('processed', 1);
                $attributeValue->setData('is_updated', 0);
                // @codingStandardsIgnoreLine
                $this->replAttributeValueRepositoryInterface->save($attributeValue);
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
            $collection = $this->replAttributeValueCollectionFactory->create();
            $this->replicationHelper->setCollectionPropertiesPlusJoinSku(
                $collection,
                $criteria,
                'LinkField1',
                null,
                'catalog_product_entity',
                'sku'
            );
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }
}
