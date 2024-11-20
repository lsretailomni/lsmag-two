<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration;

use PHPUnit\Framework\TestCase;

class AbstractIntegrationTest extends TestCase
{
    public const CS_URL = 'http://20.6.33.78/commerceservice';
    public const CS_VERSION = '2024.4.1';
    public const LS_VERSION = '25.0.0.0';
    public const CS_STORE = 'S0013';
    public const ENABLED = '1';
    public const SAMPLE_FLAT_REPLICATION_CRON_URL = 'Ls\Replication\Cron\ReplEcommItemsTask';
    public const SAMPLE_FLAT_REPLICATION_CRON_NAME = 'repl_item';
    public const SAMPLE_MAGENTO_REPLICATION_CRON_NAME = 'repl_products';

    public const DEFAULT_BATCH_SIZE = '5000';
    public const SAMPLE_SIMPLE_ITEM_ID = '40000';
    public const SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID = '40015';
    public const SAMPLE_CONFIGURABLE_ITEM_ID = '40020';
    public const SAMPLE_CONFIGURABLE_VARIANT_ID = '000';
    public const SAMPLE_ATTRIBUTE_CODE = 'FABRIC';
    public const SAMPLE_COUNTRY_CODE = 'IS';
    public const SAMPLE_OFFER_NO = 'P1001';
    public const SAMPLE_VALIDATION_PERIOD_ID = '16';
    public const SAMPLE_HIERARCHY_NAV_ID = 'FASHIONCOSMETICS';
    public const SAMPLE_UOM = 'PCS';
    public const SAMPLE_UOM_2 = 'PACK';
    public const SAMPLE_VENDOR_ID = '44010';
    public const SAMPLE_BUSINESS_TAX_GROUP = 'DOMESTIC';
    public const SAMPLE_CASH_TENDER_TYPE_ID = '1';
    public const SAMPLE_STORE_ID = 'S0001';
    public const SAMPLE_LANGUAGE_CDOE = 'ENG';
    public const SAMPLE_LOG_FILE_NAME = 'omniclient.log';
    public const SAMPLE_HARD_ATTRIBUTE = 'SIZE';
    public const SAMPLE_VARIANT_ATTRIBUTE = 'COLOUR';
    public const SAMPLE_VISUAL_SWATCH_ATTRIBUTE_CODE = 'ls_colour';
    public const SAMPLE_HIERARCHY_NODE_NAV_ID = 'ACCESSORIES';
    public const SAMPLE_HIERARCHY_NODE_NAV_ID_2 = 'BAGS';
    public const SAMPLE_UPDATE_HIERARCHY_NODE_NAV_ID = 'MAKEUP';
    public const SAMPLE_CONFIGURABLE_UOM_ITEM_ID = '20020';
    public const SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID = '40500';
    public const SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID = '43130';
    public const SAMPLE_STANDARD_VARIANT_ITEM_ID = '40190';
    public const SAMPLE_HIERARCHY_LEAF = 'DAVI';
    public const SAMPLE_CURRENCY_CODE = 'GBP';
    public const SAMPLE_OFFER_CATEGORY_1 = 'P1065';
    public const SAMPLE_OFFER_CATEGORY_2 = 'P1016';
    public const SAMPLE_OFFER_ITEM_1 = 'P1060';
    public const SAMPLE_VALID_VALIDATION_PERIOD_ID = '16';
    public const SAMPLE_STORE_GROUP_CODES = 'EASYBURGER;ECOMMERCE;';
    public const SAMPLE_PRICE_GROUP = 'ALL';

    public const SAMPLE_LANGUAGE_CODE = 'ENG';

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
