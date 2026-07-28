<?php

namespace Ls\Omni\Test\Unit\Helper;

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

    private function buildLineAndItem($amount, $quantity)
    {
        $line = $this->createMock(OrderLine::class);
        $line->method('getQuantity')->willReturn($quantity);
        $line->method('getAmount')->willReturn($amount);
        $line->method('getDiscountAmount')->willReturn(0.0);
        $line->method('getTaxAmount')->willReturn(0.0);
        $line->method('getNetAmount')->willReturn($amount);
        $line->method('getNetPrice')->willReturn($amount / $quantity);
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
