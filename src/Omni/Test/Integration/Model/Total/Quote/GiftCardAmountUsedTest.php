<?php
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Model\Total\Quote;

use Ls\Omni\Model\Total\Quote\GiftCardAmountUsed;
use Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Integration tests for GiftCardAmountUsed::collect() and fetch().
 *
 * collect() is intentionally a no-op — deduction is owned by VoucherAmountUsed
 * to avoid double-counting.  fetch() provides a display-only segment for the
 * gift card portion shown in the cart/checkout summary.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class GiftCardAmountUsedTest extends AbstractIntegrationTest
{
    private const CODE = 'ls_gift_card_amount_used';

    public $objectManager;
    public GiftCardAmountUsed $collector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->collector     = $this->objectManager->create(GiftCardAmountUsed::class);
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
    // collect() — must be a no-op
    // -------------------------------------------------------------------------

    /**
     * collect() must not deduct anything from the grand total; that is
     * VoucherAmountUsed's responsibility.
     *
     * @magentoAppIsolation enabled
     */
    public function testCollectIsNoOp(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'amount' => 50.0],
        ]);
        $total      = $this->makeTotal();
        $assignment = $this->createMock(ShippingAssignmentInterface::class);

        $this->collector->collect($quote, $assignment, $total);

        // parent::collect() initialises the code to 0 — collect() must not change it
        $this->assertEquals(0.0, (float)$total->getTotalAmount(self::CODE));
        $this->assertEquals(0.0, (float)$total->getBaseTotalAmount(self::CODE));
    }

    // -------------------------------------------------------------------------
    // fetch() — display segment
    // -------------------------------------------------------------------------

    /**
     * @magentoAppIsolation enabled
     */
    public function testFetchReturnsNullWhenNoGiftCardEntries(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV001', 'amount' => 10.0],
        ]);
        $total = $this->makeTotal();

        $this->assertNull($this->collector->fetch($quote, $total));
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

    /**
     * @magentoAppIsolation enabled
     */
    public function testFetchReturnsSingleGiftCardSegment(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'amount' => 40.0],
        ]);
        $total  = $this->makeTotal();
        $result = $this->collector->fetch($quote, $total);

        $this->assertIsArray($result);
        $this->assertSame(self::CODE, $result['code']);
        $this->assertEquals(-40.0, $result['value']);
        $this->assertStringContainsString('GC001', (string)$result['title']);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testFetchReturnsCountLabelForMultipleGiftCards(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'amount' => 25.0],
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC002', 'amount' => 25.0],
        ]);
        $total  = $this->makeTotal();
        $result = $this->collector->fetch($quote, $total);

        $this->assertIsArray($result);
        $this->assertEquals(-50.0, $result['value']);
        $this->assertStringContainsString('2', (string)$result['title']);
    }

    /**
     * Mixed entries — fetch() must only sum GIFTCARDNO entries.
     *
     * @magentoAppIsolation enabled
     */
    public function testFetchExcludesVoucherAmountsFromValue(): void
    {
        $quote = $this->makeQuote([
            ['entry_type' => 'GIFTCARDNO',   'entry_no' => 'GC001', 'amount' => 30.0],
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV001', 'amount' => 20.0],
        ]);
        $total  = $this->makeTotal();
        $result = $this->collector->fetch($quote, $total);

        $this->assertIsArray($result);
        // Must reflect GC amount only (30), not the total (50)
        $this->assertEquals(-30.0, $result['value']);
    }
}
