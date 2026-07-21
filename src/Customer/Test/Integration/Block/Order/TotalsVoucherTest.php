<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Order;

use Ls\Customer\Block\Order\Totals;
use Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Tests the voucher-entries accumulation and total-amount deduction logic in
 * Ls\Customer\Block\Order\Totals.
 *
 * Gift card amounts and voucher entries are now sourced from getMagOrder()->getLsPosDataEntries()
 * so that regular card payment lines from LS Central are never treated as vouchers.
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
            ['label' => 'STOREVOUCHER - SV001', 'amount' => 20.0],
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
            ['label' => 'STOREVOUCHER - SV001', 'amount' => 15.0],
            ['label' => 'STOREVOUCHER - SV002', 'amount' => 10.0],
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
            ['label' => 'STOREVOUCHER - SV001', 'amount' => 10.0],
            ['label' => 'STOREVOUCHER - SV002', 'amount' => 5.0],
        ];

        $result = $block->getTotalAmount();

        // 0 - 30 - 0 - 15 = -45
        $this->assertEquals(-45.0, $result);
    }

    /**
     * getLoyaltyGiftCardInfo leaves voucherEntries empty when there are no payment lines
     * and no Magento order registered (getMagOrder returns null → allEntries = []).
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

    // -----------------------------------------------------------------------
    // New tests covering the fixed getLoyaltyGiftCardInfo() behaviour
    // -----------------------------------------------------------------------

    /**
     * Build a Totals block whose getMagOrder() returns a mock Magento order with the
     * given ls_pos_data_entries JSON.
     */
    private function buildBlockWithPosEntries(array $posEntries): Totals
    {
        $magentoOrderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $magentoOrderMock->setData('ls_pos_data_entries', json_encode($posEntries));

        $block = $this->getMockBuilder(Totals::class)
            ->setConstructorArgs($this->getConstructorArgs())
            ->onlyMethods(['getMagOrder', 'getOrderPayments'])
            ->getMock();
        $block->method('getMagOrder')->willReturn($magentoOrderMock);
        $block->method('getOrderPayments')->willReturn(null);

        return $block;
    }

    /**
     * Resolve constructor arguments for Totals from the object manager.
     */
    private function getConstructorArgs(): array
    {
        $reflection = new \ReflectionClass(Totals::class);
        $constructor = $reflection->getConstructor();
        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $args[] = $this->objectManager->get($type->getName());
            } else {
                $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }
        }
        return $args;
    }

    /**
     * getLoyaltyGiftCardInfo must build voucherEntries from ls_pos_data_entries,
     * not from LS Central payment lines, so regular card payments are excluded.
     *
     * @magentoAppIsolation enabled
     */
    public function testVoucherEntriesBuiltFromPosDataEntries(): void
    {
        $block = $this->buildBlockWithPosEntries([
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV001', 'amount' => 30.0, 'tender_type' => ''],
        ]);
        $block->getLoyaltyGiftCardInfo();

        $this->assertCount(1, $block->voucherEntries);
        $this->assertEquals(30.0, $block->voucherEntries[0]['amount']);
    }

    /**
     * Voucher label must be "entry_type - entry_no" (e.g. "STOREVOUCHER - SV001").
     *
     * @magentoAppIsolation enabled
     */
    public function testVoucherLabelUsesEntryTypeAndEntryNoFormat(): void
    {
        $block = $this->buildBlockWithPosEntries([
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV001', 'amount' => 25.0, 'tender_type' => ''],
        ]);
        $block->getLoyaltyGiftCardInfo();

        $this->assertSame('STOREVOUCHER - SV001', $block->voucherEntries[0]['label']);
    }

    /**
     * GIFTCARDNO entries must accumulate into giftCardAmount, not voucherEntries.
     *
     * @magentoAppIsolation enabled
     */
    public function testGiftCardAmountBuiltFromGiftCardNoEntries(): void
    {
        $block = $this->buildBlockWithPosEntries([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'amount' => 40.0, 'tender_type' => ''],
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC002', 'amount' => 20.0, 'tender_type' => ''],
        ]);
        $block->getLoyaltyGiftCardInfo();

        $this->assertEquals(60.0, $block->giftCardAmount);
        $this->assertEmpty($block->voucherEntries);
    }

    /**
     * Mixed entries: gift cards go to giftCardAmount; vouchers go to voucherEntries.
     *
     * @magentoAppIsolation enabled
     */
    public function testMixedEntriesSeparateGiftCardsFromVouchers(): void
    {
        $block = $this->buildBlockWithPosEntries([
            ['entry_type' => 'GIFTCARDNO',   'entry_no' => 'GC001', 'amount' => 40.0, 'tender_type' => ''],
            ['entry_type' => 'STOREVOUCHER',  'entry_no' => 'SV001', 'amount' => 15.0, 'tender_type' => ''],
        ]);
        $block->getLoyaltyGiftCardInfo();

        $this->assertEquals(40.0, $block->giftCardAmount);
        $this->assertCount(1, $block->voucherEntries);
        $this->assertEquals(15.0, $block->voucherEntries[0]['amount']);
        $this->assertSame('STOREVOUCHER - SV001', $block->voucherEntries[0]['label']);
    }

    /**
     * When getMagOrder() returns null (no Magento order in registry), giftCardAmount
     * and voucherEntries must remain at their default zero/empty values.
     *
     * @magentoAppIsolation enabled
     */
    public function testNoMagOrderResultsInEmptyEntriesAndZeroGiftCard(): void
    {
        $block = $this->getMockBuilder(Totals::class)
            ->setConstructorArgs($this->getConstructorArgs())
            ->onlyMethods(['getMagOrder', 'getOrderPayments'])
            ->getMock();
        $block->method('getMagOrder')->willReturn(null);
        $block->method('getOrderPayments')->willReturn(null);

        $block->getLoyaltyGiftCardInfo();

        $this->assertEquals(0, $block->giftCardAmount);
        $this->assertEmpty($block->voucherEntries);
    }
}
