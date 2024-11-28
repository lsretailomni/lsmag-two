<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver;

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
 * Represents GetDiscountsOutput Model Class
 */
class GetItemDiscountsTest extends GraphQlTestBase
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
    public function testClickAndCollectStores()
    {
        $product = $this->getOrCreateProduct();
        $query   = $this->getQuery($product->getSku());

        $headerMap = ['Authorization' => 'Bearer ' . $this->authToken];
        $response  = $this->graphQlQuery(
            $query,
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $this->assertArrayHasKey('discounts', $response['get_discounts']['output']);
        $this->assertNotNull($response['get_discounts']['output']['discounts'][0]['discount_description_title']);
        $this->assertArrayHasKey('coupons', $response['get_discounts']['output']);
    }

    /**
     * @param $itemId
     * @return string
     */
    private function getQuery($itemId): string
    {
        return <<<QUERY
        {
            get_discounts (
                item_id: "{$itemId}"
            ) {
                output 
                    {
                        discounts
                        {
                            discount_description_title
                            discount_description_text
                            discount_min_qty
                            discount_products_data
                            {
                                product_name
                                image_url
                                sku
                            }
                        }
                        coupons
                        {
                            coupon_description
                            coupon_details
                            coupon_expire_date
                            offer_id
                        }
                    }
                
            }
        }
        QUERY;
    }
}
