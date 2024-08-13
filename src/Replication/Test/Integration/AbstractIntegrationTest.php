<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration;

use Ls\Core\Model\LSR;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\JwtUserToken\Api\Data\Revoked;
use Magento\JwtUserToken\Api\RevokedRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AbstractIntegrationTest extends TestCase
{
    public const PASSWORD = 'Nmswer123@';
    public const EMAIL = 'pipeline_retail@lsretail.com';
    public const FIRST_NAME = 'Umar';
    public const LAST_NAME = 'Yousaf';
    public const CUSTOMER_ID = '1';
    public const CS_URL = 'http://20.6.33.78/commerceservice';
    public const CS_VERSION = '2024.4.1';
    public const LS_VERSION = '25.0.0.0';
    public const CS_STORE = 'S0013';
    public const ENABLED = '1';
    public const USERNAME = 'mc_57745';
    public const LSR_ID = 'MSO000012';
    public const LSR_CARD_ID = '10051';

    public const SAMPLE_FLAT_REPLICATION_CRON_URL = 'Ls\Replication\Cron\ReplEcommItemsTask';
    public const SAMPLE_FLAT_REPLICATION_CRON_NAME = 'repl_item';
    public const SAMPLE_MAGENTO_REPLICATION_CRON_NAME = 'repl_products';

    public const DEFAULT_BATCH_SIZE = '5000';
    public const SAMPLE_SIMPLE_ITEM_ID = '40180';
    public const SAMPLE_CONFIGURABLE_ITEM_ID = '40020';
    public const SAMPLE_ATTRIBUTE_CODE = 'FABRIC';
    public const SAMPLE_COUNTRY_CODE = 'IS';
    public const SAMPLE_OFFER_NO = 'P1001';
    public const SAMPLE_VALIDATION_PERIOD_ID = '16';
    public const SAMPLE_HIERARCHY_NAV_ID = 'FASHIONCOSMETICS';
    public const SAMPLE_UOM = 'PCS';
    public const SAMPLE_VENDOR_ID = '44010';
    public const SAMPLE_BUSINESS_TAX_GROUP = 'DOMESTIC';
    public const SAMPLE_CASH_TENDER_TYPE_ID = '1';
    public const SAMPLE_STORE_ID = 'S0001';
    public const SAMPLE_LANGUAGE_CDOE = 'ENG';
    public const SAMPLE_LOG_FILE_NAME = 'omniclient.log';
    public const SAMPLE_HARD_ATTRIBUTE = 'SIZE';
    /**
     * Get environment variable value given name
     *
     * @param $name
     * @return array|false|string
     */
    public function getEnvironmentVariableValueGivenName($name)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return getenv($name);
    }

    public function testExecute()
    {
        $this->assertEquals(1, 1);
    }
}
