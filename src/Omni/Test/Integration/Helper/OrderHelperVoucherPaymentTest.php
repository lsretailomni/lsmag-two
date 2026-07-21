<?php
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Helper;

use Ls\Omni\Helper\OrderHelper;
use Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Verifies that POS data entry payment lines (gift cards, vouchers) are built correctly
 * from ls_pos_data_entries in setOrderPayments().
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class OrderHelperVoucherPaymentTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $orderHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->orderHelper   = $this->objectManager->get(OrderHelper::class);
    }

    /**
     * Build a mock order that uses the 'free' payment method (skips main payment line)
     * and has the given POS data entries set.
     */
    private function buildMockOrder(array $posEntries): Order
    {
        $paymentMethodMock = $this->getMockBuilder(MethodInterface::class)->getMock();
        $paymentMethodMock->method('getCode')->willReturn('free');

        $paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMethodInstance', 'getLastTransId', 'getCcType', 'getCcLast4',
                           'getAmountPaid', 'getAmountAuthorized'])
            ->getMock();
        $paymentMock->method('getMethodInstance')->willReturn($paymentMethodMock);
        $paymentMock->method('getLastTransId')->willReturn('');
        $paymentMock->method('getCcType')->willReturn('');
        $paymentMock->method('getCcLast4')->willReturn('');
        $paymentMock->method('getAmountPaid')->willReturn(0);
        $paymentMock->method('getAmountAuthorized')->willReturn(0);

        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPayment', 'getGrandTotal', 'getIncrementId',
                           'getStoreId', 'getPickupStore', 'getShippingMethod'])
            ->getMock();
        $orderMock->method('getPayment')->willReturn($paymentMock);
        $orderMock->method('getGrandTotal')->willReturn(100.0);
        $orderMock->method('getIncrementId')->willReturn('TEST001');
        $orderMock->method('getStoreId')->willReturn(1);
        $orderMock->method('getPickupStore')->willReturn('');
        $orderMock->method('getShippingMethod')->willReturn(null);

        // These use DataObject magic via getData/setData
        $orderMock->setData('ls_pos_data_entries', json_encode($posEntries));
        $orderMock->setData('ls_points_spent', 0);
        $orderMock->setData('ls_discount_amount', 0);

        return $orderMock;
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGiftCardEntryProducesPaymentLine(): void
    {
        $order = $this->buildMockOrder([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'pin_code' => '1234',
             'amount' => 50.0, 'tender_type' => ''],
        ]);

        $payments = $this->orderHelper->setOrderPayments($order, 'CARD001', 'S0001');

        $this->assertNotEmpty($payments);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testVoucherEntryProducesPaymentLine(): void
    {
        $order = $this->buildMockOrder([
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV001', 'pin_code' => '',
             'amount' => 20.0, 'tender_type' => ''],
        ]);

        $payments = $this->orderHelper->setOrderPayments($order, 'CARD001', 'S0001');

        $this->assertNotEmpty($payments);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testMultipleMixedEntriesProduceCorrectCount(): void
    {
        $order = $this->buildMockOrder([
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC001', 'pin_code' => '1234',
             'amount' => 30.0, 'tender_type' => ''],
            ['entry_type' => 'STOREVOUCHER', 'entry_no' => 'SV001', 'pin_code' => '',
             'amount' => 15.0, 'tender_type' => ''],
            ['entry_type' => 'GIFTCARDNO', 'entry_no' => 'GC002', 'pin_code' => '5678',
             'amount' => 20.0, 'tender_type' => ''],
        ]);

        $payments = $this->orderHelper->setOrderPayments($order, 'CARD001', 'S0001');

        $this->assertCount(3, $payments);
    }
}
