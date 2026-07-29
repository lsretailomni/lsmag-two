<?php

declare(strict_types=1);

namespace Ls\Replication\Test\Unit\Cron;

use Ls\Core\Model\LSR;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\Replication\Cron\SyncOrders;
use Ls\Replication\Helper\ReplicationHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Unit tests for the ticket-85191 branching added to {@see SyncOrders::execute()} at its
 * `formulateCentralOrderRequestFromMagentoOrder()` call site (solution plan section 5.5).
 *
 * Before this ticket, this cron unconditionally called
 * BasketHelper::formulateCentralOrderRequestFromMagentoOrder() (always bypassing remote
 * calculation, with no config gate at all). The fix makes it genuinely respect the new
 * store-scoped toggle:
 *   - config OFF -> BasketHelper::calculateOneListFromOrder() (remote calc, matching the
 *     observer's default/off behaviour)
 *   - config ON -> BasketHelper::buildOrderFromMagentoOrderItems() (local bypass)
 *
 * SyncOrders has a small promoted constructor (no body beyond assignment), so it is safe to
 * construct normally with mocks.
 */
class SyncOrdersTest extends TestCase
{
    /**
     * @var LSR&MockObject
     */
    private $lsr;

    /**
     * @var ReplicationHelper&MockObject
     */
    private $replicationHelper;

    /**
     * @var OrderHelper&MockObject
     */
    private $orderHelper;

    /**
     * @var BasketHelper&MockObject
     */
    private $basketHelper;

    /**
     * @var OrderResourceModel&MockObject
     */
    private $orderResourceModel;

    /**
     * @var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private $storeManager;

    /**
     * @var SyncOrders
     */
    private $cron;

    /**
     * @var StoreInterface&MockObject
     */
    private $store;

    /**
     * @var Order&MockObject
     */
    private $order;

    protected function setUp(): void
    {
        $this->lsr = $this->createMock(LSR::class);
        $this->replicationHelper = $this->createMock(ReplicationHelper::class);
        $this->orderHelper = $this->getMockBuilder(OrderHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrders', 'prepareOrder', 'placeOrder'])
            ->getMock();
        $this->basketHelper = $this->getMockBuilder(BasketHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'calculateOneListFromOrder',
                'buildOrderFromMagentoOrderItems',
                'formulateCentralOrderRequestFromMagentoOrder',
                'setCorrectStoreIdInCheckoutSession',
                'unSetRequiredDataFromCustomerAndCheckoutSessions',
            ])
            ->getMock();
        $this->orderResourceModel = $this->createMock(OrderResourceModel::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->cron = new SyncOrders(
            $this->lsr,
            $this->replicationHelper,
            $this->orderHelper,
            $this->basketHelper,
            $this->orderResourceModel,
            $this->logger,
            $this->storeManager
        );

        $this->lsr->method('isSSM')->willReturn(true);
        $this->store = $this->createMock(StoreInterface::class);
        $this->store->method('getId')->willReturn(1);
        $this->store->method('getWebsiteId')->willReturn(1);
        $this->lsr->method('getAdminStore')->willReturn($this->store);
        $this->lsr->method('isLSR')->willReturn(true);

        $this->order = $this->createMock(Order::class);
        $this->order->method('getStoreId')->willReturn(1);
        $this->order->method('getRelationParentId')->willReturn(null);

        $this->orderHelper->method('getOrders')->willReturn([$this->order]);
        // prepareOrder()/placeOrder() only need to not blow up; a falsy placeOrder() result
        // keeps the success branch (document id bookkeeping) from running, which is
        // irrelevant to what this test is verifying.
        $this->orderHelper->method('prepareOrder')->willReturn(null);
        $this->orderHelper->method('placeOrder')->willReturn(null);
    }

    /**
     * Config OFF (default) - the cron must attempt the remote OneList/basket calculation, not
     * the old unconditional formulateCentralOrderRequestFromMagentoOrder() bypass.
     */
    public function testConfigDisabledUsesRemoteCalculation(): void
    {
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(false);

        $this->basketHelper->expects($this->once())
            ->method('calculateOneListFromOrder')
            ->with($this->order)
            ->willReturn(null);
        $this->basketHelper->expects($this->never())->method('buildOrderFromMagentoOrderItems');
        $this->basketHelper->expects($this->never())->method('formulateCentralOrderRequestFromMagentoOrder');

        $this->cron->execute();
    }

    /**
     * Config ON - the cron must use the local bypass builder.
     */
    public function testConfigEnabledUsesBypass(): void
    {
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);

        $this->basketHelper->expects($this->once())
            ->method('buildOrderFromMagentoOrderItems')
            ->with($this->order)
            ->willReturn(null);
        $this->basketHelper->expects($this->never())->method('calculateOneListFromOrder');
        $this->basketHelper->expects($this->never())->method('formulateCentralOrderRequestFromMagentoOrder');

        $this->cron->execute();
    }
}
