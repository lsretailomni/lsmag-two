<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver\LoyaltyPoints;

use \Ls\Core\Model\LSR;
use Ls\Omni\Helper\ContactHelper;
use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoAppArea adminhtml
 *
 * Represents ApplyLoyaltyPoints Model Class
 */
class ApplyLoyaltyPointsTest extends GraphQlTestBase
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

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @var ContactHelper
     */
    public $contactHelper;
    public $customerSession;
    public $registry;

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
        $this->cartRepository  = $this->objectManager->get(CartRepositoryInterface::class);
        $this->contactHelper   = $this->objectManager->get(ContactHelper::class);
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
        $this->registry        = $this->objectManager->get(Registry::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea adminhtml
     */
    public function testApplyLoyaltyPoints()
    {
        $customer      = $this->getOrCreateCustomer();
        $product       = $this->getOrCreateProduct();
        $emptyCart     = $this->createCustomerEmptyCart($customer->getId());
        $cart          = $this->addSimpleProduct($emptyCart, $product);
        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $query = $this->getQuery(
            $maskedQuoteId,
            AbstractIntegrationTest::LOY_POINTS
        );

        $headerMap = [
            'Authorization' => 'Bearer ' . $this->authToken
        ];

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerMap
        );

        $cart = $this->cartRepository->get($cart->getId());

        $this->assertNotNull($response);
        $this->assertNotNull($response['applyLsLoyaltyPoints']['cart']['loyalty_points_info']['points_earn']);
        $this->assertEquals(
            AbstractIntegrationTest::LOY_POINTS,
            $response['applyLsLoyaltyPoints']['cart']['loyalty_points_info']['points_spent']
        );
        $this->assertNotNull($response['applyLsLoyaltyPoints']['cart']['loyalty_points_info']['points_discount']);
        $this->assertNotNull($response['applyLsLoyaltyPoints']['cart']['loyalty_points_info']['point_rate']);
        $this->assertEquals(
            AbstractIntegrationTest::LOY_POINTS,
            $cart->getLsPointsSpent()
        );
    }

    /**
     * @param string $maskedQuoteId
     * @param $loyaltyPoints
     * @return string
     */
    private function getQuery(string $maskedQuoteId, $loyaltyPoints): string
    {
        return <<<QUERY
            mutation {
              applyLsLoyaltyPoints(
                input:
                  { 
                    cart_id: "{$maskedQuoteId}"
                    loyalty_points: {$loyaltyPoints} 
                  }
                ) {
                    cart {
                        loyalty_points_info
                        {
                            points_earn
                            points_spent
                            points_discount
                            point_rate
                        }                      
                    }
                }
            }
        QUERY;
    }
}
