<?php

namespace Ls\Replication\Test\Unit\Cron;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\Replication\Cron\SyncOrders;
use Ls\Replication\Helper\ReplicationHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test coverage for Ls\Replication\Cron\SyncOrders::execute() (work item #85191,
 * solution-plan-85191-custom-order-price.md §5.5).
 *
 * TDD note: today (SyncOrders.php:124) this cron unconditionally calls
 * BasketHelper::formulateCentralOrderRequestFromMagentoOrder($order) whenever a retried order has no
 * document_id yet. Per §5.5, this is a real, user-confirmed behavior change: the retry cron must now
 * genuinely gate remote-calc-vs-bypass on LSR::isAdminOrderCustomPriceActive($order->getStoreId()),
 * matching OrderObserver's behavior:
 *   - config ON  -> BasketHelper::buildOrderFromMagentoOrderItems($order)
 *   - config OFF -> BasketHelper::calculateOneListFromOrder($order)
 * `formulateCentralOrderRequestFromMagentoOrder()` is no longer called from this call site at all
 * once implemented. These tests are expected to FAIL until that change is made.
 *
 * Per the ticket, SyncOrdersEdit.php (the order-*edit* cron) is explicitly out of scope and is not
 * tested here.
 */
class SyncOrdersTest extends TestCase
{
    /**
     * @var LSR|MockObject
     */
    private $lsr;

    /**
     * @var ReplicationHelper|MockObject
     */
    private $replicationHelper;

    /**
     * @var OrderHelper|MockObject
     */
    private $orderHelper;

    /**
     * @var BasketHelper|MockObject
     */
    private $basketHelper;

    /**
     * @var OrderResourceModel|MockObject
     */
    private $orderResourceModel;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var SyncOrders
     */
    private $syncOrders;

    public function setUp(): void
    {
        $this->lsr = $this->createMock(LSR::class);
        $this->replicationHelper = $this->createMock(ReplicationHelper::class);
        $this->orderHelper = $this->createMock(OrderHelper::class);
        $this->basketHelper = $this->createMock(BasketHelper::class);
        $this->orderResourceModel = $this->createMock(OrderResourceModel::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->syncOrders = new SyncOrders(
            $this->lsr,
            $this->replicationHelper,
            $this->orderHelper,
            $this->basketHelper,
            $this->orderResourceModel,
            $this->logger,
            $this->storeManager
        );
    }

    /**
     * @return Order|MockObject
     */
    private function makeRetriableOrder(): MockObject
    {
        // Order has no relation parent (not an edit-resend) and no document_id yet — i.e. exactly
        // the "first sync attempt failed, cron is retrying" scenario this cron exists to handle.
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRelationParentId', 'getStoreId'])
            ->getMock();
        $order->method('getRelationParentId')->willReturn(null);
        $order->method('getStoreId')->willReturn(2);

        return $order;
    }

    /**
     * @param Order $order
     * @param StoreInterface|MockObject $store
     * @return void
     */
    private function setUpStoreLoop(Order $order, MockObject $store): void
    {
        $this->lsr->method('isSSM')->willReturn(false);
        $this->lsr->method('getAllStores')->willReturn([$store]);
        $this->lsr->method('isLSR')->willReturn(true);

        $this->orderHelper->method('getOrders')->willReturn([$order]);
    }

    /**
     * @return StoreInterface|MockObject
     */
    private function makeStore(): MockObject
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(2);
        $store->method('getWebsiteId')->willReturn(1);

        return $store;
    }

    /**
     * Config OFF: the retry cron must attempt the remote OneListCalculate path
     * (calculateOneListFromOrder()), matching OrderObserver's OFF behavior — not the old
     * unconditional bypass.
     */
    public function testConfigDisabledUsesCalculateOneListFromOrder(): void
    {
        $order = $this->makeRetriableOrder();
        $store = $this->makeStore();
        $this->setUpStoreLoop($order, $store);

        $this->lsr->method('isAdminOrderCustomPriceActive')->with(2)->willReturn(false);

        $this->basketHelper->expects($this->once())
            ->method('calculateOneListFromOrder')
            ->with($order)
            ->willReturn(new Entity\Order());
        $this->basketHelper->expects($this->never())->method('buildOrderFromMagentoOrderItems');
        $this->basketHelper->expects($this->never())->method('formulateCentralOrderRequestFromMagentoOrder');

        $this->syncOrders->execute();
    }

    /**
     * Config ON: the retry cron must use the local bypass builder, consistent with
     * OrderObserver's ON behavior.
     */
    public function testConfigEnabledUsesBuildOrderFromMagentoOrderItems(): void
    {
        $order = $this->makeRetriableOrder();
        $store = $this->makeStore();
        $this->setUpStoreLoop($order, $store);

        $this->lsr->method('isAdminOrderCustomPriceActive')->with(2)->willReturn(true);

        $this->basketHelper->expects($this->once())
            ->method('buildOrderFromMagentoOrderItems')
            ->with($order)
            ->willReturn(new Entity\Order());
        $this->basketHelper->expects($this->never())->method('calculateOneListFromOrder');
        $this->basketHelper->expects($this->never())->method('formulateCentralOrderRequestFromMagentoOrder');

        $this->syncOrders->execute();
    }
}
