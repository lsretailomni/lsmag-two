<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Order;

use Ls\Customer\Block\Order\Totals;
use Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Tests the voucher-entries accumulation and total-amount deduction logic in
 * Ls\Customer\Block\Order\Totals that was introduced to support multiple
 * POS data entry redemption lines.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class TotalsVoucherTest extends AbstractIntegrationTest
{
    public $objectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testVoucherEntriesDefaultToEmptyArray(): void
    {
        $block = $this->objectManager->create(Totals::class);
        $this->assertIsArray($block->voucherEntries);
        $this->assertEmpty($block->voucherEntries);
    }

    /**
     * getTotalAmount must deduct the sum of all voucher entries from the grand total.
     * getGrandTotal returns 0 when no central order is registered, so the result is
     * 0 minus the voucher sum.
     *
     * @magentoAppIsolation enabled
     */
    public function testGetTotalAmountDeductsSingleVoucherEntry(): void
    {
        $block = $this->objectManager->create(Totals::class);
        $block->voucherEntries = [
            ['label' => 'Store Voucher', 'amount' => 20.0],
        ];

        $result = $block->getTotalAmount();

        $this->assertEquals(-20.0, $result);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetTotalAmountDeductsMultipleVoucherEntries(): void
    {
        $block = $this->objectManager->create(Totals::class);
        $block->voucherEntries = [
            ['label' => 'Voucher A', 'amount' => 15.0],
            ['label' => 'Voucher B', 'amount' => 10.0],
        ];

        $result = $block->getTotalAmount();

        // 0 (grand total) - 0 (gc) - 0 (loyalty) - 25 (vouchers)
        $this->assertEquals(-25.0, $result);
    }

    /**
     * getTotalAmount combines gift card and voucher deductions.
     *
     * @magentoAppIsolation enabled
     */
    public function testGetTotalAmountDeductsGiftCardAndVoucherTogether(): void
    {
        $block = $this->objectManager->create(Totals::class);
        $block->giftCardAmount = 30.0;
        $block->voucherEntries = [
            ['label' => 'Voucher A', 'amount' => 10.0],
            ['label' => 'Voucher B', 'amount' => 5.0],
        ];

        $result = $block->getTotalAmount();

        // 0 - 30 - 0 - 15 = -45
        $this->assertEquals(-45.0, $result);
    }

    /**
     * getLoyaltyGiftCardInfo leaves voucherEntries empty when there are no payment lines
     * (no central order registered in the test context).
     *
     * @magentoAppIsolation enabled
     */
    public function testGetLoyaltyGiftCardInfoLeavesVoucherEntriesEmptyWithNoPaymentLines(): void
    {
        $block = $this->objectManager->create(Totals::class);
        $block->getLoyaltyGiftCardInfo();

        $this->assertEmpty($block->voucherEntries);
        $this->assertEquals(0, $block->giftCardAmount);
        $this->assertEquals(0, $block->loyaltyPointAmount);
    }
}
