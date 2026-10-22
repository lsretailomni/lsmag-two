<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Controller\Stock;

use Ls\Omni\Controller\Stock\Store;
use Ls\Omni\Helper\AbstractHelperOmni;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\VendorSourcingHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Unit tests for {@see Store}'s partition-then-lookup logic (ticket 89360 follow-up: no
 * GetInventoryMultipleV2 call at all for vendor-sourcing-eligible items - only
 * COSourcingLocationListV4 for those, only the inventory call for ordinary/stocked items).
 *
 * partitionItems()/buildVendorSourcedRows()/resolveComparisonIds() are private with no public
 * wrapper suitable for isolating them from execute()'s surrounding PageFactory/Layout/Block
 * machinery (hours html rendering etc.), so they are invoked directly via
 * ReflectionMethod::setAccessible() - the same pragmatic choice used before this rewrite.
 */
class StoreTest extends TestCase
{
    /** @var VendorSourcingHelper&MockObject */
    private $vendorSourcingHelper;

    /** @var LoggerInterface&MockObject */
    private $logger;

    /** @var ItemHelper&MockObject */
    private $itemHelper;

    protected function setUp(): void
    {
        $this->vendorSourcingHelper = $this->createMock(VendorSourcingHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        // Directly injected (not reached via AbstractHelperOmni->getStockHelper()->itemHelper) -
        // see Store::resolveComparisonIds()'s docblock for why: AbstractHelperOmni's own
        // stockHelper dependency is wired as a \Proxy, and Magento's generated Proxy classes only
        // forward method calls, not direct property access.
        $this->itemHelper = $this->createMock(ItemHelper::class);
    }

    private function createController(): Store
    {
        return new Store(
            $this->createMock(JsonFactory::class),
            $this->createMock(Http::class),
            $this->createMock(PageFactory::class),
            $this->createMock(AbstractHelperOmni::class),
            $this->vendorSourcingHelper,
            $this->itemHelper,
            $this->logger
        );
    }

    private function invokePartitionItems(array $items): array
    {
        $controller = $this->createController();
        $method = new ReflectionMethod(Store::class, 'partitionItems');
        $method->setAccessible(true);

        return $method->invoke($controller, $items);
    }

    private function invokeBuildVendorSourcedRows(array $items, string $selectedStore): array
    {
        $controller = $this->createController();
        $method = new ReflectionMethod(Store::class, 'buildVendorSourcedRows');
        $method->setAccessible(true);

        return $method->invoke($controller, $items, $selectedStore);
    }

    private function createQuoteItem(Product $product, string $sku = 'SKU-1', string $productType = Type::TYPE_SIMPLE): Item
    {
        $item = $this->createMock(Item::class);
        $item->method('getProduct')->willReturn($product);
        $item->method('getOptionByCode')->willReturn(null);
        $item->method('getProductType')->willReturn($productType);
        $item->method('getSku')->willReturn($sku);
        $item->method('getName')->willReturn('Test Product');
        $item->method('getQty')->willReturn(1.0);

        return $item;
    }

    // -------------------------------------------------------------------- partitionItems() ----

    public function testPartitionItemsSplitsVendorSourcedFromOrdinary(): void
    {
        $vendorProduct = $this->createMock(Product::class);
        $vendorProduct->method('getSku')->willReturn('SKU-VENDOR');
        $vendorItem = $this->createQuoteItem($vendorProduct, 'SKU-VENDOR');

        $ordinaryProduct = $this->createMock(Product::class);
        $ordinaryProduct->method('getSku')->willReturn('SKU-ORDINARY');
        $ordinaryItem = $this->createQuoteItem($ordinaryProduct, 'SKU-ORDINARY');

        $this->vendorSourcingHelper->method('isVendorSourced')
            ->willReturnCallback(static fn (Product $p) => $p->getSku() === 'SKU-VENDOR');

        [$stocked, $vendorSourced] = $this->invokePartitionItems([$vendorItem, $ordinaryItem]);

        $this->assertSame([$ordinaryItem], $stocked);
        $this->assertSame([$vendorItem], $vendorSourced);
    }

    public function testPartitionItemsKeepsBundleItemsInStockedBucketUnchanged(): void
    {
        $bundleItem = $this->createMock(Item::class);
        $bundleItem->method('getProductType')->willReturn(Type::TYPE_BUNDLE);

        $this->vendorSourcingHelper->expects($this->never())->method('isVendorSourced');

        [$stocked, $vendorSourced] = $this->invokePartitionItems([$bundleItem]);

        $this->assertSame([$bundleItem], $stocked);
        $this->assertSame([], $vendorSourced);
    }

    // -------------------------------------------------------------- buildVendorSourcedRows() ----

    /**
     * No GetInventoryMultipleV2 call must ever be made for these items - only confirmed by the
     * absence of any expectation on $this->stockHelper for an inventory method; getOptions() is
     * the only lookup performed.
     */
    public function testBuildVendorSourcedRowsMarksAvailableWhenSelectedStoreIsACollectCode(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU-1');
        $item = $this->createQuoteItem($product);

        $this->itemHelper->method('getComparisonValues')->with('SKU-1')->willReturn(['ITEM1', 'VAR1', 'PCS', '', null, null]);

        $this->vendorSourcingHelper->method('getOptions')->with('SKU-1', null)->willReturn([
            ['type' => VendorSourcingHelper::TYPE_COLLECT, 'store_code' => 'S0001', 'store_name' => 'Store 1', 'days' => 1, 'date' => 'x', 'priority' => 1],
        ]);

        $rows = $this->invokeBuildVendorSourcedRows([$item], 'S0001');

        $this->assertCount(1, $rows);
        $this->assertSame('1', $rows[0]['status']);
        $this->assertSame('ITEM1', $rows[0]['item_id']);
        $this->assertSame('VAR1', $rows[0]['variant_id']);
    }

    /**
     * Collect-type stores exist, just not the selected one - the item IS sourceable from a
     * vendor, just not here, so the message must say so rather than claim it can't be sourced
     * from a vendor at all (matches CheckoutFallbackService::wrongStoreBlockedVerdict()'s
     * equivalent distinction).
     */
    public function testBuildVendorSourcedRowsMarksUnavailableWhenSelectedStoreIsNotACollectCode(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU-1');
        $item = $this->createQuoteItem($product);

        $this->itemHelper->method('getComparisonValues')->willReturn(['ITEM1', 'VAR1', 'PCS', '', null, null]);

        $this->vendorSourcingHelper->method('getOptions')->willReturn([
            ['type' => VendorSourcingHelper::TYPE_COLLECT, 'store_code' => 'S0002', 'store_name' => 'Store 2', 'days' => 1, 'date' => 'x', 'priority' => 1],
        ]);

        $rows = $this->invokeBuildVendorSourcedRows([$item], 'S0001');

        $this->assertSame('0', $rows[0]['status']);
        $this->assertStringContainsString('available at another store', (string)$rows[0]['display']);
        $this->assertStringNotContainsString('cannot be sourced from a vendor.', (string)$rows[0]['display']);
    }

    /**
     * No collect-type stores anywhere (not just "not this one") - the item genuinely cannot be
     * sourced from a vendor, which is a distinctly different message from the "wrong store" case.
     */
    public function testBuildVendorSourcedRowsMarksGenuinelyUnsourceableWhenNoCollectCodesExistAtAll(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU-1');
        $item = $this->createQuoteItem($product);

        $this->itemHelper->method('getComparisonValues')->willReturn(['ITEM1', 'VAR1', 'PCS', '', null, null]);
        $this->vendorSourcingHelper->method('getOptions')->willReturn([]);

        $rows = $this->invokeBuildVendorSourcedRows([$item], 'S0001');

        $this->assertSame('0', $rows[0]['status']);
        $this->assertSame(
            'This item is not available at the selected store and cannot be sourced from a vendor.',
            (string)$rows[0]['display']
        );
    }

    /**
     * Fail-closed: a getOptions() failure must not crash the ajax endpoint - the row is marked
     * unavailable (empty options -> no matching collect code), and the failure is logged.
     */
    public function testBuildVendorSourcedRowsFailsClosedWhenOptionsLookupThrows(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU-1');
        $item = $this->createQuoteItem($product);

        $this->itemHelper->method('getComparisonValues')->willReturn(['ITEM1', 'VAR1', 'PCS', '', null, null]);

        $this->vendorSourcingHelper->method('getOptions')
            ->willThrowException(new \Exception('Central sourcing lookup failed'));

        $this->logger->expects($this->once())->method('error');

        $rows = $this->invokeBuildVendorSourcedRows([$item], 'S0001');

        $this->assertSame('0', $rows[0]['status']);
    }

    /**
     * item_id/variant_id resolution must not depend on any inventory call - buildVendorSourcedRows()
     * no longer takes a StockHelper reference at all (structurally impossible to call an inventory
     * method from it), and resolveComparisonIds() only ever calls the directly-injected
     * ItemHelper::getComparisonValues() (a local DB lookup, not a Central API call).
     */
    public function testBuildVendorSourcedRowsNeverCallsAnyInventoryLookup(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU-1');
        $item = $this->createQuoteItem($product);

        $this->itemHelper->expects($this->once())->method('getComparisonValues')
            ->willReturn(['ITEM1', 'VAR1', 'PCS', '', null, null]);
        $this->vendorSourcingHelper->method('getOptions')->willReturn([]);

        $this->invokeBuildVendorSourcedRows([$item], 'S0001');
    }
}
