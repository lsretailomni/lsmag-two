<?php

namespace Ls\Omni\Test\Integration\Plugin\Checkout\CustomerData;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Plugin\Checkout\CustomerData\Cart as CartPlugin;
use \Ls\Omni\Test\Fixture\ApplyLoyaltyPointsInCartFixture;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddress;
use Magento\Checkout\CustomerData\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\LayoutFactory;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea adminhtml
 */
class CartPluginTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var Cart
     */
    public $cart;

    /**
     * @var CartPlugin
     */
    public $cartPlugin;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @var CustomerSession
     */
    public $customerSession;

    /**
     * @var LayoutFactory
     */
    public $layoutFactory;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->cart            = $this->objectManager->get(Cart::class);
        $this->cartPlugin      = $this->objectManager->get(CartPlugin::class);
        $this->eventManager    = $this->objectManager->create(ManagerInterface::class);
        $this->checkoutSession = $this->objectManager->get(CheckoutSession::class);
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
        $this->layoutFactory   = $this->objectManager->get(LayoutFactory::class)->create();
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
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, self::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
        Config('payment/payment_services/active', 0 , 'store', 'default'),

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
        DataFixture(SetBillingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(ApplyLoyaltyPointsInCartFixture::class, ['cart' => '$cart1$']),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        )
    ]
    public function testGetSectionData()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $this->checkoutSession->setLoadInactive(true);
        $this->layoutFactory->createBlock(\Magento\Catalog\Block\ShortcutButtons::class)->toHtml();
        $result = $this->cart->getSectionData();

        $response = $this->cartPlugin->afterGetSectionData($this->cart, $result);

        $this->assertArrayHasKey('lsPriceOriginal', $response['items'][0]);
        $this->assertArrayHasKey('lsDiscountAmount', $response['items'][0]);
        $this->assertArrayHasKey('product_price', $response['items'][0]);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::LSR_ORDER_EDIT, self::LSR_ORDER_EDIT, 'store', 'default'),
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
        DataFixture(SetBillingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(ApplyLoyaltyPointsInCartFixture::class, ['cart' => '$cart1$']),
        DataFixture(
            CustomerAddressFixture::class,
            [
                'customer_id' => '$customer.entity_id$'
            ],
            as: 'address'
        )
    ]
    public function testGetSectionDataWithLsrDisbled()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $this->checkoutSession->setLoadInactive(true);
        $this->layoutFactory->createBlock(\Magento\Catalog\Block\ShortcutButtons::class)->toHtml();
        $result = $this->cart->getSectionData();

        $response = $this->cartPlugin->afterGetSectionData($this->cart, $result);

        $this->assertEquals("", $response['items'][0]['lsPriceOriginal']);
        $this->assertEquals("", $response['items'][0]['lsDiscountAmount']);
    }
}
