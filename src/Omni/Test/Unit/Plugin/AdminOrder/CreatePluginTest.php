<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Unit\Plugin\AdminOrder;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\CentralEcommerce\Entity\RootMobileTransaction;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\Data;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\Omni\Plugin\AdminOrder\CreatePlugin;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order\Item;

/**
 * Unit tests for the ticket-85191 follow-up fix in
 * {@see CreatePlugin::afterSaveQuote()} (report.md's "Follow-up fix" section): this plugin
 * runs on every quote save while an admin builds an order interactively (e.g. "Update
 * Items") and, today, unconditionally recalculates the OneList/basket via a live remote call
 * (BasketHelper::update()) and then overwrites item prices from that response
 * (ItemHelper::setDiscountedPricesForItems()) - which clobbers any admin-entered custom price
 * before the order is ever placed. The fix adds the same
 * LSR::isAdminOrderCustomPriceActive() guard used elsewhere in this ticket to this plugin's
 * existing isLSR() condition, skipping the recalculation (and therefore the price overwrite)
 * entirely when the toggle is on.
 *
 * CreatePlugin has a small promoted constructor (no body beyond assignment), so it is safe to
 * construct normally with mocks.
 */
class CreatePluginTest extends TestCase
{
    /**
     * @var BasketHelper&MockObject
     */
    private $basketHelper;

    /**
     * @var ItemHelper&MockObject
     */
    private $itemHelper;

    /**
     * @var LSR&MockObject
     */
    private $lsr;

    /**
     * @var Data&MockObject
     */
    private $data;

    /**
     * @var OrderHelper&MockObject
     */
    private $orderHelper;

    /**
     * @var BackendQuoteSession&MockObject
     */
    private $backendQuoteSession;

    /**
     * @var CreatePlugin
     */
    private $plugin;

    /**
     * @var Create&MockObject
     */
    private $subject;

    /**
     * @var Quote&MockObject
     */
    private $quote;

    protected function setUp(): void
    {
        $this->basketHelper = $this->getMockBuilder(BasketHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getCustomerSession',
                'getCheckoutSession',
                'setCorrectStoreIdInCheckoutSession',
                'getOneListAdmin',
                'setOneListQuote',
                'update',
                'getCurrentQuote',
            ])
            ->getMock();
        $this->basketHelper->method('getCustomerSession')->willReturn($this->createMock(CustomerSession::class));
        $this->basketHelper->method('getCheckoutSession')->willReturn($this->createMock(CheckoutSession::class));
        $this->basketHelper->method('getOneListAdmin')->willReturn($this->createMock(RootMobileTransaction::class));
        $this->basketHelper->method('setOneListQuote')->willReturn($this->createMock(RootMobileTransaction::class));

        $this->itemHelper = $this->getMockBuilder(ItemHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setDiscountedPricesForItems'])
            ->getMock();
        $this->itemHelper->quoteResourceModel = $this->createMock(QuoteResourceModel::class);

        $this->lsr = $this->createMock(LSR::class);
        $this->data = $this->createMock(Data::class);
        $this->orderHelper = $this->getMockBuilder(OrderHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $this->orderHelper->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->backendQuoteSession = $this->createMock(BackendQuoteSession::class);

        $this->plugin = new CreatePlugin(
            $this->basketHelper,
            $this->itemHelper,
            $this->createMock(LoggerInterface::class),
            $this->lsr,
            $this->data,
            $this->orderHelper,
            $this->backendQuoteSession
        );

        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(10);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(1);

        // Quote::getCouponCode()/getLsPosDataEntries()/getLsPointsSpent() are magic
        // (DataObject::__call()-backed, not explicitly declared), so PHPUnit's mock builder
        // cannot configure them via onlyMethods()/method(). Only the methods with real logic
        // that would otherwise need unavailable collaborators (e.g. the store manager) are
        // overridden here; the data-backed getters are driven via the real setData()/setId()/
        // setStoreId() (all explicitly declared, concrete methods) on the same instance.
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllVisibleItems', 'getAllItems', 'getStore', 'getCustomer'])
            ->getMock();
        $this->quote->setId(5);
        $this->quote->setStoreId(1);
        $this->quote->setData('coupon_code', null);
        $this->quote->setData('ls_pos_data_entries', null);
        $this->quote->setData('ls_points_spent', 0);
        $this->quote->method('getAllVisibleItems')->willReturn([$this->createMock(Item::class)]);
        $this->quote->method('getAllItems')->willReturn([$this->createMock(Item::class)]);
        $this->quote->method('getStore')->willReturn($store);
        $this->quote->method('getCustomer')->willReturn($customer);

        $this->subject = $this->createMock(Create::class);
        $this->subject->method('getQuote')->willReturn($this->quote);
    }

    /**
     * Config ON + LSR enabled - the OneList recalculation must be skipped entirely: no remote
     * call (update()) and no resulting price overwrite (setDiscountedPricesForItems()).
     */
    public function testSkipsRecalculationWhenConfigEnabledAndLsrEnabled(): void
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);

        $this->basketHelper->expects($this->never())->method('getOneListAdmin');
        $this->basketHelper->expects($this->never())->method('update');
        $this->itemHelper->expects($this->never())->method('setDiscountedPricesForItems');

        $this->plugin->afterSaveQuote($this->subject, 'result');
    }

    /**
     * Config OFF + LSR enabled - existing behaviour must be unchanged: the recalculation still
     * runs and item prices are still refreshed from its response.
     */
    public function testStillRecalculatesWhenConfigDisabledAndLsrEnabled(): void
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(false);
        $this->lsr->method('getWebsiteConfig')->willReturn(1);

        $this->basketHelper->expects($this->once())->method('getOneListAdmin')->willReturn(
            $this->createMock(RootMobileTransaction::class)
        );
        $this->basketHelper->expects($this->once())->method('update')->willReturn(
            $this->createMock(RootMobileTransaction::class)
        );
        $this->basketHelper->method('getCurrentQuote')->willReturn($this->quote);
        $this->itemHelper->expects($this->once())->method('setDiscountedPricesForItems');

        $this->plugin->afterSaveQuote($this->subject, 'result');
    }

    /**
     * LSR disabled - nothing happens regardless of the new config's value (existing guard
     * behaviour preserved).
     */
    public function testDoesNothingWhenLsrDisabledRegardlessOfConfig(): void
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(false);
        $this->lsr->method('isAdminOrderCustomPriceActive')->willReturn(true);

        $this->basketHelper->expects($this->never())->method('getOneListAdmin');
        $this->basketHelper->expects($this->never())->method('update');
        $this->itemHelper->expects($this->never())->method('setDiscountedPricesForItems');

        $this->plugin->afterSaveQuote($this->subject, 'result');
    }
}
