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
