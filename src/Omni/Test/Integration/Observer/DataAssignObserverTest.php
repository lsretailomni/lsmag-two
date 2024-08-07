<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use Ls\Omni\Test\Fixture\ApplyLoyaltyPointsInCartFixture;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Test\Fixture\CustomerOrder;
use \Ls\Omni\Test\Fixture\OrderCreateFixture;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Model\Payment\PayStore;
use \Ls\Omni\Observer\DataAssignObserver;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethod;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Framework\Exception\ValidatorException;
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
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Fixture\AppArea;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\Quote\TotalsCollectorList;

class DataAssignObserverTest extends AbstractIntegrationTest
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
     * @var DataAssignObserver
     */
    public $dataAssignObserver;

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
    public const ADYEN_RESPONSE = [
        'pspReference'  => 'pspreference',
        'paymentMethod' => 'adyen_cc',
        'authResult'    => true
    ];

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
        $this->dataAssignObserver = $this->objectManager->get(DataAssignObserver::class);
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
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LICENSE, 'website'),
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
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180'
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        ),
        DataFixture(
            CustomerOrder::class,
            [
                'customer' => '$customer$',
                'cart1'    => '$cart1$',
                'address'  => '$address$',
                'payment'  => 'checkmo'
            ],
            as: 'order'
        )
    ]
    /**
     * Verify exception throw for order with click and collect payment method and
     * flatrate shipping.
     */
    public function testOrderUpdatesWithException()
    {
        $quote = $this->fixtures->get('cart1');
        $order = $this->fixtures->get('order');

        $quote->getPayment()->setMethod("ls_payment_method_pay_at_store");

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $quote->getAllVisibleItems()]);

        $this->expectException(ValidatorException::class);
        // Execute the observer method
        $this->dataAssignObserver->execute(new Observer(
            [
                'quote' => $quote,
                'order' => $order
            ]
        ));

        $quote->delete();
        $this->checkoutSession->clearQuote();
        $this->basketHelper->setOneListCalculationInCheckoutSession(null);
    }

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
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LICENSE, 'website'),
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
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180'
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        ),
        DataFixture(ApplyLoyaltyPointsInCartFixture::class, ['cart' => '$cart1$']),
        DataFixture(
            CustomerOrder::class,
            [
                'customer' => '$customer$',
                'cart1'    => '$cart1$',
                'address'  => '$address$',
                'payment'  => 'checkmo'
            ],
            as: 'order'
        )
    ]
    /**
     * Verify order object updates with click and collect shipping, pay at store payment method and
     * LS Points and coupon code usage.
     */
    public function testOrderUpdatesWithFlatRateAndCncMethod()
    {
        $quote = $this->fixtures->get('cart1');
        $order = $this->fixtures->get('order');

        $quote->getShippingAddress()->setShippingMethod('clickandcollect_clickandcollect');
        $quote->setPickupStore(AbstractIntegrationTest::STORE_PICKUP);
        $quote->setCouponCode(AbstractIntegrationTest::VALID_COUPON_CODE);
        $quote->getPayment()->setMethod("ls_payment_method_pay_at_store");

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $quote->getAllVisibleItems()]);

        // Execute the observer method
        $this->dataAssignObserver->execute(new Observer(
            [
                'quote' => $quote,
                'order' => $order
            ]
        ));

        $this->assertEquals(AbstractIntegrationTest::LSR_LOY_POINTS, $order->getLsPointsSpent());
        $this->assertEquals(AbstractIntegrationTest::STORE_PICKUP, $order->getPickupStore());
        $this->assertEquals(AbstractIntegrationTest::VALID_COUPON_CODE, $order->getCouponCode());

        $quote->delete();
        $this->checkoutSession->clearQuote();
        $this->basketHelper->setOneListCalculationInCheckoutSession(null);
    }
}
