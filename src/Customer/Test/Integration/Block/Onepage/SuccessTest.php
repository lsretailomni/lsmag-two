<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Onepage;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Block\Onepage\Success;
use \Ls\Customer\Test\Fixture\BasketCalculateFixture;
use \Ls\Customer\Test\Fixture\CreateSimpleProduct;
use \Ls\Customer\Test\Fixture\CustomerAddressFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Customer\Test\Fixture\CustomerOrder;
use \Ls\Customer\Test\Fixture\OrderCreateFixture;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class SuccessTest extends TestCase
{
    public $block;
    public $objectManager;
    public $httpContext;
    public $fixtures;
    public $customerSession;
    public $checkoutSession;
    public $eventManager;
    private $pageFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->block           = $this->objectManager->get(
            LayoutInterface::class
        )->createBlock(
            Success::class
        );
        $this->httpContext     = $this->objectManager->get(Context::class);
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
        $this->checkoutSession = $this->objectManager->get(CheckoutSession::class);
        $this->eventManager    = $this->objectManager->create(ManagerInterface::class);
        $this->pageFactory     = $this->objectManager->get(PageFactory::class);
        $this->block->setNameInLayout('checkout.success');
        $this->block->setTemplate('Magento_Checkout::success.phtml');
        $page = $this->pageFactory->create();
        $page->addHandle([
            'default',
            'checkout_onepage_success',
        ]);
        $page->getLayout()->generateXml();
    }

    #[
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
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
    public function testPrepareBlockDataForLoggedInUser()
    {
        $this->httpContext->setValue(\Magento\Customer\Model\Context::CONTEXT_AUTH, 1, 1);
        $order = $this->fixtures->get('order');
        $output = $this->block->toHtml();
        $msg = sprintf('Can\'t validate order success page html: %s', $output);
        $ele = [
            "//div[contains(@class, 'checkout-success')]",
            "//p",
            "//a[contains(@class, 'order-number')]",
            sprintf("//strong[contains(text(), '%s')]", $order->getDocumentId())
        ];
        $this->validateCountForXpath($ele, 1, $output, $msg);
    }

    #[
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        DataFixture(
            CreateSimpleProduct::class,
            [
                'lsr_item_id' => '40180',
                'sku'         => '40180'
            ],
            as: 'product'
        ),
        DataFixture(GuestCartFixture::class, as: 'cart1'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart1.id$', 'product_id' => '$product.id$', 'qty' => 2]
        ),
        DataFixture(
            BasketCalculateFixture::class,
            ['cart1' => '$cart1$']
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart1.id$']),
        DataFixture(
            SetDeliveryMethodFixture::class,
            ['cart_id' => '$cart1.id$', 'carrier_code' => 'flatrate', 'method_code' => 'flatrate']
        ),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart1.id$', 'method' => 'checkmo']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart1.id$'], 'order'),
        DataFixture(OrderCreateFixture::class, ['order' => '$order$'], 'order1'),
    ]
    public function testPrepareBlockDataForGuest()
    {
        $order = $this->fixtures->get('order1');
        $output = $this->block->toHtml();
        $msg = sprintf('Can\'t validate order success page html: %s', $output);
        $ele = [
            "//div[contains(@class, 'checkout-success')]",
            "//p",
            sprintf("//span[contains(text(), '%s')]", $order->getDocumentId())
        ];
        $this->validateCountForXpath($ele, 1, $output, $msg);
    }

    #[
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
    public function testPrepareBlockDataForLoggedInUserWithLsrDown()
    {
        $this->httpContext->setValue(\Magento\Customer\Model\Context::CONTEXT_AUTH, 1, 1);
        $order = $this->fixtures->get('order');
        $output = $this->block->toHtml();
        $msg = sprintf('Can\'t validate order success page html: %s', $output);

        $ele = [
            "//div[contains(@class, 'checkout-success')]",
            "//p",
            "//a[contains(@class, 'order-number')]",
            sprintf("//strong[contains(text(), '%s')]", $order->getIncrementId())
        ];

        $this->validateCountForXpath($ele, 1, $output, $msg);
    }

    public function validateCountForXpath($ele, $expected, $output, $msg)
    {
        $eleCount = implode('', $ele);
        $this->assertEquals(
            $expected,
            Xpath::getElementsCountForXpath($eleCount, $output),
            $msg
        );
    }
}
