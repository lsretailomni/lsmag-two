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
 * Represents GiftCardBalanceOutput Model Class
 */
class GiftCardBalanceTest extends GraphQlTestBase
{

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql'),
    ]
    public function testGiftCardBalance()
    {
        $query = $this->getQuery(AbstractIntegrationTest::GIFTCARD, AbstractIntegrationTest::GIFTCARD_PIN);

        $headerMap = [];
        $response  = $this->graphQlQuery(
            $query,
            [],
            '',
            $headerMap
        );

        $this->assertNotNull($response);
        $this->assertArrayHasKey('currency', $response['get_gift_card_balance']);
        $this->assertNotNull($response['get_gift_card_balance']['currency']);
        $this->assertNotNull($response['get_gift_card_balance']['value']);
        $this->assertNull($response['get_gift_card_balance']['error']);
    }

    /**
     * @param $giftCardNo
     * @param $giftCardPin
     * @return string
     */
    private function getQuery($giftCardNo, $giftCardPin): string
    {
        return <<<QUERY
        {
            get_gift_card_balance (
                gift_card_no: "{$giftCardNo}"
                gift_card_pin: "{$giftCardPin}"
            ) {
                currency 
                value
                error
            }
        }
        QUERY;
    }
}
