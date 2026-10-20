<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Model\Checkout;

use Ls\Omni\Client\CentralEcommerce\Entity\InventoryBufferOut;
use Ls\Omni\Client\CentralEcommerce\Entity\RootGetInventoryMultipleOut;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\GiftCardHelper;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\StockHelper;
use Ls\Omni\Helper\StoreHelper;
use Ls\Omni\Helper\VendorSourcingHelper;
use Ls\Omni\Model\Checkout\DataProvider;
use Ls\Omni\Model\VendorSourcing\PickupStoreIntersectionFilter;
use Ls\Omni\Model\VendorSourcing\ZeroStockEverywhereResolver;
use Ls\Core\Model\LSR;
use Ls\Replication\Model\ResourceModel\ReplStore\Collection;
use Ls\Replication\Model\ResourceModel\ReplStore\CollectionFactory;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Unit tests for {@see DataProvider::filterStoresOnTheBasisOfQty()}'s FR8 vendor-sourcing
 * integration point (ticket 89360 code review finding - no prior test coverage reached this
 * logic since the existing integration test (DataProviderTest::testGetConfig()) never enables
 * XPATH_CHECKOUT_ITEM_AVAILABILITY).
 *
 * Written as unit tests (not integration tests) against filterStoresOnTheBasisOfQty() directly:
 * building the full integration fixture chain (replicated stores + Central-backed stock/sourcing
 * calls) for this environment's integration suite would be impractical, and filterStoresOnTheBasisOfQty()
 * is `public` and exercises the exact logic under review without requiring any Central/HTTP calls
 * once its collaborators are mocked. StockHelper is built via getMockBuilder()->onlyMethods([])
 * (no methods mocked - mergeStockCollection() runs for real) with its public `itemHelper`
 * collaborator replaced by a mock, mirroring the DataAssignObserverTest convention for classes
 * with public constructor-promoted properties. PickupStoreIntersectionFilter is `final` (cannot be
 * doubled), so a real instance is used - same convention as PickupStoreIntersectionFilterTest.
 */
class DataProviderTest extends TestCase
{
    /** @var StoreManagerInterface&MockObject */
    private $storeManager;

    /** @var CollectionFactory&MockObject */
    private $storeCollectionFactory;

    /** @var ScopeConfigInterface&MockObject */
    private $scopeConfig;

    /** @var LSR&MockObject */
    private $lsr;

    /** @var Session&MockObject */
    private $checkoutSession;

    /** @var StockHelper&MockObject */
    private $stockHelper;

    /** @var VendorSourcingHelper&MockObject */
    private $vendorSourcingHelper;

    /** @var ZeroStockEverywhereResolver&MockObject */
    private $zeroStockResolver;

    /** @var PickupStoreIntersectionFilter real instance - final, cannot be doubled */
    private $pickupStoreIntersectionFilter;

    /** @var LoggerInterface&MockObject */
    private $logger;

    /** @var Collection&MockObject */
    private $storeCollection;

    /** @var array captured 'nav_id' => [...] filter args across addFieldToFilter() calls */
    private array $capturedFieldFilters = [];

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->storeCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->lsr = $this->createMock(LSR::class);
        $this->checkoutSession = $this->createMock(Session::class);
        $this->vendorSourcingHelper = $this->createMock(VendorSourcingHelper::class);
        $this->zeroStockResolver = $this->createMock(ZeroStockEverywhereResolver::class);
        $this->pickupStoreIntersectionFilter = new PickupStoreIntersectionFilter();
        $this->logger = $this->createMock(LoggerInterface::class);

        // mergeStockCollection() runs for real (it's pure array-building logic); only the
        // network-touching itemHelper->getComparisonValues() collaborator is stubbed.
        $this->stockHelper = $this->getMockBuilder(StockHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->stockHelper->itemHelper = $this->createMock(ItemHelper::class);

        $this->lsr->method('isSSM')->willReturn(false);
        $this->lsr->method('getCurrentWebsiteId')->willReturn(1);

        $this->storeCollection = $this->createMock(Collection::class);
        $this->storeCollection->method('addFieldToFilter')->willReturnCallback(
            function (string $field, $condition) {
                $this->capturedFieldFilters[$field] = $condition;

                return $this->storeCollection;
            }
        );
        $this->storeCollectionFactory->method('create')->willReturn($this->storeCollection);
    }

    private function createDataProvider(): DataProvider
    {
        return new DataProvider(
            $this->storeManager,
            $this->storeCollectionFactory,
            $this->scopeConfig,
            $this->lsr,
            $this->checkoutSession,
            $this->stockHelper,
            $this->createMock(StoreHelper::class),
            $this->createMock(GiftCardHelper::class),
            $this->createMock(BasketHelper::class),
            $this->createMock(LayoutFactory::class),
            $this->vendorSourcingHelper,
            $this->zeroStockResolver,
            $this->pickupStoreIntersectionFilter,
            $this->logger
        );
    }

    private function createResponse(array $bufferRows): RootGetInventoryMultipleOut
    {
        /** @var RootGetInventoryMultipleOut $response */
        $response = (new ReflectionClass(RootGetInventoryMultipleOut::class))->newInstanceWithoutConstructor();
        $response->setData(RootGetInventoryMultipleOut::INVENTORY_BUFFER_OUT, $bufferRows);

        return $response;
    }

    /**
     * A buffer row that is only ever consulted by getAllResponseItemsWithInventoryAvailable()
     * in these tests (the vendor-sourced row never reaches the qty-disqualification loop) - so
     * only Store needs to be meaningful.
     */
    private function createBufferRow(string $store): InventoryBufferOut
    {
        /** @var InventoryBufferOut $row */
        $row = (new ReflectionClass(InventoryBufferOut::class))->newInstanceWithoutConstructor();
        $row->setStore($store);
        $row->setInventory(10.0);

        return $row;
    }

    private function createQuoteItem(string $sku, Product $product, float $qty = 1.0): Item
    {
        $item = $this->createMock(Item::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getSku')->willReturn($sku);
        $item->method('getName')->willReturn($sku);
        $item->method('getQty')->willReturn($qty);
        $item->method('getProduct')->willReturn($product);
        $item->method('getOptionByCode')->willReturn(null);

        return $item;
    }

    /**
     * Critical regression test (FR8 defect): a zero-stock-everywhere, vendor-sourcing-eligible
     * item whose getOptions() lookup throws must NOT propagate the exception out of checkout page
     * load - it must contribute an empty options array to PickupStoreIntersectionFilter, same as
     * CheckoutFallbackService::fetchOptionsForRow()'s existing fail-closed behaviour, and log the
     * failure.
     */
    public function testFilterStoresOnTheBasisOfQtyFailsClosedWhenGetOptionsThrows(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU-1');
        $item = $this->createQuoteItem('SKU-1', $product);

        $this->stockHelper->itemHelper->method('getComparisonValues')
            ->with('SKU-1')
            ->willReturn(['ITEM1', 'VAR1', null, null, null, null]);

        // Zero-stock-everywhere confirmed (no store satisfies the qty).
        $this->zeroStockResolver->method('resolve')->willReturn(['ITEM1|VAR1' => false]);
        $this->vendorSourcingHelper->method('isVendorSourced')->willReturn(true);
        $this->vendorSourcingHelper->expects($this->once())
            ->method('getOptions')
            ->with('SKU-1', null)
            ->willThrowException(new \Exception('Central sourcing lookup failed'));

        $this->logger->expects($this->once())->method('error');

        // S1 has stock of every normal item (there are none here, so it's qty-satisfied
        // trivially) - without the fail-closed fix, this would be a candidate store; with it,
        // the failed sourcing lookup contributes [] to PickupStoreIntersectionFilter, which
        // empties the intersection instead of throwing out of checkout page load.
        $response = $this->createResponse([$this->createBufferRow('S1')]);

        $result = $this->createDataProvider()->filterStoresOnTheBasisOfQty($response, [$item]);

        $this->assertSame($this->storeCollection, $result);
        $this->assertSame(
            [],
            $this->capturedFieldFilters['nav_id']['in'],
            'A failed sourcing lookup must empty the candidate store list, not crash the request'
        );
    }

    /**
     * Companion happy-path test: a successful getOptions() lookup for a zero-stock-everywhere,
     * vendor-sourcing-eligible item must be passed through to PickupStoreIntersectionFilter
     * unmodified, with no error logged.
     */
    public function testFilterStoresOnTheBasisOfQtyPassesThroughOptionsOnSuccess(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSku')->willReturn('SKU-1');
        $item = $this->createQuoteItem('SKU-1', $product);

        $this->stockHelper->itemHelper->method('getComparisonValues')
            ->with('SKU-1')
            ->willReturn(['ITEM1', 'VAR1', null, null, null, null]);

        $this->zeroStockResolver->method('resolve')->willReturn(['ITEM1|VAR1' => false]);
        $this->vendorSourcingHelper->method('isVendorSourced')->willReturn(true);

        $options = [
            [
                'type' => VendorSourcingHelper::TYPE_COLLECT,
                'store_code' => 'S1',
                'store_name' => 'Store 1',
                'days' => 2,
                'date' => '2026-10-21',
                'priority' => 1,
            ],
        ];
        $this->vendorSourcingHelper->method('getOptions')->willReturn($options);

        $this->logger->expects($this->never())->method('error');

        $response = $this->createResponse([$this->createBufferRow('S1')]);

        $result = $this->createDataProvider()->filterStoresOnTheBasisOfQty($response, [$item]);

        $this->assertSame($this->storeCollection, $result);
        $this->assertSame(
            ['S1'],
            $this->capturedFieldFilters['nav_id']['in'],
            'S1 is a valid collect store for the sourced item and qty-satisfied (trivially, no normal rows)'
        );
    }
}
