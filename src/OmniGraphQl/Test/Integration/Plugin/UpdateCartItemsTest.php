<?php

namespace Ls\OmniGraphQl\Test\Integration\Plugin;

use \Ls\Core\Model\LSR;
use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\Event\ManagerInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Checkout\Model\Session;

/**
 * Represents UpdateCartItemPluginTest Class
 */
class UpdateCartItemsTest extends GraphQlTestBase
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
     * @var $authToken
     */
    private $authToken;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    public $maskedQuote;

    /**
     * @var Session
     */
    public $checkoutSession;

    /**
     * @var ManagerInterface
     */
    public $eventManager;

    /**
     * @var BasketHelper
     */
    public $basketHelper;


    public function setUp(): void
    {
        parent::setUp();
        $this->authToken       = $this->loginAndFetchToken();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->maskedQuote     = $this->objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
        $this->checkoutSession = $this->objectManager->create(Session::class);
        $this->eventManager    = $this->objectManager->create(ManagerInterface::class);
        $this->basketHelper    = $this->objectManager->create(BasketHelper::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default')

    ]
    public function testUpdateCartItem()
    {
        $customer      = $this->getOrCreateCustomer();
        $product       = $this->getOrCreateProduct();
        $emptyCart     = $this->createCustomerEmptyCart($customer->getId());
        $cart          = $this->addSimpleProduct($emptyCart, $product);
        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());
        $item          = current($cart->getAllVisibleItems());
        $query         = $this->getQuery($maskedQuoteId, $item->getId(), 5);
        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $headerMap = ['Authorization' => 'Bearer ' . $this->authToken];
        $response  = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerMap
        );

        $basketData = $this->basketHelper->getOneListCalculationFromCheckoutSession();
        $itemsArray = $basketData->getOrderLines();

        $this->assertNotNull($response);
        $this->assertEquals(5, $response['updateCartItems']['cart']['items'][0]['quantity']);
        $this->assertEquals(6, $itemsArray->getOrderLine()[0]->getQuantity());
    }

    /**
     * @param string $maskedQuoteId
     * @param int $itemId
     * @param float $quantity
     * @return string
     */
    private function getQuery(string $maskedQuoteId, int $itemId, float $quantity): string
    {
        return <<<QUERY
mutation {
  updateCartItems(input: {
    cart_id: "{$maskedQuoteId}"
    cart_items:[
      {
        cart_item_id: {$itemId}
        quantity: {$quantity}
      }
    ]
  }) {
    cart {
      items {
        id
        quantity
      }
    }
  }
}
QUERY;
    }
}
