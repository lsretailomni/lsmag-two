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
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Magento\Quote\Model\ResourceModel\Quote\Item as QuoteItemResourceModel;
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

    /**
     * @var CheckoutSession&MockObject
     */
    private $checkoutSession;

    /**
     * Records every magic (get/set/uns/has-prefixed) call made through
     * {@see CheckoutSession::__call()} on {@see $checkoutSession}, as [$method, $args] pairs.
     *
     * @var array<int, array{0: string, 1: array}>
     */
    private $checkoutSessionCalls = [];

    /**
     * @var array<int, QuoteItem&MockObject>
     */
    private $quoteItems = [];

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
        // unsetData() has no declared method of its own on CheckoutSession - it's backed by the
        // inherited SessionManager::__call() magic method (unlike setQuoteId(), which IS a real
        // declared method and is mocked normally via PHPUnit, not via __call()). createMock()
        // mocks __call() itself (since it's a real declared public method), so a plain
        // createMock() already intercepts every magic call and returns null by default; a
        // recording callback is attached to it so the unsetData() call under test can be
        // asserted on.
        $this->checkoutSessionCalls = [];
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->checkoutSession->method('__call')->willReturnCallback(
            function (string $method, array $args): void {
                $this->checkoutSessionCalls[] = [$method, $args];
            }
        );
        $this->basketHelper->method('getCheckoutSession')->willReturn($this->checkoutSession);
        $this->basketHelper->method('getOneListAdmin')->willReturn($this->createMock(RootMobileTransaction::class));
        $this->basketHelper->method('setOneListQuote')->willReturn($this->createMock(RootMobileTransaction::class));

        $this->itemHelper = $this->getMockBuilder(ItemHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setDiscountedPricesForItems', 'setBaseCurrencyFieldsFromItemPrice'])
            ->getMock();
        $this->itemHelper->quoteResourceModel = $this->createMock(QuoteResourceModel::class);
        $this->itemHelper->itemResourceModel = $this->createMock(QuoteItemResourceModel::class);

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
        $this->quoteItems = [$this->createMock(QuoteItem::class), $this->createMock(QuoteItem::class)];
        $this->quote->method('getAllItems')->willReturn($this->quoteItems);
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
     * Config ON + LSR enabled - in addition to skipping the recalculation (covered above), the
     * `stopCalcRowTotal` checkout-session flag must be explicitly cleared so that a value stuck
     * at `1` from an earlier admin action (which would otherwise make
     * {@see \Ls\Omni\Plugin\Quote\Item\AbstractItemPlugin::afterGetCalculationPriceOriginal()}
     * discard the custom price during Magento's native `calcRowTotal()`) does not silently
     * suppress the custom price.
     */
    public function testClearsStopCalcRowTotalFlagWhenConfigEnabledAndLsrEnabled(): void
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);

        $this->basketHelper->expects($this->never())->method('getOneListAdmin');
        $this->basketHelper->expects($this->never())->method('update');
        $this->itemHelper->expects($this->never())->method('setDiscountedPricesForItems');

        $this->plugin->afterSaveQuote($this->subject, 'result');

        $this->assertContains(
            ['unsetData', ['stopCalcRowTotal']],
            $this->checkoutSessionCalls,
            'Expected the stopCalcRowTotal checkout-session flag to be cleared when the custom'
            . ' price toggle is active, so a value stuck at 1 from an earlier admin action does'
            . ' not suppress the custom price during Magento\'s native calcRowTotal().'
        );
    }

    /**
     * Config ON + LSR enabled - the toggle-ON `elseif` branch trusts Magento's own native item
     * pricing/totals collection (already run by Create::saveQuote()'s own internal
     * collectTotals() call) as-is for Price/RowTotal, and only explicitly syncs the secondary
     * base-currency/tax-inclusive fields via ItemHelper::setBaseCurrencyFieldsFromItemPrice()
     * (mirroring the proven-working admin-order-create custom-price bypass pattern), then
     * persists the quote.
     */
    public function testSyncsBaseCurrencyFieldsAndSavesQuoteWhenConfigEnabledAndLsrEnabled(): void
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);

        $this->itemHelper->expects($this->once())
            ->method('setBaseCurrencyFieldsFromItemPrice')
            ->with($this->quote);
        $this->itemHelper->quoteResourceModel->expects($this->exactly(2))
            ->method('save')
            ->with($this->quote);

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

    /**
     * Config ON + LSR enabled - {@see CreatePlugin::beforeSaveQuote()} must clear the
     * `stopCalcRowTotal` checkout-session flag BEFORE
     * Magento\Sales\Model\AdminOrder\Create::saveQuote() runs its own internal
     * collectTotals() call, otherwise a value stuck at 1 from an earlier admin action would
     * cause the custom price to be discarded for the totals collected during the current
     * request (the after plugin alone is one step too late for that).
     */
    public function testBeforeSaveQuoteClearsStopCalcRowTotalFlagWhenConfigEnabledAndLsrEnabled(): void
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);

        $this->plugin->beforeSaveQuote($this->subject);

        $this->assertContains(
            ['unsetData', ['stopCalcRowTotal']],
            $this->checkoutSessionCalls,
            'Expected beforeSaveQuote() to clear the stopCalcRowTotal checkout-session flag'
            . ' before Create::saveQuote() runs its internal collectTotals() call.'
        );
    }

    /**
     * Config OFF - {@see CreatePlugin::beforeSaveQuote()} must not touch the checkout session at
     * all; the OneList recalc path manages the flag itself via setGrandTotalGivenQuote().
     */
    public function testBeforeSaveQuoteDoesNothingWhenConfigDisabled(): void
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(false);

        $this->basketHelper->expects($this->never())->method('getCheckoutSession');

        $this->plugin->beforeSaveQuote($this->subject);

        $this->assertSame([], $this->checkoutSessionCalls);
    }
}
