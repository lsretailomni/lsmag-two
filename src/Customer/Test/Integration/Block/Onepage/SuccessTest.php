<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Block\Onepage;

use Ls\Core\Model\LSR;
use Ls\Customer\Block\Onepage\Success;
use Ls\Customer\Test\Fixture\CreateSimpleProduct;
use Ls\Customer\Test\Fixture\CustomerAddressFixture;
use Ls\Customer\Test\Fixture\CustomerFixture;
use Ls\Customer\Test\Fixture\CustomerOrder;
use Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
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
    }

    #[
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
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
    public function testPrepareBlockData()
    {
        $order = $this->fixtures->get('order');
        $this->block->setNameInLayout('checkout.success');
        $this->block->setTemplate('Magento_Checkout::success.phtml');
        $page = $this->pageFactory->create();
        $page->addHandle([
            'default',
            'checkout_onepage_success',
        ]);
        $page->getLayout()->generateXml();
        $output = $this->block->toHtml();
        $this->assertStringContainsString(
            (string)__(sprintf('Your order # is: <span>%s</span>', $order->getDocumentId())),
            $output
        );
//        $ele = [
//            "//div[contains(@class, 'checkout-success')]",
//            "//p",
//            sprintf("//th[contains(text(), '%s')]", __('Subtotal'))
//        ];
//        $eleCount = implode('', $ele);
//        $msg = sprintf('Can\'t validate order items labels in Html: %s', $output);
//        $this->assertEquals(
//            1,
//            Xpath::getElementsCountForXpath($eleCount, $output),
//            $msg
//        );
    }
}
