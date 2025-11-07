<?php

namespace Ls\Customer\Test\Integration\Controller\Order;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CreateSimpleProduct;
use \Ls\Customer\Test\Fixture\CustomerAddressFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Fixture\CustomerOrder;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\DocumentIdType;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class CancelTest extends AbstractController
{
    public $objectManager;
    public $fixtures;
    public $customerSession;
    public $orderHelper;
    public $registry;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager   = Bootstrap::getObjectManager();
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
        $this->orderHelper     = $this->objectManager->get(OrderHelper::class);
        $this->registry        = $this->objectManager->get(Registry::class);
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
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
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
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
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        ),
        DataFixture(
            CreateSimpleProduct::class,
            [
                'lsr_item_id' => '40180',
                'sku'         => '40180'
            ],
            as: 'product'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$product.id$', 'qty' => 1]),
        DataFixture(
            CustomerOrder::class,
            [
                'customer' => '$customer$',
                'cart1'    => '$cart1$',
                'address'  => '$address$'
            ],
            as: 'order'
        )
    ]
    public function testExecute()
    {
        $magentoOrder = $this->fixtures->get('order');
        $customer     = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $centralOrder = $this->orderHelper->fetchOrder($magentoOrder->getDocumentId(), DocumentIdType::ORDER);

        $this->getRequest()->setParams([
            'magento_order_id' => $magentoOrder->getId(),
            'central_order_id' => $centralOrder->getLscMemberSalesBuffer()->getDocumentId(),
            'id_type'          => DocumentIdType::ORDER
        ]);

        $this->getRequest()->setMethod(Http::METHOD_POST);
        $this->dispatch('customer/order/cancel');
        $this->assertRedirect(
            $this->stringContains('customer/order/view')
        );
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
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
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        )
    ]
    public function testExecuteWithoutMagentoOrder()
    {
        $customer = $this->fixtures->get('customer');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $orders = $this->orderHelper->getCurrentCustomerOrderHistory(LSR::MAX_RECENT_ORDER);

        if ($orders) {
            $order = current($orders);
            $this->getRequest()->setParams([
                'central_order_id' => $order->getId(),
                'id_type'          => $order->getIdType()
            ]);

            $this->getRequest()->setMethod(Http::METHOD_POST);
            $this->dispatch('customer/order/cancel');
            $this->assertRedirect(
                $this->stringContains('customer/account')
            );
        }
    }
}
