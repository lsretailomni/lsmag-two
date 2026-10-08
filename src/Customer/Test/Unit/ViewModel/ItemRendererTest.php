<?php

namespace Ls\Customer\Test\Unit\ViewModel;

use Ls\Customer\ViewModel\ItemRenderer;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\OrderHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Model\Config as TaxConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test coverage for the tax-display predicate methods added to
 * Ls\Customer\ViewModel\ItemRenderer (order-invoice-item-price-subtotal-tax-consistency).
 */
class ItemRendererTest extends TestCase
{
    /**
     * @var ItemRenderer
     */
    private $itemRenderer;

    /**
     * @var TaxConfig|MockObject
     */
    private $taxConfig;

    public function setUp(): void
    {
        $itemHelper = $this->createMock(ItemHelper::class);
        $orderHelper = $this->createMock(OrderHelper::class);
        $priceCurrency = $this->createMock(PriceCurrencyInterface::class);
        $basketHelper = $this->getMockBuilder(BasketHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->taxConfig = $this->createMock(TaxConfig::class);

        $this->itemRenderer = new ItemRenderer(
            $itemHelper,
            $orderHelper,
            $priceCurrency,
            $basketHelper,
            $this->taxConfig
        );
    }

    public function testIsDisplaySalesPricesInclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesPricesInclTax')
            ->willReturn(true);

        $this->assertTrue($this->itemRenderer->isDisplaySalesPricesInclTax());
    }

    public function testIsDisplaySalesPricesExclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesPricesExclTax')
            ->willReturn(true);

        $this->assertTrue($this->itemRenderer->isDisplaySalesPricesExclTax());
    }

    public function testIsDisplaySalesPricesBothDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesPricesBoth')
            ->willReturn(true);

        $this->assertTrue($this->itemRenderer->isDisplaySalesPricesBoth());
    }

    public function testIsDisplaySalesSubtotalInclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesSubtotalInclTax')
            ->willReturn(true);

        $this->assertTrue($this->itemRenderer->isDisplaySalesSubtotalInclTax());
    }

    public function testIsDisplaySalesSubtotalExclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesSubtotalExclTax')
            ->willReturn(true);

        $this->assertTrue($this->itemRenderer->isDisplaySalesSubtotalExclTax());
    }

    public function testIsDisplaySalesSubtotalBothDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesSubtotalBoth')
            ->willReturn(true);

        $this->assertTrue($this->itemRenderer->isDisplaySalesSubtotalBoth());
    }

    public function testPredicatesReturnFalseWhenTaxConfigMethodsReturnFalsy(): void
    {
        // Unstubbed mock methods return null (falsy) - the predicates must cast to bool.
        $this->assertFalse($this->itemRenderer->isDisplaySalesPricesInclTax());
        $this->assertFalse($this->itemRenderer->isDisplaySalesSubtotalInclTax());
    }
}