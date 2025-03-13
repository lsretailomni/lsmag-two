<?php

namespace Ls\Omni\Test\Integration\Controller\Ajax;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;
use Magento\TestFramework\EventManager;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

class CouponsTest extends AbstractController
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var SerializerInterface
     */
    public $json;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var Registry
     */
    public $registry;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var EventManager
     */
    public $eventManager;

    /**
     * @var ContactHelper
     */
    public $contactHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->json            = $this->objectManager->get(SerializerInterface::class);
        $this->registry        = $this->objectManager->get(Registry::class);
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession = $this->objectManager->get(CheckoutSession::class);
        $this->eventManager    = $this->objectManager->create(ManagerInterface::class);
        $this->contactHelper   = $this->objectManager->get(ContactHelper::class);
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
    public function testExecute()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $result = $this->contactHelper->login(AbstractIntegrationTest::USERNAME, AbstractIntegrationTest::PASSWORD);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->getHeaders()
            ->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->dispatch('omni/ajax/coupons');
        $content = json_decode($this->getResponse()->getBody());
        $this->assertNotNull($content->output);
        $this->assertStringContainsString('available-coupons', $content->output);
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
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default')
    ]
    public function testExecuteNotAjaxRequest()
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('omni/ajax/coupons');
        $this->assertRedirect(
            $this->stringContains('checkout/cart')
        );
    }
}
