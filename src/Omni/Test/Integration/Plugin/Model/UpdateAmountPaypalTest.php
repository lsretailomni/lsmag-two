<?php

namespace Ls\Omni\Test\Integration\Plugin\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Test\Fixture\CustomerFixture;
use \Ls\Omni\Plugin\Checkout\Model\UpdateAmountPaypal;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddress;
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
class UpdateAmountPaypalTest extends AbstractIntegrationTest
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
     * @var UpdateAmountPaypal
     */
    public $updateAmountPaypal;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager      = Bootstrap::getObjectManager();
        $this->fixtures           = $this->objectManager
            ->get(DataFixtureStorageManager::class)->getStorage();
        $this->updateAmountPaypal = $this->objectManager->get(UpdateAmountPaypal::class);
        $this->checkoutSession    = $this->objectManager->get(CheckoutSession::class);
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
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetDeliveryMethod::class, [
            'cart_id'      => '$cart1.id$',
            'carrier_code' => 'clickandcollect',
            'method_code'  => 'clickandcollect'
        ])
    ]
    public function testBeforeSaveAddressInformationWithPaymentExist()
    {
        $cart = $this->fixtures->get('cart1');

        $this->checkoutSession->setQuoteId($cart->getId());
        $this->checkoutSession->getQuote()->getPayment()->setMethod('payflowpro');
        $result = $this->updateAmountPaypal->afterGetAmounts($cart, []);

        $this->assertArrayHasKey('subtotal', $result);

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
            as: 'customer2'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(CustomerCart::class, ['customer_id' => '$customer2.id$'], 'cart1'),
        DataFixture(AddProductToCart::class, ['cart_id' => '$cart1.id$', 'product_id' => '$p1.id$', 'qty' => 1]),
        DataFixture(SetBillingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetShippingAddress::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetDeliveryMethod::class, [
            'cart_id'      => '$cart1.id$',
            'carrier_code' => 'clickandcollect',
            'method_code'  => 'clickandcollect'
        ])
    ]
    public function testBeforeSaveAddressInformationWithPaymentNotExist()
    {
        $cart = $this->fixtures->get('cart1');
        $this->checkoutSession->setQuoteId($cart->getId());
        $this->checkoutSession->getQuote()->getPayment()->setMethod('ls_payment_method_pay_at_store');

        $result = $this->updateAmountPaypal->afterGetAmounts($cart, []);
        $this->assertArrayNotHasKey('subtotal', $result);

        $cart->delete();
    }
}
