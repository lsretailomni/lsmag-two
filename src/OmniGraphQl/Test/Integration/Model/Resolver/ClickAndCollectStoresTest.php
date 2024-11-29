<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver;

use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use Magento\TestFramework\Fixture\AppArea;

/**
 * Represents ClickAndCollectStoresOutput Model Class
 */
class ClickAndCollectStoresTest extends GraphQlTestBase
{
    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql'),
    ]
    public function testClickAndCollectStores()
    {
        $query = $this->getQuery();

        $headerMap = [];
        $response  = $this->graphQlQuery(
            $query,
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $this->assertGreaterThan(0, count($response['click_and_collect_stores']['stores']));
        $this->assertNotNull($response['click_and_collect_stores']['stores'][0]['store_id']);
        $this->assertNotNull($response['click_and_collect_stores']['stores'][0]['store_name']);
        $this->assertNotNull($response['click_and_collect_stores']['stores'][0]['click_and_collect_accepted']);
        $this->assertNotNull($response['click_and_collect_stores']['stores'][0]['latitude']);
        $this->assertNotNull($response['click_and_collect_stores']['stores'][0]['store_hours']);
    }

    /**
     * Get Query
     *
     * @return string
     */
    private function getQuery(): string
    {
        return <<<QUERY
        {
            click_and_collect_stores {
                stores 
                    {
                        store_id
                        store_name
                        click_and_collect_accepted
                        latitude
                        longitude
                        phone
                        available_hospitality_sales_types
                        store_hours
                        {
                            day_of_week
                            hour_types
                            {
                                type
                                opening_time
                                closing_time
                            }
                        }
                    }
                
            }
        }
        QUERY;
    }
}
