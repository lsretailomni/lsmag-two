<?php

namespace Ls\Omni\Test\Integration\Controller\Stock;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Fixture\FlatDataReplication;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use \Ls\Replication\Cron\ReplEcommStoresTask;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Store\Model\ScopeInterface;

class StoreTest extends AbstractController
{
    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->checkoutSession = $this->objectManager->get(CheckoutSession::class);
        $this->eventManager    = $this->objectManager->create(ManagerInterface::class);
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommStoresTask::class,
                'scope'   => ScopeInterface::SCOPE_WEBSITE
            ],
            as: 'stores'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
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
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    public function testExecute()
    {
        $params = [
            'storeid' => 'S0001'
        ];

        $cart     = $this->fixtures->get('cart1');
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $this->getRequest()->setParams($params);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->getHeaders()
            ->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');

        $this->dispatch('omni/stock/Store');
        $content = json_decode($this->getResponse()->getBody());
        $this->assertNotNull($content);
    }
}
