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
 * Represents ClickAndCollectStoresOutput Model Class
 */
class ClickAndCollectStoresTest extends GraphQlTestBase
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
        $query = $this->getQuery();

        $headerMap = ['Authorization' => 'Bearer ' . $this->authToken];
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
