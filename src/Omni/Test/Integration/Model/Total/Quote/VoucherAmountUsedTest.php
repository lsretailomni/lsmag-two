<?php
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Model\Total\Quote;

use Ls\Omni\Model\Total\Quote\VoucherAmountUsed;
use Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Integration tests for VoucherAmountUsed::collect() and fetch().
 *
 * VoucherAmountUsed is the single collector that deducts ALL ls_pos_data_entries
 * (gift cards + vouchers) from the grand total.  GiftCardAmountUsed::collect()
 * is intentionally a no-op so there is no double-deduction.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class VoucherAmountUsedTest extends AbstractIntegrationTest
{
    private const CODE = 'ls_entry_amount';

    public $objectManager;
    public VoucherAmountUsed $collector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->collector     = $this->objectManager->create(VoucherAmountUsed::class);
        $this->collector->setCode(self::CODE);
    }

    private function makeQuote(array $entries): Quote
    {
        $quote = $this->objectManager->create(Quote::class);
        $quote->setData('ls_pos_data_entries', empty($entries) ? null : json_encode($entries));
        return $quote;
    }

    private function makeTotal(): Total
    {
        return $this->objectManager->create(Total::class);
    }

    // -------------------------------------------------------------------------
    // collect()
    // -------------------------------------------------------------------------

    /**
     * @magentoAppIsolation enabled
     */
    public function testCollectDeductsAllEntries(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'GIFTCARDNO',   'entry_no' => 'GC001', 'amount' => 30.0],
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV001', 'amount' => 20.0],
        ]);
        $total      = $this->makeTotal();
        $assignment = $this->createMock(ShippingAssignmentInterface::class);

        $this->collector->collect($quote, $assignment, $total);

        $this->assertEquals(-50.0, $total->getTotalAmount(self::CODE));
        $this->assertEquals(-50.0, $total->getBaseTotalAmount(self::CODE));
    }

    /**
     * Gift-card-only entries must also be deducted — VoucherAmountUsed owns ALL entries.
     *
     * @magentoAppIsolation enabled
     */
    public function testCollectDeductsGiftCardOnlyEntries(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'amount' => 40.0],
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC002', 'amount' => 10.0],
        ]);
        $total      = $this->makeTotal();
        $assignment = $this->createMock(ShippingAssignmentInterface::class);

        $this->collector->collect($quote, $assignment, $total);

        $this->assertEquals(-50.0, $total->getTotalAmount(self::CODE));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testCollectWithNoEntriesDoesNotDeduct(): void
    {
        $quote      = $this->makeQuote([]);
        $total      = $this->makeTotal();
        $assignment = $this->createMock(ShippingAssignmentInterface::class);

        $this->collector->collect($quote, $assignment, $total);

        // parent::collect() initialises to 0 — our code must not override it
        $this->assertEquals(0.0, (float)$total->getTotalAmount(self::CODE));
    }

    // -------------------------------------------------------------------------
    // fetch()
    // -------------------------------------------------------------------------

    /**
     * @magentoAppIsolation enabled
     */
    public function testFetchReturnsNullWhenNoVoucherEntries(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'amount' => 25.0],
        ]);
        $total = $this->makeTotal();

        // fetch() checks only non-GIFTCARDNO entries for the segment value
        $this->assertNull($this->collector->fetch($quote, $total));
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testFetchReturnsSingleVoucherSegment(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV001', 'amount' => 12.5],
        ]);
        $total  = $this->makeTotal();
        $result = $this->collector->fetch($quote, $total);

        $this->assertIsArray($result);
        $this->assertSame(self::CODE, $result['code']);
        $this->assertEquals(-12.5, $result['value']);
        $this->assertStringContainsString('SV001', (string)$result['title']);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testFetchReturnsCountLabelForMultipleVouchers(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV001', 'amount' => 10.0],
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV002', 'amount' => 5.0],
        ]);
        $total  = $this->makeTotal();
        $result = $this->collector->fetch($quote, $total);

        $this->assertIsArray($result);
        $this->assertEquals(-15.0, $result['value']);
        $this->assertStringContainsString('2', (string)$result['title']);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testFetchReturnsNullForNoEntries(): void
    {
        $quote = $this->makeQuote([]);
        $total = $this->makeTotal();

        $this->assertNull($this->collector->fetch($quote, $total));
    }
}
