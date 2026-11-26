<?php

namespace Ls\Omni\Test\Unit\Observer\Adminhtml;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\Omni\Model\Sales\AdminOrder\OrderEdit;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test coverage for Ls\Omni\Observer\Adminhtml\OrderObserver::execute() (work item #85191,
 * solution-plan-85191-custom-order-price.md §5).
 *
 * TDD note: the config-gated branch (§5) does not exist yet — execute() currently always calls
 * BasketHelper::calculateOneListFromOrder() unconditionally. These tests are expected to FAIL until
 * the observer is refactored to:
 *   1. compute `$isOrderEdit` once (`!empty($order->getRelationParentId()) &&
 *      $this->lsr->getStoreConfig(LSR::LSR_ORDER_EDIT, $order->getStoreId())`),
 *   2. when NOT an order-edit AND `LSR::isAdminOrderCustomPriceActive($order->getStoreId())` is
 *      true, call `BasketHelper::buildOrderFromMagentoOrderItems($order)` instead of
 *      `BasketHelper::calculateOneListFromOrder($order)`,
 *   3. otherwise (config off, or order-edit), keep calling `calculateOneListFromOrder()` exactly as
 *      today, and
 *   4. reuse the same `$isOrderEdit` boolean for the existing edit-vs-create branch below, rather
 *      than re-evaluating `getStoreConfig(LSR::LSR_ORDER_EDIT, ...)` a second time.
 *
 * The observer's downstream logic (order-edit resend, order-create placeOrder()) is exercised only
 * far enough to reach the branch point under test — `OrderHelper`/`OrderEdit` collaborators are
 * stubbed to return falsy/null so their internal (irrelevant, out-of-scope) bodies are skipped
 * without needing a fully wired admin-order stack, consistent with this being a scoped unit test
 * rather than the existing integration test suite
 * (Ls\Omni\Test\Integration\Observer\AdminHtml\OrderObserverTest).
 */
class OrderObserverTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var BasketHelper|MockObject
     */
    private $basketHelper;

    /**
     * @var OrderHelper|MockObject
     */
    private $orderHelper;

    /**
     * @var LSR|MockObject
     */
    private $lsr;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var OrderEdit|MockObject
     */
    private $orderEdit;

    /**
     * @var OrderResourceModel|MockObject
     */
    private $orderResourceModel;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    public function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->basketHelper = $this->createMock(BasketHelper::class);

        // OrderHelper is not fully mocked away (createMock() alone would trip typed-property
        // initialization errors on its public storeManager/checkoutSession/customerSession
        // properties, which execute() touches unconditionally at the top of the method) — a
        // partial mock is used instead, with the properties it needs assigned directly, mirroring
        // BasketHelperTest's pattern for the same kind of large, promoted-property-heavy helper.
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
        $this->orderEdit = $this->createMock(OrderEdit::class);
    }

    /**
     * @param string|null $relationParentId
     * @return Order|MockObject
     */
    private function makeOrder(?string $relationParentId): MockObject
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoreId', 'getQuoteId', 'getCustomerId', 'getRelationParentId'])
            ->getMock();
        $order->method('getStoreId')->willReturn(1);
        $order->method('getQuoteId')->willReturn(99);
        $order->method('getCustomerId')->willReturn(42);
        $order->method('getRelationParentId')->willReturn($relationParentId);

        return $order;
    }

    /**
     * @param Order $order
     * @return Observer
     */
    private function makeObserver(Order $order): Observer
    {
        $event = new Event();
        $event->setData('order', $order);

        $observer = new Observer();
        $observer->setEvent($event);

        return $observer;
    }

    private function buildObserver()
    {
        return $this->objectManager->getObject(
            \Ls\Omni\Observer\Adminhtml\OrderObserver::class,
            [
                'basketHelper' => $this->basketHelper,
                'orderHelper' => $this->orderHelper,
                'logger' => $this->logger,
                'orderResourceModel' => $this->orderResourceModel,
                'LSR' => $this->lsr,
                'messageManager' => $this->messageManager,
                'orderEdit' => $this->orderEdit,
            ]
        );
    }

    /**
     * Config OFF, genuine order-create (no relation parent) — must behave exactly as today:
     * calculateOneListFromOrder() is called, buildOrderFromMagentoOrderItems() is never invoked.
     */
    public function testConfigDisabledUsesCalculateOneListFromOrder(): void
    {
        $order = $this->makeOrder(null);

        $this->lsr->method('isLSR')->willReturn(true);
        $this->lsr->method('getStoreConfig')->willReturn(false);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(false);

        $this->basketHelper->expects($this->once())
            ->method('calculateOneListFromOrder')
            ->with($order)
            ->willReturn(new Entity\Order());
        $this->basketHelper->expects($this->never())->method('buildOrderFromMagentoOrderItems');

        $observer = $this->buildObserver();
        $observer->execute($this->makeObserver($order));
    }

    /**
     * Config ON, but this is an order-edit (relation parent set AND LSR_ORDER_EDIT enabled) — FR6:
     * order-edit is out of scope, so calculateOneListFromOrder() must still be used, never the
     * bypass. Also asserts getStoreConfig(LSR::LSR_ORDER_EDIT, ...) is evaluated exactly ONCE — i.e.
     * $isOrderEdit is computed a single time and reused, not re-evaluated for the pre-existing
     * edit-vs-create branch below it.
     */
    public function testOrderEditIgnoresConfigAndStillUsesCalculateOneListFromOrder(): void
    {
        $order = $this->makeOrder('55');

        $this->lsr->method('isLSR')->willReturn(true);
        $this->lsr->expects($this->once())
            ->method('getStoreConfig')
            ->with(LSR::LSR_ORDER_EDIT, 1)
            ->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);

        // Old order lookup resolves to nothing, so the edit branch's inner body (documentId /
        // OrderEdit::prepareOrder()) is never reached — out of scope for this test.
        $this->orderHelper->method('getMagentoOrderGivenEntityId')->willReturn(null);

        $this->basketHelper->expects($this->once())
            ->method('calculateOneListFromOrder')
            ->with($order)
            ->willReturn(new Entity\Order());
        $this->basketHelper->expects($this->never())->method('buildOrderFromMagentoOrderItems');

        $observer = $this->buildObserver();
        $observer->execute($this->makeObserver($order));
    }

    /**
     * Config ON, genuine order-create (no relation parent) — the bypass path must be used:
     * buildOrderFromMagentoOrderItems() is called instead of calculateOneListFromOrder().
     */
    public function testConfigEnabledAndNotOrderEditUsesBuildOrderFromMagentoOrderItems(): void
    {
        $order = $this->makeOrder(null);

        $this->lsr->method('isLSR')->willReturn(true);
        $this->lsr->method('getStoreConfig')->willReturn(false);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);

        $this->basketHelper->expects($this->once())
            ->method('buildOrderFromMagentoOrderItems')
            ->with($order)
            ->willReturn(new Entity\Order());
        $this->basketHelper->expects($this->never())->method('calculateOneListFromOrder');

        $observer = $this->buildObserver();
        $observer->execute($this->makeObserver($order));
    }

    /**
     * Config ON, relation parent IS set but LSR_ORDER_EDIT is disabled for the store — this must
     * still count as a genuine order-create (both conditions are required for $isOrderEdit), so the
     * bypass path is used.
     */
    public function testConfigEnabledWithRelationParentButOrderEditDisabledUsesBuildOrderFromMagentoOrderItems(): void
    {
        $order = $this->makeOrder('55');

        $this->lsr->method('isLSR')->willReturn(true);
        $this->lsr->method('getStoreConfig')->with(LSR::LSR_ORDER_EDIT, 1)->willReturn(false);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);

        $this->basketHelper->expects($this->once())
            ->method('buildOrderFromMagentoOrderItems')
            ->with($order)
            ->willReturn(new Entity\Order());
        $this->basketHelper->expects($this->never())->method('calculateOneListFromOrder');

        $observer = $this->buildObserver();
        $observer->execute($this->makeObserver($order));
    }
}
