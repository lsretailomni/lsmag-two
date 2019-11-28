<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplPrice;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;

/**
 * Class SyncPrice
 * @package Ls\Replication\Cron
 */
class SyncPrice extends ProductCreateTask
{
    /** @var string  */
    const CONFIG_PATH_LAST_EXECUTE = 'ls_mag/replication/last_execute_repl_price_sync';

    /** @var bool  */
    public $cronStatus = false;

    public function execute()
    {
        $this->replicationHelper->updateConfigValue(date('d M,Y h:i:s A'), self::CONFIG_PATH_LAST_EXECUTE);
        $this->logger->debug('Running SyncPrice Task ');
        $storeId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);

        $productPricesBatchSize = $this->replicationHelper->getProductPricesBatchSize();

        /** Get list of only those prices whose items are already processed */
        $filters = [
            ['field' => 'main_table.StoreId', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
        ];

        $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters,
            $productPricesBatchSize,
            1
        );
        $collection = $this->replPriceCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'ItemId',
            'ls_replication_repl_item',
            'nav_id'
        );
        if ($collection->getSize() > 0) {
            /** @var ReplPrice $replPrice */
            foreach ($collection as $replPrice) {
                try {
                    if (!$replPrice->getVariantId()) {
                        $sku = $replPrice->getItemId();
                    } else {
                        $sku = $replPrice->getItemId() . '-' . $replPrice->getVariantId();
                    }
                    $productData = $this->productRepository->get($sku);
                    if (isset($productData)) {
                        $productData->setPrice($replPrice->getUnitPrice());
                        // @codingStandardsIgnoreStart
                        $this->productResourceModel->saveAttribute($productData, 'price');
                        // @codingStandardsIgnoreEnd
                        if ($productData->getTypeId() == 'configurable') {
                            $_children = $productData->getTypeInstance()->getUsedProducts($productData);
                            foreach ($_children as $child) {
                                $childProductData = $this->productRepository->get($child->getSKU());
                                $childProductData->setPrice($replPrice->getUnitPrice());
                                // @codingStandardsIgnoreStart
                                $this->productResourceModel->saveAttribute($childProductData, 'price');
                                // @codingStandardsIgnoreEnd
                            }
                        }
                    }
                } catch (Exception $e) {
                    $this->logger->debug("Problem with sku: " . $sku . " in " . __METHOD__);
                    $this->logger->debug($e->getMessage());
                }
                $replPrice->setData('is_updated', '0');
                $replPrice->setData('processed', '1');
                $this->replPriceRepository->save($replPrice);
            }
        }
        if (count($collection->getItems()) == 0) {
            $this->cronStatus = true;
        }
        $this->replicationHelper->updateCronStatus($this->cronStatus, LSR::SC_SUCCESS_CRON_PRODUCT_PRICE);
        $this->logger->debug('End SyncPrice task');
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
        $storeId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE);
        $filters = [
            ['field' => 'main_table.StoreId', 'value' => $storeId, 'condition_type' => 'eq'],
            ['field' => 'second.processed', 'value' => 1, 'condition_type' => 'eq']
        ];

        $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
            $filters
        );
        $collection = $this->replPriceCollectionFactory->create();
        $this->replicationHelper->setCollectionPropertiesPlusJoin(
            $collection,
            $criteria,
            'ItemId',
            'ls_replication_repl_item',
            'nav_id'
        );
        $itemsLeftToProcess = count($collection->getItems());
        return [$itemsLeftToProcess];
    }
}
