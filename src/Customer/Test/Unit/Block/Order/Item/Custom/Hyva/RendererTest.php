<?php

declare(strict_types=1);

namespace Ls\Customer\Test\Unit\Block\Order\Item\Custom\Hyva;

use Ls\Customer\Block\Order\Item\Custom\Hyva\Renderer;
use Magento\Tax\Model\Config as TaxConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for the Hyva theme's {@see Renderer} tax-display predicates
 * (order-invoice-item-price-subtotal-tax-consistency fix), mirroring
 * Ls\Customer\Test\Unit\Block\Order\Item\Custom\RendererTest for the standalone Hyva duplicate
 * class wired via hyva_customer_order_invoice.xml/creditmemo/shipment.
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
}