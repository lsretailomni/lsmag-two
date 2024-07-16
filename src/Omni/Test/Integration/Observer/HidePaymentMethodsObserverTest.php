<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer;

use Laminas\Stdlib\Parameters;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Model\Payment\PayStore;
use \Ls\Omni\Observer\HidePaymentMethods;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethod;
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
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Fixture\AppArea;
use Magento\Quote\Model\QuoteIdMaskFactory;

class HidePaymentMethodsObserverTest extends AbstractIntegrationTest
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
     * @var mixed
     */
    public $event;

    /**
     * @var mixed
     */
    public $eventManager;

    /**
     * @var mixed
     */
    public $hidePaymentMethodsObserver;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    public const PASSWORD = 'Nmswer123@';
    public const EMAIL = 'pipeline_retail@lsretail.com';
    public const USERNAME = 'mc_57745';
    public const INVALID_EMAIL = 'pipeline_retail_pipeline_retail_pipeline_retail_pipeline_retail@lsretail.com';
    public const CUSTOMER_ID = '1';
    public const CS_URL = 'http://20.6.33.78/commerceservice';
    public const CS_VERSION = '2024.4.1';
    public const CS_STORE = 'S0013';
    public const LS_MAG_ENABLE = '1';
    public const INVALID_COUPON_CODE = 'COUPON_CODE';
    public const VALID_COUPON_CODE = 'COUP0119';
    public const RETAIL_INDUSTRY = 'retail';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager              = Bootstrap::getObjectManager();
        $this->request                    = $this->objectManager->get(HttpRequest::class);
        $this->fixtures                   = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->registry                   = $this->objectManager->get(Registry::class);
        $this->customerSession            = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession            = $this->objectManager->get(CheckoutSession::class);
        $this->controllerAction           = $this->objectManager->get(Action::class);
        $this->contactHelper              = $this->objectManager->get(ContactHelper::class);
        $this->basketHelper               = $this->objectManager->get(BasketHelper::class);
        $this->eventManager               = $this->objectManager->create(ManagerInterface::class);
        $this->event                      = $this->objectManager->get(Event::class);
        $this->hidePaymentMethodsObserver = $this->objectManager->get(HidePaymentMethods::class);
        $this->checkmo                    = $this->objectManager->get(Checkmo::class);
        $this->payAtStore                 = $this->objectManager->get(PayStore::class);
        $this->quoteIdMaskFactory         = $this->objectManager->get(QuoteIdMaskFactory::class);
    }

//    /**
//     * @magentoAppIsolation enabled
//     */
//    #[
//        AppArea('frontend'),
//        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
//        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
//        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
//        DataFixture(
//            CustomerFixture::class,
//            [
//                'lsr_username' => AbstractIntegrationTest::USERNAME,
//                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
//                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
//                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
//            ],
//            as: 'customer2'
//        ),
//        DataFixture(
//            CreateSimpleProductFixture::class,
//            [
//                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
//            ],
//            as: 'p1'
//        ),
//        DataFixture(CustomerCart::class, ['customer_id' => '$customer2.id$'], 'cart2'),
//        DataFixture(AddProductToCart::class, ['cart_id' => '$cart2.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
//        DataFixture(SetBillingAddress::class, ['cart_id' => '$cart2.id$']),
//        DataFixture(SetShippingAddress::class, ['cart_id' => '$cart2.id$']),
//        DataFixture(SetDeliveryMethod::class, [
//            'cart_id'      => '$cart2.id$',
//            'carrier_code' => 'clickandcollect',
//            'method_code'  => 'clickandcollect'
//        ]),
//        DataFixture(SetPaymentMethod::class, [
//            'cart_id' => '$cart2.id$',
//            'method'  => 'ls_payment_method_pay_at_store'
//        ])
//    ]
//    /**
//     * Show payment methods enabled for click and collect shipping method from admin
//     */
//    public function testShowPayAtStorePaymentMethod()
//    {
//        $customer = $this->fixtures->get('customer2');
//        $cart     = $this->fixtures->get('cart2');
//        $this->customerSession->setData('customer_id', $customer->getId());
//        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
//        $this->checkoutSession->setQuoteId($cart->getId());
//
//        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);
//
//        $result = $this->contactHelper->login(self::USERNAME, self::PASSWORD);
//        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);
//
//        $shippingMethodCode = $this->checkoutSession->getQuote()->getShippingAddress()->getShippingMethod();
//        $result             = new DataObject();
//        $this->event->setMethodInstance($this->payAtStore)
//            ->setCode($shippingMethodCode)->setResult($result);
//
//        // Execute the observer method
//        $this->hidePaymentMethodsObserver->execute(new Observer(
//            [
//                'event'             => $this->event,
//                'request'           => $this->request,
//                'controller_action' => $this->controllerAction
//            ]
//        ));
//
//        $cart->delete();
//        $this->checkoutSession->clearQuote();
//        $this->basketHelper->setOneListCalculationInCheckoutSession(null);
//        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
//
//        $this->assertTrue($this->event->getResult()->getData('is_available'));
//    }
//
//    /**
//     * @magentoAppIsolation enabled
//     */
//    #[
//        AppArea('frontend'),
//        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
//        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
//        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
//        DataFixture(
//            CustomerFixture::class,
//            [
//                'lsr_username' => AbstractIntegrationTest::USERNAME,
//                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
//                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
//                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
//            ],
//            as: 'customer'
//        ),
//        DataFixture(
//            CreateSimpleProductFixture::class,
//            [
//                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
//            ],
//            as: 'p1'
//        ),
//        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
//        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
//        DataFixture(SetBillingAddress::class, ['cart_id' => '$cart1.id$']),
//        DataFixture(SetShippingAddress::class, ['cart_id' => '$cart1.id$']),
//        DataFixture(SetDeliveryMethod::class, ['cart_id' => '$cart1.id$']),
//        DataFixture(SetPaymentMethod::class, ['cart_id' => '$cart1.id$'])
//    ]
//    /**
//     * Hide pay at store payment method for shipping methods other than click and collect
//     */
//    public function testHidePayAtStorePaymentMethod()
//    {
//        $customer = $this->fixtures->get('customer');
//        $cart     = $this->fixtures->get('cart1');
//        $this->customerSession->setData('customer_id', $customer->getId());
//        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
//        $this->checkoutSession->setQuoteId($cart->getId());
//
//        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);
//
//        $result = $this->contactHelper->login(self::USERNAME, self::PASSWORD);
//        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);
//
//        $shippingMethodCode = $this->checkoutSession->getQuote()->getShippingAddress()->getShippingMethod();
//        $result             = new DataObject();
//        $this->event->setMethodInstance($this->checkmo)
//            ->setCode($shippingMethodCode)->setResult($result);
//
//        // Execute the observer method
//        $this->hidePaymentMethodsObserver->execute(new Observer(
//            [
//                'event'             => $this->event,
//                'request'           => $this->request,
//                'controller_action' => $this->controllerAction
//            ]
//        ));
//
//        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
//        $cart->delete();
//        $this->checkoutSession->clearQuote();
//        $this->basketHelper->setOneListCalculationInCheckoutSession(null);
//
//        $this->assertNull($this->event->getResult()->getData('is_available'));
//    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
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
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart3'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart3.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$cart3.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$cart3.id$']),
        DataFixture(SetDeliveryMethod::class, [
            'cart_id'      => '$cart3.id$',
            'carrier_code' => 'clickandcollect',
            'method_code'  => 'clickandcollect'
        ]),
        DataFixture(SetPaymentMethod::class, ['cart_id' => '$cart3.id$'])
    ]
    /**
     * Hide payment method for click and collect shipping method if not enabled from admin.
     */
    public function testHidePaymentMethodIfNotEnabledForClickandCollect()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart3');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $result = $this->contactHelper->login(self::USERNAME, self::PASSWORD);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);

        $shippingMethodCode = $this->checkoutSession->getQuote()->getShippingAddress()->getShippingMethod();
        $result             = new DataObject();
        $this->event->setMethodInstance($this->checkmo)
            ->setCode($shippingMethodCode)->setResult($result);

        // Execute the observer method
        $this->hidePaymentMethodsObserver->execute(new Observer(
            [
                'event'             => $this->event,
                'request'           => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
        $cart->delete();
        $this->checkoutSession->clearQuote();
        $this->basketHelper->setOneListCalculationInCheckoutSession(null);

        $this->assertNotTrue($this->event->getResult()->getData('is_available'));
    }
}
