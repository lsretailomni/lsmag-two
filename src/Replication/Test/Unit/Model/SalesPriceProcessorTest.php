<?php

declare(strict_types=1);

namespace Ls\Replication\Test\Unit\Model;

use Ls\Replication\Model\Central\ReplSalesPrice;
use Ls\Replication\Model\SalesPriceProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SalesPriceProcessor.
 */
class SalesPriceProcessorTest extends TestCase
{
    /**
     * @var SalesPriceProcessor
     */
    private $model;

    protected function setUp(): void
    {
        $this->model = new SalesPriceProcessor();
    }

    /**
     * @return ReplSalesPrice&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRecord(?string $startingDate, ?string $endingDate = null)
    {
        $record = $this->createMock(ReplSalesPrice::class);
        $record->method('getStartingDate')->willReturn($startingDate);
        $record->method('getEndingDate')->willReturn($endingDate);

        return $record;
    }

    public function testIsFutureSalesPriceWithNoStartDate_ReturnsFalse(): void
    {
        $record = $this->createRecord(null);

        $this->assertFalse($this->model->isFutureSalesPrice($record));
    }

    public function testIsFutureSalesPriceWithFutureStartDate_ReturnsTrue(): void
    {
        $record = $this->createRecord(date('Y-m-d', strtotime('+1 day')));

        $this->assertTrue($this->model->isFutureSalesPrice($record));
    }

    public function testIsFutureSalesPriceWithPastStartDate_ReturnsFalse(): void
    {
        $record = $this->createRecord(date('Y-m-d', strtotime('-1 day')));

        $this->assertFalse($this->model->isFutureSalesPrice($record));
    }

    public function testIsValidSalesPriceWithNoDates_ReturnsTrue(): void
    {
        $record = $this->createRecord(null, null);

        $this->assertTrue($this->model->isValidSalesPrice($record));
    }

    public function testIsValidSalesPriceWithFutureStartOnly_ReturnsFalse(): void
    {
        $record = $this->createRecord(date('Y-m-d', strtotime('+1 day')), null);

        $this->assertFalse($this->model->isValidSalesPrice($record));
    }

    public function testIsValidSalesPriceWithExpiredEndDate_ReturnsFalse(): void
    {
        $record = $this->createRecord(
            date('Y-m-d', strtotime('-10 day')),
            date('Y-m-d', strtotime('-1 day'))
        );

        $this->assertFalse($this->model->isValidSalesPrice($record));
    }

    public function testIsValidSalesPriceWithCurrentDateInRange_ReturnsTrue(): void
    {
        $record = $this->createRecord(
            date('Y-m-d', strtotime('-1 day')),
            date('Y-m-d', strtotime('+1 day'))
        );

        $this->assertTrue($this->model->isValidSalesPrice($record));
    }

    /**
     * Verify the processor never depends on getStatus(): the ReplSalesPrice
     * entity has no such method, so the mock must never receive that call.
     */
    public function testIsValidSalesPriceWithNoStatusCheck(): void
    {
        $record = $this->createMock(ReplSalesPrice::class);
        $record->method('getStartingDate')->willReturn(date('Y-m-d', strtotime('-1 day')));
        $record->method('getEndingDate')->willReturn(date('Y-m-d', strtotime('+1 day')));
        $record->expects($this->never())->method('__call')->with('getStatus');

        $this->assertTrue($this->model->isValidSalesPrice($record));
        $this->assertFalse(
            method_exists($record, 'getStatus'),
            'ReplSalesPrice must not expose getStatus().'
        );
    }

    /**
     * Build a lightweight candidate exposing the getters that selectBestPrice() reads.
     *
     * A ReplSalespriceview-like contract that is not backed by a concrete class yet,
     * so an anonymous object is used to model the exact method surface under test.
     *
     * @param string|null $currencyCode
     * @param string|null $startingDate
     * @param string $priceListCode
     * @param int $lineNumber
     * @param float $unitPriceInclVat
     * @return object
     */
    private function createCandidate(
        ?string $currencyCode,
        ?string $startingDate,
        string $priceListCode = 'PL001',
        int $lineNumber = 0,
        float $unitPriceInclVat = 0.0
    ): object {
        return new class ($currencyCode, $startingDate, $priceListCode, $lineNumber, $unitPriceInclVat) {
            public function __construct(
                private readonly ?string $currencyCode,
                private readonly ?string $startingDate,
                private readonly string $priceListCode,
                private readonly int $lineNumber,
                private readonly float $unitPriceInclVat
            ) {
            }

            public function getCurrencyCode(): ?string
            {
                return $this->currencyCode;
            }

            public function getStartingDate(): ?string
            {
                return $this->startingDate;
            }

            public function getPriceListCode(): string
            {
                return $this->priceListCode;
            }

            public function getLineNumber(): int
            {
                return $this->lineNumber;
            }

            public function getUnitPriceInclVat(): float
            {
                return $this->unitPriceInclVat;
            }
        };
    }

    public function testSelectBestPrice_CurrencySpecificWinsOverBlank(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $candidateA = $this->createCandidate('USD', $yesterday, 'PL001', 10, 100.0);
        $candidateB = $this->createCandidate('', $yesterday, 'PL001', 10, 50.0);

        $winner = $this->model->selectBestPrice([$candidateA, $candidateB]);

        $this->assertSame($candidateA, $winner);
    }

    public function testSelectBestPrice_BothBlankCurrency_NearestDateWins(): void
    {
        $candidateA = $this->createCandidate('', date('Y-m-d', strtotime('-1 day')), 'PL001', 10, 100.0);
        $candidateB = $this->createCandidate('', date('Y-m-d', strtotime('-3 days')), 'PL001', 10, 50.0);

        $winner = $this->model->selectBestPrice([$candidateA, $candidateB]);

        $this->assertSame($candidateA, $winner);
    }

    public function testSelectBestPrice_NearestStartDateWins(): void
    {
        $candidateA = $this->createCandidate('', '2026-06-30', 'PL001', 10, 200.0);
        $candidateB = $this->createCandidate('', '2026-06-25', 'PL001', 10, 100.0);

        $winner = $this->model->selectBestPrice([$candidateA, $candidateB]);

        $this->assertSame($candidateA, $winner);
    }

    public function testSelectBestPrice_NullStartDateLosesToDatedLine(): void
    {
        $candidateA = $this->createCandidate('', null, 'PL001', 10, 50.0);
        $candidateB = $this->createCandidate('', '2026-06-30', 'PL001', 10, 200.0);

        $winner = $this->model->selectBestPrice([$candidateA, $candidateB]);

        $this->assertSame($candidateB, $winner);
    }

    public function testSelectBestPrice_BothNullStartDate_TiebreakerApplies(): void
    {
        $candidateA = $this->createCandidate('', null, 'PL001', 100, 50.0);
        $candidateB = $this->createCandidate('', null, 'PL001', 200, 100.0);

        $winner = $this->model->selectBestPrice([$candidateA, $candidateB]);

        $this->assertSame($candidateB, $winner);
    }

    public function testSelectBestPrice_SameDateDifferentPriceListCode_HigherCodeWins(): void
    {
        $candidateA = $this->createCandidate('', '2026-06-30', 'PL002', 10, 50.0);
        $candidateB = $this->createCandidate('', '2026-06-30', 'PL001', 10, 100.0);

        $winner = $this->model->selectBestPrice([$candidateA, $candidateB]);

        $this->assertSame($candidateA, $winner);
    }

    public function testSelectBestPrice_NeverPicksLowestPrice(): void
    {
        $candidateA = $this->createCandidate('', '2026-06-30', 'PL001', 50, 999.0);
        $candidateB = $this->createCandidate('', '2026-06-25', 'PL001', 50, 1.0);

        $winner = $this->model->selectBestPrice([$candidateA, $candidateB]);

        $this->assertSame($candidateA, $winner);
    }

    public function testSelectBestPrice_EmptyArray_ReturnsNull(): void
    {
        $this->assertNull($this->model->selectBestPrice([]));
    }

    public function testSelectBestPrice_SingleCandidate_ReturnsThatCandidate(): void
    {
        $candidate = $this->createCandidate('USD', date('Y-m-d', strtotime('-1 day')), 'PL001', 10, 100.0);

        $this->assertSame($candidate, $this->model->selectBestPrice([$candidate]));
    }

    public function testSelectBestPrice_NullCurrencyFallsIntoBlankPool(): void
    {
        // Both candidates have null currency — should fall into blank pool and use date tiebreaker
        $a = $this->createCandidate(null, '2026-06-30', 'PL001', 10, 100.0);
        $b = $this->createCandidate(null, '2026-06-25', 'PL001', 10, 50.0);
        // A wins: nearest date (2026-06-30 > 2026-06-25), not lowest price
        $this->assertSame($a, $this->model->selectBestPrice([$a, $b]));
    }

    public function testSelectBestPrice_NonMatchingCurrencyExcluded_FallsBackToBlank(): void
    {
        // Scenario 5: store currency = USD. Line B has EUR — not matching USD.
        // Expected: blank-currency Line A wins as fallback. EUR line must NOT win.
        $a = $this->createCandidate('', '2026-01-01', 'PL001', 10, 40.0);    // blank currency
        $b = $this->createCandidate('EUR', '2026-01-01', 'PL001', 10, 60.0); // non-matching specific currency

        $winner = $this->model->selectBestPrice([$a, $b], 'USD');

        $this->assertSame($a, $winner, 'Non-matching specific currency (EUR) must not win when store uses USD. Blank-currency line is the fallback.');
    }

    public function testSelectBestPrice_MatchingCurrencyWinsOverBlank(): void
    {
        // Scenario 4 with explicit store currency: USD line wins over blank when store=USD
        $a = $this->createCandidate('', '2026-01-01', 'PL001', 10, 40.0);    // blank currency
        $b = $this->createCandidate('USD', '2026-01-01', 'PL001', 10, 60.0); // matches store currency

        $winner = $this->model->selectBestPrice([$a, $b], 'USD');

        $this->assertSame($b, $winner, 'Matching currency (USD) must win over blank when store uses USD.');
    }
}
