<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration;

use PHPUnit\Framework\TestCase;

class AbstractIntegrationTest extends TestCase
{
    public const PASSWORD = 'Nmswer123@';
    public const EMAIL = 'pipeline_retail@lsretail.com';
    public const USERNAME = 'mc_61422';
    public const FIRST_NAME = 'Deepak';
    public const LAST_NAME = 'Ret';
    public const CUSTOMER_ID = '1';
    public const CS_URL = 'http://20.6.33.78/CommerceMaster';
    public const CS_VERSION = '2024.10.0';
    public const CS_STORE = 'S0013';
    public const LS_MAG_ENABLE = '1';
    public const LS_MAG_DISABLE = '0';
    public const ENABLED = '1';
    public const LSR_ID = 'MSO000005';
    public const LSR_CARD_ID = '10044';
    public const LSR_LOY_POINTS = 2;
    public const STORE_PICKUP = 'S0001';
    public const VALID_COUPON_CODE = 'COUP0119';
    public const INVALID_COUPON_CODE = 'COUPON_CODE';
    public const GIFTCARD = '10000000';
    public const GIFTCARD_EXPIRED = '10000001';
    public const GIFTCARD_PIN = '4042';
    public const GIFTCARD_EXPIRED_PIN = '6200';
    public const GIFTCARD_AMOUNT = '1';
    public const LOY_POINTS = '2';
    public const LICENSE = '25.0.0.0 (25.0.0.0 [18056] CL:True EL:True)';
    public const LS_CENTRAL_VERSION = '25.0.0.0 (25.0.0.0 [conf])';
    public const RETAIL_INDUSTRY = 'retail';
    public const LSR_ORDER_EDIT = 1;
    public const ENABLE_COUPON_ELEMENTS = 1;
    public const COUPONS_SHOW_ON_CHECKOUT = 1;
    public const ENABLE_LOY_ELEMENTS = 1;
    public const LOY_SHOW_ON_CHECKOUT = 1;
    public const ENABLE_GC_ELEMENTS = 1;
    public const GC_SHOW_ON_CHECKOUT = 1;
    public const DISCOUNT_VALIDATION_ACTIVE = 1;
    public const PICKUP_TIMESLOTS_ENABLED = 1;
    public const SC_CLICKCOLLECT_ENABLED = 1;
    public const TENDER_TYPE_MAPPINGS = '{"item1":{"payment_method":"checkmo","tender_type":"2"},"item2":{"payment_method":"giftcard","tender_type":"8"},"item3":{"payment_method":"loypoints","tender_type":"4"},"_1724121642612_612":{"payment_method":"braintree","tender_type":"3"}}';
    public const ADYEN_RESPONSE = [
        'pspReference'  => 'pspreference',
        'paymentMethod' => 'adyen_cc',
        'authResult'    => true
    ];

    protected function setUp(): void
    {
        parent::setUp();
    }
}
