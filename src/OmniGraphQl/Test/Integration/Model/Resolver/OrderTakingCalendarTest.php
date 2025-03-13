<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver;

use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\TestFramework\Fixture\AppArea;

/**
 * Represents OrderTakingCalendarOutput Model Class
 */
class OrderTakingCalendarTest extends GraphQlTestBase
{

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql'),
    ]
    public function testOrderTakingCalendar()
    {
        $query = $this->getQuery(AbstractIntegrationTest::HOSP_STORE);

        $headerMap = [];
        $response  = $this->graphQlQuery(
            $query,
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $this->assertArrayHasKey('pickup_dates', $response['order_taking_calendar']);
        $this->assertArrayHasKey('delivery_dates', $response['order_taking_calendar']);
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getQuery($storeId): string
    {
        return <<<QUERY
        {
            order_taking_calendar (
                store_id: "{$storeId}"
            ) {
                pickup_dates 
                {
                    date
                    slots
                }
                delivery_dates
                {
                    date
                    slots
                }
            }
        }
        QUERY;
    }
}
