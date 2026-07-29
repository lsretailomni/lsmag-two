<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Helper;

use Ls\Omni\Client\CentralEcommerce\Entity\MobileTransaction;
use Ls\Omni\Client\CentralEcommerce\Entity\MobileTransactionLine;
use Ls\Omni\Client\CentralEcommerce\Entity\RootMobileTransaction;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\ItemHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for {@see BasketHelper::buildOrderFromMagentoOrderItems()} (ticket 85191).
 *
 * Note on entity types: the requirements/solution-plan docs for this ticket reference
 * `Ls\Omni\Client\Ecommerce\Entity\Order`/`OrderLine` and the `OneListCalculate` SOAP
 * operation. Neither is actually used by this call path in the current codebase - the live
 * remote calculation used by BasketHelper::calculateOneListFromOrder() is
 * BasketHelper::calculate()/update(), which invokes the `EcomCalculateBasket` operation and
 * returns a `RootMobileTransaction` (see BasketHelper::calculateOneListFromOrder(),
 * ::formulateCentralOrderRequestFromMagentoOrder(), and OrderHelper::prepareOrder()'s type
 * hint, all of which agree on RootMobileTransaction). These tests are written against the
 * types actually used by this repo so the new method is a legitimate drop-in replacement at
 * its call sites (OrderObserver, SyncOrders cron), asserting "no remote/SOAP call" as
 * "calculate()/update() (the methods that invoke EcomCalculateBasket) are never called".
 *
 * BasketHelper has a very large promoted constructor (60+ dependencies, inherited from
 * AbstractHelperOmni), so - following the precedent set by
 * Ls\Replication\Test\Unit\Cron\SyncPriceTest - the instance under test is a partial mock
 * (constructor disabled) with only the methods this test needs to control
 * (getOneListAdmin/calculate/update/createInstance) mocked; the method under test itself
 * runs for real. `createInstance()` is stubbed to build real entity objects via
 * ReflectionClass::newInstanceWithoutConstructor() instead of going through Magento's
 * ObjectManager (unavailable in a plain unit test), mirroring how MobileTransactionLine/
 * MobileTransaction/RootMobileTransaction are plain getData()/setData() wrappers around
 * \Magento\Framework\DataObject, which defaults `_data` to `[]` and therefore works
 * correctly even without the constructor running.
 */
class BasketHelperTest extends TestCase
{
    /**
     * @var BasketHelper&MockObject
     */
    private $basketHelper;

    /**
     * @var ItemHelper&MockObject
     */
    private $itemHelper;

    protected function setUp(): void
    {
        $this->basketHelper = $this->getMockBuilder(BasketHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOneListAdmin', 'calculate', 'update', 'createInstance'])
            ->getMock();

        $this->itemHelper = $this->createMock(ItemHelper::class);
        $this->itemHelper->method('getComparisonValues')
            ->willReturnCallback(static function (string $sku) {
                return [$sku . '-ITEM', $sku . '-VARIANT', 'PCS'];
            });
        $this->basketHelper->itemHelper = $this->itemHelper;

        $this->basketHelper->method('createInstance')
            ->willReturnCallback(static function (?string $entityClassName = null, array $data = []) {
                $object = (new ReflectionClass($entityClassName))->newInstanceWithoutConstructor();
                if (!empty($data['data']) && method_exists($object, 'addData')) {
                    $object->addData($data['data']);
                }
                return $object;
            });

        $mobileTransaction = (new ReflectionClass(MobileTransaction::class))->newInstanceWithoutConstructor();
        $mobileTransaction->setStoreid('STORE01')->setMembercardno('CARD01');
        $oneListAdmin = (new ReflectionClass(RootMobileTransaction::class))->newInstanceWithoutConstructor();
        $oneListAdmin->setMobiletransaction($mobileTransaction);

        $this->basketHelper->expects($this->once())
            ->method('getOneListAdmin')
            ->with('jane@example.com', '1', false)
            ->willReturn($oneListAdmin);
    }

    /**
     * @param float $originalPrice regular/original unit price (tax-exclusive baseline)
     * @param float $priceInclTax tax-inclusive unit price actually charged (reflects custom price)
     * @param float $price tax-exclusive unit price actually charged (reflects custom price)
     * @param float $rowTotal tax-exclusive row total
     * @param float $taxAmount
     * @return Item&MockObject
     */
    private function createOrderItem(
        string $sku,
        float $originalPrice,
        float $priceInclTax,
        float $price,
        float $rowTotal,
        float $rowTotalInclTax,
        float $taxAmount = 0.0,
        float $qty = 1.0,
        float $discountAmount = 0.0,
        float $discountPercent = 0.0
    ) {
        $item = $this->createMock(Item::class);
        $item->method('getSku')->willReturn($sku);
        $item->method('getOriginalPrice')->willReturn($originalPrice);
        $item->method('getPriceInclTax')->willReturn($priceInclTax);
        $item->method('getPrice')->willReturn($price);
        $item->method('getRowTotal')->willReturn($rowTotal);
        $item->method('getRowTotalInclTax')->willReturn($rowTotalInclTax);
        $item->method('getTaxAmount')->willReturn($taxAmount);
        $item->method('getDiscountAmount')->willReturn($discountAmount);
        $item->method('getDiscountPercent')->willReturn($discountPercent);
        $item->method('getQtyOrdered')->willReturn($qty);

        return $item;
    }

    /**
     * @param array $items
     * @return Order&MockObject
     */
    private function createOrder(array $items)
    {
        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')->willReturn('1');

        $order = $this->createMock(Order::class);
        $order->method('getAllVisibleItems')->willReturn($items);
        $order->method('getCustomerEmail')->willReturn('jane@example.com');
        $order->method('getStore')->willReturn($store);
        $order->method('getCustomerIsGuest')->willReturn(false);

        return $order;
    }

    /**
     * FR3/FR4: building the order for the bypass path must not touch the remote OneList/basket
     * calculation at all - i.e. calculate()/update() (the methods that invoke the
     * EcomCalculateBasket SOAP operation) must never be called.
     */
    public function testBuildOrderFromMagentoOrderItemsMakesNoRemoteCalls(): void
    {
        $order = $this->createOrder([
            $this->createOrderItem('SKU-1', 10.00, 12.00, 10.00, 10.00, 12.00),
        ]);

        $this->basketHelper->expects($this->never())->method('calculate');
        $this->basketHelper->expects($this->never())->method('update');

        $this->basketHelper->buildOrderFromMagentoOrderItems($order);
    }

    /**
     * FR5: StoreId/CardId must come from getOneListAdmin() (already asserted to be called
     * exactly once, with no remote call, in setUp()) - not from a fresh/duplicate lookup.
     */
    public function testBuildOrderFromMagentoOrderItemsSourcesStoreIdAndCardIdFromOneListAdmin(): void
    {
        $order = $this->createOrder([
            $this->createOrderItem('SKU-1', 10.00, 12.00, 10.00, 10.00, 12.00),
        ]);

        $result = $this->basketHelper->buildOrderFromMagentoOrderItems($order);

        $this->assertInstanceOf(RootMobileTransaction::class, $result);
        $mobileTransaction = $result->getMobiletransaction();
        $this->assertNotNull($mobileTransaction);
        $this->assertSame('STORE01', $mobileTransaction->getStoreid());
        $this->assertSame('CARD01', $mobileTransaction->getMembercardno());
    }

    /**
     * FR4/Edge Case 3: order lines must be built from getAllVisibleItems() with the
     * tax-inclusive/exclusive mapping preserved exactly as the existing, proven
     * getOrderLinesQuote()/formulateCentralOrderRequestFromMagentoOrder() pattern uses:
     * Price = tax-inclusive, NetPrice/NetAmount = tax-exclusive. Getting this backwards would
     * double- or zero-tax every line.
     */
    public function testBuildOrderFromMagentoOrderItemsMapsTaxInclusiveAndExclusiveFieldsCorrectly(): void
    {
        // Custom price scenario: admin set a custom price so priceInclTax/price/rowTotal
        // differ from the regular/original price - the bypass must forward these as-is.
        $item = $this->createOrderItem(
            'SKU-CUSTOM',
            25.00, // getOriginalPrice() - catalog/regular price
            18.00, // getPriceInclTax() - admin custom price, tax-inclusive
            15.00, // getPrice() - admin custom price, tax-exclusive
            15.00, // getRowTotal() - tax-exclusive row total
            18.00, // getRowTotalInclTax()
            3.00   // getTaxAmount()
        );
        $order = $this->createOrder([$item]);

        $result = $this->basketHelper->buildOrderFromMagentoOrderItems($order);
        $lines = $result->getMobiletransactionline();

        $this->assertIsArray($lines);
        $this->assertCount(1, $lines);
        /** @var MobileTransactionLine $line */
        $line = $lines[0];
        $this->assertInstanceOf(MobileTransactionLine::class, $line);
        $this->assertSame(18.00, $line->getPrice(), 'Price must be the tax-inclusive unit price');
        $this->assertSame(15.00, $line->getNetprice(), 'NetPrice must be the tax-exclusive unit price');
        $this->assertSame(15.00, $line->getNetamount(), 'NetAmount must be the tax-exclusive row total');
    }

    /**
     * FR4: shipping must NOT be added by this method - it is added later, independently of
     * the price source, by OrderHelper::prepareOrder() -> updateShippingAmount(). If the
     * bypass method also added a shipping line, orders would end up with the shipping charge
     * doubled.
     */
    public function testBuildOrderFromMagentoOrderItemsDoesNotAddAShippingLine(): void
    {
        $order = $this->createOrder([
            $this->createOrderItem('SKU-1', 10.00, 12.00, 10.00, 10.00, 12.00),
            $this->createOrderItem('SKU-2', 20.00, 24.00, 20.00, 20.00, 24.00),
        ]);

        $result = $this->basketHelper->buildOrderFromMagentoOrderItems($order);
        $lines = $result->getMobiletransactionline();

        $this->assertCount(2, $lines, 'Only the two visible order items should produce lines - no shipping line');
    }

    /**
     * Mirrors getOrderLinesQuote()'s fallback (BasketHelper.php ~line 755-758): a fixed-amount
     * cart price rule can leave getDiscountAmount() > 0 while getDiscountPercent() == 0. The
     * bypass line-builder must recompute the percentage from
     * (discountAmount / rowTotalInclTax) * 100 rather than silently omitting the discount line.
     */
    public function testBuildOrderFromMagentoOrderItemsRecomputesDiscountPercentageWhenPercentIsZero(): void
    {
        $item = $this->createOrderItem(
            'SKU-FIXED-DISCOUNT',
            10.00,
            10.00,
            10.00,
            10.00,
            10.00,
            0.0,
            1.0,
            2.00, // getDiscountAmount() - fixed-amount cart price rule
            0.0   // getDiscountPercent() - not populated for fixed-amount rules
        );
        $order = $this->createOrder([$item]);

        $result = $this->basketHelper->buildOrderFromMagentoOrderItems($order);
        $discountLines = $result->getMobiletransdiscountline();

        $this->assertIsArray($discountLines);
        $this->assertCount(1, $discountLines, 'A discount line must still be produced when only the amount is set');
        $discountLine = $discountLines[0];
        $this->assertSame(2.00, $discountLine->getDiscountamount());
        $this->assertSame(20.0, $discountLine->getDiscountpercent(), 'Percent must be recomputed as (2.00 / 10.00) * 100');
    }
}
