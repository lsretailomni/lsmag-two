<?php

namespace Integration\Plugin;

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
 * Represents RemoveCouponFromCartPlugin Class
 */
class RemoveCouponCodeTest extends GraphQlTestBase
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
        $customer  = $this->getOrCreateCustomer();
        $product   = $this->getOrCreateProduct();
        $emptyCart = $this->createCustomerEmptyCart($customer->getId());
        $cart      = $this->addSimpleProduct($emptyCart, $product);
        $cart->setCouponCode(AbstractIntegrationTest::VALID_COUPON_CODE)->collectTotals()->save();
        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());
        $query         = $this->getQuery($maskedQuoteId);
        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $headerMap = ['Authorization' => 'Bearer ' . $this->authToken];
        $response  = $this->graphQlQuery(
            $query,
            [],
            '',
            $headerMap
        );

        $basketData         = $this->basketHelper->getOneListCalculationFromCheckoutSession();
        $discountOrderLines = $basketData->getOrderDiscountLines()->getOrderDiscountLine();

        $this->assertNotNull($response);
        $this->assertArrayHasKey('discounts', $response['cart']['items'][0]['prices']);
        $this->assertArrayHasKey('total_item_discount', $response['cart']['items'][0]['prices']);
        $this->assertGreaterThan(0, $response['cart']['items'][0]['prices']['total_item_discount']['value']);
        $this->assertGreaterThan(0, count($response['cart']['items'][0]['prices']['discounts']));
        $this->assertNotNull($response['cart']['items'][0]['prices']['discounts'][0]['amount']['value']);
        $this->assertGreaterThan(0, count($discountOrderLines));

        $removeCouponQuery = $this->getQuery($maskedQuoteId, true);
        $removeResponse    = $this->graphQlMutation(
            $removeCouponQuery,
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($removeResponse);
        $this->assertNull($removeResponse['removeCouponFromCart']['cart']['applied_coupon']);
    }

    /**
     * @param string $maskedQuoteId
     * @param int $itemId
     * @param float $quantity
     * @return string
     */
    private function getQuery(string $maskedQuoteId, $removeCoupon = false): string
    {
        if ($removeCoupon) {
            return <<<QUERY
                mutation {
                  removeCouponFromCart(
                    input:
                      { cart_id: "{$maskedQuoteId}" }
                    ) {
                        cart {
                          applied_coupon {
                            code
                          }
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
        } else {
            return <<<QUERY
                {
                    cart(cart_id: "{$maskedQuoteId}") {
                        email
                        items {
                            uid
                            prices {
                                total_item_discount {
                                    value
                                }
                                price {
                                        value
                                }
                                discounts {
                                    label
                                    amount {
                                        value
                                    }
                                }
                            }
                            product {
                                name
                                sku
                            }
                            quantity
                        }
                        applied_coupons {
                          code
                        }
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
        QUERY;
        }
    }
}
