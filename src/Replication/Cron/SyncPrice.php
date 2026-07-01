<?php

declare(strict_types=1);

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplPrice;
use \Ls\Replication\Model\Central\ReplSalesPrice;
use \Ls\Replication\Model\SalesPriceProcessor;
use \Ls\Replication\Model\ResourceModel\ReplPrice\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Cron responsible to update prices for item and variants
 */
class SyncPrice extends ProductCreateTask
{
    /** @var bool */
    public $cronStatus = false;

    /** @var int */
    public $remainingRecords;

    /**
     * @var array
     */
    public $processed = [];

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
                        LSR::SC_PRODUCT_PRICE_CONFIG_PATH_LAST_EXECUTE,
                        $this->store->getId(),
                        ScopeInterface::SCOPE_STORES
                    );
                    $this->logger->debug('Running SyncPrice Task for store ' . $this->store->getName());
                    $this->processed = [];

                    if ($this->lsr->getStoreConfig(LSR::SC_USE_SALES_PRICE, $this->store->getId())) {
                        $this->executeSalesPrice($this->store->getId());
                        $this->lsr->setStoreId(null);
                        continue;
                    }

                    $productPricesBatchSize = $this->replicationHelper->getProductPricesBatchSize();

                    /** Get list of only those prices whose items are already processed */
                    $filters = [
                        ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
                        ['field' => 'main_table.Status', 'value' => '1', 'condition_type' => 'eq']
                    ];

                    $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                        $filters,
                        $productPricesBatchSize,
                        1
                    );
                    $collection = $this->replPriceCollectionFactory->create();
                    $this->replicationHelper->setCollectionPropertiesPlusJoinSku(
                        $collection,
                        $criteria,
                        'ItemId',
                        'VariantId',
                        ['repl_price_id']
                    );

                    $websiteId = $this->store->getWebsiteId();
                    $this->replicationHelper->applyProductWebsiteJoin($collection, $websiteId);
                    $this->process($collection);
                    $remainingItems = (int)$this->getRemainingRecords($this->store);
                    if ($remainingItems == 0) {
                        $this->cronStatus = true;
                    }
                    $this->replicationHelper->updateCronStatus(
                        $this->cronStatus,
                        LSR::SC_SUCCESS_CRON_PRODUCT_PRICE,
                        $this->store->getId(),
                        false,
                        ScopeInterface::SCOPE_STORES
                    );
                    $this->logger->debug('End SyncPrice Task for store ' . $this->store->getName());
                }
                $this->lsr->setStoreId(null);
            }
        }
    }

    /**
     * Process Price
     *
     * @param Collection $collection
     * @return void
     */
    public function process($collection)
    {
        /** @var ReplPrice $replPrice */
        foreach ($collection as $replPrice) {
            /** @var ReplPrice $replPrice */
            try {
                if (in_array($replPrice->getId(), $this->processed)) {
                    continue;
                }

                // Check if price is scheduled for future (don't mark as processed)
                if ($this->isFuturePrice($replPrice)) {
                    $this->logger->debug(
                        sprintf(
                            'Skipping future price for store: %s, item id: %s, variant id: %s, start date: %s (not marking as processed)',
                            $this->store->getName(),
                            $replPrice->getItemId(),
                            $replPrice->getVariantId(),
                            $replPrice->getStartingDate()
                        )
                    );
                    // Don't mark as processed - allow it to be picked up in next cron run
                    continue;
                }

                // Validate price record
                if (!$this->isValidPrice($replPrice)) {
                    $this->logger->debug(
                        sprintf(
                            'Skipping invalid price for store: %s, item id: %s, variant id: %s, status: %s, start date: %s, end date: %s',
                            $this->store->getName(),
                            $replPrice->getItemId(),
                            $replPrice->getVariantId(),
                            $replPrice->getStatus(),
                            $replPrice->getStartingDate(),
                            $replPrice->getEndingDate()
                        )
                    );
                    // Mark as processed but don't update the product
                    $replPrice->setData('is_updated', 0);
                    $replPrice->setData('processed', 1);
                    $replPrice->setData('processed_at', $this->replicationHelper->getDateTime());
                    $this->replPriceRepository->save($replPrice);
                    $this->processed[$replPrice->getId()] = $replPrice->getId();
                    continue;
                }

                $productDataArray = $this->replicationHelper->getProductDataByIdentificationAttributes(
                    $replPrice->getItemId(),
                    $replPrice->getVariantId(),
                    $replPrice->getUnitOfMeasure(),
                    $this->store->getId(),
                    true,
                    true,
                    true
                );
                if (isset($productDataArray)) {
                    $this->processProductPrice($productDataArray, $replPrice);
                }
            } catch (Exception $e) {
                $this->logger->debug(
                    sprintf(
                        'Exception happened in %s for store: %s, item id: %s, variant id: %s, uom: %s ',
                        __METHOD__,
                        $this->store->getName(),
                        $replPrice->getItemId(),
                        $replPrice->getVariantId(),
                        $replPrice->getUnitOfMeasure()
                    )
                );
                $this->logger->debug($e->getMessage());
                $replPrice->setData('is_failed', 1);
                $replPrice->setData('is_updated', 0);
                $replPrice->setData('processed', 1);
                $this->replPriceRepository->save($replPrice);
            }

        }
    }

    /**
     * Check if price is scheduled for future (start date not reached yet)
     *
     * @param ReplPrice $replPrice
     * @return bool
     */
    protected function isFuturePrice($replPrice)
    {
        // Only check if status is Active
        if ($replPrice->getStatus() !== '1') {
            return false;
        }

        $startingDate = $replPrice->getStartingDate();
        $invalidDate = '1900-01-01T00:00:00';
        $invalidDateAlt = '1900-01-01';

        // If starting date is empty or invalid, it's not a future price
        $isStartingDateInvalid = empty($startingDate) ||
            strpos($startingDate, $invalidDate) === 0 ||
            strpos($startingDate, $invalidDateAlt) === 0;

        if ($isStartingDateInvalid) {
            return false;
        }

        try {
            $currentDate = $this->replicationHelper->getCurrentDate();
            $format      = LSR::DATE_FORMAT;
            $startDateTime = $this->replicationHelper->convertDateTimeIntoCurrentTimeZone(
                $startingDate,
                $format
            );

            // If current date is before start date, it's a future price
            if ($currentDate < $startDateTime) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->debug(
                sprintf(
                    'Error checking future price for item: %s, variant: %s, start date: %s - Error: %s',
                    $replPrice->getItemId(),
                    $replPrice->getVariantId(),
                    $startingDate,
                    $e->getMessage()
                )
            );
            return false;
        }

        return false;
    }

    /**
     * Validate if price record is active and within valid date range
     *
     * @param ReplPrice $replPrice
     * @return bool
     */
    protected function isValidPrice($replPrice)
    {
        // Check if status is Active
        if ($replPrice->getStatus() !== '1') {
            return false;
        }

        $startingDate = $replPrice->getStartingDate();
        $endingDate = $replPrice->getEndingDate();
        $invalidDate = '1900-01-01T00:00:00';
        $invalidDateAlt = '1900-01-01';

        $isStartingDateInvalid = empty($startingDate) ||
            strpos($startingDate, $invalidDate) === 0 ||
            strpos($startingDate, $invalidDateAlt) === 0;

        $isEndingDateInvalid = empty($endingDate) ||
            strpos($endingDate, $invalidDate) === 0 ||
            strpos($endingDate, $invalidDateAlt) === 0;

        // If both dates are invalid/empty, allow the price (no date restrictions)
        if ($isStartingDateInvalid && $isEndingDateInvalid) {
            return true;
        }

        try {
            $currentDate = $this->replicationHelper->getCurrentDate();
            $format      = LSR::DATE_FORMAT;
            // Case 1: Only start date is valid (check if current date is after start)
            if (!$isStartingDateInvalid && $isEndingDateInvalid) {
                $startDateTime = $this->replicationHelper->convertDateTimeIntoCurrentTimeZone(
                    $startingDate,
                    $format
                );
                if ($currentDate < $startDateTime) {
                    return false; // Start date not reached yet
                }
                return true;
            }

            // Case 2: Only end date is valid (no start date restriction)
            if ($isStartingDateInvalid && !$isEndingDateInvalid) {
                $endDateTime = $this->replicationHelper->convertDateTimeIntoCurrentTimeZone(
                    $endingDate,
                    $format
                );
                if ($currentDate > $endDateTime) {
                    return false; // Price has expired
                }
                return true;
            }

            // Case 3: Both dates are valid - check if current date is within range
            if (!$isStartingDateInvalid && !$isEndingDateInvalid) {
                $startDateTime = $this->replicationHelper->convertDateTimeIntoCurrentTimeZone(
                    $startingDate,
                    $format
                );
                $endDateTime = $this->replicationHelper->convertDateTimeIntoCurrentTimeZone(
                    $endingDate,
                    $format
                );
                if ($currentDate < $startDateTime || $currentDate > $endDateTime) {
                    return false;
                }
                return true;
            }

        } catch (\Exception $e) {
            $this->logger->debug(
                sprintf(
                    'Invalid date format in price record for item: %s, variant: %s, start date: %s, end date: %s - Error: %s',
                    $replPrice->getItemId(),
                    $replPrice->getVariantId(),
                    $startingDate,
                    $endingDate,
                    $e->getMessage()
                )
            );
            return true; // On date parsing error, allow the price
        }

        return true;
    }

    /**
     * Process product price
     *
     * @param array $productDataArray
     * @param object $replPrice
     * @return void
     * @throws Exception
     */
    public function processProductPrice($productDataArray, $replPrice)
    {
        /** @var ReplPrice $replPrice */
        $replItemPriceList = null;
        if (count($productDataArray) > 1) {
            $replItemPriceList = $this->getItemPriceList($replPrice->getItemId());
        }
        foreach ($productDataArray as $productData) {
            $price = $replPrice;
            if (!empty($replItemPriceList)) {
                $replItemPrice = $this->getPrice($productData, $replItemPriceList);
                if (!empty($replItemPrice)) {
                    $price = $replItemPrice;
                }
            }

            if ($this->isFuturePrice($price)) {
                continue;
            }

            if ($productData->getPrice() != $price->getUnitPriceInclVat()) {
                $productData->setPrice($price->getUnitPriceInclVat());
                $this->productResourceModel->saveAttribute($productData, 'price');
            }
            $price->addData(
                [
                    'is_updated' => 0,
                    'processed' => 1,
                    'processed_at' => $this->replicationHelper->getDateTime()
                ]
            );
            $this->replPriceRepository->save($price);
            $this->processed[$price->getId()] = $price->getId();
        }
    }

    /**
     * Process sales prices sourced from the repl_sales_price table (GetSalesPrice flow).
     *
     * Only runs when the UseSalesPrice config is enabled.
     *
     * @param int|string $storeId
     * @return void
     */
    public function executeSalesPrice(int|string $storeId): void
    {
        $this->logger->debug('Running SyncPrice (sales price) flow for store ' . $this->store->getName());

        $scopeId = $this->getScopeId();
        $processor = $this->getSalesPriceProcessor();
        $collection = $this->getReplSalesPriceCollection();
        $collection->addFieldToFilter('is_updated', 1)
            ->addFieldToFilter('scope_id', $scopeId);

        /** @var ReplSalesPrice $salesPrice */
        foreach ($collection as $salesPrice) {
            try {
                if ($processor->isFutureSalesPrice($salesPrice)) {
                    // Not active yet - do not mark as processed so it is retried later.
                    continue;
                }

                if (!$processor->isValidSalesPrice($salesPrice)) {
                    $this->markSalesPriceProcessed($salesPrice);
                    continue;
                }

                $productData = $this->replicationHelper->getProductDataByIdentificationAttributes(
                    $salesPrice->getItemNo(),
                    (string) $salesPrice->getVariantCode(),
                    (string) $salesPrice->getUnitOfMeasureCode(),
                    $storeId
                );

                if (!empty($productData)) {
                    $products = is_array($productData) ? $productData : [$productData];
                    $price = $salesPrice->getLscUnitPriceIncludingVat();

                    foreach ($products as $product) {
                        if ($price !== null && $product->getPrice() != $price) {
                            $product->setPrice($price);
                            $this->productResourceModel->saveAttribute($product, 'price');
                        }
                    }
                }

                $this->markSalesPriceProcessed($salesPrice);
            } catch (\Exception $e) {
                $this->logger->debug(
                    sprintf(
                        'Error processing sales price for item: %s, variant: %s - Error: %s',
                        $salesPrice->getItemNo(),
                        $salesPrice->getVariantCode(),
                        $e->getMessage()
                    )
                );
            }
        }

        $remaining = $this->getRemainingSalesPriceRecords($scopeId);
        if ($remaining == 0) {
            $this->cronStatus = true;
        }
        $this->replicationHelper->updateCronStatus(
            $this->cronStatus,
            LSR::SC_SUCCESS_CRON_PRODUCT_PRICE,
            $this->store->getId(),
            false,
            ScopeInterface::SCOPE_STORES
        );
        $this->logger->debug('End SyncPrice (sales price) flow for store ' . $this->store->getName());
    }

    /**
     * Get the number of remaining unprocessed sales price records for the given scope.
     *
     * @param int|string $scopeId
     * @return int
     */
    private function getRemainingSalesPriceRecords(int|string $scopeId): int
    {
        $collection = $this->getReplSalesPriceCollection();
        $collection->addFieldToFilter('is_updated', 1)
            ->addFieldToFilter('scope_id', $scopeId);

        return (int) $collection->getSize();
    }

    /**
     * Mark a sales price record as processed.
     *
     * @param ReplSalesPrice $salesPrice
     * @return void
     */
    private function markSalesPriceProcessed(ReplSalesPrice $salesPrice): void
    {
        $salesPrice->addData(
            [
                'is_updated' => 0,
                'processed' => 1,
                'processed_at' => $this->replicationHelper->getDateTime()
            ]
        );
        $this->getReplSalesPriceRepository()->save($salesPrice);
    }

    /**
     * Lazily resolve the sales price processor.
     *
     * @return SalesPriceProcessor
     */
    private function getSalesPriceProcessor(): SalesPriceProcessor
    {
        // SyncPrice extends ProductCreateTask, which declares a ~52-argument PHP 8 promoted-property
        // constructor. Injecting SalesPriceProcessor via the constructor would require re-declaring all
        // 52 parent arguments in SyncPrice — an unacceptable fragility risk. The ObjectManager accessor
        // is the established project pattern for this situation (see AbstractReplicationTask::getObjectManager,
        // used by getLsrModel). ProductCreateTask does not extend AbstractReplicationTask, so that accessor
        // is not inherited here; the static ObjectManager call is used deliberately, not out of laziness.
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get(SalesPriceProcessor::class);
    }

    /**
     * Lazily create a repl_sales_price collection.
     *
     * @return \Ls\Replication\Model\Central\ResourceModel\ReplSalesPrice\Collection
     */
    private function getReplSalesPriceCollection(): \Ls\Replication\Model\Central\ResourceModel\ReplSalesPrice\Collection
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Ls\Replication\Model\Central\ResourceModel\ReplSalesPrice\CollectionFactory::class)
            ->create();
    }

    /**
     * Lazily resolve the repl_sales_price repository.
     *
     * @return \Ls\Replication\Api\Central\ReplSalesPriceRepositoryInterface
     */
    private function getReplSalesPriceRepository(): \Ls\Replication\Api\Central\ReplSalesPriceRepositoryInterface
    {
        return \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Ls\Replication\Api\Central\ReplSalesPriceRepositoryInterface::class);
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
     * Getting item price of given product
     *
     * @param object $productData
     * @param array $replItemPriceList
     * @return object|null
     */
    public function getPrice($productData, $replItemPriceList)
    {
        $itemId = $productData->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $variantId = $productData->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE);
        $uom = $productData->getData(LSR::LS_UOM_ATTRIBUTE);
        if ($uom) {
            $attr = $productData->getResource()->getAttribute(LSR::LS_UOM_ATTRIBUTE);
            if ($attr->usesSource()) {
                $uom = $this->replicationHelper->getUomCodeGivenDescription($attr->getSource()->getOptionText($uom));
            }
        }
        $key = $itemId . '-' . $variantId . '-' . $uom;
        if (array_key_exists($key, $replItemPriceList)) {
            $price = $replItemPriceList[$key];
            // Validate the price before returning
            if ($this->isValidPrice($price)) {
                return $price;
            }
        }
        if ($uom) {
            $variantId = '';
            $baseUnitOfMeasure = $this->replicationHelper->getBaseUnitOfMeasure($itemId);
            if ($uom == $baseUnitOfMeasure) {
                $uom = '';
            }
            $key = $itemId . '-' . $variantId . '-' . $uom;
            if (array_key_exists($key, $replItemPriceList)) {
                $price = $replItemPriceList[$key];
                // Validate the price before returning
                if ($this->isValidPrice($price)) {
                    return $price;
                }
            }
        }
        return null;
    }

    /**
     * Get item price list price
     *
     * @param string $itemId
     * @return array
     */
    public function getItemPriceList(string $itemId): array
    {
        $replItemPriceListArray = [];
        $webStoreId = $this->lsr->getStoreConfig(
            LSR::SC_SERVICE_STORE,
            $this->store->getId()
        );
        $filters = [
            ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'StoreId', 'value' => $webStoreId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
            ['field' => 'Status', 'value' => '1', 'condition_type' => 'eq']
        ];
        $searchCriteria = $this->replicationHelper
            ->buildCriteriaForDirect($filters, -1)
            ->setSortOrders(
                [
                    $this->sortOrderBuilder->setField('ItemId')->setDirection('ASC')->create(),
                    $this->sortOrderBuilder->setField('VariantId')->setDirection('ASC')->create(),
                    $this->sortOrderBuilder->setField('UnitOfMeasure')->setDirection('ASC')->create()
                ]
            );
        try {
            $replItemPriceList = $this->replPriceRepository->getList($searchCriteria);
            $allCandidates = [];
            /** @var ReplPrice $replPrice */
            foreach ($replItemPriceList->getItems() as $replPrice) {
                // Validate price before adding to array
                if ($this->isValidPrice($replPrice)) {
                    $key = $replPrice->getItemId() . '-' . $replPrice->getVariantId() . '-' .
                        $replPrice->getUnitOfMeasure();
                    $allCandidates[$key][] = $replPrice;
                }
            }
            $salesPriceProcessor = $this->getSalesPriceProcessor();
            $storeCurrency = (string)$this->store->getBaseCurrencyCode();
            foreach ($allCandidates as $key => $candidates) {
                $winner = $salesPriceProcessor->selectBestPrice($candidates, $storeCurrency);
                if ($winner !== null) {
                    $replItemPriceListArray[$key] = $winner;
                }
            }
        } catch (Exception $e) {
            $this->logger->debug(
                sprintf(
                    'Exception happened in %s for store: %s, item id: %s',
                    __METHOD__,
                    $this->store->getName(),
                    $itemId
                )
            );
            $this->logger->debug($e->getMessage());
        }

        return $replItemPriceListArray;
    }

    /**
     * Get remaining records
     *
     * @param mixed $storeData
     * @return int
     * @throws LocalizedException
     */
    public function getRemainingRecords(
        $storeData = null
    )
    {
        if (!$this->remainingRecords) {
            /** Get list of only those prices whose items are already processed */
            $filters = [
                ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
                ['field' => 'main_table.Status', 'value' => '1', 'condition_type' => 'eq']
            ];

            $criteria = $this->replicationHelper->buildCriteriaForArrayWithAlias(
                $filters
            );
            $collection = $this->replPriceCollectionFactory->create();
            $this->replicationHelper->setCollectionPropertiesPlusJoinSku(
                $collection,
                $criteria,
                'ItemId',
                'VariantId',
                ['repl_price_id']
            );

            $websiteId = $this->store->getWebsiteId();
            $this->replicationHelper->applyProductWebsiteJoin($collection, $websiteId);
            $this->remainingRecords = $collection->getSize();
        }
        return $this->remainingRecords;
    }
}
