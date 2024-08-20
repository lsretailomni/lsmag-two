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
    public const USERNAME = 'mc_57745';
    public const FIRST_NAME = 'Deepak';
    public const LAST_NAME = 'Ret';
    public const CUSTOMER_ID = '1';
    public const CS_URL = 'http://20.6.33.78/commerceservice';
    public const CS_VERSION = '2024.4.1';
    public const CS_STORE = 'S0013';
    public const LS_MAG_ENABLE = '1';
    public const ENABLED = '1';
    public const LSR_ID = 'MSO000012';
    public const LSR_CARD_ID = '10051';
    public const LSR_LOY_POINTS = 2;
    public const STORE_PICKUP = 'S0001';
    public const VALID_COUPON_CODE = 'COUP0119';
    public const INVALID_COUPON_CODE = 'COUPON_CODE';
    public const LICENSE = '25.0.0.0 (25.0.0.0 [18056] CL:True EL:True)';
    public const LS_CENTRAL_VERSION = '25.0.0.0 (25.0.0.0 [conf])';
    public const RETAIL_INDUSTRY = 'retail';
    public const LSR_ORDER_EDIT = 1;
    public const TENDER_TYPE_MAPPINGS = '{"item1":{"payment_method":"checkmo","tender_type":"2"},"item2":{"payment_method":"giftcard","tender_type":"8"},"item3":{"payment_method":"loypoints","tender_type":"11"},"_1695705730821_821":{"payment_method":"braintree","tender_type":"3"},"_1706667787416_416":{"payment_method":"paypal_express","tender_type":"3"},"_1719205594553_553":{"payment_method":"refund","tender_type":"17"}}';

    protected function setUp(): void
    {
        parent::setUp();
    }
}
