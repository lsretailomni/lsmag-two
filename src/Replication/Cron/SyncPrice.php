<?php

namespace Ls\Replication\Cron;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Replication\Model\ReplPrice;
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
     *
     * @var array|null
     */
    private $storePriceGroups = null;

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
                        ['field' => 'main_table.Status', 'value' => 'Active', 'condition_type' => 'eq']
                    ];

                    $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
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

                // AC9: skip prices excluded by the store's configured price groups.
                // Mark as processed (do not apply, do not churn); the allowed price for
                // this item is applied when its own record is processed.
                if (!$this->isSaleCodeAllowedForStore($replPrice)) {
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
        if ($replPrice->getStatus() !== 'Active') {
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
        if ($replPrice->getStatus() !== 'Active') {
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
                // Use end-of-day so timezone shifts (e.g. UTC->UTC-X) don't move the date backward.
                $endDateTime = $this->replicationHelper->convertDateTimeIntoCurrentTimeZone(
                    substr($endingDate, 0, 10) . ' 23:59:59',
                    $format
                );
                if ($currentDate > $endDateTime) {
                    return false;
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
        // Always resolve the winning price for the item so that, on every run, the
        // currently-active price is re-selected among all valid candidates (dated
        // promo vs open-ended base). This is what allows the re-queued fallback price
        // to take over automatically once a dated winner expires (see resetNonWinnerPrices).
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
                    'is_updated'   => 0,
                    'processed'    => 1,
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
     * When a dated price wins, reset to processed=0 any non-winner line for the same
     * ItemId that is itself still within a valid date window — whether that window is
     * open-ended (blank/sentinel dates) or dated-but-currently-active.
     *
     * Matching is intentionally scoped to ItemId only (not VariantId/PriceListCode):
     * any currently-valid sibling price for the same item — whether item-level, a
     * different variant's price, or a different price list — is a reset candidate,
     * because getPrice()'s own waterfall already spans variant and item-level
     * candidates and must get the chance to re-run once this winner expires so it can
     * pick the correct fallback.
     *
     * This ensures every still-valid fallback price re-enters the cron queue and is
     * automatically reconsidered once the dated winner expires. Expired or future
     * non-winners are left alone (expired ones need no further action; future ones are
     * never marked processed in the first place — see process()/isFuturePrice()).
     *
     * Only runs when the winner itself has actual dates (not open-ended), so that
     * an open-ended winner (base price restored after expiry) does not trigger resets.
     *
     * @param object $winner
     * @return void
     */
    private function resetNonWinnerPrices($winner)
    {
        // Only reset when a dated price wins, not when the open-ended base price is restored.
        if ($this->isOpenEndedPrice($winner)) {
            return;
        }

        $webStoreId = $this->lsr->getStoreConfig(LSR::SC_SERVICE_STORE, $this->store->getId());
        $filters = [
            ['field' => 'ItemId',        'value' => (string)$winner->getItemId(), 'condition_type' => 'eq'],
            ['field' => 'StoreId',       'value' => $webStoreId,                  'condition_type' => 'eq'],
            ['field' => 'scope_id',      'value' => $this->getScopeId(),          'condition_type' => 'eq'],
            ['field' => 'Status',        'value' => 'Active',                     'condition_type' => 'eq'],
            ['field' => 'repl_price_id', 'value' => $winner->getId(),             'condition_type' => 'neq'],
        ];
        $searchCriteria = $this->replicationHelper->buildCriteriaForDirect($filters, -1);
        try {
            $nonWinners = $this->replPriceRepository->getList($searchCriteria);
            foreach ($nonWinners->getItems() as $nonWinner) {
                // Never re-queue a price excluded by the store's price groups (AC9).
                if (!$this->isSaleCodeAllowedForStore($nonWinner)) {
                    continue;
                }
                // Only reset non-winners that are themselves still within a valid date window
                // (open-ended, or dated-but-currently-active) — these are the fallback prices
                // that must re-activate once the current winner expires. Expired/future prices
                // are left alone (expired ones need no further action; future ones are never
                // marked processed in the first place, see process()/isFuturePrice()).
                if (!$this->isValidPrice($nonWinner)) {
                    continue;
                }
                // Idempotency short-circuit: if this sibling is already in the correct
                // pending-reevaluation state (processed=0, is_updated=1) from a previous
                // cron pass, skip the save() entirely. This avoids a redundant no-op DB
                // write on every cron pass for a sibling that already needs no change —
                // it does not change WHICH rows get requeued, only avoids re-saving a row
                // that is unchanged since the last reset. Values may come back as string
                // '0'/'1' from the repository, so cast before comparing.
                if ((int)$nonWinner->getData('processed') === 0
                    && (int)$nonWinner->getData('is_updated') === 1) {
                    continue;
                }
                $nonWinner->setData('processed', 0);
                $nonWinner->setData('is_updated', 1);
                $this->replPriceRepository->save($nonWinner);
            }
        } catch (Exception $e) {
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
    private function isOpenEndedPrice($replPrice)
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
     * Load the store's configured price group codes from repl_store (AC9).
     * Result is cached per store — $this->storePriceGroups is reset in execute() per store.
     *
     * ObjectManager is used to resolve the ReplStore collection factory because
     * SyncPrice inherits ProductCreateTask's large constructor; adding a constructor
     * here would require re-declaring every parent argument.
     *
     * @return array
     */
    private function getStorePriceGroups()
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
     * Whether a price's SaleCode is allowed by the store's configured price groups (AC9).
     *
     * When no price groups are configured, all prices are allowed (no check performed).
     * A blank SaleCode is treated as a universal price and is always allowed.
     *
     * @param object $replPrice
     * @return bool
     */
    private function isSaleCodeAllowedForStore($replPrice)
    {
        $priceGroups = $this->getStorePriceGroups();
        if (empty($priceGroups)) {
            return true;
        }
        $saleCode = (string)($replPrice->getSaleCode() ?? '');
        if ($saleCode === '') {
            return true;
        }
        return in_array($saleCode, $priceGroups, true);
    }

    /**
     * Select the best price from multiple valid candidates for the same item/variant/UOM key.
     *
     * Priority: currency-specific (matching store base currency) > blank currency,
     * then nearest (most recent) StartingDate, then PriceListCode desc, then LineNumber desc.
     * Never selects by lowest price.
     *
     * @param object[] $candidates
     * @param string $storeCurrency the store's base currency code; blank disables currency matching
     * @return object|null
     */
    private function selectBestPrice(array $candidates, $storeCurrency = '')
    {
        if (empty($candidates)) {
            return null;
        }

        // Currency matching against the store's base currency: prefer matching-currency
        // lines, fall back to blank-currency lines; exclude non-matching specific currencies.
        if ($storeCurrency !== '') {
            $matchingCurrency = array_values(array_filter(
                $candidates,
                fn($c) => (string)($c->getCurrencyCode() ?? '') === $storeCurrency
            ));
            if (!empty($matchingCurrency)) {
                $pool = $matchingCurrency;
            } else {
                $pool = array_values(array_filter(
                    $candidates,
                    fn($c) => (string)($c->getCurrencyCode() ?? '') === ''
                ));
            }
        } else {
            // No store currency provided — any specific currency beats blank.
            $currencySpecific = array_values(array_filter(
                $candidates,
                fn($c) => (string)($c->getCurrencyCode() ?? '') !== ''
            ));
            $pool = !empty($currencySpecific) ? $currencySpecific : array_values($candidates);
        }

        if (empty($pool)) {
            return null;
        }
        if (count($pool) === 1) {
            return $pool[0];
        }

        // Sort descending by starting date (nearest = most recent = highest timestamp),
        // then descending by PriceListCode, then descending by LineNumber.
        usort($pool, function ($a, $b) {
            $tsA = !empty($a->getStartingDate()) ? (strtotime($a->getStartingDate()) ?: 0) : 0;
            $tsB = !empty($b->getStartingDate()) ? (strtotime($b->getStartingDate()) ?: 0) : 0;
            if ($tsA !== $tsB) {
                return $tsB <=> $tsA;
            }
            $codeCompare = strcmp((string)($b->getPriceListCode() ?? ''), (string)($a->getPriceListCode() ?? ''));
            if ($codeCompare !== 0) {
                return $codeCompare;
            }
            return ((int)($b->getLineNumber() ?? 0)) - ((int)($a->getLineNumber() ?? 0));
        });

        return $pool[0];
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
     * itemId-variantId-uom). Here we run a final cross-UOM waterfall so that a
     * date-specific price beats an open-ended blank-UOM price for the same UOM.
     *
     * Candidates are first filtered to the product's own UOM: a candidate is kept only when its
     * UnitOfMeasure is blank (the universal catch-all) or exactly equals the product's resolved
     * UOM code. A product whose UOM attribute is unset resolves to an empty code, which therefore
     * keeps ONLY the blank-UOM catch-all candidates and excludes every UOM-specific one. Among the
     * survivors, a UOM-specific price is preferred over the blank catch-all, and selectBestPrice()
     * breaks any remaining ties.
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

        // Resolve this product's own UOM code so cross-UOM price rows (e.g. a PACK-only
        // price) are never considered for a different-UOM product (e.g. PCS). A blank
        // UnitOfMeasure candidate is always kept — it's the universal catch-all. This filter
        // runs UNCONDITIONALLY before any early return: a lone candidate that is UOM-specific
        // for a different UOM than the product must still be dropped.
        $productUomLabel = $productData->getAttributeText(LSR::LS_UOM_ATTRIBUTE);
        $productUomCode  = $productUomLabel
            ? $this->replicationHelper->getUomCodeGivenDescription(
                (string)$productUomLabel,
                $this->getScopeId()
            )
            : '';
        $candidates = array_values(array_filter(
            $candidates,
            function ($c) use ($productUomCode) {
                $candidateUom = (string)($c->getUnitOfMeasure() ?? '');
                return $candidateUom === '' || $candidateUom === $productUomCode;
            }
        ));

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

        return $this->selectBestPrice(
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
    public function getItemPriceList($itemId)
    {
        $replItemPriceListArray = [];
        $webStoreId             = $this->lsr->getStoreConfig(
            LSR::SC_SERVICE_STORE,
            $this->store->getId()
        );
        $filters                = [
            ['field' => 'ItemId', 'value' => $itemId, 'condition_type' => 'eq'],
            ['field' => 'StoreId', 'value' => $webStoreId, 'condition_type' => 'eq'],
            ['field' => 'scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
            ['field' => 'Status', 'value' => 'Active', 'condition_type' => 'eq']
        ];
        $searchCriteria         = $this->replicationHelper
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
            $allCandidates     = [];
            /** @var ReplPrice $replPrice */
            foreach ($replItemPriceList->getItems() as $replPrice) {
                // Validate price before adding to array
                if ($this->isValidPrice($replPrice)) {
                    // AC9: when the store has price groups configured, only include prices
                    // whose SaleCode matches one of those groups (blank SaleCode = universal).
                    if (!$this->isSaleCodeAllowedForStore($replPrice)) {
                        continue;
                    }
                    $key                   = $replPrice->getItemId() . '-' . $replPrice->getVariantId() . '-' .
                        $replPrice->getUnitOfMeasure();
                    $allCandidates[$key][] = $replPrice;
                }
            }
            $storeCurrency = (string)$this->store->getBaseCurrencyCode();
            foreach ($allCandidates as $key => $candidates) {
                $winner = $this->selectBestPrice($candidates, $storeCurrency);
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
    ) {
        if (!$this->remainingRecords) {
            /** Get list of only those prices whose items are already processed */
            $filters = [
                ['field' => 'main_table.scope_id', 'value' => $this->getScopeId(), 'condition_type' => 'eq'],
                ['field' => 'main_table.Status', 'value' => 'Active', 'condition_type' => 'eq']
            ];

            $criteria   = $this->replicationHelper->buildCriteriaForArrayWithAlias(
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
