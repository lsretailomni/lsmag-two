<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer\AdminHtml;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Model\Payment\PayStore;
use \Ls\Omni\Observer\Adminhtml\SalesObserver;
use \Ls\Omni\Test\Fixture\ApplyLoyaltyPointsInCartFixture;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddress;
use Magento\Quote\Model\Quote\Address\Total\CollectorInterface;
use Magento\Quote\Model\Quote\Address\TotalFactory;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddress;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Payment\Model\Method\Free;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\Quote\TotalsCollectorList;

/**
 * @magentoAppIsolation enabled
 * @magentoAppArea adminhtml
 */
class SalesObserverTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var mixed
     */
    public $request;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var mixed
     */
    public $registry;

    /**
     * @var mixed
     */
    public $customerSession;

    /**
     * @var mixed
     */
    public $checkoutSession;

    /**
     * @var mixed
     */
    public $controllerAction;

    /**
     * @var mixed
     */
    public $contactHelper;

    /**
     * @var mixed
     */
    public $basketHelper;

    /**
     * @var mixed
     */
    public $checkmo;

    /**
     * @var mixed
     */
    public $payAtStore;

    /**
     * @var Free
     */
    public $free;

    /**
     * @var mixed
     */
    public $event;

    /**
     * @var mixed
     */
    public $eventManager;

    /**
     * @var SalesObserver
     */
    public $salesObserver;

    /**
     * @var QuoteIdMaskFactory
     */
    public $quoteIdMaskFactory;

    /**
     * @var mixed
     */
    public $collectorList;

    /**
     * @var TotalFactory
     */
    public $totalFactory;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager      = Bootstrap::getObjectManager();
        $this->request            = $this->objectManager->get(HttpRequest::class);
        $this->fixtures           = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->registry           = $this->objectManager->get(Registry::class);
        $this->customerSession    = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession    = $this->objectManager->get(CheckoutSession::class);
        $this->controllerAction   = $this->objectManager->get(Action::class);
        $this->contactHelper      = $this->objectManager->get(ContactHelper::class);
        $this->basketHelper       = $this->objectManager->get(BasketHelper::class);
        $this->eventManager       = $this->objectManager->create(ManagerInterface::class);
        $this->event              = $this->objectManager->get(Event::class);
        $this->salesObserver      = $this->objectManager->get(SalesObserver::class);
        $this->checkmo            = $this->objectManager->get(Checkmo::class);
        $this->payAtStore         = $this->objectManager->get(PayStore::class);
        $this->free               = $this->objectManager->get(Free::class);
        $this->quoteIdMaskFactory = $this->objectManager->get(QuoteIdMaskFactory::class);
        $this->totalFactory       = $this->objectManager->get(TotalFactory::class);
        $this->collectorList      = $this->objectManager->get(TotalsCollectorList::class);
        $this->loyaltyHelper      = $this->objectManager->get(LoyaltyHelper::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(ApplyLoyaltyPointsInCartFixture::class, ['cart' => '$cart1$'])
    ]
    /**
     * Show payment methods enabled for click and collect shipping method from admin
     */
    public function testUpdatedGrandTotalForShippingAddressType()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->basketHelper->setCalculateBasket('1');
        $basketData = $this->basketHelper->syncBasketWithCentral($cart->getId());
        $this->basketHelper->setOneListCalculationInCheckoutSession($basketData);

        $result = $this->contactHelper->login(AbstractIntegrationTest::USERNAME, AbstractIntegrationTest::PASSWORD);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);

        $shippingAssignment = $this->checkoutSession->getQuote()->getExtensionAttributes()->getShippingAssignments()[0];
        $quote              = $this->checkoutSession->getQuote();

        $total = $this->totalFactory->create(\Magento\Quote\Model\Quote\Address\Total::class);
        $this->eventManager->dispatch(
            'sales_quote_address_collect_totals_before',
            [
                'quote'               => $quote,
                'shipping_assignment' => $shippingAssignment,
                'total'               => $total
            ]
        );

        foreach ($this->collectorList->getCollectors($quote->getStoreId()) as $collector) {
            /** @var CollectorInterface $collector */
            $collector->collect($quote, $shippingAssignment, $total);
        }
        $result = new DataObject();
        $this->event->setQuote($quote)
            ->setShippingAssignment($shippingAssignment)->setTotal($total)->setResult($result);

        // Execute the observer method
        $this->salesObserver->execute(new Observer(
            [
                'event'             => $this->event,
                'request'           => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $expectedLsPointsDiscount = AbstractIntegrationTest::LSR_LOY_POINTS * $this->loyaltyHelper->getPointRate();

        $mobileTransaction = current((array)$this->basketHelper->getOneListCalculationFromCheckoutSession()->getMobiletransaction());
        $this->assertNotEquals(0, count($this->checkoutSession->getQuote()->getAllItems()));
        $this->assertNotNull($this->basketHelper->getOneListCalculationFromCheckoutSession());
        $this->assertEquals(
            (float) $mobileTransaction->getIssuedpoints(),
            (float) $this->checkoutSession->getQuote()->getLsPointsEarn()
        );
        $this->assertEquals($expectedLsPointsDiscount, $this->checkoutSession->getQuote()->getLsPointsDiscount());

        $this->basketHelper->setOneListCalculationInCheckoutSession(null);
        $cart->delete();
        $this->checkoutSession->clearQuote();
        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
    }
}
