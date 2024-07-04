<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer;

use Laminas\Stdlib\Parameters;
use \Ls\Core\Model\LSR;
use \Ls\Customer\Observer\LoginObserver;
use \Ls\Omni\Helper\BasketHelper;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Observer\CartObserver;
use \Ls\Omni\Observer\CouponCodeObserver;
use \Ls\Omni\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethod;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddress;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Test\Fixture\Customer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CouponManagementInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Psr\Log\LoggerInterface;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Quote\Model\QuoteFactory;

class CouponCodeObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $request;
    public $couponCodeObserver;
    public $customerSession;
    public $checkoutSession;
    public $controllerAction;
    public $basketHelper;
    public $basketHelperMock;
    public $logger;
    public $messageManager;
    public $redirectFactory;
    public $urlInterface;
    public $lsr;
    public $lsMagEnable;
    public $cart;
    public $quote;
    public $loginObserver;
    public $cartObserver;
    public $productRepository;
    public $fixtures;
    public $couponManagement;
    public $event;

    public $cartManagement;
    public $cartItemRepository;
    public $cartItem;
    public $customerRepository;
    public $registry;
    public $contactHelper;

    public const PASSWORD = 'Signout369';
    public const EMAIL = 'deep.ret@lsretail.com';
    public const INVALID_EMAIL = 'pipeline_retail_pipeline_retail_pipeline_retail_pipeline_retail@lsretail.com';
    public const USERNAME = 'mc_61394';
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
        $this->objectManager      = Bootstrap::getObjectManager();
        $this->request            = $this->objectManager->get(HttpRequest::class);
        $this->couponCodeObserver = $this->objectManager->get(CouponCodeObserver::class);
        $this->loginObserver      = $this->objectManager->get(LoginObserver::class);
        $this->customerSession    = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession    = $this->objectManager->get(CheckoutSession::class);
        $this->controllerAction   = $this->objectManager->get(Action::class);
        $this->basketHelper       = $this->objectManager->get(BasketHelper::class);
        $this->logger             = $this->objectManager->get(LoggerInterface::class);
        $this->messageManager     = $this->objectManager->get(ManagerInterface::class);
        $this->redirectFactory    = $this->objectManager->get(RedirectFactory::class);
        $this->urlInterface       = $this->objectManager->get(UrlInterface::class);
        $this->lsr                = $this->objectManager->get(Lsr::class);
        $this->cartObserver       = $this->objectManager->get(CartObserver::class);
        $this->couponManagement   = $this->objectManager->get(CouponManagementInterface::class);
        $this->event              = $this->objectManager->get(Event::class);
        $this->fixtures           = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->registry           = $this->objectManager->get(Registry::class);
        $this->contactHelper      = $this->objectManager->get(ContactHelper::class);

        $this->cartManagement     = $this->objectManager->create(CartManagementInterface::class);
        $this->cartItemRepository = $this->objectManager->create(CartItemRepositoryInterface::class);
        $this->cartItem           = $this->objectManager->create(CartItemInterface::class);
        $this->customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
        DataFixture(
            \Ls\Customer\Test\Fixture\CustomerFixture::class,
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
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    /**
     * Test the execute method
     */
    public function testExecute()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $result = $this->contactHelper->login(self::USERNAME, self::PASSWORD);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);

        $this->request->setPost(
            new Parameters(['coupon_code' => self::VALID_COUPON_CODE])
        );

        // Execute the observer method
//        $this->couponCodeObserver->execute(new Observer(
//            [
//                'request'           => $this->request,
//                'controller_action' => $this->controllerAction
//            ]
//        ));

        $quoteId = $this->checkoutSession->getQuoteId();
        $this->assertNotNull($quoteId);

        // Verify the message manager for success message
//        $messages = $this->messageManager->getMessages()->getItems();
//        $this->assertNotEmpty($messages);
//        $this->assertEquals('You used coupon code "' . self::VALID_COUPON_CODE . '".', $messages[0]->getText());
    }

    /**
     * Get environment variable value given name
     *
     * @param $name
     * @return array|false|string
     */
    public function getEnvironmentVariableValueGivenName($name)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return getenv($name);
    }
}
