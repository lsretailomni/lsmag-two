<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Observer\CartObserver;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
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
use Magento\TestFramework\Fixture\AppArea;

class CartObserverTest extends AbstractIntegrationTest
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
    public $eventManager;

    /**
     * @var mixed
     */
    public $cartObserver;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManager    = Bootstrap::getObjectManager();
        $this->request          = $this->objectManager->get(HttpRequest::class);
        $this->fixtures         = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->registry         = $this->objectManager->get(Registry::class);
        $this->customerSession  = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession  = $this->objectManager->get(CheckoutSession::class);
        $this->controllerAction = $this->objectManager->get(Action::class);
        $this->contactHelper    = $this->objectManager->get(ContactHelper::class);
        $this->basketHelper     = $this->objectManager->get(BasketHelper::class);
        $this->eventManager     = $this->objectManager->create(ManagerInterface::class);
        $this->cartObserver     = $this->objectManager->get(CartObserver::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
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
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    /**
     * Test cart observer with items
     */
    public function testCartObserverWithOneListSave()
    {
        $customer      = $this->fixtures->get('customer');
        $cart          = $this->fixtures->get('cart1');
        $expectedTotal = "85.5";
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $result = $this->contactHelper->login(AbstractIntegrationTest::USERNAME, AbstractIntegrationTest::PASSWORD);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);

        // Execute the observer method
        $this->cartObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction,
                'items'             => $cart->getAllVisibleItems()
            ]
        ));

        $quoteId = $this->checkoutSession->getQuoteId();
        $this->assertNotNull($quoteId);
        $this->assertNotEquals(0, count($this->checkoutSession->getQuote()->getAllItems()));
        $this->assertNotNull($this->basketHelper->getOneListCalculationFromCheckoutSession());
        $this->assertEquals(
            $this->basketHelper->getOneListCalculationFromCheckoutSession()->getPointsRewarded(),
            $this->checkoutSession->getQuote()->getLsPointsEarn()
        );
        $this->assertEquals(
            $this->basketHelper->getOneListCalculationFromCheckoutSession()->getTotalAmount(),
            $this->checkoutSession->getQuote()->getGrandTotal()
        );

        $cart->delete();
        $this->basketHelper->setOneListCalculationInCheckoutSession(null);
        $this->checkoutSession->clearQuote();
        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer2'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer2.id$'], 'cart2')
    ]
    /**
     * Test cart observer with null items in cart.
     *
     * @return void
     */
    public function testCartObserverWithOneListNull()
    {
        $customer = $this->fixtures->get('customer2');
        $cart     = $this->fixtures->get('cart2');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $result = $this->contactHelper->login(AbstractIntegrationTest::USERNAME, AbstractIntegrationTest::PASSWORD);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);

        // Execute the observer method
        $this->cartObserver->execute(new Observer(
            [
                'request'           => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);

        $quoteId = $this->checkoutSession->getQuoteId();
        $this->assertNotNull($quoteId);
        $this->assertEquals(0, count($this->checkoutSession->getQuote()->getAllItems()));
        $this->assertEquals(0, $this->checkoutSession->getQuote()->getLsPointsEarn());
        $this->assertNull($this->basketHelper->getOneListCalculationFromCheckoutSession());
    }
}
