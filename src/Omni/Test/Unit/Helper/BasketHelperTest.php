<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Helper;

use Ls\Omni\Client\CentralEcommerce\Entity\MobileTransaction;
use Ls\Omni\Client\CentralEcommerce\Entity\MobileTransactionLine;
use Ls\Omni\Client\CentralEcommerce\Entity\RootMobileTransaction;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\ItemHelper;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\Store;
use Magento\Tax\Model\Config as TaxConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for {@see BasketHelper::buildOrderFromMagentoOrderItems()} (ticket 85191) and
 * {@see BasketHelper::getItemRowTotal()}/{@see BasketHelper::getPrice()} (ticket 88392).
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

    /**
     * @var TaxConfig&MockObject
     */
    private $taxConfig;

    protected function setUp(): void
    {
        $this->basketHelper = $this->getMockBuilder(BasketHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOneListAdmin', 'calculate', 'update', 'createInstance', 'getOneListCalculation'])
            ->getMock();

        $this->itemHelper = $this->createMock(ItemHelper::class);
        $this->itemHelper->method('getComparisonValues')
            ->willReturnCallback(static function (string $sku) {
                return [$sku . '-ITEM', $sku . '-VARIANT', 'PCS'];
            });
        $this->basketHelper->itemHelper = $this->itemHelper;

        // Unstubbed methods return null => falsy => tax-exclusive, preserving prior test
        // expectations unless a test opts into incl. tax.
        $this->taxConfig = $this->createMock(TaxConfig::class);
        $this->basketHelper->taxConfig = $this->taxConfig;

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

        $this->basketHelper->method('getOneListAdmin')
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
     * FR5: StoreId/CardId must come from getOneListAdmin() - not from a fresh/duplicate lookup.
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

    /**
     * @param float $netprice tax-exclusive unit price
     * @param float $price tax-inclusive unit price
     * @param float $netamount tax-exclusive row total
     * @param float $taxamount tax amount for the row
     * @return MobileTransactionLine
     */
    private function createOrderLine(
        float $netprice,
        float $price,
        float $netamount,
        float $taxamount,
        float $quantity = 1.0
    ): MobileTransactionLine {
        /** @var MobileTransactionLine $line */
        $line = (new ReflectionClass(MobileTransactionLine::class))->newInstanceWithoutConstructor();
        $line->setNetprice($netprice)
            ->setPrice($price)
            ->setNetamount($netamount)
            ->setTaxamount($taxamount)
            ->setQuantity($quantity)
            ->setNumber('SKU-1-ITEM')
            ->setVariantcode('SKU-1-VARIANT');

        return $line;
    }

    /**
     * Ticket 88392: US customers (real sales tax) saw tax-inclusive prices where Magento
     * expects the tax-exclusive row total. getItemRowTotal() must source the row total from
     * NetAmount alone, never NetAmount + TaxAmount (which is the tax-inclusive gross amount).
     */
    public function testGetItemRowTotalUsesTaxExclusiveNetAmountNotGrossAmount(): void
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getSku')->willReturn('SKU-1');
        $item->method('getQty')->willReturn(1.0);
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getData')->with('uom')->willReturn('PCS');
        $item->method('getProduct')->willReturn($product);

        $line = $this->createOrderLine(15.00, 18.00, 15.00, 3.00);

        $this->basketHelper->expects($this->once())
            ->method('getOneListCalculation')
            ->willReturnCallback(function () use ($line) {
                $basketData = (new ReflectionClass(RootMobileTransaction::class))->newInstanceWithoutConstructor();
                $basketData->setMobiletransactionline([$line]);
                return $basketData;
            });
        $this->itemHelper->method('isValid')->willReturn(true);

        $rowTotal = $this->basketHelper->getItemRowTotal($item);

        $this->assertSame(15.00, $rowTotal, 'Row total must be the tax-exclusive NetAmount, not NetAmount + TaxAmount');
    }

    /**
     * Ticket 88392: getPrice() must source the unit price from NetPrice (tax-exclusive), not
     * Price (tax-inclusive) - otherwise the "excl. tax" price shown is actually tax-inclusive.
     */
    public function testGetPriceUsesTaxExclusiveNetPriceNotTaxInclusivePrice(): void
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getSku')->willReturn('SKU-1');
        $item->method('getQty')->willReturn(1.0);
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getData')->with('uom')->willReturn('PCS');
        $item->method('getProduct')->willReturn($product);

        $line = $this->createOrderLine(15.00, 18.00, 15.00, 3.00);

        $this->basketHelper->expects($this->once())
            ->method('getOneListCalculation')
            ->willReturnCallback(function () use ($line) {
                $basketData = (new ReflectionClass(RootMobileTransaction::class))->newInstanceWithoutConstructor();
                $basketData->setMobiletransactionline([$line]);
                return $basketData;
            });
        $this->itemHelper->method('isValid')->willReturn(true);
        $this->basketHelper->basketHelper = $this->basketHelper;

        $price = $this->basketHelper->getPrice($item);

        $this->assertSame(15.00, $price, 'Price must be the tax-exclusive NetPrice, not the tax-inclusive Price');
    }

    /**
     * When "Display Cart Subtotal" is configured for Including Tax, getItemRowTotal() must
     * source the row total from NetAmount + TaxAmount (tax-inclusive), not NetAmount alone.
     */
    public function testGetItemRowTotalUsesTaxInclusiveAmountWhenConfigIsInclTax(): void
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getSku')->willReturn('SKU-1');
        $item->method('getQty')->willReturn(1.0);
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getData')->with('uom')->willReturn('PCS');
        $item->method('getProduct')->willReturn($product);

        $line = $this->createOrderLine(15.00, 18.00, 15.00, 3.00);

        $this->basketHelper->expects($this->once())
            ->method('getOneListCalculation')
            ->willReturnCallback(function () use ($line) {
                $basketData = (new ReflectionClass(RootMobileTransaction::class))->newInstanceWithoutConstructor();
                $basketData->setMobiletransactionline([$line]);
                return $basketData;
            });
        $this->itemHelper->method('isValid')->willReturn(true);
        $this->taxConfig->method('displayCartSubtotalInclTax')->willReturn(true);

        $rowTotal = $this->basketHelper->getItemRowTotal($item);

        $this->assertSame(18.00, $rowTotal, 'Row total must be NetAmount + TaxAmount when config is incl. tax');
    }

    /**
     * When "Display Cart Subtotal" is configured for Including Tax, getPrice() must source the
     * unit price from Price (tax-inclusive), not NetPrice.
     */
    public function testGetPriceUsesTaxInclusivePriceWhenConfigIsInclTax(): void
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getSku')->willReturn('SKU-1');
        $item->method('getQty')->willReturn(1.0);
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getData')->with('uom')->willReturn('PCS');
        $item->method('getProduct')->willReturn($product);

        $line = $this->createOrderLine(15.00, 18.00, 15.00, 3.00);

        $this->basketHelper->expects($this->once())
            ->method('getOneListCalculation')
            ->willReturnCallback(function () use ($line) {
                $basketData = (new ReflectionClass(RootMobileTransaction::class))->newInstanceWithoutConstructor();
                $basketData->setMobiletransactionline([$line]);
                return $basketData;
            });
        $this->itemHelper->method('isValid')->willReturn(true);
        $this->basketHelper->basketHelper = $this->basketHelper;
        $this->taxConfig->method('displayCartSubtotalInclTax')->willReturn(true);

        $price = $this->basketHelper->getPrice($item);

        $this->assertSame(18.00, $price, 'Price must be the tax-inclusive Price when config is incl. tax');
    }

    /**
     * An explicit $inclTax argument must always override the config default.
     */
    public function testGetItemRowTotalExplicitInclTaxArgumentOverridesConfig(): void
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getSku')->willReturn('SKU-1');
        $item->method('getQty')->willReturn(1.0);
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getData')->with('uom')->willReturn('PCS');
        $item->method('getProduct')->willReturn($product);

        $line = $this->createOrderLine(15.00, 18.00, 15.00, 3.00);

        $this->basketHelper->expects($this->once())
            ->method('getOneListCalculation')
            ->willReturnCallback(function () use ($line) {
                $basketData = (new ReflectionClass(RootMobileTransaction::class))->newInstanceWithoutConstructor();
                $basketData->setMobiletransactionline([$line]);
                return $basketData;
            });
        $this->itemHelper->method('isValid')->willReturn(true);
        // Config says exclusive, but the explicit argument must still win.
        $this->taxConfig->method('displayCartSubtotalInclTax')->willReturn(false);

        $rowTotal = $this->basketHelper->getItemRowTotal($item, true);

        $this->assertSame(18.00, $rowTotal, 'Explicit $inclTax = true must override the config default');
    }

    /**
     * Cart item price-fallback fix: getItemUnitPrice() must return the per-unit price (row
     * total ÷ qty), not the row total itself - otherwise the cart item "Price" cell duplicates
     * the "Subtotal" cell for any item with qty > 1.
     */
    public function testGetItemUnitPriceDividesRowTotalByQty(): void
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getSku')->willReturn('SKU-1');
        $item->method('getQty')->willReturn(2.0);
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getData')->with('uom')->willReturn('PCS');
        $item->method('getProduct')->willReturn($product);

        $line = $this->createOrderLine(15.00, 18.00, 30.00, 6.00, 2.0);

        $this->basketHelper->expects($this->once())
            ->method('getOneListCalculation')
            ->willReturnCallback(function () use ($line) {
                $basketData = (new ReflectionClass(RootMobileTransaction::class))->newInstanceWithoutConstructor();
                $basketData->setMobiletransactionline([$line]);
                return $basketData;
            });
        $this->itemHelper->method('isValid')->willReturn(true);

        $unitPrice = $this->basketHelper->getItemUnitPrice($item, false);

        $this->assertSame(
            15.00,
            $unitPrice,
            'getItemUnitPrice() must be the row total (30.00 for qty 2) divided by qty, not the row total itself'
        );
    }

    /**
     * getItemUnitPriceIncludeCustomOptions() is the per-unit counterpart to
     * getItemPriceIncludeCustomOptions() (getPrice()), used for the strikethrough
     * original-price display alongside getItemUnitPrice() - it must also be qty-divided so both
     * values shown together in the "Price" cell are on the same per-unit scale.
     */
    public function testGetItemUnitPriceIncludeCustomOptionsDividesPriceByQty(): void
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getProductType')->willReturn('simple');
        $item->method('getSku')->willReturn('SKU-1');
        $item->method('getQty')->willReturn(2.0);
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getData')->with('uom')->willReturn('PCS');
        $item->method('getProduct')->willReturn($product);

        $line = $this->createOrderLine(15.00, 18.00, 30.00, 6.00, 2.0);

        $this->basketHelper->expects($this->once())
            ->method('getOneListCalculation')
            ->willReturnCallback(function () use ($line) {
                $basketData = (new ReflectionClass(RootMobileTransaction::class))->newInstanceWithoutConstructor();
                $basketData->setMobiletransactionline([$line]);
                return $basketData;
            });
        $this->itemHelper->method('isValid')->willReturn(true);
        $this->basketHelper->basketHelper = $this->basketHelper;

        $unitPrice = $this->basketHelper->getItemUnitPriceIncludeCustomOptions($item, false);

        $this->assertSame(
            15.00,
            $unitPrice,
            'getItemUnitPriceIncludeCustomOptions() must be getPrice() (15.00 netprice x qty 2 = 30.00) divided by qty'
        );
    }

    /**
     * Defensive: getItemUnitPrice() must not divide by zero for a (theoretically impossible)
     * zero-qty item.
     */
    public function testGetItemUnitPriceReturnsZeroWhenQtyIsZero(): void
    {
        $item = $this->createMock(QuoteItem::class);
        $item->method('getQty')->willReturn(0.0);

        $unitPrice = $this->basketHelper->getItemUnitPrice($item, false);

        $this->assertSame(0.0, $unitPrice, 'getItemUnitPrice() must return 0.0, not divide by zero, when qty is 0');
    }

    /**
     * getItemUnitDiscount() must divide the whole-line getDiscountAmount() (set by a prior
     * getItemRowDiscount() call) by qty, so the "Save X" label reconciles with the per-unit
     * price/strikethrough it's displayed alongside.
     */
    public function testGetItemUnitDiscountDividesRowDiscountByQty(): void
    {
        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQty'])
            ->addMethods(['getDiscountAmount'])
            ->getMock();
        $item->method('getQty')->willReturn(2.0);
        $item->method('getDiscountAmount')->willReturn(10.0);

        $unitDiscount = $this->basketHelper->getItemUnitDiscount($item);

        $this->assertSame(
            5.0,
            $unitDiscount,
            'getItemUnitDiscount() must be the whole-line discount (10.00 for qty 2) divided by qty'
        );
    }

    /**
     * Defensive: getItemUnitDiscount() must not divide by zero for a (theoretically impossible)
     * zero-qty item.
     */
    public function testGetItemUnitDiscountReturnsZeroWhenQtyIsZero(): void
    {
        $item = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQty'])
            ->addMethods(['getDiscountAmount'])
            ->getMock();
        $item->method('getQty')->willReturn(0.0);
        $item->method('getDiscountAmount')->willReturn(10.0);

        $unitDiscount = $this->basketHelper->getItemUnitDiscount($item);

        $this->assertSame(
            0.0,
            $unitDiscount,
            'getItemUnitDiscount() must return 0.0, not divide by zero, when qty is 0'
        );
    }
}
