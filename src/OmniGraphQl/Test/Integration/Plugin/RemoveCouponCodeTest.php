<?php

namespace Integration\Plugin;

use \Ls\Core\Model\LSR;
use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Session as CustomerSession;
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
     * @var Session
     */
    public $customerSession;

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
        $this->customerSession = $this->objectManager->create(CustomerSession::class);
        $this->eventManager    = $this->objectManager->create(ManagerInterface::class);
        $this->basketHelper    = $this->objectManager->create(BasketHelper::class);
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
    public function testRemoveCouponCode()
    {
        $customer  = $this->getOrCreateCustomer();
        $product   = $this->getOrCreateProduct();
        $emptyCart = $this->createCustomerEmptyCart($customer->getId());
        $cart      = $this->addSimpleProduct($emptyCart, $product);
        $cart->setCouponCode(AbstractIntegrationTest::VALID_COUPON_CODE)->collectTotals()->save();
        $maskedQuoteId = $this->maskedQuote->execute($cart->getId());
        $query         = $this->getQuery($maskedQuoteId);
        $this->eventManager->dispatch('checkout_cart_save_after', ['items' => $cart->getAllVisibleItems()]);

        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($cart->getId());
        
        $headerMap = ['Authorization' => 'Bearer ' . $this->authToken];
        $response  = $this->graphQlQuery(
            $query,
            [],
            '',
            $headerMap
        );

        $basketData         = $this->basketHelper->getOneListCalculationFromCheckoutSession();
        $discountOrderLines = $basketData->getMobiletransdiscountline();

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
