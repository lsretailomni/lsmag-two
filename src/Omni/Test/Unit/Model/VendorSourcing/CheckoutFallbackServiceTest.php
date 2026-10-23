<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Model\VendorSourcing;

use Ls\Core\Model\LSR;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\StockHelper;
use Ls\Omni\Helper\VendorSourcingHelper;
use Ls\Omni\Model\VendorSourcing\CheckoutFallbackResult;
use Ls\Omni\Model\VendorSourcing\CheckoutFallbackService;
use Ls\Omni\Model\VendorSourcing\ItemSourcingVerdict;
use Ls\Omni\Model\VendorSourcing\ZeroStockEverywhereResolver;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Unit tests for {@see CheckoutFallbackService} (ticket 89360, solution plan Rev 2 §6.2/§12).
 *
 * Collaborators (StockHelper, ZeroStockEverywhereResolver, LSR, Logger) are mocked via
 * createMock()/getMockBuilder()->disableOriginalConstructor(), following the ItemHelperTest/
 * BasketHelperTest convention. Real Magento\Quote\Model\Quote\Item instances are built via
 * ReflectionClass::newInstanceWithoutConstructor() (AbstractItem's getters/setters are plain
 * getData()/setData() wrappers, safe without the constructor running) and carry a real (also
 * constructor-disabled-mock) Product.
 *
 * VendorSourcingHelper is a PARTIAL mock (getMockBuilder()->onlyMethods(['getOptions'])) - only
 * getOptions() is stubbed per-test; isVendorSourced() runs as real, unmocked production code. This
 * exercises the real attribute-read logic directly instead of duplicating it in a test stub, which
 * requires the fake Product objects built by createProduct() to carry the
 * LSR::LS_VENDOR_SOURCING_ATTRIBUTE_CODE data via getData() for isVendorSourced() to read correctly
 * (see createProduct()). disableOriginalConstructor() combined with onlyMethods() is supported by
 * PHPUnit 10 - the constructor is skipped while isVendorSourced() still calls through to its real
 * body.
 */
class CheckoutFallbackServiceTest extends TestCase
{
    /** @var VendorSourcingHelper&MockObject */
    private $vendorSourcingHelper;

    /** @var StockHelper&MockObject */
    private $stockHelper;

    /** @var ZeroStockEverywhereResolver&MockObject */
    private $zeroStockResolver;

    /** @var LSR&MockObject */
    private $lsr;

    /** @var LoggerInterface&MockObject */
    private $logger;

    protected function setUp(): void
    {
        // Partial mock: only getOptions() is stubbed per-test, isVendorSourced() runs for real
        // (see class docblock).
        $this->vendorSourcingHelper = $this->getMockBuilder(VendorSourcingHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOptions'])
            ->getMock();
        $this->stockHelper = $this->createMock(StockHelper::class);
        // ItemHelper is a public typed property on AbstractHelperOmni (StockHelper's parent);
        // since StockHelper's constructor is disabled by createMock(), it starts uninitialized -
        // assign a mock directly so buildRowForEligibleItem()'s
        // $this->stockHelper->itemHelper->getComparisonValues() call has something to hit.
        $this->stockHelper->itemHelper = $this->createMock(ItemHelper::class);
        $this->zeroStockResolver = $this->createMock(ZeroStockEverywhereResolver::class);
        $this->lsr = $this->createMock(LSR::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /**
     * Stub ItemHelper::getComparisonValues() (the local DB/repository lookup
     * buildRowForEligibleItem() uses instead of an inventory API call) for a given SKU.
     *
     * @param string $sku
     * @param string $itemId
     * @param string $variantId
     */
    private function stubComparisonValues(string $sku, string $itemId, string $variantId): void
    {
        $this->stockHelper->itemHelper->method('getComparisonValues')
            ->with($sku)
            ->willReturn([$itemId, $variantId, null, null, null, null]);
    }

    private function createService(): CheckoutFallbackService
    {
        return new CheckoutFallbackService(
            $this->vendorSourcingHelper,
            $this->stockHelper,
            $this->zeroStockResolver,
            $this->lsr,
            $this->logger
        );
    }

    /**
     * @param bool $vendorSourced sets lsr_vendor_sourcing on the product (drives the real,
     *                            unstubbed VendorSourcingHelper::isVendorSourced())
     */
    private function createProduct(string $sku, bool $vendorSourced, string $typeId = Type::TYPE_SIMPLE): Product
    {
        /** @var Product&MockObject $product */
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn($sku);
        $product->method('getTypeId')->willReturn($typeId);
        $product->method('getData')->willReturnCallback(
            static function (string $key) use ($vendorSourced) {
                if ($key === LSR::LS_VENDOR_SOURCING_ATTRIBUTE_CODE) {
                    return $vendorSourced ? 1 : 0;
                }

                return null;
            }
        );

        return $product;
    }

    private function createQuoteItem(int $itemId, string $sku, string $name, Product $product, float $qty = 1.0): Item
    {
        /** @var Item $item */
        $item = (new ReflectionClass(Item::class))->newInstanceWithoutConstructor();
        $item->setData('id', $itemId);
        $item->setSku($sku);
        $item->setName($name);
        $item->setData('qty', $qty);
        $item->setData('product', $product);

        return $item;
    }

    /**
     * @param string $itemId
     * @param string $variantId
     * @param bool $inStock
     * @param Item $quoteItem
     * @param float $qty
     * @return array
     */
    private function stockRow(
        string $itemId,
        string $variantId,
        bool $inStock,
        Item $quoteItem,
        float $qty = 1.0
    ): array {
        return [
            'item_id' => $itemId,
            'variant_id' => $variantId,
            'name' => $quoteItem->getName(),
            'qty' => $qty,
            'status' => $inStock ? '1' : '0',
            'display' => $inStock ? __('This item is available') : __('This item is not available'),
            'quote_item' => $quoteItem,
        ];
    }

    private function enableVendorSourcing(bool $enabled = true): void
    {
        $this->lsr->method('getStoreConfig')
            ->with(LSR::SC_CART_VENDOR_SOURCING_OPTIONS, $this->anything())
            ->willReturn($enabled);
    }

    // ---------------------------------------------------------------- evaluate() / C&C ----

    public function testEvaluateSkipsInStockRowWithoutCallingGetOptions(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-1', false);
        $item = $this->createQuoteItem(1, 'SKU-1', 'In Stock Product', $product);
        $stockCollection = [$this->stockRow('ITEM1', 'VAR1', true, $item)];

        $this->vendorSourcingHelper->expects($this->never())->method('getOptions');
        $this->stockHelper->expects($this->never())->method('getGivenItemsStockIgnoringManageStock');

        $result = $this->createService()->evaluate($stockCollection, VendorSourcingHelper::TYPE_COLLECT);

        $this->assertInstanceOf(CheckoutFallbackResult::class, $result);
        $this->assertFalse($result->hasBlockedItems());
        $this->assertSame([], $result->getVerdicts());
    }

    public function testEvaluateVendorSourcingDisabledAtStoreLevelReturnsLegacyBlockedMessageWithoutCentralCall(): void
    {
        $this->enableVendorSourcing(false);

        $product = $this->createProduct('SKU-1', true);
        $item = $this->createQuoteItem(1, 'SKU-1', 'Zero Stock Product', $product);
        $stockCollection = [$this->stockRow('ITEM1', 'VAR1', false, $item)];

        $this->vendorSourcingHelper->expects($this->never())->method('getOptions');

        $result = $this->createService()->evaluate($stockCollection, VendorSourcingHelper::TYPE_COLLECT);

        $this->assertTrue($result->hasBlockedItems());
        $blocked = $result->getBlockedVerdicts();
        $this->assertCount(1, $blocked);
        $this->assertSame(
            'Unable to use selected shipping method since some or all of the cart items are not available in selected store.',
            (string)$blocked[0]->getMessage()
        );
    }

    public function testEvaluateZeroStockWithNoCollectTypeOptionsBlocksWithoutDisambiguationCall(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-1', true);
        $item = $this->createQuoteItem(1, 'SKU-1', 'Zero Stock Product', $product);
        $stockCollection = [$this->stockRow('ITEM1', 'VAR1', false, $item)];

        // Vendor sourcing eligible, but the only usable row is ship-type - not collect-type,
        // so filtering to TYPE_COLLECT yields nothing usable.
        $this->vendorSourcingHelper->method('getOptions')
            ->with('SKU-1', null)
            ->willReturn([
                [
                    'type' => VendorSourcingHelper::TYPE_SHIP,
                    'store_code' => null,
                    'store_name' => null,
                    'days' => 3,
                    'date' => '2026-10-24',
                    'priority' => 1,
                ],
            ]);

        $this->stockHelper->expects($this->never())->method('getGivenItemsStockIgnoringManageStock');

        $result = $this->createService()->evaluate($stockCollection, VendorSourcingHelper::TYPE_COLLECT);

        $this->assertTrue($result->hasBlockedItems());
        $blocked = $result->getBlockedVerdicts();
        $this->assertSame('SKU-1', $blocked[0]->getSku());
        $this->assertStringContainsString('cannot be sourced from a vendor', (string)$blocked[0]->getMessage());
    }

    public function testEvaluateZeroStockWithCollectOptionsDefersToDisambiguationAndConfirmsZeroStockEverywhere(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-1', true);
        $item = $this->createQuoteItem(1, 'SKU-1', 'Zero Stock Product', $product);
        $stockCollection = [$this->stockRow('ITEM1', 'VAR1', false, $item)];

        $collectOptions = [
            [
                'type' => VendorSourcingHelper::TYPE_COLLECT,
                'store_code' => 'S1',
                'store_name' => 'Store 1',
                'days' => 2,
                'date' => '2026-10-21',
                'priority' => 1,
            ],
        ];
        $this->vendorSourcingHelper->method('getOptions')->with('SKU-1', null)->willReturn($collectOptions);

        $this->stockHelper->expects($this->once())
            ->method('getGivenItemsStockIgnoringManageStock')
            ->with($this->callback(static fn (array $items): bool => $items === [$item]))
            ->willReturn([null, [['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'qty' => 1.0]]]);

        // Zero-stock-everywhere confirmed (no store satisfies the qty) -> sourced verdict.
        $this->zeroStockResolver->method('resolve')->willReturn(['ITEM1|VAR1' => false]);

        $result = $this->createService()->evaluate($stockCollection, VendorSourcingHelper::TYPE_COLLECT);

        $this->assertFalse($result->hasBlockedItems());
        $verdicts = $result->getVerdicts();
        $this->assertCount(1, $verdicts);
        $this->assertSame(ItemSourcingVerdict::STATUS_VENDOR_SOURCED, $verdicts[0]->getStatus());
        $this->assertSame($collectOptions, $verdicts[0]->getOptions());
        $this->assertSame('ITEM1', $verdicts[0]->getItemId(), 'Sourced verdict must carry the row LS Central item_id (ticket 89364)');
        $this->assertSame('VAR1', $verdicts[0]->getVariantId(), 'Sourced verdict must carry the row LS Central variant_id (ticket 89364)');
    }

    /**
     * Regression test for a real production bug: Central's COSourcingLocationListV4 response can
     * return MULTIPLE valid collect-type stores, sorted by Central's own priority/lead-time
     * ranking - that ranking is NOT the customer's actual pickup-store selection. The verdict's
     * suggested store (and therefore order-line SourcingLocation/LeadTimeCalculation) must match
     * the store the customer actually selected, not whichever one Central ranked first.
     */
    public function testEvaluateDisambiguationMatchesSelectedStoreNotCentralsTopRankedOption(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-1', true);
        $item = $this->createQuoteItem(1, 'SKU-1', 'Zero Stock Product', $product);
        $stockCollection = [$this->stockRow('ITEM1', 'VAR1', false, $item)];

        // S0004 is Central's top-ranked (priority 1) option; S0001 is what the customer actually
        // selected at checkout and is only priority 2.
        $collectOptions = [
            [
                'type' => VendorSourcingHelper::TYPE_COLLECT,
                'store_code' => 'S0004',
                'store_name' => 'Store 4',
                'days' => 1,
                'date' => '2026-10-21',
                'priority' => 1,
                'lead_time_calculation' => '1D',
            ],
            [
                'type' => VendorSourcingHelper::TYPE_COLLECT,
                'store_code' => 'S0001',
                'store_name' => 'Store 1',
                'days' => 2,
                'date' => '2026-10-22',
                'priority' => 2,
                'lead_time_calculation' => '2D',
            ],
        ];
        $this->vendorSourcingHelper->method('getOptions')->with('SKU-1', null)->willReturn($collectOptions);

        $this->stockHelper->method('getGivenItemsStockIgnoringManageStock')
            ->willReturn([null, [['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'qty' => 1.0]]]);
        $this->zeroStockResolver->method('resolve')->willReturn(['ITEM1|VAR1' => false]);

        $result = $this->createService()->evaluate($stockCollection, VendorSourcingHelper::TYPE_COLLECT, [], 'S0001');

        $verdicts = $result->getVerdicts();
        $this->assertCount(1, $verdicts);
        $this->assertSame(ItemSourcingVerdict::STATUS_VENDOR_SOURCED, $verdicts[0]->getStatus());
        $suggestedStore = $verdicts[0]->getSuggestedStore();
        $this->assertSame('S0001', $suggestedStore['store_code'], 'Must match the customer-selected store, not S0004 (Central\'s top-ranked option)');
        $this->assertSame('2D', $suggestedStore['lead_time_calculation'], 'Lead time must be S0001\'s, not S0004\'s');
    }

    public function testEvaluateDisambiguationFindsStockElsewhereBlocksWithWrongStoreMessage(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-1', true);
        $item = $this->createQuoteItem(1, 'SKU-1', 'Zero Stock Product', $product);
        $stockCollection = [$this->stockRow('ITEM1', 'VAR1', false, $item)];

        $collectOptions = [
            [
                'type' => VendorSourcingHelper::TYPE_COLLECT,
                'store_code' => 'S1',
                'store_name' => 'Store 1',
                'days' => 2,
                'date' => '2026-10-21',
                'priority' => 1,
            ],
        ];
        $this->vendorSourcingHelper->method('getOptions')->with('SKU-1', null)->willReturn($collectOptions);

        $this->stockHelper->expects($this->once())
            ->method('getGivenItemsStockIgnoringManageStock')
            ->willReturn([null, [['item_id' => 'ITEM1', 'variant_id' => 'VAR1', 'qty' => 1.0]]]);

        // Stock exists at another store - this is the "wrong store" case, not genuinely
        // zero-stock-everywhere.
        $this->zeroStockResolver->method('resolve')->willReturn(['ITEM1|VAR1' => true]);

        $result = $this->createService()->evaluate($stockCollection, VendorSourcingHelper::TYPE_COLLECT);

        $this->assertTrue($result->hasBlockedItems());
        $blocked = $result->getBlockedVerdicts();
        $message = (string)$blocked[0]->getMessage();
        $this->assertStringContainsString('available at another store', $message);
        $this->assertStringNotContainsString('cannot be sourced from a vendor', $message);
        $this->assertSame('ITEM1', $blocked[0]->getItemId());
        $this->assertSame('VAR1', $blocked[0]->getVariantId());
    }

    // ------------------------------------------------------------ evaluateDelivery() ----

    /**
     * Critical, explicitly-confirmed regression test (solution plan §15/R8): a cart with no
     * vendor-sourcing-flagged item must return immediately, with ZERO calls to the sourcing
     * lookup (getOptions()) or the local item identity lookup
     * (ItemHelper::getComparisonValues()) - not merely an empty result, but genuinely zero added
     * latency/calls for merchants not touching vendor sourcing at all (NFR2).
     */
    public function testEvaluateDeliveryWithNoFlaggedItemReturnsImmediatelyWithZeroCalls(): void
    {
        $product = $this->createProduct('SKU-ORDINARY', false);
        $item = $this->createQuoteItem(1, 'SKU-ORDINARY', 'Ordinary Product', $product);

        $this->vendorSourcingHelper->expects($this->never())->method('getOptions');
        $this->stockHelper->itemHelper->expects($this->never())->method('getComparisonValues');

        $result = $this->createService()->evaluateDelivery([$item]);

        $this->assertInstanceOf(CheckoutFallbackResult::class, $result);
        $this->assertFalse($result->hasBlockedItems());
        $this->assertSame([], $result->getVerdicts());
    }

    public function testEvaluateDeliveryFlaggedZeroStockEverywhereWithShipOptionsReturnsVendorSourcedWithSuggestedStore(): void
    {
        $product = $this->createProduct('SKU-FLAGGED', true);
        $item = $this->createQuoteItem(1, 'SKU-FLAGGED', 'Flagged Product', $product);

        // manage_stock=0 is itself the eligibility signal now - no inventory API call at all,
        // just the local item_id/variant_id lookup.
        $this->stubComparisonValues('SKU-FLAGGED', 'ITEM1', 'VAR1');

        $shipOptions = [
            [
                'type' => VendorSourcingHelper::TYPE_SHIP,
                'store_code' => null,
                'store_name' => null,
                'days' => 2,
                'date' => '2026-10-21',
                'priority' => 1,
            ],
            [
                'type' => VendorSourcingHelper::TYPE_SHIP,
                'store_code' => null,
                'store_name' => null,
                'days' => 5,
                'date' => '2026-10-24',
                'priority' => 2,
            ],
        ];
        $this->vendorSourcingHelper->method('getOptions')->with('SKU-FLAGGED', null)->willReturn($shipOptions);

        $result = $this->createService()->evaluateDelivery([$item]);

        $this->assertFalse($result->hasBlockedItems());
        $verdicts = $result->getVerdicts();
        $this->assertCount(1, $verdicts);
        $this->assertSame(ItemSourcingVerdict::STATUS_VENDOR_SOURCED, $verdicts[0]->getStatus());
        $this->assertSame($shipOptions[0], $verdicts[0]->getSuggestedStore(), 'Suggested store must be the first (highest-priority) ship row');
        $this->assertSame('ITEM1', $verdicts[0]->getItemId());
        $this->assertSame('VAR1', $verdicts[0]->getVariantId());
    }

    public function testEvaluateDeliveryFlaggedZeroStockEverywhereWithNoShipOptionsBlocks(): void
    {
        $product = $this->createProduct('SKU-FLAGGED', true);
        $item = $this->createQuoteItem(1, 'SKU-FLAGGED', 'Flagged Product', $product);

        $this->stubComparisonValues('SKU-FLAGGED', 'ITEM1', 'VAR1');
        $this->vendorSourcingHelper->method('getOptions')->with('SKU-FLAGGED', null)->willReturn([]);

        $result = $this->createService()->evaluateDelivery([$item]);

        $this->assertTrue($result->hasBlockedItems());
        $this->assertSame('SKU-FLAGGED', $result->getBlockedVerdicts()[0]->getSku());
    }

    /**
     * Edge-case regression test (ticket 89360 follow-up): buildRowForEligibleItem()'s local
     * ItemHelper::getComparisonValues() lookup returning null (item not found in the local
     * repository) must not crash evaluateDelivery() - item_id/variant_id simply resolve to null
     * and the item still flows through to a verdict (sourced or blocked) via the sourcing lookup,
     * same as any other eligible item.
     */
    public function testEvaluateDeliveryItemNotFoundInLocalRepositoryStillProducesVerdictWithNullIdentity(): void
    {
        $product = $this->createProduct('SKU-FLAGGED', true);
        $item = $this->createQuoteItem(1, 'SKU-FLAGGED', 'Flagged Product', $product);

        $this->stockHelper->itemHelper->method('getComparisonValues')
            ->with('SKU-FLAGGED')
            ->willReturn(null);

        $shipOptions = [
            [
                'type' => VendorSourcingHelper::TYPE_SHIP,
                'store_code' => null,
                'store_name' => null,
                'days' => 2,
                'date' => '2026-10-21',
                'priority' => 1,
            ],
        ];
        $this->vendorSourcingHelper->method('getOptions')->with('SKU-FLAGGED', null)->willReturn($shipOptions);

        $result = $this->createService()->evaluateDelivery([$item]);

        $this->assertFalse($result->hasBlockedItems());
        $verdicts = $result->getVerdicts();
        $this->assertCount(1, $verdicts);
        $this->assertSame(ItemSourcingVerdict::STATUS_VENDOR_SOURCED, $verdicts[0]->getStatus());
        $this->assertNull($verdicts[0]->getItemId());
        $this->assertNull($verdicts[0]->getVariantId());
    }

    /**
     * Regression test for the confirmed R8 decision: a cart with BOTH a vendor-sourcing-flagged
     * item AND an ordinary (non-flagged) item that is ALSO zero-stock must never let the ordinary
     * item appear in any verdict, and the stock-check call must only ever receive the flagged
     * item - the ordinary item's stock status is never computed or reasoned about by this path.
     */
    public function testEvaluateDeliveryOnlyEverQueriesAndVerdictsTheFlaggedItemNotTheOrdinaryOne(): void
    {
        $flaggedProduct = $this->createProduct('SKU-FLAGGED', true);
        $flaggedItem = $this->createQuoteItem(1, 'SKU-FLAGGED', 'Flagged Product', $flaggedProduct);

        $ordinaryProduct = $this->createProduct('SKU-ORDINARY', false);
        $ordinaryItem = $this->createQuoteItem(2, 'SKU-ORDINARY', 'Ordinary Zero Stock Product', $ordinaryProduct);

        // The ordinary item is filtered out by getVendorSourcingEligibleItems() before
        // buildRowForEligibleItem() ever runs, so the local lookup is only ever hit for the
        // flagged item's SKU.
        $this->stockHelper->itemHelper->expects($this->once())
            ->method('getComparisonValues')
            ->with('SKU-FLAGGED')
            ->willReturn(['ITEM1', 'VAR1', null, null, null, null]);

        $shipOptions = [
            [
                'type' => VendorSourcingHelper::TYPE_SHIP,
                'store_code' => null,
                'store_name' => null,
                'days' => 2,
                'date' => '2026-10-21',
                'priority' => 1,
            ],
        ];
        $this->vendorSourcingHelper->method('getOptions')->with('SKU-FLAGGED', null)->willReturn($shipOptions);

        $result = $this->createService()->evaluateDelivery([$flaggedItem, $ordinaryItem]);

        $verdicts = $result->getVerdicts();
        $this->assertCount(1, $verdicts, 'Only the flagged item should ever receive a verdict');
        $this->assertSame('SKU-FLAGGED', $verdicts[0]->getSku());

        foreach ($verdicts as $verdict) {
            $this->assertNotSame('SKU-ORDINARY', $verdict->getSku());
        }
    }

    // -------------------------------------------------------- resolveDisambiguation() ----

    /**
     * When the batched all-store disambiguation stock lookup throws, the pending row must still
     * resolve to a VENDOR_SOURCED verdict (Central already confirmed valid sourcing options for
     * this row - the disambiguation call only checks "is it ALSO available elsewhere", so a
     * failure there must not block an otherwise-good verdict), and the failure must be logged.
     */
    public function testEvaluateDisambiguationStockLookupThrowsStillProducesSourcedVerdict(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-1', true);
        $item = $this->createQuoteItem(1, 'SKU-1', 'Zero Stock Product', $product);
        $stockCollection = [$this->stockRow('ITEM1', 'VAR1', false, $item)];

        $collectOptions = [
            [
                'type' => VendorSourcingHelper::TYPE_COLLECT,
                'store_code' => 'S1',
                'store_name' => 'Store 1',
                'days' => 2,
                'date' => '2026-10-21',
                'priority' => 1,
            ],
        ];
        $this->vendorSourcingHelper->method('getOptions')->with('SKU-1', null)->willReturn($collectOptions);

        $this->stockHelper->expects($this->once())
            ->method('getGivenItemsStockIgnoringManageStock')
            ->willThrowException(new \Exception('SOAP call failed'));

        $this->zeroStockResolver->expects($this->once())
            ->method('resolve')
            ->with(null, $this->anything())
            ->willReturn(['ITEM1|VAR1' => false]);

        $this->logger->expects($this->once())->method('error');

        $result = $this->createService()->evaluate($stockCollection, VendorSourcingHelper::TYPE_COLLECT);

        $this->assertFalse($result->hasBlockedItems());
        $verdicts = $result->getVerdicts();
        $this->assertCount(1, $verdicts);
        $this->assertSame(ItemSourcingVerdict::STATUS_VENDOR_SOURCED, $verdicts[0]->getStatus());
    }

    // ------------------------------------- evaluate()'s $quoteItems param ("C&C trigger gap") ----

    /**
     * Regression test for the bug fix (post-merge review, Bug A, "C&C trigger gap"): once ticket
     * 89358 gives a vendor-sourced item manage_stock=0, StockHelper::getGivenItemsStockInGivenStore()
     * silently excludes it from $stockCollection altogether - never even a status=0 row - so it
     * would never be represented there at all. evaluate()'s $quoteItems param must independently
     * re-discover and evaluate such an item via the UNGATED stock check, producing a verdict even
     * though it never appeared in $stockCollection.
     */
    public function testEvaluateQuoteItemsParamEvaluatesEligibleItemMissingFromStockCollection(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-EXCLUDED', true);
        $excludedItem = $this->createQuoteItem(2, 'SKU-EXCLUDED', 'Excluded Product', $product);

        // $stockCollection is empty - the gated manage_stock=0 exclusion this bug fix addresses.
        $stockCollection = [];

        // manage_stock=0 is itself the eligibility signal now - no inventory API call at all,
        // just the local item_id/variant_id lookup via ItemHelper::getComparisonValues().
        $this->stubComparisonValues('SKU-EXCLUDED', 'ITEM2', 'VAR2');

        $collectOptions = [
            [
                'type' => VendorSourcingHelper::TYPE_COLLECT,
                'store_code' => 'S1',
                'store_name' => 'Store 1',
                'days' => 2,
                'date' => '2026-10-21',
                'priority' => 1,
            ],
        ];
        $this->vendorSourcingHelper->method('getOptions')->with('SKU-EXCLUDED', null)->willReturn($collectOptions);

        $result = $this->createService()->evaluate(
            $stockCollection,
            VendorSourcingHelper::TYPE_COLLECT,
            [$excludedItem]
        );

        $this->assertFalse($result->hasBlockedItems());
        $verdicts = $result->getVerdicts();
        $this->assertCount(1, $verdicts);
        $this->assertSame(ItemSourcingVerdict::STATUS_VENDOR_SOURCED, $verdicts[0]->getStatus());
        $this->assertSame('SKU-EXCLUDED', $verdicts[0]->getSku());
    }

    /**
     * An eligible item missing from $stockCollection, with no usable collect-type options, must
     * be blocked - same outcome the main loop would have produced had it been able to see the row
     * at all.
     */
    public function testEvaluateQuoteItemsParamBlocksEligibleItemMissingFromStockCollectionWithNoOptions(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-EXCLUDED', true);
        $excludedItem = $this->createQuoteItem(2, 'SKU-EXCLUDED', 'Excluded Product', $product);

        $this->stubComparisonValues('SKU-EXCLUDED', 'ITEM2', 'VAR2');
        $this->vendorSourcingHelper->method('getOptions')->willReturn([]);

        $result = $this->createService()->evaluate([], VendorSourcingHelper::TYPE_COLLECT, [$excludedItem]);

        $this->assertTrue($result->hasBlockedItems());
        $this->assertSame('SKU-EXCLUDED', $result->getBlockedVerdicts()[0]->getSku());
    }

    /**
     * Dedup regression test: an item already represented in $stockCollection must NOT be
     * double-processed by the $quoteItems path, even though it is also vendor-sourcing-eligible.
     * The local item_id/variant_id lookup still runs for every eligible item (that's how the dedup
     * key is derived in the first place) but the sourcing lookup (getOptions()) and the
     * $stockCollection-loop's own disambiguation (resolve()) must not run again for it.
     */
    public function testEvaluateQuoteItemsParamDoesNotDoubleProcessItemAlreadyInStockCollection(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-1', true);
        $item = $this->createQuoteItem(1, 'SKU-1', 'Zero Stock Product', $product);
        $stockCollection = [$this->stockRow('ITEM1', 'VAR1', true, $item)]; // in stock, no verdict

        // Resolves to the same item_id|variant_id key already present in $stockCollection.
        $this->stockHelper->itemHelper->expects($this->once())
            ->method('getComparisonValues')
            ->with('SKU-1')
            ->willReturn(['ITEM1', 'VAR1', null, null, null, null]);

        // Nothing is pending for the main $stockCollection loop (the row is in stock) so resolve()
        // must never be called, and the dedup filter means the sourcing lookup never runs either.
        $this->zeroStockResolver->expects($this->never())->method('resolve');
        $this->vendorSourcingHelper->expects($this->never())->method('getOptions');

        $result = $this->createService()->evaluate($stockCollection, VendorSourcingHelper::TYPE_COLLECT, [$item]);

        $this->assertFalse($result->hasBlockedItems());
        $this->assertSame([], $result->getVerdicts());
    }

    /**
     * No-op regression: when $quoteItems contains no vendor-sourcing-eligible item at all, the
     * local item_id/variant_id lookup must never be called.
     */
    public function testEvaluateQuoteItemsParamDoesNothingWhenNoEligibleItems(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-ORDINARY', false);
        $ordinaryItem = $this->createQuoteItem(3, 'SKU-ORDINARY', 'Ordinary Product', $product);

        $this->stockHelper->itemHelper->expects($this->never())->method('getComparisonValues');

        $result = $this->createService()->evaluate([], VendorSourcingHelper::TYPE_COLLECT, [$ordinaryItem]);

        $this->assertFalse($result->hasBlockedItems());
        $this->assertSame([], $result->getVerdicts());
    }

    /**
     * Edge-case regression test (ticket 89360 follow-up): buildRowForEligibleItem()'s local
     * ItemHelper::getComparisonValues() lookup returning null (item not found in the local
     * repository) must not crash evaluateItemsExcludedFromStockCollection() - item_id/variant_id
     * simply resolve to null and the item still flows through to a verdict via the sourcing
     * lookup, same as any other eligible item.
     */
    public function testEvaluateQuoteItemsParamItemNotFoundInLocalRepositoryStillProducesVerdictWithNullIdentity(): void
    {
        $this->enableVendorSourcing();

        $product = $this->createProduct('SKU-EXCLUDED', true);
        $excludedItem = $this->createQuoteItem(2, 'SKU-EXCLUDED', 'Excluded Product', $product);

        $this->stockHelper->itemHelper->method('getComparisonValues')
            ->with('SKU-EXCLUDED')
            ->willReturn(null);
        $this->vendorSourcingHelper->method('getOptions')->willReturn([]);

        $result = $this->createService()->evaluate([], VendorSourcingHelper::TYPE_COLLECT, [$excludedItem]);

        $this->assertTrue($result->hasBlockedItems());
        $blocked = $result->getBlockedVerdicts();
        $this->assertCount(1, $blocked);
        $this->assertSame('SKU-EXCLUDED', $blocked[0]->getSku());
        $this->assertNull($blocked[0]->getItemId());
        $this->assertNull($blocked[0]->getVariantId());
    }

    // ---------------------------------------------------------- buildVendorSourcedLinesMap() ----

    /**
     * {@see CheckoutFallbackService::buildVendorSourcedLinesMap()} (moved here from
     * DataAssignObserver, ticket 89360 post-merge review, Bug B): a quote item carrying a
     * STATUS_VENDOR_SOURCED verdict must produce a map entry keyed by the verdict's LS Central
     * "item_id|variant_id" identity.
     */
    public function testBuildVendorSourcedLinesMapAddsEntryForVendorSourcedVerdict(): void
    {
        $verdict = $this->createMock(ItemSourcingVerdict::class);
        $verdict->method('getStatus')->willReturn(ItemSourcingVerdict::STATUS_VENDOR_SOURCED);
        $verdict->method('getItemId')->willReturn('ITEM1');
        $verdict->method('getVariantId')->willReturn('VAR1');
        $verdict->method('getSuggestedStore')->willReturn(['store_code' => 'S1']);

        $item = $this->createMock(Item::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getData')->with(CheckoutFallbackService::QUOTE_ITEM_VERDICT_KEY)->willReturn($verdict);

        $map = $this->createService()->buildVendorSourcedLinesMap([$item]);

        $this->assertSame(
            ['ITEM1|VAR1' => [
                'vendor_sourcing' => true,
                'sourcing_location' => 'S1',
                'lead_time_calculation' => null,
            ]],
            $map
        );
    }

    /**
     * No vendor-sourced verdict anywhere must leave the map empty.
     */
    public function testBuildVendorSourcedLinesMapReturnsEmptyArrayWhenNoVerdictsPresent(): void
    {
        $item = $this->createMock(Item::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getData')->with(CheckoutFallbackService::QUOTE_ITEM_VERDICT_KEY)->willReturn(null);

        $map = $this->createService()->buildVendorSourcedLinesMap([$item]);

        $this->assertSame([], $map);
    }

    /**
     * A blocked verdict must not produce a map entry - only STATUS_VENDOR_SOURCED verdicts are
     * relevant here.
     */
    public function testBuildVendorSourcedLinesMapIgnoresBlockedVerdicts(): void
    {
        $verdict = $this->createMock(ItemSourcingVerdict::class);
        $verdict->method('getStatus')->willReturn(ItemSourcingVerdict::STATUS_BLOCKED);

        $item = $this->createMock(Item::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getData')->with(CheckoutFallbackService::QUOTE_ITEM_VERDICT_KEY)->willReturn($verdict);

        $map = $this->createService()->buildVendorSourcedLinesMap([$item]);

        $this->assertSame([], $map);
    }

    /**
     * Bundle items must have their children expanded (the verdict lives on the child quote item,
     * never the bundle parent) - mirrors getVendorSourcingEligibleItems()'s expansion exactly.
     */
    public function testBuildVendorSourcedLinesMapExpandsBundleChildren(): void
    {
        $verdict = $this->createMock(ItemSourcingVerdict::class);
        $verdict->method('getStatus')->willReturn(ItemSourcingVerdict::STATUS_VENDOR_SOURCED);
        $verdict->method('getItemId')->willReturn('ITEM2');
        $verdict->method('getVariantId')->willReturn('VAR2');
        $verdict->method('getSuggestedStore')->willReturn(null);

        $child = $this->createMock(Item::class);
        $child->method('getData')->with(CheckoutFallbackService::QUOTE_ITEM_VERDICT_KEY)->willReturn($verdict);

        $bundleParent = $this->createMock(Item::class);
        $bundleParent->method('getProductType')->willReturn(Type::TYPE_BUNDLE);
        $bundleParent->method('getChildren')->willReturn([$child]);

        $map = $this->createService()->buildVendorSourcedLinesMap([$bundleParent]);

        $this->assertSame(
            ['ITEM2|VAR2' => [
                'vendor_sourcing' => true,
                'sourcing_location' => null,
                'lead_time_calculation' => null,
            ]],
            $map
        );
    }
}
