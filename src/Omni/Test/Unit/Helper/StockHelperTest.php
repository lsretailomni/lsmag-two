<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Helper;

use Ls\Omni\Client\CentralEcommerce\Entity\RootGetInventoryMultipleOut;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\StockHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for {@see StockHelper::mergeStockCollection()}'s new, additive `$quoteItem`
 * parameter (ticket 89360, solution plan §6.5, carried forward from Rev 1 §3.1).
 *
 * The quote item must be threaded through onto the 'quote_item' key of every row this method
 * produces - both when a brand new row is created AND when qty is merged into an already-present
 * row for the same (item_id, variant_id) - so downstream consumers (CheckoutFallbackService,
 * ZeroStockEverywhereResolver, PickupStoreIntersectionFilter) can resolve a stock-collection row
 * back to its originating Quote\Item without a second lookup.
 *
 * StockHelper extends AbstractHelperOmni (~70-argument constructor) - following the
 * ItemHelperTest/BasketHelperTest precedent, the instance under test is built with its
 * constructor disabled and no methods mocked (mergeStockCollection() runs for real); the quote
 * item is a real Magento\Quote\Model\Quote\Item built via
 * ReflectionClass::newInstanceWithoutConstructor(), mirroring ItemHelperTest's own convention.
 */
class StockHelperTest extends TestCase
{
    /** @var StockHelper&MockObject */
    private $stockHelper;

    protected function setUp(): void
    {
        $this->stockHelper = $this->getMockBuilder(StockHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    private function createQuoteItem(string $sku): Item
    {
        /** @var Item $item */
        $item = (new ReflectionClass(Item::class))->newInstanceWithoutConstructor();
        $item->setSku($sku);

        return $item;
    }

    /**
     * A brand new (item_id, variant_id) row must carry the given quote item under 'quote_item'.
     */
    public function testMergeStockCollectionStoresQuoteItemOnNewRow(): void
    {
        $quoteItem = $this->createQuoteItem('ITEM1-VAR1');
        $stockCollection = [];

        $this->stockHelper->mergeStockCollection(
            $stockCollection,
            'ITEM1',
            'VAR1',
            'Product Name',
            2.0,
            $quoteItem
        );

        $this->assertCount(1, $stockCollection);
        $this->assertSame($quoteItem, $stockCollection[0]['quote_item']);
        $this->assertSame('ITEM1', $stockCollection[0]['item_id']);
        $this->assertSame('VAR1', $stockCollection[0]['variant_id']);
        $this->assertSame(2.0, $stockCollection[0]['qty']);
    }

    /**
     * Merging additional qty into an ALREADY-PRESENT row for the same (item_id, variant_id) must
     * also carry the quote item onto 'quote_item' - not just the qty summation.
     */
    public function testMergeStockCollectionStoresQuoteItemWhenMergingIntoExistingRow(): void
    {
        $firstQuoteItem = $this->createQuoteItem('ITEM1-VAR1-FIRST');
        $secondQuoteItem = $this->createQuoteItem('ITEM1-VAR1-SECOND');

        $stockCollection = [];
        $this->stockHelper->mergeStockCollection(
            $stockCollection,
            'ITEM1',
            'VAR1',
            'Product Name',
            1.0,
            $firstQuoteItem
        );

        $this->stockHelper->mergeStockCollection(
            $stockCollection,
            'ITEM1',
            'VAR1',
            'Product Name',
            3.0,
            $secondQuoteItem
        );

        $this->assertCount(1, $stockCollection, 'Second call must merge into the existing row, not add a new one');
        $this->assertSame(4.0, $stockCollection[0]['qty'], 'Qty must be summed across merges');
        $this->assertSame($secondQuoteItem, $stockCollection[0]['quote_item']);
    }

    /**
     * Omitting the (new, optional) quote item parameter altogether must not error - purely
     * additive change, existing call shape (5 args) stays valid.
     */
    public function testMergeStockCollectionStillWorksWithoutQuoteItemParameter(): void
    {
        $stockCollection = [];

        $this->stockHelper->mergeStockCollection(
            $stockCollection,
            'ITEM1',
            'VAR1',
            'Product Name',
            2.0
        );

        $this->assertCount(1, $stockCollection);
        $this->assertSame(2.0, $stockCollection[0]['qty']);
    }

    // ------------------------------------------- getGivenItemsStockIgnoringManageStock() ----

    /**
     * Unit tests for {@see StockHelper::getGivenItemsStockIgnoringManageStock()} (bug fix,
     * post-merge review, Bug A): unlike {@see StockHelper::getGivenItemsStockInGivenStore()}, this
     * method must NEVER exclude an item based on use_config_manage_stock - it mirrors that
     * method's exact loop but always includes every item/child unconditionally, so a vendor-
     * sourced product (which, once ticket 89358 lands, gets manage_stock=0) is still visible to
     * callers that specifically need to check it regardless of its manage_stock setting.
     *
     * A partial mock stubbing getAllItemsStockInSingleStore() isolates this method from the real
     * SOAP call chain (getItemsStockInStoreFromSourcingLocation() -> createInstance()/execute()),
     * following the same "constructor disabled, no circular SOAP dependency" spirit as the rest of
     * this test class. itemHelper is a real, directly-assigned mock (public promoted property,
     * same convention as OrderHelperTest's $this->orderHelper->itemHelper = ...).
     */
    private function createPartialStockHelper(): StockHelper
    {
        $stockHelper = $this->getMockBuilder(StockHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllItemsStockInSingleStore'])
            ->getMock();

        $stockHelper->itemHelper = $this->createMock(ItemHelper::class);

        return $stockHelper;
    }

    private function createSimpleQuoteItem(string $sku, float $qty): Item
    {
        /** @var Item $item */
        $item = (new ReflectionClass(Item::class))->newInstanceWithoutConstructor();
        $item->setSku($sku);
        $item->setName($sku . ' Name');
        $item->setData('qty', $qty);
        // Item::getProductType() falls through to getProduct()->getTypeId() when no
        // 'product_type' option is registered - getProduct() itself calls
        // $product->setFinalPrice(null) unconditionally, so a real/mocked Product must be present
        // on 'product' to avoid a null method call.
        $item->setData('product', $this->createMock(Product::class));

        return $item;
    }

    public function testGetGivenItemsStockIgnoringManageStockReturnsNullResponseForEmptyItems(): void
    {
        $stockHelper = $this->createPartialStockHelper();
        $stockHelper->expects($this->never())->method('getAllItemsStockInSingleStore');

        [$response, $stockCollection] = $stockHelper->getGivenItemsStockIgnoringManageStock([]);

        $this->assertNull($response);
        $this->assertSame([], $stockCollection);
    }

    /**
     * Critical regression test: every item must be included regardless of its manage_stock
     * setting - this method never reads getUseConfigManageStock()/getManageStock() at all (unlike
     * getGivenItemsStockInGivenStore()), since the whole point is to bypass that gate.
     */
    public function testGetGivenItemsStockIgnoringManageStockIncludesItemUnconditionally(): void
    {
        $item = $this->createSimpleQuoteItem('VENDOR-SOURCED-SKU', 2.0);

        $stockHelper = $this->createPartialStockHelper();
        $stockHelper->itemHelper->method('getComparisonValues')
            ->with('VENDOR-SOURCED-SKU')
            ->willReturn(['ITEM1', 'VAR1', 'PCS', 'BARCODE1', null, null]);

        $response = $this->createMock(RootGetInventoryMultipleOut::class);

        $stockHelper->expects($this->once())
            ->method('getAllItemsStockInSingleStore')
            ->with('', [['parent' => 'ITEM1', 'child' => 'VAR1']])
            ->willReturn($response);

        [$actualResponse, $stockCollection] = $stockHelper->getGivenItemsStockIgnoringManageStock([$item]);

        $this->assertSame($response, $actualResponse);
        $this->assertCount(1, $stockCollection);
        $this->assertSame('ITEM1', $stockCollection[0]['item_id']);
        $this->assertSame('VAR1', $stockCollection[0]['variant_id']);
        $this->assertSame(2.0, $stockCollection[0]['qty']);
        $this->assertSame($item, $stockCollection[0]['quote_item']);
    }

    /**
     * UOM qty multiplier must still be applied (same as getGivenItemsStockInGivenStore()) - the
     * only difference this method introduces is removing the manage_stock gate, not the qty math.
     */
    public function testGetGivenItemsStockIgnoringManageStockAppliesUomQtyMultiplier(): void
    {
        $item = $this->createSimpleQuoteItem('UOM-SKU', 3.0);

        $stockHelper = $this->createPartialStockHelper();
        $stockHelper->itemHelper->method('getComparisonValues')
            ->with('UOM-SKU')
            ->willReturn(['ITEM1', 'VAR1', 'PCS', 'BARCODE1', 2, null]);

        $stockHelper->method('getAllItemsStockInSingleStore')->willReturn(null);

        [, $stockCollection] = $stockHelper->getGivenItemsStockIgnoringManageStock([$item]);

        $this->assertSame(6.0, $stockCollection[0]['qty'], 'qty (3) * uomQty (2) = 6');
    }

    /**
     * Bundle items must have their children expanded, same as getGivenItemsStockInGivenStore().
     */
    public function testGetGivenItemsStockIgnoringManageStockExpandsBundleChildren(): void
    {
        $child1 = $this->createSimpleQuoteItem('CHILD1-SKU', 1.0);
        $child2 = $this->createSimpleQuoteItem('CHILD2-SKU', 1.0);

        $bundleParent = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType', 'getChildren', 'getQty'])
            ->getMock();
        $bundleParent->method('getProductType')->willReturn(Type::TYPE_BUNDLE);
        $bundleParent->method('getChildren')->willReturn([$child1, $child2]);
        $bundleParent->method('getQty')->willReturn(1.0);

        $stockHelper = $this->createPartialStockHelper();
        $stockHelper->itemHelper->method('getComparisonValues')
            ->willReturnMap([
                ['CHILD1-SKU', ['ITEM1', 'VAR1', 'PCS', 'BARCODE1', null, null]],
                ['CHILD2-SKU', ['ITEM2', 'VAR2', 'PCS', 'BARCODE2', null, null]],
            ]);
        $stockHelper->method('getAllItemsStockInSingleStore')->willReturn(null);

        [, $stockCollection] = $stockHelper->getGivenItemsStockIgnoringManageStock([$bundleParent]);

        $this->assertCount(2, $stockCollection);
        $this->assertSame('ITEM1', $stockCollection[0]['item_id']);
        $this->assertSame('ITEM2', $stockCollection[1]['item_id']);
    }
}
