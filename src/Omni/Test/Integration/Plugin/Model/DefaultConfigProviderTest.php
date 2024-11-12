<?php

namespace Ls\Omni\Test\Integration\Plugin\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Test\Fixture\CustomerFixture;
use \Ls\Omni\Plugin\Checkout\Model\DefaultConfigProvider;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Checkout\Model\DefaultConfigProvider as CoreDefaultConfigProvider;

/**
 * @magentoAppArea frontend
 */
class DefaultConfigProviderTest extends AbstractIntegrationTest
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
     * @var DefaultConfigProvider
     */
    public $defaultConfigProvider;

    /**
     * @var CoreDefaultConfigProvider
     */
    public $coreDefaultConfigProvider;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

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
    public $customerSession;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager             = Bootstrap::getObjectManager();
        $this->fixtures                  = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->defaultConfigProvider     = $this->objectManager->get(DefaultConfigProvider::class);
        $this->coreDefaultConfigProvider = $this->objectManager->get(CoreDefaultConfigProvider::class);
        $this->checkoutSession           = $this->objectManager->get(CheckoutSession::class);
        $this->customerSession           = $this->objectManager->get(CustomerSession::class);
        $this->eventManager              = $this->objectManager->create(ManagerInterface::class);
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
    public function testAfterGetConfig()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $quoteItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
        $itemId     = 1;
        foreach ($quoteItems as $quoteItem) {
            $itemId = $quoteItem->getId();
        }

        $resultArr['totalsData']['items'][0]['item_id'] = $itemId;

        $this->checkoutSession->setQuoteId($cart->getId());
        $response = $this->defaultConfigProvider->afterGetConfig($this->coreDefaultConfigProvider, $resultArr);

        $this->assertArrayHasKey('discountprice', $response['quoteItemData'][0]);
        $this->assertTrue(
            ($response['quoteItemData'][0]["discountprice"] != 0.00
                && $response['quoteItemData'][0]["discountprice"] != "")
        );

        $cart->delete();
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
    public function testAfterGetConfigNoCustomPrice()
    {
        $customer = $this->fixtures->get('customer');
        $cart     = $this->fixtures->get('cart1');
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $quoteItems = $this->checkoutSession->getQuote()->getAllVisibleItems();
        $itemId     = 1;
        foreach ($quoteItems as $quoteItem) {
            $itemId = $quoteItem->getId();
            $quoteItem->setCustomPrice(0);
        }

        $resultArr['totalsData']['items'][0]['item_id'] = $itemId;

        $this->checkoutSession->setQuoteId($cart->getId());
        $response = $this->defaultConfigProvider->afterGetConfig($this->coreDefaultConfigProvider, $resultArr);

        $this->assertArrayHasKey('discountprice', $response['quoteItemData'][0]);
        $this->assertTrue($response['quoteItemData'][0]["discountprice"] == "");

        $cart->delete();
    }
}
