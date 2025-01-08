<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\OmniGraphQl\Test\Integration;

use PHPUnit\Framework\TestCase;

//just using separate name for these one as it conflicting with customer module constant
define('PASSWORD_1', getenv('PASSWORD'));
define('EMAIL_1', getenv('EMAIL'));
define('FIRST_NAME_1', getenv('FIRST_NAME'));
define('LAST_NAME_1', getenv('LAST_NAME'));
define('CUSTOMER_ID_1', getenv('CUSTOMER_ID'));
define('CS_URL_1', getenv('CS_URL'));
define('CS_VERSION_1', getenv('CS_VERSION'));
define('LS_VERSION_1', getenv('LS_VERSION'));
define('CS_STORE_1', getenv('CS_STORE'));
define('ENABLED_1', getenv('ENABLED'));
define('USERNAME_1', getenv('USERNAME'));
define('LSR_ID_1', getenv('LSR_ID'));
define('LSR_CARD_ID_1', getenv('LSR_CARD_ID'));
define('LS_MAG_ENABLE', getenv('LS_MAG_ENABLE'));
define('LS_MAG_DISABLE', getenv('LS_MAG_DISABLE'));
define('LSR_LOY_POINTS', getenv('LSR_LOY_POINTS'));
define('STORE_PICKUP', getenv('STORE_PICKUP'));
define('VALID_COUPON_CODE', getenv('VALID_COUPON_CODE'));
define('INVALID_COUPON_CODE', getenv('INVALID_COUPON_CODE'));
define('GIFTCARD', getenv('GIFTCARD'));
define('GIFTCARD_EXPIRED', getenv('GIFTCARD_EXPIRED'));
define('GIFTCARD_PIN', getenv('GIFTCARD_PIN'));
define('GIFTCARD_EXPIRED_PIN', getenv('GIFTCARD_EXPIRED_PIN'));
define('GIFTCARD_AMOUNT', getenv('GIFTCARD_AMOUNT'));
define('LOY_POINTS', getenv('LOY_POINTS'));
define('LICENSE', getenv('LICENSE'));
define('LS_CENTRAL_VERSION', getenv('LS_CENTRAL_VERSION'));
define('RETAIL_INDUSTRY', getenv('RETAIL_INDUSTRY'));
define('LSR_ORDER_EDIT', getenv('LSR_ORDER_EDIT'));
define('ENABLE_COUPON_ELEMENTS', getenv('ENABLE_COUPON_ELEMENTS'));
define('COUPONS_SHOW_ON_CHECKOUT', getenv('COUPONS_SHOW_ON_CHECKOUT'));
define('ENABLE_LOY_ELEMENTS', getenv('ENABLE_LOY_ELEMENTS'));
define('LOY_SHOW_ON_CHECKOUT', getenv('LOY_SHOW_ON_CHECKOUT'));
define('ENABLE_GC_ELEMENTS', getenv('ENABLE_GC_ELEMENTS'));
define('GC_SHOW_ON_CHECKOUT', getenv('GC_SHOW_ON_CHECKOUT'));
define('DISCOUNT_VALIDATION_ACTIVE', getenv('DISCOUNT_VALIDATION_ACTIVE'));
define('PICKUP_TIMESLOTS_ENABLED', getenv('PICKUP_TIMESLOTS_ENABLED'));
define('SC_CLICKCOLLECT_ENABLED', getenv('SC_CLICKCOLLECT_ENABLED'));
define('TENDER_TYPE_MAPPINGS', json_decode(getenv('TENDER_TYPE_MAPPINGS'), true));
define('ADYEN_RESPONSE', json_decode(getenv('ADYEN_RESPONSE'), true));

class AbstractIntegrationTest extends TestCase
{
    //php const need to defined in phpunit.xml file
    public const PASSWORD = PASSWORD_1;
    public const EMAIL = EMAIL_1;
    public const USERNAME = USERNAME_1;
    public const FIRST_NAME = FIRST_NAME_1;
    public const LAST_NAME = LAST_NAME_1;
    public const CUSTOMER_ID = CUSTOMER_ID_1;
    public const CS_URL = CS_URL_1;
    public const CS_VERSION = CS_VERSION_1;
    public const CS_STORE = CS_STORE_1;
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

    protected function setUp(): void
    {
        parent::setUp();
    }
}
