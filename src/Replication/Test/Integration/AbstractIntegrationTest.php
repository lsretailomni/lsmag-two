<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration;

use PHPUnit\Framework\TestCase;
define('SC_CLIENT_ID', getenv('SC_CLIENT_ID'));
if (!defined("CS_URL_1")) { define('CS_URL_1', getenv('CS_URL')); }
if (!defined("BASE_URL")) { define('BASE_URL', getenv('BASE_URL')); }
if (!defined("SC_COMPANY_NAME")) { define('SC_COMPANY_NAME', getenv('SC_COMPANY_NAME')); }
if (!defined("SC_TENANT")) { define('SC_TENANT', getenv('SC_TENANT')); }
if (!defined("SC_CLIENT_ID")) { define('SC_CLIENT_ID', getenv('SC_CLIENT_ID')); }
if (!defined("SC_CLIENT_SECRET")) { define('SC_CLIENT_SECRET', getenv('SC_CLIENT_SECRET')); }
if (!defined("SC_ENVIRONMENT_NAME")) { define('SC_ENVIRONMENT_NAME', getenv('SC_ENVIRONMENT_NAME')); }
if (!defined("SC_SERVICE_TIMEOUT")) { define('SC_SERVICE_TIMEOUT', getenv('SC_SERVICE_TIMEOUT')); }
if (!defined("CS_VERSION_1")) { define('CS_VERSION_1', getenv('CS_VERSION')); }
if (!defined("LS_VERSION_1")) { define('LS_VERSION_1', getenv('LS_VERSION')); }
if (!defined("CS_STORE_1")) { define('CS_STORE_1', getenv('CS_STORE')); }
if (!defined("ENABLED_1")) { define('ENABLED_1', getenv('ENABLED'));}
if (!defined("SAMPLE_FLAT_REPLICATION_CRON_URL")) { define('SAMPLE_FLAT_REPLICATION_CRON_URL', getenv('SAMPLE_FLAT_REPLICATION_CRON_URL')); }
if (!defined("SAMPLE_FLAT_REPLICATION_CRON_NAME")) { define('SAMPLE_FLAT_REPLICATION_CRON_NAME', getenv('SAMPLE_FLAT_REPLICATION_CRON_NAME')); }
if (!defined("SAMPLE_MAGENTO_REPLICATION_CRON_NAME")) { define('SAMPLE_MAGENTO_REPLICATION_CRON_NAME', getenv('SAMPLE_MAGENTO_REPLICATION_CRON_NAME')); }
if (!defined("DEFAULT_BATCH_SIZE")) { define('DEFAULT_BATCH_SIZE', getenv('DEFAULT_BATCH_SIZE')); }
if (!defined("SAMPLE_SIMPLE_ITEM_ID")) { define('SAMPLE_SIMPLE_ITEM_ID', getenv('SAMPLE_SIMPLE_ITEM_ID')); }
if (!defined("SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID")) { define('SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID', getenv('SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID')); }
if (!defined("SAMPLE_CONFIGURABLE_ITEM_ID")) { define('SAMPLE_CONFIGURABLE_ITEM_ID', getenv('SAMPLE_CONFIGURABLE_ITEM_ID')); }
if (!defined("SAMPLE_CONFIGURABLE_VARIANT_ID")) { define('SAMPLE_CONFIGURABLE_VARIANT_ID', getenv('SAMPLE_CONFIGURABLE_VARIANT_ID'));}
if (!defined("SAMPLE_ATTRIBUTE_CODE")) { define('SAMPLE_ATTRIBUTE_CODE', getenv('SAMPLE_ATTRIBUTE_CODE')); }
if (!defined("SAMPLE_COUNTRY_CODE")) { define('SAMPLE_COUNTRY_CODE', getenv('SAMPLE_COUNTRY_CODE')); }
if (!defined("SAMPLE_OFFER_NO")) { define('SAMPLE_OFFER_NO', getenv('SAMPLE_OFFER_NO')); }
if (!defined("SAMPLE_VALIDATION_PERIOD_ID")) { define('SAMPLE_VALIDATION_PERIOD_ID', getenv('SAMPLE_VALIDATION_PERIOD_ID')); }
if (!defined("SAMPLE_HIERARCHY_NAV_ID")) { define('SAMPLE_HIERARCHY_NAV_ID', getenv('SAMPLE_HIERARCHY_NAV_ID')); }
if (!defined("SAMPLE_UOM")) { define('SAMPLE_UOM', getenv('SAMPLE_UOM')); }
if (!defined("SAMPLE_UOM_2")) { define('SAMPLE_UOM_2', getenv('SAMPLE_UOM_2')); }
if (!defined("SAMPLE_VENDOR_ID")) { define('SAMPLE_VENDOR_ID', getenv('SAMPLE_VENDOR_ID')); }
if (!defined("SAMPLE_BUSINESS_TAX_GROUP")) { define('SAMPLE_BUSINESS_TAX_GROUP', getenv('SAMPLE_BUSINESS_TAX_GROUP')); }
if (!defined("SAMPLE_CASH_TENDER_TYPE_ID")) { define('SAMPLE_CASH_TENDER_TYPE_ID', getenv('SAMPLE_CASH_TENDER_TYPE_ID')); }
if (!defined("SAMPLE_STORE_ID")) { define('SAMPLE_STORE_ID', getenv('SAMPLE_STORE_ID')); }
if (!defined("SAMPLE_LANGUAGE_CODE")) { define('SAMPLE_LANGUAGE_CODE', getenv('SAMPLE_LANGUAGE_CODE')); }
if (!defined("SAMPLE_LOG_FILE_NAME")) { define('SAMPLE_LOG_FILE_NAME', getenv('SAMPLE_LOG_FILE_NAME')); }
if (!defined("SAMPLE_HARD_ATTRIBUTE")) { define('SAMPLE_HARD_ATTRIBUTE', getenv('SAMPLE_HARD_ATTRIBUTE')); }
if (!defined("SAMPLE_VARIANT_ATTRIBUTE")) { define('SAMPLE_VARIANT_ATTRIBUTE', getenv('SAMPLE_VARIANT_ATTRIBUTE')); }
if (!defined("SAMPLE_VISUAL_SWATCH_ATTRIBUTE_CODE")) { define('SAMPLE_VISUAL_SWATCH_ATTRIBUTE_CODE', getenv('SAMPLE_VISUAL_SWATCH_ATTRIBUTE_CODE')); }
if (!defined("SAMPLE_HIERARCHY_NODE_NAV_ID")) { define('SAMPLE_HIERARCHY_NODE_NAV_ID', getenv('SAMPLE_HIERARCHY_NODE_NAV_ID')); }
if (!defined("SAMPLE_HIERARCHY_NODE_NAV_ID_2")) { define('SAMPLE_HIERARCHY_NODE_NAV_ID_2', getenv('SAMPLE_HIERARCHY_NODE_NAV_ID_2')); }
if (!defined("SAMPLE_UPDATE_HIERARCHY_NODE_NAV_ID")) { define('SAMPLE_UPDATE_HIERARCHY_NODE_NAV_ID', getenv('SAMPLE_UPDATE_HIERARCHY_NODE_NAV_ID')); }
if (!defined("SAMPLE_CONFIGURABLE_UOM_ITEM_ID")) { define('SAMPLE_CONFIGURABLE_UOM_ITEM_ID', getenv('SAMPLE_CONFIGURABLE_UOM_ITEM_ID')); }
if (!defined("SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID")) { define('SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID', getenv('SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID')); }
if (!defined("SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID")) { define('SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID', getenv('SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID')); }
if (!defined("SAMPLE_STANDARD_VARIANT_ITEM_ID")) { define('SAMPLE_STANDARD_VARIANT_ITEM_ID', getenv('SAMPLE_STANDARD_VARIANT_ITEM_ID')); }
if (!defined("SAMPLE_STANDARD_VARIANT_ID")) { define('SAMPLE_STANDARD_VARIANT_ID', getenv('SAMPLE_STANDARD_VARIANT_ID')); }
if (!defined("SAMPLE_HIERARCHY_LEAF")) { define('SAMPLE_HIERARCHY_LEAF', getenv('SAMPLE_HIERARCHY_LEAF')); }
if (!defined("SAMPLE_CURRENCY_CODE")) { define('SAMPLE_CURRENCY_CODE', getenv('SAMPLE_CURRENCY_CODE')); }
if (!defined("SAMPLE_OFFER_CATEGORY_1")) { define('SAMPLE_OFFER_CATEGORY_1', getenv('SAMPLE_OFFER_CATEGORY_1')); }
if (!defined("SAMPLE_OFFER_CATEGORY_2")) { define('SAMPLE_OFFER_CATEGORY_2', getenv('SAMPLE_OFFER_CATEGORY_2')); }
if (!defined("SAMPLE_OFFER_ITEM_1")) { define('SAMPLE_OFFER_ITEM_1', getenv('SAMPLE_OFFER_ITEM_1')); }
if (!defined("SAMPLE_VALID_VALIDATION_PERIOD_ID")) { define('SAMPLE_VALID_VALIDATION_PERIOD_ID', getenv('SAMPLE_VALID_VALIDATION_PERIOD_ID')); }
if (!defined("SAMPLE_STORE_GROUP_CODES")) { define('SAMPLE_STORE_GROUP_CODES', getenv('SAMPLE_STORE_GROUP_CODES')); }
if (!defined("SAMPLE_PRICE_GROUP")) { define('SAMPLE_PRICE_GROUP', getenv('SAMPLE_PRICE_GROUP')); }
if (!defined("SC_SUCCESS_CRON_CATEGORY")) { define('SC_SUCCESS_CRON_CATEGORY', getenv('SC_SUCCESS_CRON_CATEGORY')); }
if (!defined("PASSWORD")) { define('PASSWORD', getenv('PASSWORD')); }
if (!defined("EMAIL")) { define('EMAIL', getenv('EMAIL')); }
if (!defined("CUSTOMER_ID")) { define('CUSTOMER_ID', getenv('CUSTOMER_ID')); }
if (!defined("USERNAME")) { define('USERNAME', getenv('USERNAME_1')); }
if (!defined("LSR_ID")) { define('LSR_ID', getenv('LSR_ID')); }
if (!defined("LSR_CARD_ID")) { define('LSR_CARD_ID', getenv('LSR_CARD_ID')); }
if (!defined("ENABLED")) { define('ENABLED', getenv('ENABLED')); }

class AbstractIntegrationTest extends TestCase
{
    public const CS_URL = CS_URL_1;
    public const BASE_URL = BASE_URL;
    public const SC_COMPANY_NAME = SC_COMPANY_NAME;
    public const SC_TENANT = SC_TENANT;
    public const SC_CLIENT_ID = SC_CLIENT_ID;
    public const SC_CLIENT_SECRET = SC_CLIENT_SECRET;
    public const SC_ENVIRONMENT_NAME = SC_ENVIRONMENT_NAME;
    public const SC_SERVICE_TIMEOUT = SC_SERVICE_TIMEOUT;
    public const CS_VERSION = CS_VERSION_1;
    public const LS_VERSION = LS_VERSION_1;
    public const CS_STORE = CS_STORE_1;
    public const ENABLED = ENABLED_1;
    public const SAMPLE_FLAT_REPLICATION_CRON_URL = SAMPLE_FLAT_REPLICATION_CRON_URL;
    public const SAMPLE_FLAT_REPLICATION_CRON_NAME = SAMPLE_FLAT_REPLICATION_CRON_NAME;
    public const SAMPLE_MAGENTO_REPLICATION_CRON_NAME = SAMPLE_MAGENTO_REPLICATION_CRON_NAME;

    public const EMAIL = EMAIL;
    public const CUSTOMER_ID = CUSTOMER_ID;
    public const PASSWORD = PASSWORD;
    public const USERNAME = USERNAME;
    public const LSR_ID = LSR_ID;
    public const LSR_CARD_ID = LSR_CARD_ID;
    
    public const DEFAULT_BATCH_SIZE = DEFAULT_BATCH_SIZE;
    public const SAMPLE_SIMPLE_ITEM_ID = SAMPLE_SIMPLE_ITEM_ID;
    public const SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID = SAMPLE_DISCOUNTED_CONFIGURABLE_ITEM_ID;
    public const SAMPLE_CONFIGURABLE_ITEM_ID = SAMPLE_CONFIGURABLE_ITEM_ID;
    public const SAMPLE_CONFIGURABLE_VARIANT_ID = SAMPLE_CONFIGURABLE_VARIANT_ID;
    public const SAMPLE_ATTRIBUTE_CODE = SAMPLE_ATTRIBUTE_CODE;
    public const SAMPLE_COUNTRY_CODE = SAMPLE_COUNTRY_CODE;
    public const SAMPLE_OFFER_NO = SAMPLE_OFFER_NO;
    public const SAMPLE_VALIDATION_PERIOD_ID = SAMPLE_VALIDATION_PERIOD_ID;
    public const SAMPLE_HIERARCHY_NAV_ID = SAMPLE_HIERARCHY_NAV_ID;
    public const SAMPLE_UOM = SAMPLE_UOM;
    public const SAMPLE_UOM_2 = SAMPLE_UOM_2;
    public const SAMPLE_VENDOR_ID = SAMPLE_VENDOR_ID;
    public const SAMPLE_BUSINESS_TAX_GROUP = SAMPLE_BUSINESS_TAX_GROUP;
    public const SAMPLE_CASH_TENDER_TYPE_ID = SAMPLE_CASH_TENDER_TYPE_ID;
    public const SAMPLE_STORE_ID = SAMPLE_STORE_ID;
    public const SAMPLE_LANGUAGE_CODE = SAMPLE_LANGUAGE_CODE;
    public const SAMPLE_LOG_FILE_NAME = SAMPLE_LOG_FILE_NAME;
    public const SAMPLE_HARD_ATTRIBUTE = SAMPLE_HARD_ATTRIBUTE;
    public const SAMPLE_VARIANT_ATTRIBUTE = SAMPLE_VARIANT_ATTRIBUTE;
    public const SAMPLE_VISUAL_SWATCH_ATTRIBUTE_CODE = SAMPLE_VISUAL_SWATCH_ATTRIBUTE_CODE;
    public const SAMPLE_HIERARCHY_NODE_NAV_ID = SAMPLE_HIERARCHY_NODE_NAV_ID;
    public const SAMPLE_HIERARCHY_NODE_NAV_ID_2 = SAMPLE_HIERARCHY_NODE_NAV_ID_2;
    public const SAMPLE_UPDATE_HIERARCHY_NODE_NAV_ID = SAMPLE_UPDATE_HIERARCHY_NODE_NAV_ID;
    public const SAMPLE_CONFIGURABLE_UOM_ITEM_ID = SAMPLE_CONFIGURABLE_UOM_ITEM_ID;
    public const SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID = SAMPLE_CONFIGURABLE_VARIANT_ITEM_ID;
    public const SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID = SAMPLE_CONFIGURABLE2_VARIANT_ITEM_ID;
    public const SAMPLE_STANDARD_VARIANT_ITEM_ID = SAMPLE_STANDARD_VARIANT_ITEM_ID;
    public const SAMPLE_STANDARD_VARIANT_ID = SAMPLE_STANDARD_VARIANT_ID;
    public const SAMPLE_HIERARCHY_LEAF = SAMPLE_HIERARCHY_LEAF;
    public const SAMPLE_CURRENCY_CODE = SAMPLE_CURRENCY_CODE;
    public const SAMPLE_OFFER_CATEGORY_1 = SAMPLE_OFFER_CATEGORY_1;
    public const SAMPLE_OFFER_CATEGORY_2 = SAMPLE_OFFER_CATEGORY_2;
    public const SAMPLE_OFFER_ITEM_1 = SAMPLE_OFFER_ITEM_1;
    public const SAMPLE_VALID_VALIDATION_PERIOD_ID = SAMPLE_VALID_VALIDATION_PERIOD_ID;
    public const SAMPLE_STORE_GROUP_CODES = SAMPLE_STORE_GROUP_CODES;
    public const SAMPLE_PRICE_GROUP = SAMPLE_PRICE_GROUP;
    public const SC_SUCCESS_CRON_CATEGORY = SC_SUCCESS_CRON_CATEGORY;    

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

    /**
     * @magentoDbIsolation disabled
     * @magentoAppIsolation disabled
     */
    public function testExecute()
    {
        $this->assertEquals(1, 1);
    }
}
