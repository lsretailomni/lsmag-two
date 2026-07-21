<?php
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Block\Adminhtml\Sales;

use Ls\Omni\Block\Adminhtml\Sales\Totals;
use Ls\Omni\Helper\LoyaltyHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Directory\Model\Currency;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class TotalsTest extends AbstractIntegrationTest
{
    public $objectManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Build a Totals block with mocked parent block; return totals added via addTotalBefore.
     *
     * @param array $posEntries  raw POS data entries array (will be json_encoded)
     * @param float $pointsSpent ls_points_spent value on the order
     */
    private function runInitTotals(array $posEntries, float $pointsSpent = 0.0): array
    {
        $order = $this->objectManager->create(Order::class);
        $order->setData('ls_pos_data_entries', empty($posEntries) ? null : json_encode($posEntries));
        $order->setData('ls_points_spent', $pointsSpent);
        $order->setData('ls_discount_amount', 0);
        $order->setStoreId(1);

        $addedTotals = [];

        $parentMock = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrder', 'setOrder', 'getInvoice', 'getCreditmemo', 'getSource', 'addTotalBefore'])
            ->getMock();
        $parentMock->method('getOrder')->willReturn($order);
        $parentMock->method('getSource')->willReturn($order);
        $parentMock->method('setOrder')->willReturn(null);
        $parentMock->method('getInvoice')->willReturn(null);
        $parentMock->method('getCreditmemo')->willReturn(null);
        $parentMock->method('addTotalBefore')->willReturnCallback(
            function ($total) use (&$addedTotals) {
                $addedTotals[] = $total;
            }
        );

        $totalsBlock = $this->getMockBuilder(Totals::class)
            ->setConstructorArgs([
                $this->objectManager->get(Context::class),
                $this->objectManager->get(OrderHelper::class),
                $this->objectManager->get(LoyaltyHelper::class),
                $this->objectManager->get(Currency::class),
            ])
            ->onlyMethods(['getParentBlock'])
            ->getMock();
        $totalsBlock->method('getParentBlock')->willReturn($parentMock);

        $totalsBlock->initTotals();

        return $addedTotals;
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testInitTotalsWithSingleGiftCardEntry(): void
    {
        $totals = $this->runInitTotals([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'amount' => 50.0, 'tender_type' => ''],
        ]);

        $codes = array_map(fn($t) => $t->getCode(), $totals);
        $this->assertContains('ls_gift_card_amount_used', $codes);

        $gcEntry = current(array_filter($totals, fn($t) => $t->getCode() === 'ls_gift_card_amount_used'));
        $this->assertEquals(-50.0, $gcEntry->getValue());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testInitTotalsWithMultipleGiftCardEntries(): void
    {
        $totals = $this->runInitTotals([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'amount' => 30.0, 'tender_type' => ''],
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC002', 'amount' => 20.0, 'tender_type' => ''],
        ]);

        $codes = array_map(fn($t) => $t->getCode(), $totals);
        $this->assertContains('ls_gift_card_amount_used', $codes);

        $gcEntry = current(array_filter($totals, fn($t) => $t->getCode() === 'ls_gift_card_amount_used'));
        $this->assertEquals(-50.0, $gcEntry->getValue());
        // Label should reference count "2" for multiple cards
        $this->assertStringContainsString('2', (string)$gcEntry->getLabel());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testInitTotalsWithVoucherEntry(): void
    {
        $totals = $this->runInitTotals([
            ['entry_type' => 'VOUCHER', 'entry_no' => 'V001', 'amount' => 15.0, 'tender_type' => ''],
        ]);

        $codes = array_map(fn($t) => $t->getCode(), $totals);
        $this->assertNotContains('ls_gift_card_amount_used', $codes);
        $this->assertContains('ls_entry_amount', $codes);

        $vEntry = current(array_filter($totals, fn($t) => $t->getCode() === 'ls_entry_amount'));
        $this->assertEquals(-15.0, $vEntry->getValue());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testInitTotalsWithEmptyEntriesAddsNoGiftCardOrVoucherRow(): void
    {
        $totals = $this->runInitTotals([]);
        $codes  = array_map(fn($t) => $t->getCode(), $totals);

        $this->assertNotContains('ls_gift_card_amount_used', $codes);
        $this->assertNotContains('ls_entry_amount', $codes);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testInitTotalsWithLoyaltyPointsAddsLoyaltyRow(): void
    {
        $totals = $this->runInitTotals([], 100.0);
        $codes  = array_map(fn($t) => $t->getCode(), $totals);

        $this->assertContains('ls_points_spent', $codes);
    }
}
