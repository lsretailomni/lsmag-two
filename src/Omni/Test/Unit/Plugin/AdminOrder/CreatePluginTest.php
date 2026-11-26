<?php

namespace Ls\Omni\Test\Unit\Plugin\AdminOrder;

use Ls\Core\Model\LSR;
use Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\Data;
use Ls\Omni\Helper\ItemHelper;
use Ls\Omni\Helper\OrderHelper;
use Ls\Omni\Plugin\AdminOrder\CreatePlugin;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Verifies CreatePlugin::afterSaveQuote() skips the OneList recalculation (and the resulting
 * quote item price overwrite) when the admin_order_custom_price config is enabled for the
 * quote's store, per ticket 85191 follow-up.
 */
class CreatePluginTest extends TestCase
{
    /** @var BasketHelper|MockObject */
    private $basketHelper;

    /** @var ItemHelper|MockObject */
    private $itemHelper;

    /** @var LSR|MockObject */
    private $lsr;

    /** @var Data|MockObject */
    private $data;

    /** @var OrderHelper|MockObject */
    private $orderHelper;

    /** @var CreatePlugin */
    private $plugin;

    /** @var Quote|MockObject */
    private $quote;

    /** @var Create|MockObject */
    private $subject;

    protected function setUp(): void
    {
        $this->basketHelper = $this->createMock(BasketHelper::class);
        $this->itemHelper   = $this->createMock(ItemHelper::class);
        $this->lsr          = $this->createMock(LSR::class);
        $this->data         = $this->createMock(Data::class);
        $this->orderHelper  = $this->createMock(OrderHelper::class);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $this->orderHelper->storeManager = $storeManager;

        $checkoutSession = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->orderHelper->checkoutSession = $checkoutSession;

        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(5);

        $this->quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['setLsPointsEarn'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote->method('getId')->willReturn(100);
        $this->quote->method('getAllVisibleItems')->willReturn([$this->createMock(\Magento\Quote\Model\Quote\Item::class)]);
        $this->quote->method('getStoreId')->willReturn(1);
        $this->quote->method('getCustomer')->willReturn($customer);
        $this->quote->method('getAllItems')->willReturn([$this->createMock(\Magento\Quote\Model\Quote\Item::class)]);
        $this->quote->method('setLsPointsEarn')->willReturnSelf();

        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn(1);
        $this->quote->method('getStore')->willReturn($store);

        $customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->basketHelper->method('getCustomerSession')->willReturn($customerSession);

        $this->subject = $this->createMock(Create::class);
        $this->subject->method('getQuote')->willReturn($this->quote);

        $this->plugin = new CreatePlugin(
            $this->basketHelper,
            $this->itemHelper,
            $this->createMock(LoggerInterface::class),
            $this->lsr,
            $this->data,
            $this->orderHelper
        );
    }

    public function testSkipsOneListRecalculationWhenAdminOrderCustomPriceActive()
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(true);
        $this->orderHelper->checkoutSession->expects($this->once())->method('setQuoteId')->with(100);

        $this->basketHelper->expects($this->never())->method('getOneListAdmin');
        $this->basketHelper->expects($this->never())->method('setOneListQuote');
        $this->basketHelper->expects($this->never())->method('update');
        $this->itemHelper->expects($this->never())->method('setDiscountedPricesForItems');
        $this->itemHelper->expects($this->once())
            ->method('setBaseCurrencyFieldsFromItemPrice')
            ->with($this->quote);

        $this->plugin->afterSaveQuote($this->subject, new DataObject());
    }

    public function testRunsOneListRecalculationWhenAdminOrderCustomPriceInactive()
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(false);
        $this->orderHelper->checkoutSession->expects($this->once())->method('setQuoteId')->with(100);

        $oneList = $this->createMock(\Ls\Omni\Client\Ecommerce\Entity\OneList::class);
        $basketData = $this->createMock(\Ls\Omni\Client\Ecommerce\Entity\Order::class);
        $this->basketHelper->expects($this->once())->method('getOneListAdmin')->willReturn($oneList);
        $this->basketHelper->expects($this->once())->method('setOneListQuote')->willReturn($oneList);
        $this->basketHelper->expects($this->once())->method('update')->willReturn($basketData);
        $this->itemHelper->expects($this->once())->method('setDiscountedPricesForItems');
        $this->itemHelper->expects($this->never())->method('setBaseCurrencyFieldsFromItemPrice');

        $this->plugin->afterSaveQuote($this->subject, new DataObject());
    }

    public function testFallsBackToCatalogPriceWhenOneListCalculationFails()
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(true);
        $this->lsr->method('isAdminOrderCustomPriceActive')->with(1)->willReturn(false);
        $this->orderHelper->checkoutSession->expects($this->once())->method('setQuoteId')->with(100);

        $oneList = $this->createMock(\Ls\Omni\Client\Ecommerce\Entity\OneList::class);
        $this->basketHelper->expects($this->once())->method('getOneListAdmin')->willReturn($oneList);
        $this->basketHelper->expects($this->once())->method('setOneListQuote')->willReturn($oneList);
        $this->basketHelper->expects($this->once())->method('update')->willReturn(null);
        $this->itemHelper->expects($this->never())->method('setDiscountedPricesForItems');
        $this->itemHelper->expects($this->once())
            ->method('setBaseCurrencyFieldsFromItemPrice')
            ->with($this->quote);

        $this->plugin->afterSaveQuote($this->subject, new DataObject());
    }

    public function testDoesNothingWhenLsrDisabled()
    {
        $this->lsr->method('isLSR')->with(1)->willReturn(false);
        $this->orderHelper->checkoutSession->expects($this->once())->method('setQuoteId')->with(100);

        $this->basketHelper->expects($this->never())->method('getOneListAdmin');
        $this->basketHelper->expects($this->never())->method('update');
        $this->itemHelper->expects($this->never())->method('setDiscountedPricesForItems');
        $this->itemHelper->expects($this->never())->method('setBaseCurrencyFieldsFromItemPrice');

        $this->plugin->afterSaveQuote($this->subject, new DataObject());
    }
}
