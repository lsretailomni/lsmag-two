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
 * Represents StoresOutput Model Class
 */
class StoresTest extends GraphQlTestBase
{

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql'),
    ]
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
