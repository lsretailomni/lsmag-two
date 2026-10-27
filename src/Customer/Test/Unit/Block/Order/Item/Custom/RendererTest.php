<?php

declare(strict_types=1);

namespace Ls\Customer\Test\Unit\Block\Order\Item\Custom;

use Ls\Customer\Block\Order\Item\Custom\Renderer;
use Ls\Omni\Client\CentralEcommerce\Entity\GetSelectedSalesDoc_GetSelectedSalesDoc;
use Ls\Omni\Client\CentralEcommerce\Entity\LSCMemberSalesDocLine;
use Ls\Omni\Helper\OrderHelper;
use Magento\Tax\Model\Config as TaxConfig;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @var OrderHelper&MockObject
     */
    private $orderHelper;

    protected function setUp(): void
    {
        $this->renderer = (new ReflectionClass(Renderer::class))->newInstanceWithoutConstructor();
        $this->taxConfig = $this->createMock(TaxConfig::class);
        $this->renderer->taxConfig = $this->taxConfig;
        $this->orderHelper = $this->createMock(OrderHelper::class);
        $this->renderer->orderHelper = $this->orderHelper;
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

    #[DataProvider('estimatedDeliveryTimeProvider')]
    public function testGetEstimatedDeliveryTime(string $leadTime, ?string $expected): void
    {
        $this->renderer->setData('item', $this->createLine('CO1', 10000, $leadTime, 0.0));

        $result = $this->renderer->getEstimatedDeliveryTime();

        $this->assertSame($expected, $result === null ? null : (string)$result);
    }

    public static function estimatedDeliveryTimeProvider(): array
    {
        return [
            'days' => ['2D', '2 days'],
            'single day' => ['1D', '1 day'],
            'weeks converted to days' => ['3W', '21 days'],
            'bare number is days' => ['5', '5 days'],
            'empty' => ['', null],
            'zero' => ['0D', null],
            'unsupported' => ['CM+1D', null],
        ];
    }

    public function testGetQuantityReceivedIsHiddenWithoutLeadTimeAndReceivedQty(): void
    {
        $this->renderer->setData('item', $this->createLine('CO1', 10000, '', 0.0));

        $this->assertNull($this->renderer->getQuantityReceived());
    }

    public function testGetQuantityReceivedIsShownAsZeroWhenLeadTimeIsSet(): void
    {
        $this->renderer->setData('item', $this->createLine('CO1', 10000, '2D', 0.0));

        $this->assertSame('0.00', $this->renderer->getQuantityReceived());
    }

    public function testGetQuantityReceivedReturnsFormattedReceivedQty(): void
    {
        $this->renderer->setData('item', $this->createLine('CO1', 10000, '2D', 1.0));

        $this->assertSame('1.00', $this->renderer->getQuantityReceived());
    }

    public function testCompletedOrderReceiptLineFallsBackToCustomerOrderLine(): void
    {
        $receiptLine = $this->createLine('00000P0072000000001', 10000, '', 0.0);
        $orderLine = $this->createLine('CO000006', 10000, '2D', 1.0);
        $this->setOrderLines([$receiptLine, $orderLine]);
        $this->renderer->setData('item', $receiptLine);

        $this->assertSame('2 days', (string)$this->renderer->getEstimatedDeliveryTime());
        $this->assertSame('1.00', $this->renderer->getQuantityReceived());
    }

    public function testFallbackMatchesLinesOfTheSameProductByPosition(): void
    {
        $receiptA = $this->createLine('RCPT', 10000, '', 0.0);
        $receiptB = $this->createLine('RCPT', 20000, '', 0.0);
        $orderA = $this->createLine('CO1', 10000, '2D', 1.0);
        $orderB = $this->createLine('CO1', 20000, '1W', 3.0);
        $this->setOrderLines([$receiptA, $receiptB, $orderA, $orderB]);

        $this->renderer->setData('item', $receiptB);

        $this->assertSame('7 days', (string)$this->renderer->getEstimatedDeliveryTime());
        $this->assertSame('3.00', $this->renderer->getQuantityReceived());
    }

    public function testFallbackIgnoresOtherProductsAndNonItemEntries(): void
    {
        $receiptLine = $this->createLine('RCPT', 10000, '', 0.0);
        $otherProduct = $this->createLine('CO1', 10000, '2D', 1.0, '99999');
        $shippingEntry = $this->createLine('CO1', 20000, '2D', 1.0, '40425', 7);
        $this->setOrderLines([$receiptLine, $otherProduct, $shippingEntry]);
        $this->renderer->setData('item', $receiptLine);

        $this->assertNull($this->renderer->getEstimatedDeliveryTime());
        $this->assertNull($this->renderer->getQuantityReceived());
    }

    private function createLine(
        string $documentId,
        int $lineNo,
        string $leadTime,
        float $quantityReceived,
        string $number = '40425',
        int $entryType = 0
    ): LSCMemberSalesDocLine {
        $line = (new ReflectionClass(LSCMemberSalesDocLine::class))->newInstanceWithoutConstructor();
        $line->setData([
            LSCMemberSalesDocLine::DOCUMENT_ID => $documentId,
            LSCMemberSalesDocLine::LINE_NO => $lineNo,
            LSCMemberSalesDocLine::ENTRY_TYPE => $entryType,
            LSCMemberSalesDocLine::NUMBER => $number,
            LSCMemberSalesDocLine::VARIANT_CODE => '',
            LSCMemberSalesDocLine::UNIT_OF_MEASURE => 'PCS',
            'Lead Time' => $leadTime,
            'Quantity Received' => $quantityReceived
        ]);

        return $line;
    }

    private function setOrderLines(array $lines): void
    {
        $order = (new ReflectionClass(GetSelectedSalesDoc_GetSelectedSalesDoc::class))->newInstanceWithoutConstructor();
        $order->setLscMemberSalesDocLine($lines);
        $this->orderHelper->method('getOrder')->willReturn($order);
    }
}

