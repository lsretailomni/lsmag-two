<?php

declare(strict_types=1);

namespace Ls\Customer\Test\Unit\Block\Order\Item\Custom;

use Ls\Customer\Block\Order\Item\Custom\Renderer;
use Magento\Tax\Model\Config as TaxConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for {@see Renderer}'s tax-display predicates (order-invoice-item-price-subtotal-
 * tax-consistency fix): each of the 6 predicates must delegate to the matching
 * Magento\Tax\Model\Config method, driving the Price/Subtotal Incl/Excl/Both branching in
 * order/item/custom/renderer.phtml (Order View fallback path, Invoice, Credit Memo, Shipment
 * tabs and their Print variants).
 */
class RendererTest extends TestCase
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var TaxConfig&MockObject
     */
    private $taxConfig;

    protected function setUp(): void
    {
        $this->renderer = (new ReflectionClass(Renderer::class))->newInstanceWithoutConstructor();
        $this->taxConfig = $this->createMock(TaxConfig::class);
        $this->renderer->taxConfig = $this->taxConfig;
    }

    public function testIsDisplaySalesPricesInclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesPricesInclTax')->willReturn(true);
        $this->assertTrue($this->renderer->isDisplaySalesPricesInclTax());
    }

    public function testIsDisplaySalesPricesExclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesPricesExclTax')->willReturn(true);
        $this->assertTrue($this->renderer->isDisplaySalesPricesExclTax());
    }

    public function testIsDisplaySalesPricesBothDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesPricesBoth')->willReturn(true);
        $this->assertTrue($this->renderer->isDisplaySalesPricesBoth());
    }

    public function testIsDisplaySalesSubtotalInclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesSubtotalInclTax')->willReturn(true);
        $this->assertTrue($this->renderer->isDisplaySalesSubtotalInclTax());
    }

    public function testIsDisplaySalesSubtotalExclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesSubtotalExclTax')->willReturn(true);
        $this->assertTrue($this->renderer->isDisplaySalesSubtotalExclTax());
    }

    public function testIsDisplaySalesSubtotalBothDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesSubtotalBoth')->willReturn(true);
        $this->assertTrue($this->renderer->isDisplaySalesSubtotalBoth());
    }

    /**
     * Ticket 88392: LS Central doesn't fold tax into Amount for US sales-tax stores (Amount
     * comes back equal to NetAmount), so the "Incl. Tax" spans must add VatAmount explicitly.
     */
    public function testGetInclTaxAmountAddsVatAmountToAmount(): void
    {
        $this->assertSame(80.56, $this->renderer->getInclTaxAmount(76.0, 4.56));
    }

    public function testGetInclTaxAmountTreatsNullVatAmountAsZero(): void
    {
        $this->assertSame(76.0, $this->renderer->getInclTaxAmount(76.0, null));
    }
}