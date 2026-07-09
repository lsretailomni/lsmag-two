<?php

declare(strict_types=1);

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplPrice;
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
     * Cached store price group codes, reset per store in execute().
     * @var array|null
     */
    private ?array $storePriceGroups = null;

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
                    $this->storePriceGroups = null;

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

                // Normalize UOM: if the price record's UOM equals the item's base UOM,
                // the Magento product is stored without a UOM attribute (blank), so use blank.
                $uom = $replPrice->getUnitOfMeasure();
                if ($uom) {
                    $baseUom = $this->replicationHelper->getBaseUnitOfMeasure($replPrice->getItemId());
                    if ($uom === $baseUom) {
                        $uom = '';
                    }
                }
                $productDataArray = $this->replicationHelper->getProductDataByIdentificationAttributes(
                    $replPrice->getItemId(),
                    $replPrice->getVariantId(),
                    $uom,
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
        $invalidDateMin = '0001-01-01';

        // If starting date is empty or invalid, it's not a future price
        $isStartingDateInvalid = empty($startingDate) ||
            strpos($startingDate, $invalidDate) === 0 ||
            strpos($startingDate, $invalidDateAlt) === 0 ||
            strpos($startingDate, $invalidDateMin) === 0;

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
        $invalidDateMin = '0001-01-01';

        $isStartingDateInvalid = empty($startingDate) ||
            strpos($startingDate, $invalidDate) === 0 ||
            strpos($startingDate, $invalidDateAlt) === 0 ||
            strpos($startingDate, $invalidDateMin) === 0;

        $isEndingDateInvalid = empty($endingDate) ||
            strpos($endingDate, $invalidDate) === 0 ||
            strpos($endingDate, $invalidDateAlt) === 0 ||
            strpos($endingDate, $invalidDateMin) === 0;

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
                // Use end-of-day so timezone shifts (e.g. UTC→UTC-X) don't move the date backward.
                $endDateTime = $this->replicationHelper->convertDateTimeIntoCurrentTimeZone(
                    substr($endingDate, 0, 10) . ' 23:59:59',
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
                // Use end-of-day so timezone shifts don't move the EndingDate backward.
                $endDateTime = $this->replicationHelper->convertDateTimeIntoCurrentTimeZone(
                    substr($endingDate, 0, 10) . ' 23:59:59',
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
        $replItemPriceList = $this->getItemPriceList($replPrice->getItemId());
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
            // Keep valid non-winner prices unprocessed so they can become the active
            // price once the current winner expires (e.g. blank-dates fallback price).
            $this->resetNonWinnerPrices($price);
        }
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
     * Load the store's configured price group codes from repl_store (AC9).
     * Result is cached per store — reset $this->storePriceGroups in execute() per store.
     *
     * @return array
     */
    private function getStorePriceGroups(): array
    {
        if ($this->storePriceGroups !== null) {
            return $this->storePriceGroups;
        }
        $webStoreId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE, $this->store->getId());
        $websiteId  = $this->store->getWebsiteId();
        /** @var \Ls\Replication\Model\ResourceModel\ReplStore\Collection $storeCollection */
        $storeCollection = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory::class)
            ->create();
        $storeCollection->addFieldToFilter('scope_id', $websiteId)
            ->addFieldToFilter('nav_id', $webStoreId);
        $this->storePriceGroups = [];
        foreach ($storeCollection->getItems() as $storeData) {
            if ($storeData->getPriceGroupCodes()) {
                $this->storePriceGroups = array_values(
                    array_filter(explode(';', $storeData->getPriceGroupCodes()))
                );
                break;
            }
        }
        return $this->storePriceGroups;
    }

    /**
     * When a dated price wins, reset to processed=0 any lines that share the same
     * PriceListCode and have blank/sentinel dates (open-ended fallback prices).
     * This ensures the fallback price re-enters the cron queue and is automatically
     * applied once the dated winner expires.
     *
     * Only runs when the winner itself has actual dates (not open-ended), so that
     * an open-ended winner (base price restored after expiry) does not trigger resets.
     *
     * @param object $winner
     * @return void
     */
    private function resetNonWinnerPrices(object $winner): void
    {
        // Only reset when a dated price wins, not when the open-ended base price is restored.
        if ($this->isOpenEndedPrice($winner)) {
            return;
        }

        $webStoreId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE, $this->store->getId());
        $variantId  = $winner->getVariantId();
        $filters = [
            ['field' => 'ItemId',        'value' => (string)$winner->getItemId(),                'condition_type' => 'eq'],
            ['field' => 'PriceListCode', 'value' => (string)($winner->getPriceListCode() ?? ''), 'condition_type' => 'eq'],
            ['field' => 'StoreId',       'value' => $webStoreId,                                 'condition_type' => 'eq'],
            ['field' => 'scope_id',      'value' => $this->getScopeId(),                         'condition_type' => 'eq'],
            ['field' => 'Status',        'value' => '1',                                         'condition_type' => 'eq'],
            ['field' => 'repl_price_id', 'value' => $winner->getId(),                            'condition_type' => 'neq'],
        ];
        // VariantId is stored as NULL in DB for non-variant items; NULL != '' in SQL.
        if ($variantId !== null && $variantId !== '') {
            $filters[] = ['field' => 'VariantId', 'value' => $variantId, 'condition_type' => 'eq'];
        } else {
            $filters[] = ['field' => 'VariantId', 'value' => true, 'condition_type' => 'null'];
        }
        $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        try {
            $nonWinners = $this->replPriceRepository->getList($searchCriteria);
            foreach ($nonWinners->getItems() as $nonWinner) {
                // Only reset open-ended (blank / sentinel date) lines — these are
                // the fallback prices that must re-activate when the winner expires.
                if (!$this->isOpenEndedPrice($nonWinner)) {
                    continue;
                }
                $nonWinner->setData('processed', 0);
                $nonWinner->setData('is_updated', 1);
                $this->replPriceRepository->save($nonWinner);
            }
        } catch (\Exception $e) {
            $this->logger->debug(
                sprintf(
                    'Error resetting non-winner prices for item: %s, variant: %s - Error: %s',
                    $winner->getItemId(),
                    $winner->getVariantId() ?? '',
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Returns true when both StartingDate and EndingDate are blank or an LS Central
     * sentinel (0001-01-01 / 1900-01-01), meaning the price has no date restriction.
     *
     * @param object $replPrice
     * @return bool
     */
    private function isOpenEndedPrice(object $replPrice): bool
    {
        $invalidPrefixes = ['0001-01-01', '1900-01-01'];
        foreach (['getStartingDate', 'getEndingDate'] as $getter) {
            $date = (string)($replPrice->$getter() ?? '');
            $isInvalid = $date === '';
            foreach ($invalidPrefixes as $prefix) {
                if (strpos($date, $prefix) === 0) {
                    $isInvalid = true;
                    break;
                }
            }
            if (!$isInvalid) {
                return false;
            }
        }
        return true;
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
     * getItemPriceList() runs the waterfall independently per UOM pool (keyed as
     * itemId-variantId-uom).  Here we run a final cross-UOM waterfall so that a
     * date-specific PCS price beats an open-ended blank-UOM price even when the
     * Magento product has no UOM attribute set.
     *
     * @param object $productData
     * @param array $replItemPriceList
     * @return object|null
     */
    public function getPrice($productData, $replItemPriceList)
    {
        $itemId    = $productData->getData(LSR::LS_ITEM_ID_ATTRIBUTE_CODE);
        $variantId = (string)($productData->getData(LSR::LS_VARIANT_ID_ATTRIBUTE_CODE) ?? '');

        // Collect all per-UOM winners for this item+variant (any UOM suffix).
        $prefix     = $itemId . '-' . $variantId . '-';
        $candidates = [];
        foreach ($replItemPriceList as $key => $price) {
            if (strpos($key, $prefix) === 0) {
                $candidates[] = $price;
            }
        }

        // Fallback: if no variant-specific price found, look for no-variant prices.
        if (empty($candidates) && $variantId !== '') {
            $prefix = $itemId . '--';
            foreach ($replItemPriceList as $key => $price) {
                if (strpos($key, $prefix) === 0) {
                    $candidates[] = $price;
                }
            }
        }

        if (empty($candidates)) {
            return null;
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        // UOM specificity: a price record with an explicit UOM is more specific
        // than a blank-UOM (catch-all) record. Prefer UOM-specific candidates;
        // fall back to blank-UOM candidates only when no UOM-specific ones exist.
        $uomSpecific = array_values(array_filter(
            $candidates,
            fn($c) => (string)($c->getUnitOfMeasure() ?? '') !== ''
        ));
        if (!empty($uomSpecific)) {
            $candidates = $uomSpecific;
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        return $this->getSalesPriceProcessor()->selectBestPrice(
            $candidates,
            (string)$this->store->getBaseCurrencyCode()
        );
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
        $priceGroups = $this->getStorePriceGroups();
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
                    // AC9: when the store has price groups configured, only include prices
                    // whose SaleCode (customer price group) matches one of those groups.
                    // A blank SaleCode is treated as a universal price and always included.
                    if (!empty($priceGroups)) {
                        $saleCode = (string)($replPrice->getSaleCode() ?? '');
                        if ($saleCode !== '' && !in_array($saleCode, $priceGroups, true)) {
                            continue;
                        }
                    }
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
