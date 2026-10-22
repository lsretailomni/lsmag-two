<?php
declare(strict_types=1);

namespace Ls\Omni\Model\VendorSourcing;

use Ls\Core\Model\LSR;
use Ls\Omni\Helper\StockHelper;
use Ls\Omni\Helper\VendorSourcingHelper;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;

/**
 * Checkout-time vendor-sourcing fallback (ticket 89360): given a Click & Collect stock
 * collection (§6.2 evaluate()) or a delivery/flatrate cart's visible items (§6.2
 * evaluateDelivery()), decides per item whether it can proceed as vendor-sourced or must
 * block checkout with a customer-facing message.
 */
class CheckoutFallbackService
{
    public const QUOTE_ITEM_VERDICT_KEY = 'ls_vendor_sourcing_fallback_verdict';

    public function __construct(
        private readonly VendorSourcingHelper $vendorSourcingHelper,
        private readonly StockHelper $stockHelper,
        private readonly ZeroStockEverywhereResolver $zeroStockResolver,
        private readonly LSR $lsr,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Evaluate a Click & Collect stock collection, with disambiguation between
     * "zero stock everywhere" (vendor-sourceable) and "zero stock at this store only"
     * (real stock exists elsewhere - block with a different message, solution plan §4).
     *
     * @param array $stockCollection rows shaped like StockHelper::mergeStockCollection()'s output
     * @param string $requiredType VendorSourcingHelper::TYPE_COLLECT - evaluate()'s only caller
     *                             (DataAssignObserver::storeInventoryCheck()) always passes
     *                             TYPE_COLLECT; the parameter is kept for clarity/future reuse
     * @param Item[] $quoteItems the quote's visible items (bug fix, post-merge review): once
     *                           ticket 89358 gives vendor-sourced products manage_stock=0, they
     *                           are silently excluded from $stockCollection altogether by
     *                           StockHelper::getGivenItemsStockInGivenStore()'s manage_stock gate -
     *                           never even a status=0 row - so DataAssignObserver::
     *                           storeInventoryCheck()'s hasZeroStockRow($stockCollection) check
     *                           never sees them and this method never runs for them at all.
     *                           Passing the quote's items here lets evaluate() independently
     *                           re-discover such items and evaluate them via the ungated stock
     *                           check, merging their verdicts into the same result. Optional/
     *                           empty by default so existing callers/tests that don't have a
     *                           quote handy (e.g. the GraphQL call path, which already omits the
     *                           $quote argument today) are unaffected.
     * @return CheckoutFallbackResult
     */
    public function evaluate(array $stockCollection, string $requiredType, array $quoteItems = []): CheckoutFallbackResult
    {
        $result = new CheckoutFallbackResult();

        $vendorSourcingEnabled = (bool)$this->lsr->getStoreConfig(
            LSR::SC_CART_VENDOR_SOURCING_OPTIONS,
            $this->lsr->getCurrentStoreId()
        );

        // Rows that have usable sourcing options and need the batched all-store disambiguation.
        $pendingDisambiguation = [];

        foreach ($stockCollection as $row) {
            if (!empty($row['status'])) {
                continue; // in stock - nothing to evaluate
            }

            if (!$vendorSourcingEnabled) {
                $verdict = $this->legacyBlockedVerdict($row);
                $this->storeOnQuoteItem($row, $verdict);
                $result->addVerdict($verdict);
                continue;
            }

            $options = $this->fetchOptionsForRow($row);
            $typeOptions = $this->filterByType($options, $requiredType);

            if (empty($typeOptions)) {
                $verdict = $this->blockedNotSourceableVerdict($row, $requiredType);
                $this->storeOnQuoteItem($row, $verdict);
                $result->addVerdict($verdict);
                continue;
            }

            // evaluate() is only ever called with TYPE_COLLECT (DataAssignObserver::
            // storeInventoryCheck()) - defer the verdict until the batched all-store
            // disambiguation runs.
            $pendingDisambiguation[] = [$row, $typeOptions];
        }

        if (!empty($pendingDisambiguation)) {
            $this->resolveDisambiguation($pendingDisambiguation, $result);
        }

        if ($vendorSourcingEnabled && !empty($quoteItems)) {
            $this->evaluateItemsExcludedFromStockCollection($quoteItems, $stockCollection, $requiredType, $result);
        }

        return $result;
    }

    /**
     * Independently re-discover vendor-sourcing-eligible quote items that are NOT represented at
     * all in $stockCollection (bug fix, post-merge review, "C&C trigger gap") - this happens once
     * manage_stock=0 (ticket 89358) excludes such items from StockHelper::
     * getGivenItemsStockInGivenStore()'s per-item gate entirely. Each such item is run through the
     * ungated stock check ({@see StockHelper::getGivenItemsStockIgnoringManageStock()}), the
     * relevant-type options lookup, and the same verdict-building helpers the main loop above
     * uses, merging into the same $result. Items already present in $stockCollection are skipped
     * here (deduped by item_id|variant_id) to avoid double-processing in the rare case
     * manage_stock isn't actually 0 for some eligible item.
     *
     * @param Item[] $quoteItems
     * @param array $stockCollection
     * @param string $requiredType
     * @param CheckoutFallbackResult $result
     */
    private function evaluateItemsExcludedFromStockCollection(
        array $quoteItems,
        array $stockCollection,
        string $requiredType,
        CheckoutFallbackResult $result
    ): void {
        $eligibleItems = $this->getVendorSourcingEligibleItems($quoteItems);

        if (empty($eligibleItems)) {
            return;
        }

        $presentKeys = [];
        foreach ($stockCollection as $row) {
            $presentKeys[$row['item_id'] . '|' . $row['variant_id']] = true;
        }

        // manage_stock=0 (ticket 89358) is itself the eligibility signal for these items - no
        // GetInventoryMultipleV2 call is made for them at all, unlike rows already present in
        // $stockCollection (which came from the existing per-store managed-stock check). Go
        // straight from isVendorSourced()=true to the sourcing lookup.
        foreach ($eligibleItems as $eligible) {
            $row = $this->buildRowForEligibleItem($eligible['item']);
            $key = $row['item_id'] . '|' . $row['variant_id'];

            if (isset($presentKeys[$key])) {
                continue; // already processed via the main per-store loop above
            }

            $options = $this->fetchOptionsForRow($row);
            $typeOptions = $this->filterByType($options, $requiredType);

            $verdict = empty($typeOptions)
                ? $this->blockedNotSourceableVerdict($row, $requiredType)
                : $this->sourcedVerdict($row, $typeOptions, $requiredType);

            $this->storeOnQuoteItem($row, $verdict);
            $result->addVerdict($verdict);
        }
    }

    /**
     * Evaluate a delivery/flatrate cart for vendor-sourcing fallback (FR7/FR9). Structurally
     * scoped to vendor-sourcing-flagged items only - an ordinary item never reaches the stock
     * call, the loop, or a verdict (solution plan §4/§15 R8).
     *
     * @param Item[] $quoteItems
     * @return CheckoutFallbackResult
     */
    public function evaluateDelivery(array $quoteItems): CheckoutFallbackResult
    {
        $result = new CheckoutFallbackResult();

        $eligibleItems = $this->getVendorSourcingEligibleItems($quoteItems);

        if (empty($eligibleItems)) {
            return $result; // no-op - zero API calls, ordinary items untouched
        }

        // manage_stock=0 (ticket 89358) is itself the eligibility signal for these items - no
        // GetInventoryMultipleV2 call is made at all, for classic or GraphQL checkout. Go straight
        // from isVendorSourced()=true to the sourcing lookup.
        foreach ($eligibleItems as $eligible) {
            $row = $this->buildRowForEligibleItem($eligible['item']);

            $options = $this->fetchOptionsForRow($row);
            $shipOptions = $this->filterByType($options, VendorSourcingHelper::TYPE_SHIP);

            $verdict = empty($shipOptions)
                ? $this->blockedNotSourceableVerdict($row, VendorSourcingHelper::TYPE_SHIP)
                : $this->sourcedVerdict($row, $shipOptions, VendorSourcingHelper::TYPE_SHIP);

            $this->storeOnQuoteItem($row, $verdict);
            $result->addVerdict($verdict);
        }

        return $result;
    }

    /**
     * Build a stock-collection-row-shaped array for an item we deliberately do NOT perform an
     * inventory check for (manage_stock=0 items need no GetInventoryMultipleV2 call at all -
     * ticket 89360 follow-up fix). item_id/variant_id come from a local repository lookup
     * (ItemHelper::getComparisonValues() - a DB query, not a Central API call), matching the
     * same identity space an inventory-checked row would have carried.
     *
     * @param Item $item
     * @return array
     */
    private function buildRowForEligibleItem(Item $item): array
    {
        $comparisonValues = $this->stockHelper->itemHelper->getComparisonValues($item->getSku());
        [$itemId, $variantId] = $comparisonValues ?: [null, null];

        return [
            'item_id' => $itemId,
            'variant_id' => $variantId,
            'name' => $item->getName(),
            'quote_item' => $item,
        ];
    }

    /**
     * Batch the deferred C&C rows' quote items into one all-store stock call and disambiguate
     * "zero stock everywhere" (sourced) from "stock exists at another store" (blocked).
     *
     * @param array $pendingDisambiguation array of [row, typeOptions] pairs
     * @param CheckoutFallbackResult $result
     */
    private function resolveDisambiguation(array $pendingDisambiguation, CheckoutFallbackResult $result): void
    {
        $items = [];

        foreach ($pendingDisambiguation as [$row, $typeOptions]) {
            if (!empty($row['quote_item'])) {
                $items[] = $row['quote_item'];
            }
        }

        try {
            [$allStoreResponse] = $this->stockHelper->getGivenItemsStockIgnoringManageStock($items);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Vendor sourcing checkout fallback disambiguation stock lookup failed: ' . $e->getMessage()
            );

            // Fail toward the already-confirmed-eligible vendor-sourcing path: Central has already
            // confirmed this item has valid sourcing options for $requiredType - this call is only
            // the "is it ALSO available at another store" check, so a failure here must not block
            // an otherwise-good verdict. ZeroStockEverywhereResolver::resolve() treats a null
            // response as "not covered by any store" for every row, which is exactly this outcome.
            $allStoreResponse = null;
        }

        $rows = array_map(static fn (array $pending): array => $pending[0], $pendingDisambiguation);
        $stockMap = $this->zeroStockResolver->resolve($allStoreResponse, $rows);

        foreach ($pendingDisambiguation as [$row, $typeOptions]) {
            $key = $row['item_id'] . '|' . $row['variant_id'];

            $verdict = ($stockMap[$key] ?? false)
                ? $this->wrongStoreBlockedVerdict($row)
                : $this->sourcedVerdict($row, $typeOptions, VendorSourcingHelper::TYPE_COLLECT);

            $this->storeOnQuoteItem($row, $verdict);
            $result->addVerdict($verdict);
        }
    }

    /**
     * Build a map of LS Central item-identity ("item_id|variant_id") to vendor-sourcing info, for
     * every quote item (bundle children expanded) carrying a STATUS_VENDOR_SOURCED verdict - i.e.
     * one previously produced by a evaluate()/evaluateDelivery() run against these same quote item
     * objects (verdicts are stored via setData(), in-memory only, by storeOnQuoteItem()).
     *
     * Moved here from DataAssignObserver::buildVendorSourcedLinesMap() (ticket 89360 post-merge
     * review, Bug B): OrderHelper::prepareOrder() also needs to build this exact map, off a
     * freshly-loaded quote's items it has just re-evaluated itself - a fresh evaluate()/
     * evaluateDelivery() call is required there because neither the transient Order-level
     * 'ls_vendor_sourced_lines' data NOR the quote items' QUOTE_ITEM_VERDICT_KEY setData() survive
     * a reloaded Order/freshly-loaded Quote across the separate HTTP request some payment methods
     * place order submission on. Giving this method a single home on this service lets both
     * call sites build the map identically, directly off whichever quote item objects they just
     * ran through evaluate()/evaluateDelivery().
     *
     * @param Item[] $quoteItems
     * @return array<string, array{vendor_sourcing: bool, sourcing_location: ?string}>
     */
    public function buildVendorSourcedLinesMap(array $quoteItems): array
    {
        $map = [];

        foreach ($quoteItems as $item) {
            $children = $item->getProductType() == Type::TYPE_BUNDLE
                ? $item->getChildren() : [$item];

            foreach ($children as $child) {
                /** @var ItemSourcingVerdict|null $verdict */
                $verdict = $child->getData(self::QUOTE_ITEM_VERDICT_KEY);

                if (!$verdict instanceof ItemSourcingVerdict
                    || $verdict->getStatus() !== ItemSourcingVerdict::STATUS_VENDOR_SOURCED
                ) {
                    continue;
                }

                $key = $verdict->getItemId() . '|' . $verdict->getVariantId();
                $map[$key] = [
                    'vendor_sourcing' => true,
                    'sourcing_location' => $verdict->getSuggestedStore()['store_code'] ?? null,
                    'lead_time_calculation' => $verdict->getSuggestedStore()['lead_time_calculation'] ?? null,
                ];
            }
        }

        return $map;
    }

    /**
     * @param Item[] $quoteItems
     * @return array{item: Item}[] only the items (bundle children expanded) that are
     *         isVendorSourced()-flagged. Ordinary items are excluded here, structurally,
     *         not merely deprioritized.
     */
    private function getVendorSourcingEligibleItems(array $quoteItems): array
    {
        $eligible = [];

        foreach ($quoteItems as $item) {
            $children = $item->getProductType() == Type::TYPE_BUNDLE
                ? $item->getChildren() : [$item];

            foreach ($children as $child) {
                [$parent, $simple] = $this->resolveParentAndChildProducts($child);

                if ($this->vendorSourcingHelper->isVendorSourced($parent, $simple)) {
                    $eligible[] = ['item' => $child];
                }
            }
        }

        return $eligible;
    }

    /**
     * Resolve the (parent product, chosen simple product) pair off a quote item
     *
     * @param Item $item
     * @return array{0: \Magento\Catalog\Model\Product, 1: ?\Magento\Catalog\Model\Product}
     */
    private function resolveParentAndChildProducts(Item $item): array
    {
        $parent = $item->getProduct();
        $child = null;

        if ($item->getOptionByCode('simple_product')) {
            $child = $item->getOptionByCode('simple_product')->getProduct();
        }

        return [$parent, $child];
    }

    /**
     * Fetch the sourcing options for a stock collection row, fail-closed to [] on error
     *
     * @param array $row
     * @param \Magento\Catalog\Model\Product|null $parent
     * @param \Magento\Catalog\Model\Product|null $child
     * @return array
     */
    private function fetchOptionsForRow(array $row, $parent = null, $child = null): array
    {
        $item = $row['quote_item'] ?? null;

        if ($parent === null && $item) {
            [$parent, $child] = $this->resolveParentAndChildProducts($item);
        }

        if (!$parent) {
            return [];
        }

        try {
            return $this->vendorSourcingHelper->getOptions(
                $parent->getSku(),
                $child ? (int)$child->getId() : null
            );
        } catch (\Throwable $e) {
            $this->logger->error('Vendor sourcing checkout fallback failed: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * @param array $options
     * @param string $type
     * @return array
     */
    private function filterByType(array $options, string $type): array
    {
        return array_values(array_filter(
            $options,
            static fn (array $o): bool => $o['type'] === $type
        ));
    }

    /**
     * Resolve the sku/name/quote item id/LS Central item id/variant id of a stock collection row,
     * used by every verdict builder. The LS Central item_id/variant_id (solution plan #89364) let
     * a vendor-sourced verdict later be correlated back to the CustomerOrderCreateCOLineV6 line
     * actually sent to LS Central.
     *
     * @param array $row
     * @return array{0: ?int, 1: string, 2: string, 3: ?string, 4: ?string}
     */
    private function rowIdentity(array $row): array
    {
        $item = $row['quote_item'] ?? null;
        $sku = $item ? (string)$item->getSku() : (string)($row['item_id'] ?? '');
        $name = $row['name'] ?? $sku;
        $itemId = isset($row['item_id']) ? (string)$row['item_id'] : null;
        $variantId = isset($row['variant_id']) ? (string)$row['variant_id'] : null;

        return [$item ? (int)$item->getId() : null, $sku, (string)$name, $itemId, $variantId];
    }

    /**
     * Legacy blocked verdict - vendor sourcing disabled at store config level
     *
     * @param array $row
     * @return ItemSourcingVerdict
     */
    private function legacyBlockedVerdict(array $row): ItemSourcingVerdict
    {
        return $this->genericBlockedVerdict($row, __(
            'Unable to use selected shipping method since some or all of the cart items are not available '
            . 'in selected store.'
        ));
    }

    /**
     * Blocked verdict carrying a caller-supplied message - used by call sites that need a
     * context-specific message (e.g. delivery-neutral wording, or a stock-lookup-failure message)
     * rather than one of the other, more specific verdict builders below.
     *
     * @param array $row
     * @param Phrase $message
     * @return ItemSourcingVerdict
     */
    private function genericBlockedVerdict(array $row, Phrase $message): ItemSourcingVerdict
    {
        [$quoteItemId, $sku, , $itemId, $variantId] = $this->rowIdentity($row);

        return new ItemSourcingVerdict(
            $quoteItemId,
            $sku,
            ItemSourcingVerdict::STATUS_BLOCKED,
            [],
            $message,
            null,
            $itemId,
            $variantId
        );
    }

    /**
     * Blocked verdict - zero stock and no usable sourcing options for the relevant fulfillment type
     *
     * @param array $row
     * @param string $requiredType
     * @return ItemSourcingVerdict
     */
    private function blockedNotSourceableVerdict(array $row, string $requiredType): ItemSourcingVerdict
    {
        [$quoteItemId, $sku, $name, $itemId, $variantId] = $this->rowIdentity($row);

        $message = $requiredType === VendorSourcingHelper::TYPE_SHIP
            ? __('%1 (SKU: %2) is not available and cannot be sourced from a vendor for delivery.', $name, $sku)
            : __(
                '%1 (SKU: %2) is not available at the selected store and cannot be sourced from a vendor.',
                $name,
                $sku
            );

        return new ItemSourcingVerdict(
            $quoteItemId,
            $sku,
            ItemSourcingVerdict::STATUS_BLOCKED,
            [],
            $message,
            null,
            $itemId,
            $variantId
        );
    }

    /**
     * Blocked verdict - C&C disambiguation found real stock at another store (solution plan §4/§9)
     *
     * @param array $row
     * @return ItemSourcingVerdict
     */
    private function wrongStoreBlockedVerdict(array $row): ItemSourcingVerdict
    {
        [$quoteItemId, $sku, $name, $itemId, $variantId] = $this->rowIdentity($row);

        $message = __(
            '%1 (SKU: %2) is not available at the selected store, but is available at another store. '
            . 'Please choose a different store.',
            $name,
            $sku
        );

        return new ItemSourcingVerdict(
            $quoteItemId,
            $sku,
            ItemSourcingVerdict::STATUS_BLOCKED,
            [],
            $message,
            null,
            $itemId,
            $variantId
        );
    }

    /**
     * Vendor-sourced verdict - carries the relevant-type options and the highest-priority matched
     * row (both fulfillment types now, not just delivery/ship - also used to carry StoreNo
     * consistency and LeadTimeCalculation through to the order line, not just FR9's suggested
     * store for delivery).
     *
     * @param array $row
     * @param array $typeOptions
     * @param string $requiredType
     * @return ItemSourcingVerdict
     */
    private function sourcedVerdict(array $row, array $typeOptions, string $requiredType): ItemSourcingVerdict
    {
        [$quoteItemId, $sku, , $itemId, $variantId] = $this->rowIdentity($row);

        $resolvedOption = $typeOptions[0] ?? null;

        return new ItemSourcingVerdict(
            $quoteItemId,
            $sku,
            ItemSourcingVerdict::STATUS_VENDOR_SOURCED,
            $typeOptions,
            null,
            $resolvedOption,
            $itemId,
            $variantId
        );
    }

    /**
     * Store the verdict on the quote item (in-memory, single-request convention, no new
     * persisted/extension-attribute state - solution plan §1)
     *
     * @param array $row
     * @param ItemSourcingVerdict $verdict
     */
    private function storeOnQuoteItem(array $row, ItemSourcingVerdict $verdict): void
    {
        $item = $row['quote_item'] ?? null;

        if ($item) {
            $item->setData(self::QUOTE_ITEM_VERDICT_KEY, $verdict);
        }
    }
}
