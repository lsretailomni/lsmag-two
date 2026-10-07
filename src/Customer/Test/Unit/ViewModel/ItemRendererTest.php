<?php

declare(strict_types=1);

namespace Ls\Customer\Test\Unit\ViewModel;

use Ls\Customer\ViewModel\ItemRenderer;
use Magento\Tax\Model\Config as TaxConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for {@see ItemRenderer}'s tax-display predicates
 * (order-invoice-item-price-subtotal-tax-consistency fix): each of the 6 predicates must
 * delegate to the matching Magento\Tax\Model\Config method, driving the Price/Subtotal
 * Incl/Excl/Both branching in order/item/renderer/default.phtml, default-hyva.phtml and
 * bundle.phtml (Order View, native-Magento-item path).
 */
class ItemRendererTest extends TestCase
{
    /**
     * @var ItemRenderer
     */
    private $itemRenderer;

    /**
     * @var TaxConfig&MockObject
     */
    private $taxConfig;

    protected function setUp(): void
    {
        $this->itemRenderer = (new ReflectionClass(ItemRenderer::class))->newInstanceWithoutConstructor();
        $this->taxConfig = $this->createMock(TaxConfig::class);
        $this->itemRenderer->taxConfig = $this->taxConfig;
    }

    public function testIsDisplaySalesPricesInclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesPricesInclTax')->willReturn(true);
        $this->assertTrue($this->itemRenderer->isDisplaySalesPricesInclTax());
    }

    public function testIsDisplaySalesPricesExclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesPricesExclTax')->willReturn(true);
        $this->assertTrue($this->itemRenderer->isDisplaySalesPricesExclTax());
    }

    public function testIsDisplaySalesPricesBothDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesPricesBoth')->willReturn(true);
        $this->assertTrue($this->itemRenderer->isDisplaySalesPricesBoth());
    }

    public function testIsDisplaySalesSubtotalInclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesSubtotalInclTax')->willReturn(true);
        $this->assertTrue($this->itemRenderer->isDisplaySalesSubtotalInclTax());
    }

    public function testIsDisplaySalesSubtotalExclTaxDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesSubtotalExclTax')->willReturn(true);
        $this->assertTrue($this->itemRenderer->isDisplaySalesSubtotalExclTax());
    }

    public function testIsDisplaySalesSubtotalBothDelegatesToTaxConfig(): void
    {
        $this->taxConfig->method('displaySalesSubtotalBoth')->willReturn(true);
        $this->assertTrue($this->itemRenderer->isDisplaySalesSubtotalBoth());
    }

    /**
     * Ticket 88392: LS Central doesn't fold tax into Amount for US sales-tax stores (Amount
     * comes back equal to NetAmount), so the "Incl. Tax" spans must add VatAmount explicitly.
     */
    public function testGetInclTaxAmountAddsVatAmountToAmount(): void
    {
        $this->assertSame(80.56, $this->itemRenderer->getInclTaxAmount(76.0, 4.56));
    }

    public function testGetInclTaxAmountTreatsNullVatAmountAsZero(): void
    {
        $this->assertSame(76.0, $this->itemRenderer->getInclTaxAmount(76.0, null));
    }
}