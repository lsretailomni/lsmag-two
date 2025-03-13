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
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::LSR_ORDER_EDIT, self::LSR_ORDER_EDIT, 'store', 'default'),
        Config(LSR::LS_ENABLE_COUPON_ELEMENTS, self::ENABLE_COUPON_ELEMENTS, 'store', 'default'),
        Config(LSR::LS_COUPONS_SHOW_ON_CHECKOUT, 0, 'store', 'default'),
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
