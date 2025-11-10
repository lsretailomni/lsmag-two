<?php

namespace Ls\Omni\Test\Integration\Plugin\Model;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Plugin\Checkout\Model\ShippingInformationManagement;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\ShippingInformationManagement as CheckoutShippingInformationManagement;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Quote\Model\QuoteRepository;
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
class ShippingInformationManagementTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var LayoutProcessor
     */
    public $layoutProcessor;

    /**
     * @var ShippingInformationManagement
     */
    public $shippingInformationManagement;

    /**
     * @var ShippingInformationInterface
     */
    public $shippingInformationInterface;

    /**
     * @var CheckoutShippingInformationManagement
     */
    public $checkoutShippingInformationManagement;

    /**
     * @var QuoteRepository
     */
    public $quoteRepository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager                         = Bootstrap::getObjectManager();
        $this->fixtures                              = $this->objectManager
            ->get(DataFixtureStorageManager::class)->getStorage();
        $this->shippingInformationManagement         = $this->objectManager->get(ShippingInformationManagement::class);
        $this->checkoutShippingInformationManagement = $this->objectManager
            ->get(CheckoutShippingInformationManagement::class);
        $this->shippingInformationInterface          = $this->objectManager->get(ShippingInformationInterface::class);
        $this->quoteRepository                       = $this->objectManager->get(QuoteRepository::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
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
        Config(LSR::LSR_ORDER_EDIT, self::LSR_ORDER_EDIT, 'store', 'default'),
        Config(LSR::LS_ENABLE_COUPON_ELEMENTS, self::ENABLE_COUPON_ELEMENTS, 'store', 'default'),
        Config(LSR::LS_COUPONS_SHOW_ON_CHECKOUT, 0, 'store', 'default'),  
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
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1])
    ]
    public function testBeforeSaveAddressInformation()
    {
        $cart   = $this->fixtures->get('cart1');
        $cartId = $cart->getId();

        $this->shippingInformationInterface->getExtensionAttributes()->setPickupDate('2024-01-01');
        $this->shippingInformationInterface->getExtensionAttributes()->setPickupTimeslot('15:00:00');
        $this->shippingInformationInterface->getExtensionAttributes()->setSubscriptionId('11111');
        $this->shippingInformationInterface->getExtensionAttributes()->setPickupStore('S00001');

        $this->shippingInformationManagement->beforeSaveAddressInformation(
            $this->checkoutShippingInformationManagement,
            $cartId,
            $this->shippingInformationInterface
        );

        $quote = $this->quoteRepository->getActive($cartId);

        $this->assertEquals('S00001', $quote->getPickupStore());
        $this->assertEquals('2024-01-01 03:00 PM', $quote->getPickupDateTimeslot());
        $this->assertEquals('11111', $quote->getLsSubscriptionId());
    }
}
