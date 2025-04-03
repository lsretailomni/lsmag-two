<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver;

use Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
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
     * @var $authToken
     */
    private $authToken;

    public function setUp(): void
    {
        parent::setUp();
        $this->authToken     = $this->loginAndFetchToken();
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql'),
    ]
    public function testgetItemsDiscount()
    {
        $query   = $this->getQuery(AbstractIntegrationTest::DISCOUNT_SKU);

        $headerMap = ['Authorization' => 'Bearer ' . $this->authToken];
        $response  = $this->graphQlQuery(
            $query,
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $this->assertArrayHasKey('discounts', $response['get_discounts']['output']);
        $this->assertArrayHasKey('coupons', $response['get_discounts']['output']);
        if (!empty($response['get_discounts']['output']['coupons'])
        ) {
            $this->assertNotNull($response['get_discounts']['output']['coupons'][0]['coupon_description']);
        }
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
