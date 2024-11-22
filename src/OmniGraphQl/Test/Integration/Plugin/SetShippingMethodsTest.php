<?php

namespace Integration\Plugin;

use \Ls\Core\Model\LSR;
use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;

/**
 * Represents SetShippingMethodsOnCart Class
 */
class SetShippingMethodsTest extends GraphQlTestBase
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

    public function testShippingMethodsOnCart()
    {
        $customer  = $this->getOrCreateCustomer();
        $product   = $this->getOrCreateProduct();
        $emptyCart = $this->createCustomerEmptyCart($customer->getId());
        $cart      = $this->addSimpleProduct($emptyCart, $product);
        $cart      = $this->setShippingAddress($cart);

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());
        $query         = $this->getQuery($maskedQuoteId, "S0013");
        $headerMap     = ['Authorization' => 'Bearer ' . $this->authToken];

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $this->assertEquals(
            'clickandcollect',
            $response['setShippingMethodsOnCart']['cart']['shipping_addresses'][0]['selected_shipping_method']['carrier_code']
        );
        $this->assertEquals(
            'clickandcollect',
            $response['setShippingMethodsOnCart']['cart']['shipping_addresses'][0]['selected_shipping_method']['method_code']
        );
    }

    /**
     * @param string $maskedQuoteId
     * @param int $itemId
     * @param float $quantity
     * @return string
     */
    private function getQuery($maskedQuoteId, $storeId): string
    {
        return <<<QUERY
            mutation {
                setShippingMethodsOnCart(
                 input: {
                        cart_id: "{$maskedQuoteId}", 
                        shipping_methods: [
                            {
                                carrier_code: "clickandcollect", 
                                method_code: "clickandcollect"
                            }                            
                        ], 
                        store_id: "{$storeId}", 
                        selected_date: "Today", 
                        selected_date_time_slot: "10:30 PM"
                    }
               ) {
                 cart {
                   id
                   shipping_addresses {
                      selected_shipping_method {
                         carrier_code
                         method_code
                         carrier_title
                         method_title
                      }
                   }
                 }
               }
           }
    QUERY;
    }

    public function setShippingAddress($cart)
    {
        $cart->getShippingAddress()
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setCountryId('US')
            ->setCity('Montgomery')
            ->setPostcode("36104")
            ->setRegionId(1)
            ->setStreet(['Green str, 67'])
            ->setCollectShippingRates(true);

        $cart->save();

        return $cart;

        //$this->quoteRepository->save($this->customerCart->getQuote());
    }
}
