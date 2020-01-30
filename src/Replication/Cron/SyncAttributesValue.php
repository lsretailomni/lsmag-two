<?php

namespace Ls\Replication\Cron;

use Ls\Core\Model\LSR;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Setup\Exception;

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
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if ($this->lsr->isLSR()) {
            $cronAttributeCheck = $this->lsr->getStoreConfig(LSR::SC_SUCCESS_CRON_ATTRIBUTE);
            if ($cronAttributeCheck == 1) {
                $this->replicationHelper->updateConfigValue(
                    $this->replicationHelper->getDateTime(),
                    LSR::LAST_EXECUTE_REPL_SYNC_ATTRIBUTES_VALUE
                );
                $this->logger->debug('Running Sync Attributes Value Task');
                $storeId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
                $this->processAttributesValue();
                $remainingItems = (int)$this->getRemainingRecords();
                if ($remainingItems == 0) {
                    $this->cronStatus = true;
                }
            } else {
                $this->cronStatus = false;
            }
            $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_ATTRIBUTES_VALUE);
            $this->logger->debug('End Sync Attributes Value Task');
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
     * @param $attributeCode
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function processAttributesValue()
    {
        try {
            $criteria = $this->replicationHelper->buildCriteriaForNewItems('', '', 'eq', -1, 1);
            /** @var ReplAttributeValueSearchResults $items */
            $replAttributeValue = $this->replAttributeValueRepositoryInterface->getList($criteria);
            /** @var ReplAttributeValue $replAttributeValue */
            foreach ($replAttributeValue->getItems() as $attributeValue) {
                $itemId = $attributeValue->getLinkField1();
                try {
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
                    $attributeValue->setData('processed_at', $this->replicationHelper->getDateTime());
                    $attributeValue->setData('processed', 1);
                    $attributeValue->setData('is_updated', 0);
                    // @codingStandardsIgnoreLine
                    $this->replAttributeValueRepositoryInterface->save($attributeValue);
                } catch (NoSuchEntityException $e) {
                    $this->logger->debug('Problem with sku: ' . $itemId . ' in ' . __METHOD__);
                    $this->logger->debug($e->getMessage());
                }
            }
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    /**
     * @return int
     */
    public function getRemainingRecords()
    {
        if (!$this->remainingRecords) {
            if (!$this->remainingRecords) {
                $criteria               = $this->replicationHelper->buildCriteriaForNewItems();
                $this->remainingRecords = $this->replAttributeValueRepositoryInterface->getList($criteria)
                    ->getTotalCount();
            }
            return $this->remainingRecords;
        }
        return $this->remainingRecords;
    }
}
