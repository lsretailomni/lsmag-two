<?php

namespace Ls\Omni\Test\Unit\Helper;

use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Client\Ecommerce\Entity\OrderLine;
use Ls\Omni\Helper\ItemHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\ResourceModel\Quote\Item as ItemResourceModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Verifies setRelatedAmountsAgainstGivenQuoteItem() populates base_price for admin order-create
 * (type=2) only, leaving the storefront/default path (type=1) unchanged. Magento's own tax engine
 * is intentionally disabled whenever LSR is active (Ls\Omni\Model\Sales\Total\Quote\Subtotal
 * aroundCollect on Magento\Tax\Model\Sales\Total\Quote\Tax), so base_price would otherwise never
 * be populated for admin-created orders.
 */
class ItemHelperTest extends TestCase
{
    /** @var ItemHelper|MockObject */
    private $itemHelper;

    protected function setUp(): void
    {
        $this->itemHelper = $this->getMockBuilder(ItemHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['convertToBaseCurrency'])
            ->getMock();
        $this->itemHelper->method('convertToBaseCurrency')->willReturnArgument(0);
    }

    /**
     * @param float $amount Tax-inclusive line amount (drives getAmount()/getPrice()).
     * @param float $quantity
     * @param float|null $netAmount Tax-exclusive line amount (drives getNetAmount()/getNetPrice()).
     *                              Defaults to $amount so pre-existing callers that only care about
     *                              a single value keep passing (inclusive == exclusive).
     */
    private function buildLineAndItem($amount, $quantity, $netAmount = null)
    {
        $netAmount = $netAmount ?? $amount;

        $line = $this->createMock(OrderLine::class);
        $line->method('getQuantity')->willReturn($quantity);
        $line->method('getAmount')->willReturn($amount);
        $line->method('getDiscountAmount')->willReturn(0.0);
        $line->method('getTaxAmount')->willReturn(0.0);
        $line->method('getNetAmount')->willReturn($netAmount);
        $line->method('getNetPrice')->willReturn($netAmount / $quantity);
        $line->method('getPrice')->willReturn($amount / $quantity);

        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getPrice')->willReturn($amount);

        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQty', 'getParentItem', 'getProduct', 'setCustomPrice'])
            ->addMethods(['setOriginalCustomPrice', 'setTaxAmount', 'setBaseTaxAmount',
                 'setPriceInclTax', 'setBasePriceInclTax', 'setLsDiscountAmount', 'setRowTotal',
                 'setBaseRowTotal', 'setRowTotalInclTax', 'setBaseRowTotalInclTax', 'setBasePrice'])
            ->getMock();
        $item->method('getQty')->willReturn($quantity);
        $item->method('getParentItem')->willReturn(null);
        $item->method('getProduct')->willReturn($product);
        foreach (['setCustomPrice', 'setOriginalCustomPrice', 'setTaxAmount', 'setBaseTaxAmount',
                  'setPriceInclTax', 'setBasePriceInclTax', 'setLsDiscountAmount', 'setRowTotal',
                  'setBaseRowTotal', 'setRowTotalInclTax', 'setBaseRowTotalInclTax', 'setBasePrice'] as $method) {
            $item->method($method)->willReturnSelf();
        }

        return [$line, $item];
    }

    public function testSetsBasePriceForAdminType()
    {
        [$line, $item] = $this->buildLineAndItem(20.0, 1.0);
        $unitPrice = 20.0;

        $item->expects($this->once())
            ->method('setBasePrice')
            ->with(20.0)
            ->willReturnSelf();

        $this->itemHelper->setRelatedAmountsAgainstGivenQuoteItem($line, $item, $unitPrice, 2);
    }

    public function testDoesNotSetBasePriceForDefaultType()
    {
        [$line, $item] = $this->buildLineAndItem(20.0, 1.0);
        $unitPrice = 20.0;

        $item->expects($this->never())->method('setBasePrice');

        $this->itemHelper->setRelatedAmountsAgainstGivenQuoteItem($line, $item, $unitPrice, 1);
    }

    /**
     * FR4/solution-plan-88392 §2a-§2b: setPriceInclTax()/setBasePriceInclTax() must always receive
     * the line's tax-INCLUSIVE unit price (getPrice()), never the $unitPrice parameter verbatim.
     * getPrice() (20.0) is deliberately different from the passed-in $unitPrice (18.0, simulating
     * the exclusive value the FR1-fixed caller now passes) so this fails if the two ever get
     * conflated, i.e. if setPriceInclTax() naively forwards $unitPrice instead of sourcing the
     * inclusive value directly from $line.
     */
    public function testSetPriceInclTaxUsesTaxInclusiveLinePriceNotTheExclusiveUnitPrice()
    {
        [$line, $item] = $this->buildLineAndItem(20.0, 1.0, 18.0);
        $unitPriceExclTax = 18.0;

        $item->expects($this->once())
            ->method('setPriceInclTax')
            ->with(20.0)
            ->willReturnSelf();
        $item->expects($this->once())
            ->method('setBasePriceInclTax')
            ->with(20.0)
            ->willReturnSelf();

        $this->itemHelper->setRelatedAmountsAgainstGivenQuoteItem($line, $item, $unitPriceExclTax, 1);
    }

    /**
     * Regression guard for FR1 (solution-plan-88392 §2b, row 3 of the table): setBasePrice()
     * (type==2, admin order-create) must receive whatever tax-exclusive unit price it is handed,
     * distinct from the line's tax-inclusive getPrice()/getAmount() (20.0). The real FR1 fix lives
     * in the caller (compareQuoteItemsWithOrderLinesAndSetRelatedAmounts() — covered separately by
     * testCompareQuoteItemsWithOrderLinesUsesNetPriceForUnitPrice()); this method only forwards the
     * value it is given, so this test documents/guards that forwarding is not itself broken by the
     * §2b split-variable fix.
     */
    public function testSetBasePriceUsesTheExclusiveUnitPriceForAdminType()
    {
        [$line, $item] = $this->buildLineAndItem(20.0, 1.0, 18.0);
        $unitPriceExclTax = 18.0;

        $item->expects($this->once())
            ->method('setBasePrice')
            ->with(18.0)
            ->willReturnSelf();

        $this->itemHelper->setRelatedAmountsAgainstGivenQuoteItem($line, $item, $unitPriceExclTax, 2);
    }

    /**
     * FR1/solution-plan-88392 §2b: compareQuoteItemsWithOrderLinesAndSetRelatedAmounts() must derive
     * $unitPrice from $line->getNetPrice() (tax-exclusive), not $line->getAmount() / $line->getQuantity()
     * (tax-inclusive). getAmount()/getQuantity() (20.0/2.0 = 10.0) is deliberately different from
     * getNetPrice() (9.0) so this fails if the caller keeps deriving the inclusive value.
     *
     * isSameItem() and setRelatedAmountsAgainstGivenQuoteItem() are stubbed out on the partial mock
     * so this test is isolated to the $unitPrice computation at the call site, not the full matching
     * or downstream field-setting logic (covered by the other tests in this class).
     */
    public function testCompareQuoteItemsWithOrderLinesUsesNetPriceForUnitPrice()
    {
        $itemHelper = $this->getMockBuilder(ItemHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['convertToBaseCurrency', 'isSameItem', 'setRelatedAmountsAgainstGivenQuoteItem'])
            ->getMock();
        $itemHelper->method('convertToBaseCurrency')->willReturnArgument(0);
        $itemHelper->method('isSameItem')->willReturn(true);

        $itemResourceModel = $this->createMock(ItemResourceModel::class);
        $itemResourceModel->method('save')->willReturnSelf();
        $reflection = new \ReflectionProperty(ItemHelper::class, 'itemResourceModel');
        $reflection->setAccessible(true);
        $reflection->setValue($itemHelper, $itemResourceModel);

        $line = $this->createMock(OrderLine::class);
        $line->method('getId')->willReturn('');          // non-numeric -> routes through isSameItem()
        $line->method('getQuantity')->willReturn(2.0);
        $line->method('getAmount')->willReturn(20.0);     // tax-inclusive; getAmount()/getQuantity() = 10.0
        $line->method('getNetPrice')->willReturn(9.0);    // tax-exclusive unit price (correct post-fix value)

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsSuperMode'])
            ->getMock();
        $product->method('setIsSuperMode')->willReturnSelf();

        $quoteItem = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType', 'getProduct'])
            ->getMock();
        $quoteItem->method('getProductType')->willReturn('simple');
        $quoteItem->method('getProduct')->willReturn($product);

        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$quoteItem]);

        $orderLines = $this->createMock(Entity\ArrayOfOrderLine::class);
        $orderLines->method('getOrderLine')->willReturn([$line]);

        $basketData = $this->createMock(Entity\Order::class);
        $basketData->method('getOrderLines')->willReturn($orderLines);

        $itemHelper->expects($this->once())
            ->method('setRelatedAmountsAgainstGivenQuoteItem')
            ->with($line, $quoteItem, 9.0, 1);

        $itemHelper->compareQuoteItemsWithOrderLinesAndSetRelatedAmounts($quote, $basketData, 1);
    }

    public function testSetBaseCurrencyFieldsFromItemPricePopulatesFieldsAndSaves()
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->addMethods(['setIsSuperMode'])
            ->getMock();
        $product->expects($this->once())->method('setIsSuperMode')->with(true);

        // getPrice() (catalog, 24) deliberately differs from getCalculationPrice() (custom
        // price, 20) to verify the fix uses the applied custom price, not the raw catalog price.
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPrice', 'getCalculationPrice', 'getProduct'])
            ->addMethods(['getRowTotal', 'setBasePrice', 'setPriceInclTax', 'setBasePriceInclTax', 'setRowTotalInclTax'])
            ->getMock();
        $item->method('getPrice')->willReturn(24.0);
        $item->method('getCalculationPrice')->willReturn(20.0);
        $item->method('getRowTotal')->willReturn(20.0);
        $item->method('getProduct')->willReturn($product);
        foreach (['setBasePrice', 'setPriceInclTax', 'setBasePriceInclTax', 'setRowTotalInclTax'] as $method) {
            $item->method($method)->willReturnSelf();
        }
        $item->expects($this->once())->method('setBasePrice')->with(20.0)->willReturnSelf();
        $item->expects($this->once())->method('setPriceInclTax')->with(20.0)->willReturnSelf();
        $item->expects($this->once())->method('setBasePriceInclTax')->with(20.0)->willReturnSelf();
        $item->expects($this->once())->method('setRowTotalInclTax')->with(20.0)->willReturnSelf();

        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$item]);

        $itemResourceModel = $this->createMock(ItemResourceModel::class);
        $itemResourceModel->expects($this->once())->method('save')->with($item);

        $reflection = new \ReflectionProperty(ItemHelper::class, 'itemResourceModel');
        $reflection->setAccessible(true);
        $reflection->setValue($this->itemHelper, $itemResourceModel);

        $this->itemHelper->setBaseCurrencyFieldsFromItemPrice($quote);
    }
}
