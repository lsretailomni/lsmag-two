<?php

namespace Ls\Customer\Test\Unit\Block\Order\Item\Custom;

use Ls\Customer\Block\Order\Item\Custom\Renderer;
use Magento\Tax\Model\Config as TaxConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test coverage for the tax-display predicate methods added to
 * Ls\Customer\Block\Order\Item\Custom\Renderer (order-invoice-item-price-subtotal-tax-consistency).
 *
 * Renderer extends Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer, whose own constructor
 * chain pulls in a large Context dependency graph. Rather than wiring the full constructor, this
 * suite builds a partial mock with the original constructor disabled and assigns only the public
 * `taxConfig` property the methods under test actually touch - same pattern already used by
 * BasketHelperTest in this repo for large, promoted-property-heavy classes.
 */
class RendererTest extends TestCase
{
    /**
     * @var Renderer|MockObject
     */
    private $renderer;

    /**
     * @var TaxConfig|MockObject
     */
    private $taxConfig;

    public function setUp(): void
    {
        $this->taxConfig = $this->createMock(TaxConfig::class);

        $this->renderer = $this->getMockBuilder(Renderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->renderer->taxConfig = $this->taxConfig;
    }

    public function testIsDisplaySalesPricesInclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesPricesInclTax')
            ->willReturn(true);

        $this->assertTrue($this->renderer->isDisplaySalesPricesInclTax());
    }

    public function testIsDisplaySalesPricesExclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesPricesExclTax')
            ->willReturn(true);

        $this->assertTrue($this->renderer->isDisplaySalesPricesExclTax());
    }

    public function testIsDisplaySalesPricesBothDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesPricesBoth')
            ->willReturn(true);

        $this->assertTrue($this->renderer->isDisplaySalesPricesBoth());
    }

    public function testIsDisplaySalesSubtotalInclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesSubtotalInclTax')
            ->willReturn(true);

        $this->assertTrue($this->renderer->isDisplaySalesSubtotalInclTax());
    }

    public function testIsDisplaySalesSubtotalExclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesSubtotalExclTax')
            ->willReturn(true);

        $this->assertTrue($this->renderer->isDisplaySalesSubtotalExclTax());
    }

    public function testIsDisplaySalesSubtotalBothDelegatesToTaxConfig(): void
    {
        $this->taxConfig->expects($this->once())
            ->method('displaySalesSubtotalBoth')
            ->willReturn(true);

        $this->assertTrue($this->renderer->isDisplaySalesSubtotalBoth());
    }

    public function testPredicatesReturnFalseWhenTaxConfigMethodsReturnFalsy(): void
    {
        // Unstubbed mock methods return null (falsy) - the predicates must cast to bool.
        $this->assertFalse($this->renderer->isDisplaySalesPricesInclTax());
        $this->assertFalse($this->renderer->isDisplaySalesSubtotalInclTax());
    }
}
