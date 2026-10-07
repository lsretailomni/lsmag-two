<?php

declare(strict_types=1);

namespace Ls\Customer\Test\Unit\Block\Order;

use Ls\Core\Model\LSR;
use Ls\Customer\Block\Order\Totals;
use Ls\Omni\Client\CentralEcommerce\Entity\LSCMemberSalesBuffer;
use Ls\Omni\Client\CentralEcommerce\Entity\LSCMemberSalesDocLine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for {@see Totals}'s tax-aware total calculations (ticket 88392).
 *
 * LS Central's header Gross Amount is tax-inclusive for VAT-style stores (differs from Net
 * Amount by the tax amount) but comes back equal to Net Amount for US sales-tax stores, where
 * tax is tracked only per-line (VatAmount). getGrandTotal()/getSubtotal() must add the computed
 * tax only in the latter case, to avoid double-counting in the former.
 */
class TotalsTest extends TestCase
{
    private const SHIPMENT_SKU = 'SHIP';

    /**
     * @var Totals&MockObject
     */
    private $totals;

    protected function setUp(): void
    {
        $this->totals = $this->getMockBuilder(Totals::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems', 'getCurrentTransaction', 'getLoyaltyGiftCardInfo'])
            ->getMock();
        $this->totals->method('getLoyaltyGiftCardInfo')->willReturn([]);

        $lsr = $this->createMock(LSR::class);
        $lsr->method('getStoreConfig')->with(LSR::LSR_SHIPMENT_ITEM_ID)->willReturn(self::SHIPMENT_SKU);
        $lsr->method('getCurrentStoreId')->willReturn(1);
        $this->totals->lsr = $lsr;
    }

    /**
     * @param string $number
     * @param float $amount
     * @param float $netAmount
     * @param float|null $vatAmount
     * @return LSCMemberSalesDocLine
     */
    private function createOrderLine(
        string $number,
        float $amount,
        float $netAmount,
        ?float $vatAmount
    ): LSCMemberSalesDocLine {
        /** @var LSCMemberSalesDocLine $line */
        $line = (new ReflectionClass(LSCMemberSalesDocLine::class))->newInstanceWithoutConstructor();
        $line->setNumber($number)
            ->setAmount($amount)
            ->setNetAmount($netAmount)
            ->setVatAmount($vatAmount);

        return $line;
    }

    /**
     * @param float $grossAmount
     * @param float $netAmount
     * @param float $discountAmount
     * @return LSCMemberSalesBuffer
     */
    private function createHeaderBuffer(
        float $grossAmount,
        float $netAmount,
        float $discountAmount = 0.0
    ): LSCMemberSalesBuffer {
        /** @var LSCMemberSalesBuffer $buffer */
        $buffer = (new ReflectionClass(LSCMemberSalesBuffer::class))->newInstanceWithoutConstructor();
        $buffer->setGrossAmount($grossAmount)
            ->setNetAmount($netAmount)
            ->setDiscountAmount($discountAmount);

        return $buffer;
    }

    public function testGetTotalTaxSumsVatAmountAcrossOrderLines(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([]);
        $this->totals->method('getItems')->willReturn([
            $this->createOrderLine('SKU1', 2.50, 2.50, 2.50),
            $this->createOrderLine('SKU2', 1.25, 1.25, 1.25),
        ]);

        $this->assertSame(3.75, $this->totals->getTotalTax());
    }

    public function testGetTotalTaxTreatsNullVatAmountAsZero(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([]);
        $this->totals->method('getItems')->willReturn([
            $this->createOrderLine('SKU1', 0.0, 0.0, null),
            $this->createOrderLine('SKU2', 4.00, 4.00, 4.00),
        ]);

        $this->assertSame(4.0, $this->totals->getTotalTax());
    }

    public function testGetTotalTaxReturnsZeroWhenNoOrderLines(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([]);
        $this->totals->method('getItems')->willReturn([]);

        $this->assertSame(0.0, $this->totals->getTotalTax());
    }

    /**
     * VAT-style case: Gross Amount (107) already differs from Net Amount (100) by the tax
     * amount, so getTotalTax() must use Gross - Net directly (7) rather than trusting every
     * line's VatAmount to be populated for this tax model.
     */
    public function testGetTotalTaxUsesGrossMinusNetWhenFolded(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([$this->createHeaderBuffer(107.0, 100.0)]);

        $this->assertSame(7.0, $this->totals->getTotalTax());
    }

    public function testIsTaxFoldedIntoAmountFalseWhenGrossEqualsNet(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([$this->createHeaderBuffer(100.0, 100.0)]);

        $this->assertFalse($this->totals->isTaxFoldedIntoAmount());
    }

    public function testIsTaxFoldedIntoAmountTrueWhenGrossDiffersFromNet(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([$this->createHeaderBuffer(107.0, 100.0)]);

        $this->assertTrue($this->totals->isTaxFoldedIntoAmount());
    }

    public function testIsTaxFoldedIntoAmountFalseWhenNoTransaction(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([]);

        $this->assertFalse($this->totals->isTaxFoldedIntoAmount());
    }

    /**
     * US sales-tax case: Gross Amount (100) equals Net Amount, so getGrandTotal() must add the
     * summed line VatAmount (6 merchandise + 1 shipping = 7) itself.
     */
    public function testGetGrandTotalAddsTaxWhenNotFolded(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([$this->createHeaderBuffer(100.0, 100.0)]);
        $this->totals->method('getItems')->willReturn([
            $this->createOrderLine('SKU1', 80.0, 80.0, 6.0),
            $this->createOrderLine(self::SHIPMENT_SKU, 20.0, 20.0, 1.0),
        ]);

        $this->assertSame(107.0, $this->totals->getGrandTotal());
    }

    /**
     * VAT-style case: Gross Amount (107) already differs from Net Amount (100) by the tax
     * amount, so getGrandTotal() must trust it as-is and NOT add tax again.
     */
    public function testGetGrandTotalTrustsGrossWhenAlreadyFolded(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([$this->createHeaderBuffer(107.0, 100.0)]);
        $this->totals->method('getItems')->willReturn([
            $this->createOrderLine('SKU1', 86.0, 80.0, 6.0),
            $this->createOrderLine(self::SHIPMENT_SKU, 21.0, 20.0, 1.0),
        ]);

        $this->assertSame(107.0, $this->totals->getGrandTotal());
    }

    public function testGetGrandTotalReturnsZeroWhenNoTransaction(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([]);

        $this->assertSame(0.0, $this->totals->getGrandTotal());
    }

    /**
     * US sales-tax case: Subtotal must be the merchandise amount plus its own tax (80 + 6 = 86),
     * excluding the Shipment line's amount (20) and tax (1) which getShipmentChargeLineFee()
     * doesn't fold in for this tax model.
     */
    public function testGetSubtotalForNotFoldedCase(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([$this->createHeaderBuffer(100.0, 100.0)]);
        $this->totals->method('getItems')->willReturn([
            $this->createOrderLine('SKU1', 80.0, 80.0, 6.0),
            $this->createOrderLine(self::SHIPMENT_SKU, 20.0, 20.0, 1.0),
        ]);

        $this->assertSame(86.0, $this->totals->getSubtotal());
    }

    /**
     * VAT-style case: Shipment line's Amount (21) is already tax-inclusive, so Subtotal is
     * obtained by removing it from the (already tax-inclusive) Gross Amount directly, without
     * subtracting the Shipment tax a second time.
     */
    public function testGetSubtotalForFoldedCase(): void
    {
        $this->totals->method('getCurrentTransaction')->willReturn([$this->createHeaderBuffer(107.0, 100.0)]);
        $this->totals->method('getItems')->willReturn([
            $this->createOrderLine('SKU1', 86.0, 80.0, 6.0),
            $this->createOrderLine(self::SHIPMENT_SKU, 21.0, 20.0, 1.0),
        ]);

        $this->assertSame(86.0, $this->totals->getSubtotal());
    }
}