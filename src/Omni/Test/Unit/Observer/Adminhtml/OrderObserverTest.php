<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Observer\Adminhtml;

use Ls\Core\Model\LSR;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\Omni\Model\Sales\AdminOrder\OrderEdit;
use Ls\Omni\Observer\Adminhtml\OrderObserver;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the ticket-85191 branching added to {@see OrderObserver::execute()}:
 * whether an admin-created order's price source is the remote OneList/basket calculation
 * (BasketHelper::calculateOneListFromOrder()) or the new local bypass
 * (BasketHelper::buildOrderFromMagentoOrderItems()), and - critically - that the bypass never
 * applies to the order-*edit* path (FR6), even when the new config is enabled.
 *
 * OrderObserver's constructor is small (7 typed dependencies, no body beyond assignment), so
 * unlike BasketHelper/OrderHelper it is safe to construct normally with mocks.
 */
class OrderObserverTest extends TestCase
{
    /**
     * @var BasketHelper&MockObject
     */
    private $basketHelper;

    /**
     * @var OrderHelper&MockObject
     */
    private $orderHelper;

    /**
     * @var LSR&MockObject
     */
    private $lsr;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * @var OrderResourceModel&MockObject
     */
    private $orderResourceModel;

    /**
     * @var ManagerInterface&MockObject
     */
    private $messageManager;

    /**
     * @var OrderEdit&MockObject
     */
    private $orderEdit;

    /**
     * @var OrderObserver
     */
    private $observer;

    protected function setUp(): void
    {
        $this->basketHelper = $this->getMockBuilder(BasketHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['calculateOneListFromOrder', 'buildOrderFromMagentoOrderItems', 'setCalculateBasket', 'unSetRequiredDataFromCustomerAndCheckoutSessions'])
            ->getMock();

        $this->orderHelper = $this->getMockBuilder(OrderHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['prepareOrder', 'placeOrder', 'getMagentoOrderGivenEntityId'])
            ->getMock();
        $this->orderHelper->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->orderHelper->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->orderHelper->customerSession = $this->createMock(CustomerSession::class);

        $this->lsr = $this->createMock(LSR::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->orderResourceModel = $this->createMock(OrderResourceModel::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->orderEdit = $this->getMockBuilder(OrderEdit::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new OrderObserver(
            $this->basketHelper,
            $this->orderHelper,
            $this->logger,
            $this->orderResourceModel,
            $this->lsr,
            $this->messageManager,
            $this->orderEdit
        );
    }

    /**
     * @param Order&MockObject $order
     * @return Observer
     */
    private function observerEventFor($order): Observer
    {
        $event = new Event(['order' => $order]);

        return new Observer(['event' => $event]);
    }

    /**
     * @param int $storeId
     * @param mixed $relationParentId
     * @return Order&MockObject
     */
    private function createOrder(int $storeId, $relationParentId)
    {
        $order = $this->createMock(Order::class);
        $order->method('getStoreId')->willReturn($storeId);
        $order->method('getQuoteId')->willReturn(55);
        $order->method('getCustomerId')->willReturn(99);
        $order->method('getIncrementId')->willReturn('100000001');
        $order->method('getRelationParentId')->willReturn($relationParentId);

        return $order;
    }

    /**
     * Config OFF (default) - genuine order create. The bypass must not be used, regardless of
     * the config's non-effect here; calculateOneListFromOrder() must be the one called.
     */
    public function testConfigDisabledUsesRemoteCalculationOnCreate(): void
    {
        $order = $this->createOrder(1, null);

        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(false);
        $this->lsr->method('isLSR')->willReturn(false);

        $this->basketHelper->expects($this->once())
            ->method('calculateOneListFromOrder')
            ->with($order)
            ->willReturn(null);
        $this->basketHelper->expects($this->never())->method('buildOrderFromMagentoOrderItems');

        $this->observer->execute($this->observerEventFor($order));
    }

    /**
     * Config OFF (default) - order edit (relation parent set, LSR_ORDER_EDIT enabled). The
     * bypass must still not be used (it's off), same as the create case.
     */
    public function testConfigDisabledUsesRemoteCalculationOnEdit(): void
    {
        $order = $this->createOrder(1, 42);

        $this->lsr->method('getStoreConfig')->with(LSR::LSR_ORDER_EDIT, 1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->willReturn(false);
        $this->lsr->method('isLSR')->willReturn(false);

        $this->basketHelper->expects($this->once())
            ->method('calculateOneListFromOrder')
            ->with($order)
            ->willReturn(null);
        $this->basketHelper->expects($this->never())->method('buildOrderFromMagentoOrderItems');

        $this->observer->execute($this->observerEventFor($order));
    }

    /**
     * Config ON, genuine order create (no relation parent) - the bypass must be used instead
     * of the remote calculation.
     */
    public function testConfigEnabledUsesBypassOnGenuineCreate(): void
    {
        $order = $this->createOrder(1, null);

        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);
        $this->lsr->method('isLSR')->willReturn(false);

        $this->basketHelper->expects($this->once())
            ->method('buildOrderFromMagentoOrderItems')
            ->with($order)
            ->willReturn(null);
        $this->basketHelper->expects($this->never())->method('calculateOneListFromOrder');

        $this->observer->execute($this->observerEventFor($order));
    }

    /**
     * Critical order-edit guard test (FR6): config ON, but this is an order-edit
     * (getRelationParentId() set AND LSR_ORDER_EDIT enabled) - the bypass must NOT apply here
     * even though the config is on. calculateOneListFromOrder() must still be used, exactly as
     * the edit flow needs (OrderEdit::prepareOrder() consumes its result).
     */
    public function testConfigEnabledStillUsesRemoteCalculationOnOrderEdit(): void
    {
        $order = $this->createOrder(1, 42);

        $this->lsr->method('getStoreConfig')->with(LSR::LSR_ORDER_EDIT, 1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->willReturn(true);
        $this->lsr->method('isLSR')->willReturn(false);

        $this->basketHelper->expects($this->once())
            ->method('calculateOneListFromOrder')
            ->with($order)
            ->willReturn(null);
        $this->basketHelper->expects($this->never())->method('buildOrderFromMagentoOrderItems');

        $this->observer->execute($this->observerEventFor($order));
    }
}
