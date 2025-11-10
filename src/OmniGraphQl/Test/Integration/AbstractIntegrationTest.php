<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\OmniGraphQl\Test\Integration;

use PHPUnit\Framework\TestCase;

//just using separate name for these one as it conflicting with customer module constant
if (!defined("PASSWORD_1")) { define('PASSWORD_1', getenv('PASSWORD')); }
if (!defined("EMAIL_1")) { define('EMAIL_1', getenv('EMAIL')); }
if (!defined("FIRST_NAME_1")) { define('FIRST_NAME_1', getenv('FIRST_NAME')); }
if (!defined("LAST_NAME_1")) { define('LAST_NAME_1', getenv('LAST_NAME')); }
if (!defined("CUSTOMER_ID_1")) { define('CUSTOMER_ID_1', getenv('CUSTOMER_ID')); }
if (!defined("BASE_URL")) { define('BASE_URL', getenv('BASE_URL')); }
if (!defined("SC_COMPANY_NAME")) { define('SC_COMPANY_NAME', getenv('SC_COMPANY_NAME')); }
if (!defined("SC_TENANT")) { define('SC_TENANT', getenv('SC_TENANT')); }
if (!defined("SC_CLIENT_ID")) { define('SC_CLIENT_ID', getenv('SC_CLIENT_ID')); }
if (!defined("SC_CLIENT_SECRET")) { define('SC_CLIENT_SECRET', getenv('SC_CLIENT_SECRET'));}
if (!defined("LS_VERSION_1")) { define('LS_VERSION_1', getenv('LS_VERSION')); }
if (!defined("WEB_STORE")) { define('WEB_STORE', getenv('WEB_STORE')); }
if (!defined("ENABLED_1")) { define('ENABLED_1', getenv('ENABLED')); }
if (!defined("USERNAME_1")) { define('USERNAME_1', getenv('USERNAME')); }
if (!defined("LSR_ID_1")) { define('LSR_ID_1', getenv('LSR_ID')); }
if (!defined("LSR_CARD_ID_1")) { define('LSR_CARD_ID_1', getenv('LSR_CARD_ID')); }
if (!defined("LS_MAG_ENABLE")) { define('LS_MAG_ENABLE', getenv('LS_MAG_ENABLE')); }
if (!defined("LS_MAG_DISABLE")) { define('LS_MAG_DISABLE', getenv('LS_MAG_DISABLE')); }
if (!defined("LSR_LOY_POINTS")) { define('LSR_LOY_POINTS', getenv('LSR_LOY_POINTS')); }
if (!defined("STORE_PICKUP")) { define('STORE_PICKUP', getenv('STORE_PICKUP')); }
if (!defined("VALID_COUPON_CODE")) { define('VALID_COUPON_CODE', getenv('VALID_COUPON_CODE')); }
if (!defined("INVALID_COUPON_CODE")) { define('INVALID_COUPON_CODE', getenv('INVALID_COUPON_CODE')); }
if (!defined("GIFTCARD")) { define('GIFTCARD', getenv('GIFTCARD')); }
if (!defined("GIFTCARD_EXPIRED")) { define('GIFTCARD_EXPIRED', getenv('GIFTCARD_EXPIRED')); }
if (!defined("GIFTCARD_PIN")) { define('GIFTCARD_PIN', getenv('GIFTCARD_PIN')); }
if (!defined("GIFTCARD_EXPIRED_PIN")) { define('GIFTCARD_EXPIRED_PIN', getenv('GIFTCARD_EXPIRED_PIN')); }
if (!defined("GIFTCARD_AMOUNT")) { define('GIFTCARD_AMOUNT', getenv('GIFTCARD_AMOUNT')); }
if (!defined("LOY_POINTS")) { define('LOY_POINTS', getenv('LOY_POINTS')); }
if (!defined("LICENSE")) { define('LICENSE', getenv('LICENSE')); }
if (!defined("LS_CENTRAL_VERSION")) { define('LS_CENTRAL_VERSION', getenv('LS_CENTRAL_VERSION')); }
if (!defined("RETAIL_INDUSTRY")) { define('RETAIL_INDUSTRY', getenv('RETAIL_INDUSTRY')); }
if (!defined("LSR_ORDER_EDIT")) { define('LSR_ORDER_EDIT', getenv('LSR_ORDER_EDIT')); }
if (!defined("ENABLE_COUPON_ELEMENTS")) { define('ENABLE_COUPON_ELEMENTS', getenv('ENABLE_COUPON_ELEMENTS')); }
if (!defined("COUPONS_SHOW_ON_CHECKOUT")) { define('COUPONS_SHOW_ON_CHECKOUT', getenv('COUPONS_SHOW_ON_CHECKOUT')); }
if (!defined("ENABLE_LOY_ELEMENTS")) { define('ENABLE_LOY_ELEMENTS', getenv('ENABLE_LOY_ELEMENTS')); }
if (!defined("LOY_SHOW_ON_CHECKOUT")) { define('LOY_SHOW_ON_CHECKOUT', getenv('LOY_SHOW_ON_CHECKOUT')); }
if (!defined("ENABLE_GC_ELEMENTS")) { define('ENABLE_GC_ELEMENTS', getenv('ENABLE_GC_ELEMENTS')); }
if (!defined("GC_SHOW_ON_CHECKOUT")) { define('GC_SHOW_ON_CHECKOUT', getenv('GC_SHOW_ON_CHECKOUT')); }
if (!defined("DISCOUNT_VALIDATION_ACTIVE")) { define('DISCOUNT_VALIDATION_ACTIVE', getenv('DISCOUNT_VALIDATION_ACTIVE')); }
if (!defined("PICKUP_TIMESLOTS_ENABLED")) { define('PICKUP_TIMESLOTS_ENABLED', getenv('PICKUP_TIMESLOTS_ENABLED')); }
if (!defined("SC_CLICKCOLLECT_ENABLED")) { define('SC_CLICKCOLLECT_ENABLED', getenv('SC_CLICKCOLLECT_ENABLED')); }
if (!defined("ITEM_SIMPLE")) { define('ITEM_SIMPLE', getenv('ITEM_SIMPLE')); }
if (!defined("HOSP_STORE")) { define('HOSP_STORE', getenv('HOSP_STORE')); }
if (!defined("DISCOUNT_SKU")) { define('DISCOUNT_SKU', getenv('DISCOUNT_SKU')); }
if (!defined("TENDER_TYPE_MAPPINGS")) { define('TENDER_TYPE_MAPPINGS', json_decode(getenv('TENDER_TYPE_MAPPINGS'), true)); }
if (!defined("ADYEN_RESPONSE")) { define('ADYEN_RESPONSE', json_decode(getenv('ADYEN_RESPONSE'), true)); }

class AbstractIntegrationTest extends TestCase
{
    //php const need to defined in phpunit.xml file
    public const PASSWORD = PASSWORD_1;
    public const EMAIL = EMAIL_1;
    public const USERNAME = USERNAME_1;
    public const FIRST_NAME = FIRST_NAME_1;
    public const LAST_NAME = LAST_NAME_1;
    public const CUSTOMER_ID = CUSTOMER_ID_1;
    public const BASE_URL = BASE_URL;
    public const SC_COMPANY_NAME = SC_COMPANY_NAME;
    public const SC_TENANT = SC_TENANT;
    public const SC_CLIENT_ID = SC_CLIENT_ID;
    public const SC_CLIENT_SECRET = SC_CLIENT_SECRET;
    public const SC_ENVIRONMENT_NAME = SC_ENVIRONMENT_NAME;
    public const WEB_STORE = WEB_STORE;
    public const LS_MAG_ENABLE = LS_MAG_ENABLE;
    public const LS_MAG_DISABLE = LS_MAG_DISABLE;
    public const ENABLED = ENABLED_1;
    public const LSR_ID = LSR_ID_1;
    public const LSR_CARD_ID = LSR_CARD_ID_1;
    public const LSR_LOY_POINTS = LSR_LOY_POINTS;
    public const STORE_PICKUP = STORE_PICKUP;
    public const VALID_COUPON_CODE = VALID_COUPON_CODE;
    public const INVALID_COUPON_CODE = INVALID_COUPON_CODE;
    public const GIFTCARD = GIFTCARD;
    public const GIFTCARD_EXPIRED = GIFTCARD_EXPIRED;
    public const GIFTCARD_PIN = GIFTCARD_PIN;
    public const GIFTCARD_EXPIRED_PIN = GIFTCARD_EXPIRED_PIN;
    public const GIFTCARD_AMOUNT = GIFTCARD_AMOUNT;
    public const LOY_POINTS = LOY_POINTS;
    public const LICENSE = LICENSE;
    public const LS_CENTRAL_VERSION = LS_CENTRAL_VERSION;
    public const RETAIL_INDUSTRY = RETAIL_INDUSTRY;
    public const LSR_ORDER_EDIT = LSR_ORDER_EDIT;
    public const ENABLE_COUPON_ELEMENTS = ENABLE_COUPON_ELEMENTS;
    public const COUPONS_SHOW_ON_CHECKOUT = COUPONS_SHOW_ON_CHECKOUT;
    public const ENABLE_LOY_ELEMENTS = ENABLE_LOY_ELEMENTS;
    public const LOY_SHOW_ON_CHECKOUT = LOY_SHOW_ON_CHECKOUT;
    public const ENABLE_GC_ELEMENTS = ENABLE_GC_ELEMENTS;
    public const GC_SHOW_ON_CHECKOUT = GC_SHOW_ON_CHECKOUT;
    public const DISCOUNT_VALIDATION_ACTIVE = DISCOUNT_VALIDATION_ACTIVE;
    public const PICKUP_TIMESLOTS_ENABLED = PICKUP_TIMESLOTS_ENABLED;
    public const SC_CLICKCOLLECT_ENABLED = SC_CLICKCOLLECT_ENABLED;
    public const TENDER_TYPE_MAPPINGS = TENDER_TYPE_MAPPINGS;
    public const ADYEN_RESPONSE = ADYEN_RESPONSE;
    public const ITEM_SIMPLE = ITEM_SIMPLE;
    public const HOSP_STORE = HOSP_STORE;
    public const DISCOUNT_SKU = DISCOUNT_SKU;
    

    protected function setUp(): void
    {
        parent::setUp();
    }
}
