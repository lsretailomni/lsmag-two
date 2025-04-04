<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver;

use Ls\Omni\Test\Fixture\FlatDataReplication;
use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use Ls\Replication\Cron\ReplEcommStoresTask;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;

/**
 * Represents StoresOutput Model Class
 */
class StoresTest extends GraphQlTestBase
{

    /**
     * @magentoAppIsolation enabled
     */
    public function testStores()
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
        $this->assertGreaterThan(0, count($response['get_all_stores']['stores']));
        $this->assertNotNull($response['get_all_stores']['stores'][0]['store_id']);
        $this->assertNotNull($response['get_all_stores']['stores'][0]['store_name']);
        $this->assertNotNull($response['get_all_stores']['stores'][0]['click_and_collect_accepted']);
    }

    /**
     * @param $parentSku
     * @param $childSku
     * @param $storeId
     * @return string
     */
    private function getQuery(): string
    {
        return <<<QUERY
        {
            get_all_stores {
                stores
                {
                    store_id
                    store_name
                    click_and_collect_accepted
                    latitude
                    longitude
                }
            }
        }
        QUERY;
    }
}
