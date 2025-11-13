<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver\GiftCard;

use \Ls\Core\Model\LSR;
use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
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

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

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
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'store', 'default'),
        Config(LSR::SC_REPLICATION_CENTRAL_TYPE, AbstractIntegrationTest::SC_REPLICATION_CENTRAL_TYPE, 'website'),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'store', 'default' ),
        Config(LSR::SC_WEB_SERVICE_URI, AbstractIntegrationTest::SC_WEB_SERVICE_URI, 'website' ),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'store', 'default'),
        Config(LSR::SC_ODATA_URI, AbstractIntegrationTest::SC_ODATA_URI, 'website'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'store', 'default'),
        Config(LSR::SC_USERNAME, AbstractIntegrationTest::SC_USERNAME, 'website'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'store', 'default'),
        Config(LSR::SC_PASSWORD, AbstractIntegrationTest::SC_PASSWORD, 'website'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::BASE_URL, 'website'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'store', 'default'),
        Config(LSR::SC_COMPANY_NAME, AbstractIntegrationTest::SC_COMPANY_NAME, 'website'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'store', 'default'),
        Config(LSR::SC_ENVIRONMENT_NAME, AbstractIntegrationTest::SC_ENVIRONMENT_NAME, 'website'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'store', 'default'),
        Config(LSR::SC_TENANT, AbstractIntegrationTest::SC_TENANT, 'website'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'store', 'default'),
        Config(LSR::SC_CLIENT_ID, AbstractIntegrationTest::SC_CLIENT_ID, 'website'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'store', 'default'),
        Config(LSR::SC_CLIENT_SECRET, AbstractIntegrationTest::SC_CLIENT_SECRET, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::WEB_STORE, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'website'),
        Config(LSR::SC_SERVICE_LS_CENTRAL_VERSION, AbstractIntegrationTest::LS_CENTRAL_VERSION, 'store', 'default'),

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

        $this->assertNotNull($response);
        $this->assertEquals(
            AbstractIntegrationTest::GIFTCARD,
            $this->checkoutSession->getQuote()->getLsGiftCardNo()
        );
        $this->assertEquals(
            AbstractIntegrationTest::GIFTCARD_AMOUNT,
            $this->checkoutSession->getQuote()->getLsGiftCardAmountUsed()
        );

        $cart = $this->cartRepository->get($cart->getId());
        $this->assertEquals(
            AbstractIntegrationTest::GIFTCARD,
            $cart->getLsGiftCardNo()
        );

        $this->assertEquals(
            AbstractIntegrationTest::GIFTCARD_AMOUNT,
            $cart->getLsGiftCardAmountUsed()
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
