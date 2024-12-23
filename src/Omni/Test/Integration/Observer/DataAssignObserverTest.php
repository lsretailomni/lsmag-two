<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Test\Fixture\ApplyLoyaltyPointsInCartFixture;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Test\Fixture\CustomerOrder;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Observer\DataAssignObserver;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\Exception\ValidatorException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Quote\Model\QuoteFactory;
use Magento\TestFramework\Fixture\AppArea;

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
    public $basketHelper;

    /**
     * @var mixed
     */
    public $eventManager;

    /**
     * @var DataAssignObserver
     */
    public $dataAssignObserver;

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
        $this->basketHelper       = $this->objectManager->get(BasketHelper::class);
        $this->eventManager       = $this->objectManager->create(ManagerInterface::class);
        $this->dataAssignObserver = $this->objectManager->get(DataAssignObserver::class);
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
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, self::LICENSE, 'website'),
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

        $this->checkoutSession->setQuoteId($order->getQuoteId());
        $quote->getPayment()->setMethod("ls_payment_method_pay_at_store");

        $this->expectException(ValidatorException::class);
        // Execute the observer method
        $this->dataAssignObserver->execute(new Observer(
            [
                'quote' => $quote,
                'order' => $order
            ]
        ));

        $quote->delete();
        $this->basketHelper->setOneListCalculationInCheckoutSession(null);
        $this->checkoutSession->clearQuote();
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

        $this->checkoutSession->setQuoteId($quote->getId());
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
        $this->basketHelper->setOneListCalculationInCheckoutSession(null);
        $this->checkoutSession->clearQuote();
    }
}
