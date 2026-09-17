<?php

namespace Ls\Omni\Test\Unit\Helper;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\ItemHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit test coverage for BasketHelper::buildOrderFromMagentoOrderItems() and the private
 * BasketHelper::buildOrderLinesFromOrderItems() (work item #85191,
 * solution-plan-85191-custom-order-price.md §4).
 *
 * TDD note: neither method exists yet. These tests are expected to FAIL (undefined method errors)
 * until the production code is implemented.
 *
 * BasketHelper has no constructor of its own — it inherits AbstractHelperOmni's large constructor,
 * whose dependencies (itemHelper, customerFactory, lsr, contactHelper, ...) are all public,
 * promoted properties. Rather than wiring the full constructor, this suite builds a partial mock
 * with the original constructor disabled (so no real dependency needs to be supplied) and then
 * assigns only the public properties the methods under test actually touch — same pattern already
 * used by SyncPriceTest/LSRTest in this repo for large, promoted-property-heavy classes.
 *
 * `calculate()` (BasketHelper.php:895) is the *only* call site in this class that reaches
 * `Operation\OneListCalculate::execute()` (confirmed by grep — `update()` merely delegates to
 * `calculate()`). The partial mock therefore stubs `calculate()` and asserts it is never invoked, as
 * a proxy for "no remote OneListCalculate SOAP call was made".
 */
class BasketHelperTest extends TestCase
{
    /**
     * @var BasketHelper|MockObject
     */
    private $basketHelper;

    /**
     * @var ItemHelper|MockObject
     */
    private $itemHelper;

    /**
     * @var LSR|MockObject
     */
    private $lsr;

    /**
     * @var CustomerFactory|MockObject
     */
    private $customerFactory;

    public function setUp(): void
    {
        $this->itemHelper = $this->createMock(ItemHelper::class);
        $this->lsr = $this->createMock(LSR::class);
        $this->customerFactory = $this->createMock(CustomerFactory::class);

        // Partial mock: `calculate()` (the sole gateway to the remote OneListCalculate SOAP call)
        // and `getOneListCalculation()` (used by getItemRowTotal()/getPrice() to fetch the cached
        // basket comparison data without a remote call in tests) are stubbed. Everything else
        // (buildOrderFromMagentoOrderItems(), buildOrderLinesFromOrderItems(), getOneListAdmin(),
        // _offers()) runs as real production code once implemented.
        $this->basketHelper = $this->getMockBuilder(BasketHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['calculate', 'getOneListCalculation'])
            ->getMock();

        // Public, promoted properties inherited from AbstractHelperOmni — assigned directly since
        // the constructor (which would normally set them) is disabled above.
        $this->basketHelper->itemHelper = $this->itemHelper;
        $this->basketHelper->lsr = $this->lsr;
        $this->basketHelper->customerFactory = $this->customerFactory;
    }

    /**
     * @param array $overrides
     * @return OrderItem|MockObject
     */
    private function makeOrderItem(array $overrides = [])
    {
        $defaults = [
            'sku'              => 'ITEM001',
            'originalPrice'    => 100.0,
            'priceInclTax'     => 100.0,
            'qtyOrdered'       => 2.0,
            'rowTotalInclTax'  => 200.0,
            'rowTotal'         => 180.0,
            'price'            => 90.0,
            'taxAmount'        => 20.0,
            'discountAmount'   => 0.0,
            'discountPercent'  => 0.0,
        ];
        $data = array_merge($defaults, $overrides);

        $orderItem = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getSku',
                'getOriginalPrice',
                'getPriceInclTax',
                'getQtyOrdered',
                'getRowTotalInclTax',
                'getRowTotal',
                'getPrice',
                'getTaxAmount',
                'getDiscountAmount',
                'getDiscountPercent',
            ])
            ->getMock();

        $orderItem->method('getSku')->willReturn($data['sku']);
        $orderItem->method('getOriginalPrice')->willReturn($data['originalPrice']);
        $orderItem->method('getPriceInclTax')->willReturn($data['priceInclTax']);
        $orderItem->method('getQtyOrdered')->willReturn($data['qtyOrdered']);
        $orderItem->method('getRowTotalInclTax')->willReturn($data['rowTotalInclTax']);
        $orderItem->method('getRowTotal')->willReturn($data['rowTotal']);
        $orderItem->method('getPrice')->willReturn($data['price']);
        $orderItem->method('getTaxAmount')->willReturn($data['taxAmount']);
        $orderItem->method('getDiscountAmount')->willReturn($data['discountAmount']);
        $orderItem->method('getDiscountPercent')->willReturn($data['discountPercent']);

        return $orderItem;
    }

    /**
     * @param OrderItem[] $visibleItems
     * @param bool $isGuest
     * @return Order|MockObject
     */
    private function makeOrder(array $visibleItems, bool $isGuest = true)
    {
        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getWebsiteId'])
            ->getMock();
        $store->method('getWebsiteId')->willReturn(1);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerEmail', 'getStore', 'getCustomerIsGuest', 'getAllVisibleItems'])
            ->getMock();
        $order->method('getCustomerEmail')->willReturn('customer@example.com');
        $order->method('getStore')->willReturn($store);
        $order->method('getCustomerIsGuest')->willReturn($isGuest);
        $order->method('getAllVisibleItems')->willReturn($visibleItems);

        return $order;
    }

    /**
     * FR3/Evidence §2.4-2.5/Acceptance Criteria #2: building the order from Magento order items must
     * never trigger the remote OneListCalculate SOAP call.
     */
    public function testBuildOrderFromMagentoOrderItemsNeverCallsRemoteCalculate(): void
    {
        $this->itemHelper->method('getComparisonValues')->willReturn(['IT001', 'VAR001', 'PCS']);
        $this->lsr->method('getWebsiteConfig')->willReturn('10101');

        $order = $this->makeOrder([$this->makeOrderItem()]);

        $this->basketHelper->expects($this->never())->method('calculate');

        $this->basketHelper->buildOrderFromMagentoOrderItems($order);
    }

    /**
     * FR5/Evidence §2.5: StoreId/CardId must come from getOneListAdmin() — i.e. from
     * $this->lsr->getWebsiteConfig(LSR::SC_SERVICE_STORE, $websiteId) and (for non-guests) the
     * customer's lsr_cardid — with zero remote calls, identical to how calculateOneListFromOrder()
     * resolves the same two fields.
     */
    public function testBuildOrderFromMagentoOrderItemsSetsStoreIdAndCardIdFromOneListAdmin(): void
    {
        $this->itemHelper->method('getComparisonValues')->willReturn(['IT001', 'VAR001', 'PCS']);
        $this->lsr->expects($this->once())
            ->method('getWebsiteConfig')
            ->with(LSR::SC_SERVICE_STORE, 1)
            ->willReturn('10101');

        // Guest order: getOneListAdmin() must not attempt any customer lookup at all.
        $this->customerFactory->expects($this->never())->method('create');

        $order = $this->makeOrder([$this->makeOrderItem()], true);

        $result = $this->basketHelper->buildOrderFromMagentoOrderItems($order);

        $this->assertInstanceOf(Entity\Order::class, $result);
        $this->assertSame('10101', $result->getStoreId());
        $this->assertSame('', $result->getCardId());
    }

    /**
     * Non-guest customer with an already-synced lsr_cardid: CardId must be sourced from the
     * customer record via getOneListAdmin(), exactly as the non-bypass path already does — no new
     * remote sync call.
     */
    public function testBuildOrderFromMagentoOrderItemsSetsCardIdFromSyncedCustomer(): void
    {
        $this->itemHelper->method('getComparisonValues')->willReturn(['IT001', 'VAR001', 'PCS']);
        $this->lsr->method('getWebsiteConfig')->willReturn('10101');

        // setWebsiteId() is not a declared method on Customer (it's handled by DataObject::__call()
        // magic set*/get* dispatch), so it cannot be listed in onlyMethods() — it is left as real
        // (harmless) DataObject behavior; only the DB-touching loadByEmail() and getData() are
        // stubbed.
        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByEmail', 'getData'])
            ->getMock();
        $customer->method('loadByEmail')->willReturnSelf();
        $customer->method('getData')->with('lsr_cardid')->willReturn('CARD-123');

        $this->customerFactory->method('create')->willReturn($customer);

        $order = $this->makeOrder([$this->makeOrderItem()], false);

        $result = $this->basketHelper->buildOrderFromMagentoOrderItems($order);

        $this->assertSame('CARD-123', $result->getCardId());
    }

    /**
     * Core field-mapping / tax-direction assertion (Edge Case §6.3): setPrice() must carry the
     * tax-INCLUSIVE unit price (getPriceInclTax()), setNetPrice()/setNetAmount() the tax-EXCLUSIVE
     * figures (getPrice()/getRowTotal()). Getting this backwards double- or zero-taxes every line.
     * Uses order-item data with no discount, so the price/discount branch logic in §4.3 is a no-op
     * and the only thing under test is the tax-inclusive/exclusive field mapping.
     */
    public function testBuildOrderLinesFromOrderItemsMapsTaxFieldsCorrectly(): void
    {
        $this->itemHelper->expects($this->once())
            ->method('getComparisonValues')
            ->with('ITEM001')
            ->willReturn(['IT001', 'VAR001', 'PCS']);

        $orderItem = $this->makeOrderItem();
        $order = $this->makeOrder([$orderItem]);

        $method = new ReflectionMethod(BasketHelper::class, 'buildOrderLinesFromOrderItems');
        $method->setAccessible(true);
        /** @var Entity\ArrayOfOrderLine $arrayOfOrderLine */
        $arrayOfOrderLine = $method->invoke($this->basketHelper, $order);

        $this->assertInstanceOf(Entity\ArrayOfOrderLine::class, $arrayOfOrderLine);
        $lines = $arrayOfOrderLine->getOrderLine();
        $this->assertCount(1, $lines);

        /** @var Entity\OrderLine $line */
        $line = $lines[0];
        $this->assertSame(10000, $line->getLineNumber());
        $this->assertSame('IT001', $line->getItemId());
        $this->assertSame('VAR001', $line->getVariantId());
        $this->assertSame('PCS', $line->getUomId());
        $this->assertSame('', $line->getId());
        $this->assertSame(Entity\Enum\LineType::ITEM, $line->getLineType());
        $this->assertSame(2.0, $line->getQuantity());

        // Amount/NetAmount: row totals, discount-adjusted.
        $this->assertSame(200.0, $line->getAmount());
        $this->assertSame(180.0, $line->getNetAmount());

        // The critical tax-direction assertion: Price is tax-inclusive, NetPrice is tax-exclusive.
        $this->assertSame(100.0, $line->getPrice(), 'setPrice() must use the tax-inclusive unit price');
        $this->assertSame(90.0, $line->getNetPrice(), 'setNetPrice() must use the tax-exclusive unit price');
        $this->assertSame(20.0, $line->getTaxAmount());
    }

    /**
     * Multiple visible order items must be assigned sequential line numbers (10000, 20000, ...),
     * mirroring getOrderLinesQuote()'s numbering scheme (BasketHelper.php:247-309).
     */
    public function testBuildOrderLinesFromOrderItemsAssignsSequentialLineNumbers(): void
    {
        $this->itemHelper->method('getComparisonValues')->willReturn(['IT001', 'VAR001', 'PCS']);

        $orderItemOne = $this->makeOrderItem(['sku' => 'ITEM001']);
        $orderItemTwo = $this->makeOrderItem(['sku' => 'ITEM002']);
        $order = $this->makeOrder([$orderItemOne, $orderItemTwo]);

        $method = new ReflectionMethod(BasketHelper::class, 'buildOrderLinesFromOrderItems');
        $method->setAccessible(true);
        /** @var Entity\ArrayOfOrderLine $arrayOfOrderLine */
        $arrayOfOrderLine = $method->invoke($this->basketHelper, $order);

        $lines = $arrayOfOrderLine->getOrderLine();
        $this->assertCount(2, $lines);
        $this->assertSame(10000, $lines[0]->getLineNumber());
        $this->assertSame(20000, $lines[1]->getLineNumber());
    }

    /**
     * Edge Case §6.1 (no shipping line): buildOrderLinesFromOrderItems() must build lines only from
     * getAllVisibleItems() — it must never append a shipping/service line. That responsibility
     * belongs solely to OrderHelper::updateShippingAmount(), unrelated to this method.
     */
    public function testBuildOrderLinesFromOrderItemsDoesNotAddShippingLine(): void
    {
        $this->itemHelper->method('getComparisonValues')->willReturn(['IT001', 'VAR001', 'PCS']);

        $order = $this->makeOrder([$this->makeOrderItem()]);

        $method = new ReflectionMethod(BasketHelper::class, 'buildOrderLinesFromOrderItems');
        $method->setAccessible(true);
        /** @var Entity\ArrayOfOrderLine $arrayOfOrderLine */
        $arrayOfOrderLine = $method->invoke($this->basketHelper, $order);

        $lines = $arrayOfOrderLine->getOrderLine();
        $this->assertCount(1, $lines, 'Only visible order items should produce lines, no shipping line appended');
        foreach ($lines as $line) {
            $this->assertSame(Entity\Enum\LineType::ITEM, $line->getLineType());
        }
    }

    /**
     * FR2/solution-plan-88392 §1a: getItemRowTotal() must compute the row total from
     * $line->getNetAmount() (tax-exclusive), not $line->getAmount() (tax-inclusive). getAmount()
     * (24.0) is deliberately different from getNetAmount() (20.0) so this fails if the wrong field
     * is used.
     */
    public function testGetItemRowTotalUsesNetAmountNotAmount(): void
    {
        $line = $this->createMock(Entity\OrderLine::class);
        $line->method('getQuantity')->willReturn(2.0);
        $line->method('getAmount')->willReturn(24.0);      // tax-inclusive row total
        $line->method('getNetAmount')->willReturn(20.0);   // tax-exclusive row total

        $orderLines = $this->createMock(Entity\ArrayOfOrderLine::class);
        $orderLines->method('getOrderLine')->willReturn([$line]);

        $basketData = $this->createMock(Entity\Order::class);
        $basketData->method('getOrderLines')->willReturn($orderLines);

        $this->basketHelper->method('getOneListCalculation')->willReturn($basketData);

        $this->itemHelper->method('getComparisonValues')->willReturn(['IT001', 'VAR001', 'PCS']);
        $this->itemHelper->method('isValid')->willReturn(true);

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getData')->willReturn('PCS');

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType', 'getProduct', 'getSku', 'getQty'])
            ->addMethods(['getRowTotalInclTax'])
            ->getMock();
        $item->method('getProductType')->willReturn('simple');
        $item->method('getProduct')->willReturn($product);
        $item->method('getSku')->willReturn('ITEM001');
        $item->method('getQty')->willReturn(2.0);
        $item->method('getRowTotalInclTax')->willReturn(24.0);

        $result = $this->basketHelper->getItemRowTotal($item);

        $this->assertSame(20.0, $result, 'getItemRowTotal() must use getNetAmount(), not getAmount()');
    }

    /**
     * FR3/solution-plan-88392 §1b: getPrice() must return $line->getNetPrice() (tax-exclusive), not
     * $line->getPrice() (tax-inclusive). getPrice() (12.0) is deliberately different from
     * getNetPrice() (10.0) so this fails if the wrong field is used.
     */
    public function testGetPriceUsesNetPriceNotPrice(): void
    {
        $line = $this->createMock(Entity\OrderLine::class);
        $line->method('getPrice')->willReturn(12.0);      // tax-inclusive unit price
        $line->method('getNetPrice')->willReturn(10.0);   // tax-exclusive unit price

        $orderLines = $this->createMock(Entity\ArrayOfOrderLine::class);
        $orderLines->method('getOrderLine')->willReturn([$line]);

        $basketData = $this->createMock(Entity\Order::class);
        $basketData->method('getOrderLines')->willReturn($orderLines);

        $this->basketHelper->method('getOneListCalculation')->willReturn($basketData);
        // getPrice() finishes with $this->basketHelper->getPriceAddingCustomOptions(...) — the
        // real (unmocked) implementation, so the inherited `basketHelper` proxy property (normally
        // wired by the disabled constructor) must be set for that call to succeed.
        $this->basketHelper->basketHelper = $this->basketHelper;

        $this->itemHelper->method('getComparisonValues')->willReturn(['IT001', 'VAR001', 'PCS']);
        $this->itemHelper->method('isValid')->willReturn(true);

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getData')->willReturn('PCS');

        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType', 'getProduct', 'getSku', 'getQty', 'getPrice'])
            ->getMock();
        $item->method('getProductType')->willReturn('simple');
        $item->method('getProduct')->willReturn($product);
        $item->method('getSku')->willReturn('ITEM001');
        $item->method('getQty')->willReturn(1.0);
        $item->method('getPrice')->willReturn(12.0);      // fallback value, must not be returned

        $result = $this->basketHelper->getPrice($item);

        $this->assertSame(10.0, $result, 'getPrice() must use getNetPrice(), not getPrice()');
    }
}
