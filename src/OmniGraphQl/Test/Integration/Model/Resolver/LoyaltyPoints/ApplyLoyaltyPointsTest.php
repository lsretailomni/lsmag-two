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
     * @magentoConfigFixture graphql/session/disable 0
     */
    public function testApplyLoyaltyPoints()
    {
        $customer      = $this->getOrCreateCustomer();
        $product       = $this->getOrCreateProduct();
        $emptyCart     = $this->createCustomerEmptyCart($customer->getId());
        $cart          = $this->addSimpleProduct($emptyCart, $product);
        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());

        //$this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $query = $this->getQuery(
            $maskedQuoteId,
            2.00
        );

        $headerMap = [
            'Authorization' => 'Bearer ' . $this->authToken,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

//        $this->customerSession->setData('customer_id', $customer->getId());
//        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
//        $this->customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $customer->getData('lsr_id'));
//        $this->checkoutSession->setQuoteId($cart->getId());
//        $this->contactHelper->setCardIdInCustomerSession($customer->getLsrCardid());

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerMap
        );

        $cart = $this->cartRepository->get($cart->getId());

        $this->assertNotNull($response);
        $this->assertEquals(
            AbstractIntegrationTest::LOY_POINTS,
            $cart->getLsPointsSpent()
        );

        $basketData         = $this->basketHelper->getOneListCalculationFromCheckoutSession();
        $expectedGrandTotal = $basketData->getTotalAmount() - AbstractIntegrationTest::LOY_POINTS;
        $this->assertEquals(
            $expectedGrandTotal,
            $cart->checkoutSession->getQuote()->getGrandTotal()
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
                        prices {
                        lsdiscount {
                            label
                            amount {
                                value
                                currency
                            }
                        }
                        lstax {         
                          label
                          amount {
                            value
                            currency
                          }
                        }
                        grand_total {
                            value
                        }
                      }                        
                    }
                }
            }
        QUERY;
    }
}
