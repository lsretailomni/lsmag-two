<?php

namespace Ls\Omni\Test\Integration\Plugin\AdminOrder;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ItemHelper;
use \Ls\Omni\Test\Fixture\ApplyLoyaltyPointsInCartFixture;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Plugin\AdminOrder\CreatePlugin;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Test\Fixture\AddProductToCart;
use Magento\Quote\Test\Fixture\CustomerCart;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Backend\Model\Session\Quote;

/**
 * @magentoAppArea adminhtml
 */
class CreatePluginTest extends AbstractIntegrationTest
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
     * @var Create
     */
    public $model;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var Quote
     */
    public $quoteSession;

    /**
     * @var CreatePlugin
     */
    public $createPlugin;

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
        $this->model         = $this->objectManager->get(Create::class);
        $this->eventManager  = $this->objectManager->create(ManagerInterface::class);
        $this->quoteSession  = $this->objectManager->create(Quote::class);
        $this->createPlugin  = $this->objectManager->create(CreatePlugin::class);
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
        DataFixture(ApplyLoyaltyPointsInCartFixture::class, ['cart' => '$cart1$'])
    ]
    public function testCreatePlugin()
    {
        $quote = $this->fixtures->get('cart1');

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $quote->getAllVisibleItems()]);

        $this->quoteSession->setQuoteId($quote->getId());

        $oneList = $this->basketHelper->getOneListAdmin(
            $quote->getCustomerEmail(),
            $quote->getStore()->getWebsiteId(),
            false
        );

        $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
        $this->basketHelper->setOneListCalculationInCheckoutSession($oneList);
        $basketData = $this->basketHelper->update($oneList);
        $quote      = $this->quoteSession->getQuote();
        $this->itemHelper->setDiscountedPricesForItems($quote, $basketData, 2);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');

        $this->quoteSession->setQuote($quote);
        $this->model->setQuote($quote);

        $result = new DataObject();
        $this->createPlugin->afterSaveQuote($this->model, $result);

        $this->assertEquals($basketData->getPointsRewarded(), $this->quoteSession->getQuote()->getLsPointsEarn());
        $this->assertEquals(self::LSR_LOY_POINTS, $this->quoteSession->getQuote()->getLsPointsSpent());
    }
}
