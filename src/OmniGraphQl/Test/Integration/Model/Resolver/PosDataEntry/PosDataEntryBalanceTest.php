<?php

namespace Ls\OmniGraphQl\Test\Integration\Model\Resolver\PosDataEntry;

use \Ls\OmniGraphQl\Test\Integration\GraphQlTestBase;
use \Ls\OmniGraphQl\Test\Integration\AbstractIntegrationTest;
use Magento\TestFramework\Fixture\AppArea;

/**
 * Gift cards (entry_type = GIFTCARDNO) and vouchers (any other tender-type-mapped entry_type)
 * both resolve through the unified get_pos_data_entry_balance query. With entry_type omitted the
 * resolver walks the configured entry types via VoucherHelper::resolveCode and reports which one
 * matched.
 */
class PosDataEntryBalanceTest extends GraphQlTestBase
{
    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql')
    ]
    public function testGiftCardBalance()
    {
        $balance = $this->getBalance(
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN
        );

        $this->assertNull($balance['error']);
        $this->assertNotNull($balance['currency']);
        $this->assertNotNull($balance['value']);
        $this->assertEquals(AbstractIntegrationTest::GIFTCARD_ENTRY_TYPE, $balance['entry_type']);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql')
    ]
    public function testVoucherBalance()
    {
        $balance = $this->getBalance(
            AbstractIntegrationTest::VOUCHER,
            AbstractIntegrationTest::VOUCHER_PIN
        );

        $this->assertNull($balance['error']);
        $this->assertNotNull($balance['currency']);
        $this->assertNotNull($balance['value']);
        $this->assertEquals(AbstractIntegrationTest::VOUCHER_ENTRY_TYPE, $balance['entry_type']);
    }

    /**
     * An explicit entry_type skips auto-detection and queries that entry type directly.
     *
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql')
    ]
    public function testExplicitEntryTypeBalance()
    {
        $balance = $this->getBalance(
            AbstractIntegrationTest::VOUCHER,
            AbstractIntegrationTest::VOUCHER_PIN,
            AbstractIntegrationTest::VOUCHER_ENTRY_TYPE
        );

        $this->assertNull($balance['error']);
        $this->assertNotNull($balance['value']);
        $this->assertEquals(AbstractIntegrationTest::VOUCHER_ENTRY_TYPE, $balance['entry_type']);
    }

    /**
     * An entry type outside the admin-configured set is rejected before it reaches LS Central, so it
     * cannot be used to probe POS data entries (e.g. INCOMEACCOUNT) never exposed to ecommerce. The
     * auto-detect path already restricts itself to configured entry types; this covers the explicit path.
     *
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql')
    ]
    public function testUnconfiguredEntryTypeIsRejected()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The requested entry type is not available.');

        $this->getBalance(
            AbstractIntegrationTest::GIFTCARD,
            AbstractIntegrationTest::GIFTCARD_PIN,
            'THIS-ENTRY-TYPE-IS-NOT-CONFIGURED'
        );
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('graphql')
    ]
    public function testInvalidCodeReturnsError()
    {
        $balance = $this->getBalance('this-code-does-not-exist', '');

        $this->assertNotNull($balance['error']);
        $this->assertNull($balance['value']);
        $this->assertNull($balance['entry_type']);
    }

    /**
     * Run the balance query and return the get_pos_data_entry_balance payload.
     *
     * @param string $code
     * @param string $pin
     * @param string|null $entryType
     * @return array
     */
    private function getBalance(string $code, string $pin, ?string $entryType = null): array
    {
        $entryTypeArg = $entryType === null ? '' : "entry_type: \"{$entryType}\"";

        $query = <<<QUERY
        {
            get_pos_data_entry_balance (
                code: "{$code}"
                pin: "{$pin}"
                {$entryTypeArg}
            ) {
                currency
                value
                entry_type
                expiry_date
                error
            }
        }
        QUERY;

        $response = $this->executeQuery($query);

        $this->assertNotNull($response);
        $this->assertArrayHasKey('get_pos_data_entry_balance', $response);

        return $response['get_pos_data_entry_balance'];
    }
}
