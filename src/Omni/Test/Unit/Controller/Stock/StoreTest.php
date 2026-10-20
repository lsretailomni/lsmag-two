<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Controller\Stock;

use Ls\Omni\Controller\Stock\Store;
use Ls\Omni\Helper\AbstractHelperOmni;
use Ls\Omni\Helper\StockHelper;
use Ls\Omni\Helper\VendorSourcingHelper;
use Ls\Omni\Model\VendorSourcing\ZeroStockEverywhereResolver;
use Magento\Catalog\Model\Product;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Unit tests for {@see Store::applyVendorSourcingStoreCheck()}'s FR8 vendor-sourcing integration
 * point (ticket 89360 code review finding - no prior test coverage reached this logic; the
 * existing integration test (StoreTest::testExecute()) exercises an ordinary, non-vendor-sourced
 * product only).
 *
 * applyVendorSourcingStoreCheck() is `private` with no public wrapper suitable for isolating it
 * from execute()'s surrounding PageFactory/Layout/Block machinery (hours html rendering etc.), so
 * it is invoked directly via ReflectionMethod::setAccessible() - a pragmatic, environment-local
 * choice to exercise this logic as a unit test without standing up the full integration fixture
 * chain (replicated stores + Central-backed stock/sourcing calls) this ticket's finding calls
 * impractical here.
 */
class StoreTest extends TestCase
{
    /** @var VendorSourcingHelper&MockObject */
    private $vendorSourcingHelper;

    /** @var ZeroStockEverywhereResolver&MockObject */
    private $zeroStockResolver;

    /** @var LoggerInterface&MockObject */
    private $logger;

    /** @var StockHelper&MockObject */
    private $stockHelper;

    protected function setUp(): void
    {
        $this->vendorSourcingHelper = $this->createMock(VendorSourcingHelper::class);
        $this->zeroStockResolver = $this->createMock(ZeroStockEverywhereResolver::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->stockHelper = $this->createMock(StockHelper::class);
    }

    private function createController(): Store
    {
        return new Store(
            $this->createMock(JsonFactory::class),
            $this->createMock(Http::class),
            $this->createMock(PageFactory::class),
            $this->createMock(AbstractHelperOmni::class),
            $this->vendorSourcingHelper,
            $this->zeroStockResolver,
            $this->logger
        );
    }

    /**
     * @param array $stockCollection
     * @param array $items
     * @param string $selectedStore
     * @return array
     */
    private function invokeApplyVendorSourcingStoreCheck(array $stockCollection, array $items, string $selectedStore): array
    {
        $controller = $this->createController();
        $method = new ReflectionMethod(Store::class, 'applyVendorSourcingStoreCheck');
        $method->setAccessible(true);

        return $method->invoke($controller, $stockCollection, $items, $selectedStore, $this->stockHelper);
    }

    private function createQuoteItem(Product $product): Item
    {
        $item = $this->createMock(Item::class);
        $item->method('getProduct')->willReturn($product);
        $item->method('getOptionByCode')->willReturn(null);

        return $item;
    }

    /**
     * Critical regression test: when the all-store stock lookup (the FR8 disambiguation call)
     * throws, every row must be left untouched (same as the empty($flaggedRows) early-return
     * path) rather than crash the ajax endpoint, and the failure must be logged.
     */
    public function testApplyVendorSourcingStoreCheckLeavesRowsUntouchedWhenAllStoreStockLookupThrows(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU-1');
        $item = $this->createQuoteItem($product);

        $this->vendorSourcingHelper->method('isVendorSourced')->willReturn(true);
        $this->vendorSourcingHelper->expects($this->never())->method('getOptions');

        $this->stockHelper->expects($this->once())
            ->method('getGivenItemsStockIgnoringManageStock')
            ->willThrowException(new \Exception('SOAP call failed'));

        $this->zeroStockResolver->expects($this->never())->method('resolve');
        $this->logger->expects($this->once())->method('error');

        $stockCollection = [
            [
                'item_id' => 'ITEM1',
                'variant_id' => 'VAR1',
                'status' => '0',
                'display' => __('This item is not available'),
                'quote_item' => $item,
            ],
        ];

        $result = $this->invokeApplyVendorSourcingStoreCheck($stockCollection, [$item], 'S0001');

        $this->assertSame($stockCollection, $result);
    }

    /**
     * Companion fail-closed test for the per-row vendor sourcing options lookup: when
     * getOptions() throws for a flagged, zero-stock-everywhere row, that row must be left
     * untouched rather than crash the ajax endpoint, and the failure must be logged.
     */
    public function testApplyVendorSourcingStoreCheckLeavesRowUntouchedWhenOptionsLookupThrows(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU-1');
        $item = $this->createQuoteItem($product);

        $this->vendorSourcingHelper->method('isVendorSourced')->willReturn(true);
        $this->stockHelper->method('getGivenItemsStockIgnoringManageStock')->willReturn([null, []]);

        // Zero-stock-everywhere confirmed (no store satisfies the qty).
        $this->zeroStockResolver->method('resolve')->willReturn(['ITEM1|VAR1' => false]);

        $this->vendorSourcingHelper->expects($this->once())
            ->method('getOptions')
            ->with('SKU-1', null)
            ->willThrowException(new \Exception('Central sourcing lookup failed'));

        $this->logger->expects($this->once())->method('error');

        $originalRow = [
            'item_id' => 'ITEM1',
            'variant_id' => 'VAR1',
            'status' => '0',
            'display' => __('This item is not available'),
            'quote_item' => $item,
        ];
        $stockCollection = [$originalRow];

        $result = $this->invokeApplyVendorSourcingStoreCheck($stockCollection, [$item], 'S0001');

        $this->assertSame($originalRow['status'], $result[0]['status'], 'Row must be left untouched on lookup failure');
        $this->assertSame($originalRow['display'], $result[0]['display'], 'Row must be left untouched on lookup failure');
    }
}
