<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver\GiftCard;

use \Ls\Core\Model\LSR;
use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;

/**
 * Represents ApplyGiftCard Model Class
 */
class ApplyGiftCardTest extends GraphQlTestBase
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
    public function testApplyGiftCard()
    {
        $customer      = $this->getOrCreateCustomer();
        $product       = $this->getOrCreateProduct();
        $emptyCart     = $this->createCustomerEmptyCart($customer->getId());
        $cart          = $this->addSimpleProduct($emptyCart, $product);
        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());
        $query         = $this->getQuery(
            $maskedQuoteId,
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN,
            AbstractIntegrationTest::GIFTCARD_AMOUNT
        );

        $headerMap = ['Authorization' => 'Bearer ' . $this->authToken];
        $response  = $this->graphQlMutation(
            $query,
            [],
            '',
            $headerMap
        );

        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $basketData         = $this->basketHelper->getOneListCalculationFromCheckoutSession();
        $discountOrderLines = $basketData->getOrderDiscountLines()->getOrderDiscountLine();

        $this->assertNotNull($response);
        $this->assertEquals(
            AbstractIntegrationTest::GIFTCARD,
            $this->checkoutSession->getQuote()->getLsGiftCardNo()
        );
        $this->assertEquals(
            AbstractIntegrationTest::GIFTCARD_AMOUNT,
            $this->checkoutSession->getQuote()->getLsGiftCardAmountUsed()
        );

        $expectedGrandTotal = $basketData->getTotalAmount() - AbstractIntegrationTest::GIFTCARD_AMOUNT;
        $this->assertEquals(
            $expectedGrandTotal,
            $this->checkoutSession->getQuote()->getGrandTotal()
        );
    }

    /**
     * @param string $maskedQuoteId
     * @param $giftcard
     * @param $pin
     * @param $amount
     * @return string
     */
    private function getQuery(string $maskedQuoteId, $giftcard, $pin, $amount): string
    {
        return <<<QUERY
                mutation {
                  applyLsGiftCard(
                    input:
                      { 
                        cart_id: "{$maskedQuoteId}"
                        code: "{$giftcard}" 
                        pin: "{$pin}" 
                        amount: "{$amount}" 
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
