<?php

namespace Ls\Omni\Test\Integration\Plugin\Block\Adminhtml\Order\Create\Shipping;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Test\Fixture\ApplyLoyaltyPointsInCartFixture;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Fixture\CustomerAddressFixture;
use \Ls\Omni\Test\Fixture\CustomerOrder;
use \Ls\Omni\Plugin\Block\Adminhtml\Order\Create\Shipping\FormPlugin;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddress;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddress;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Method\Form;

/**
 * @magentoAppArea adminhtml
 */
class FormPluginTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var RequestInterface
     */
    public $request;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var Form
     */
    public $form;

    /**
     * @var FormPlugin
     */
    public $formPlugin;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var UrlInterface
     */
    public $urlBuilder;

    /**
     * @var Quote
     */
    public $quoteSession;

    /**
     * @var BasketHelper
     */
    public $basketHelper;

    /**
     * @var ItemHelper
     */
    public $itemHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures      = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->form          = $this->objectManager->get(Form::class);
        $this->formPlugin    = $this->objectManager->get(FormPlugin::class);
        $this->eventManager  = $this->objectManager->create(ManagerInterface::class);
        $this->urlBuilder    = $this->objectManager->get(UrlInterface::class);
        $this->request       = $this->objectManager->get(RequestInterface::class);
        $this->quoteSession  = $this->objectManager->get(Quote::class);
        $this->basketHelper  = $this->objectManager->get(BasketHelper::class);
        $this->itemHelper    = $this->objectManager->get(ItemHelper::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
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
        ),
        DataFixture(
            CustomerOrder::class,
            [
                'customer' => '$customer$',
                'cart1'    => '$cart1$',
                'address'  => '$address$',
                'payment'  => 'free'
            ],
            as: 'order'
        )
    ]
    public function testGetShippingRatesForCnc()
    {
        $cart = $this->fixtures->get('cart1');

        $this->setRequestUrl();
        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);
        $this->quoteSession->setQuoteId($cart->getId());

        $oneList = $this->basketHelper->getOneListAdmin(
            $cart->getCustomerEmail(),
            $cart->getStore()->getWebsiteId(),
            false
        );

        $oneList = $this->basketHelper->setOneListQuote($cart, $oneList);
        $this->basketHelper->setOneListCalculationInCheckoutSession($oneList);
        $basketData = $this->basketHelper->update($oneList);
        $quote      = $this->quoteSession->getQuote();
        $this->itemHelper->setDiscountedPricesForItems($quote, $basketData, 2);
        $quote = $this->quoteSession->getQuote();
        $quote->getShippingAddress()->setShippingMethod('clickandcollect_clickandcollect');

        $this->form->setQuote($quote);
        $result = $this->quoteSession->getQuote()->getShippingAddress()->getGroupedAllShippingRates();

        $response = $this->formPlugin->afterGetShippingRates($this->form, $result);

        $this->assertEquals('clickandcollect', array_key_first($response));
        $this->assertEquals(1, count($response));
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
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
    ,
        DataFixture(
            CustomerOrder::class,
            [
                'customer' => '$customer$',
                'cart1'    => '$cart1$',
                'address'  => '$address$',
                'payment'  => 'free'
            ],
            as: 'order'
        )
    ]
    public function testGetShippingRatesForFlat()
    {
        $cart = $this->fixtures->get('cart1');

        $this->setRequestUrl();
        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);
        $this->quoteSession->setQuoteId($cart->getId());

        $oneList = $this->basketHelper->getOneListAdmin(
            $cart->getCustomerEmail(),
            $cart->getStore()->getWebsiteId(),
            false
        );

        $oneList = $this->basketHelper->setOneListQuote($cart, $oneList);
        $this->basketHelper->setOneListCalculationInCheckoutSession($oneList);
        $basketData = $this->basketHelper->update($oneList);
        $quote      = $this->quoteSession->getQuote();
        $this->itemHelper->setDiscountedPricesForItems($quote, $basketData, 2);
        $quote = $this->quoteSession->getQuote();
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');

        $this->form->setQuote($quote);
        $result = $this->quoteSession->getQuote()->getShippingAddress()->getGroupedAllShippingRates();

        $response = $this->formPlugin->afterGetShippingRates($this->form, $result);

        $this->assertNotTrue(array_key_exists('clickandcollect', $response));
    }

    public function setRequestUrl()
    {
        $this->request->setControllerName('order_edit')
            ->setActionName('index')
            ->setRouteName('sales');

        $this->urlBuilder->setRequest($this->request);
        $this->objectManager->get(SessionManagerInterface::class)->setData('_form_key', 'salt');
    }
}
