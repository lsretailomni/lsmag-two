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
 * Represents RemoveGiftCard Model Class
 */
class RemoveGiftCardTest extends GraphQlTestBase
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
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default')

    ]
    public function testRemoveGiftCard()
    {
        $customer  = $this->getOrCreateCustomer();
        $product   = $this->getOrCreateProduct();
        $emptyCart = $this->createCustomerEmptyCart($customer->getId());
        $cart      = $this->addSimpleProduct($emptyCart, $product);
        $cart->setLsGiftCardNo(AbstractIntegrationTest::GIFTCARD);
        $cart->setLsGiftCardPin(AbstractIntegrationTest::GIFTCARD_PIN);
        $cart->setLsGiftCardAmountUsed(AbstractIntegrationTest::GIFTCARD_AMOUNT);
        $cart->save();
        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());
        $headerMap     = ['Authorization' => 'Bearer ' . $this->authToken];

        $removeQuery = $this->getQuery(
            $maskedQuoteId,
            '',
            '',
            0
        );

        $removeResponse = $this->graphQlMutation(
            $removeQuery,
            [],
            '',
            $headerMap
        );

        // $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $cart = $this->cartRepository->get($cart->getId());

        $this->assertNotNull($removeResponse);
        $this->assertEquals(
            null,
            $cart->getLsGiftCardNo()
        );
        $this->assertEquals(
            0,
            $cart->getLsGiftCardAmountUsed()
        );
    }

    /**
     * @param string $maskedQuoteId
     * @param string $giftcard
     * @param string $pin
     * @param int $amount
     * @return string
     */
    private function getQuery(
        string $maskedQuoteId,
        $giftcard = '',
        $pin = '',
        $amount = 0
    ): string {
        return <<<QUERY
                mutation {
                  removeLsGiftCard(
                    input:
                      { 
                        cart_id: "{$maskedQuoteId}"
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
