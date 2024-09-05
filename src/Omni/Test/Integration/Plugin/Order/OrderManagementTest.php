<?php

namespace Ls\Omni\Test\Integration\Plugin\Order;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Plugin\Order\OrderManagement;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Test\Fixture\CustomerOrder;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea frontend
 */
class OrderManagementTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var OrderManagement
     */
    public $orderManagement;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var \Closure
     */
    private $proceed;

    /**
     * @var OrderManagementInterface
     */
    public $orderManagementInterface;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager            = Bootstrap::getObjectManager();
        $this->fixtures                 = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->orderManagement          = $this->objectManager->get(OrderManagement::class);
        $this->orderManagementInterface = $this->objectManager->create(OrderManagementInterface::class);
        $this->proceed                  = function () {
            $this->proceed;
        };
        $this->basketHelper             = $this->objectManager->get(BasketHelper::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, self::LS_CENTRAL_VERSION, 'website'),
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
    public function testAroundCancel()
    {
        $order = $this->fixtures->get('order');

        $this->orderManagement->aroundCancel(
            $this->orderManagementInterface,
            $this->proceed,
            $order->getId()
        );

        $this->assertNull($this->basketHelper->getCorrectStoreIdFromCheckoutSession());
    }
}
