<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver;

use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\TestFramework\Fixture\AppArea;

/**
 * Represents ReturnPolicyOutput Model Class
 */
class ReturnPolicyTest extends GraphQlTestBase
{

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql'),
    ]
    public function testOrderTakingCalendar()
    {
        $query = $this->getQuery(AbstractIntegrationTest::ITEM_SIMPLE, '', 'S0013');

        $headerMap = [];
        $response  = $this->graphQlQuery(
            $query,
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $this->assertArrayHasKey('text', $response['return_policy']);
        $this->assertNotNull($response['return_policy']['text']);
    }

    /**
     * @param $parentSku
     * @param $childSku
     * @param $storeId
     * @return string
     */
    private function getQuery($parentSku, $childSku, $storeId): string
    {
        return <<<QUERY
        {
            return_policy (
                parent_sku: "{$parentSku}"
                child_sku: "{$childSku}"
                store_id: "{$storeId}"
            ) {
                text
            }
        }
        QUERY;
    }
}
