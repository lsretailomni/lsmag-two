<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\ReplAttributeValue;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * For sync soft attribute value
 */
class SyncAttributesValue extends ProductCreateTask
{

    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /**
     * @param null $storeData
     * @throws InputException
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
                    $cronAttributeCheck = $this->lsr->getConfigValueFromDb(
                        LSR::SC_SUCCESS_CRON_ATTRIBUTE,
                        ScopeInterface::SCOPE_STORES,
                        $this->store->getId()
                    );
                    if ($cronAttributeCheck == 1) {
                        $this->replicationHelper->updateConfigValue(
                            $this->replicationHelper->getDateTime(),
                            LSR::LAST_EXECUTE_REPL_SYNC_ATTRIBUTES_VALUE,
                            $this->store->getId()
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
                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_ATTRIBUTES_VALUE,
                        $this->store->getId()
                    );
                    $this->logger->debug('End Sync Attributes Value Task for store ' . $this->store->getName());
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * @param null $storeData
     * @return array|int[]
     * @throws InputException
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
     * For syncing attribute value
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputException
     */
    public function processAttributesValue()
    {
        /** Get list of only those Attribute Value whose items are already processed */
        $attributeBatchSize = $this->replicationHelper->getProductAttributeBatchSize();
        $criteria           = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            [['field' => 'main_table.scope_id', 'value' => $this->store->getId(), 'condition_type' => 'eq']],
            $attributeBatchSize,
            false
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
                    // skipping is failed and processed in case of variant attribute value
                    $checkIsVariant   = false;
                    $checkIsException = false;
                    $itemId           = $attributeValue->getLinkField1();
                    $variantId        = $attributeValue->getLinkField2();
                    $sku              = $itemId;
                    if (!empty($variantId)) {
                        $sku            = $sku . '-' . $variantId;
                        $checkIsVariant = true;
                    }
                    $product        = $this->productRepository->get($sku, true, 0);
                    $formattedCode  = $this->replicationHelper->formatAttributeCode(
                        $attributeValue->getCode()
                    );
                    $attributeSetId = $product->getAttributeSetId();
                    $this->attributeAssignmentToAttributeSet(
                        $attributeSetId,
                        $formattedCode,
                        LSR::SC_REPLICATION_ATTRIBUTE_SET_SOFT_ATTRIBUTES_GROUP
                    );
                    $attribute = $this->eavConfig->getAttribute('catalog_product', $formattedCode);
                    if ($attribute->getFrontendInput() == 'multiselect') {
                        $value = $this->replicationHelper->getAllValuesForGivenMultiSelectAttribute(
                            $itemId,
                            $variantId,
                            $attributeValue->getCode(),
                            $formattedCode,
                            $this->store->getId()
                        );
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

                    $uomCodes = $this->getUomCodesProcessed($itemId);
                    $this->replicationHelper->processUomAttributes(
                        $uomCodes,
                        $itemId,
                        $sku,
                        $formattedCode,
                        $value,
                        $variantId,
                        $this->productRepository
                    );
                } catch (Exception $e) {
                    if (!$checkIsVariant) {
                        $this->logger->debug('Problem with sku: ' . $itemId . ' in ' . __METHOD__);
                        $this->logger->debug($e->getMessage());
                        $attributeValue->setData('is_failed', 1);
                    }
                    $checkIsException = true;
                }
                if (!$checkIsVariant || !$checkIsException) {
                    $attributeValue->setData('processed_at', $this->replicationHelper->getDateTime());
                    $attributeValue->setData('processed', 1);
                    $attributeValue->setData('is_updated', 0);
                    // @codingStandardsIgnoreLine
                    $this->replAttributeValueRepositoryInterface->save($attributeValue);
                }
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
            $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                [['field' => 'main_table.scope_id', 'value' => $this->store->getId(), 'condition_type' => 'eq']],
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
