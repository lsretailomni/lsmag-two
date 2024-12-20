<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver\Stock;

use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\Omni\Helper\BasketHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;

/**
 * Represents CartItemsAvailabilityInStoreOutput Model Class
 */
class CartItemsAvailabilityTest extends GraphQlTestBase
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
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql'),
    ]
    public function testCartsItemAvailability()
    {
        $customer      = $this->getOrCreateCustomer();
        $product       = $this->getOrCreateProduct();
        $emptyCart     = $this->createCustomerEmptyCart($customer->getId());
        $cart          = $this->addSimpleProduct($emptyCart, $product);
        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());
        $query         = $this->getQuery(
            $maskedQuoteId,
            'S0001',
        );
        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $headerMap = ['Authorization' => 'Bearer ' . $this->authToken];
        $response  = $this->graphQlQuery(
            $query,
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $this->assertGreaterThan(0, count($response['cart_items_availability_in_store']['stock']));
        $this->assertArrayHasKey('name', $response['cart_items_availability_in_store']['stock'][0]);
        $this->assertArrayHasKey('qty', $response['cart_items_availability_in_store']['stock'][0]);
        $this->assertArrayHasKey('status', $response['cart_items_availability_in_store']['stock'][0]);
    }

    /**
     * @param string $maskedQuoteId
     * @param $giftcard
     * @param $pin
     * @param $amount
     * @return string
     */
    private function getQuery(string $maskedQuoteId, $storeId): string
    {
        return <<<QUERY
        {
            cart_items_availability_in_store (
             cart_id: "{$maskedQuoteId}",
             store_id: "{$storeId}"
            ) {
                stock 
                    {
                        sku
                        name
                        qty
                        status
                        display
                    }
                
            }
        }
        QUERY;
    }
}
