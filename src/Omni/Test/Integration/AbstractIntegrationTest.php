<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\JwtUserToken\Api\Data\Revoked;
use Magento\JwtUserToken\Api\RevokedRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Customer\Api\CustomerRepositoryInterface;
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
    public const ENABLED = '1';
    public const LSR_ID = 'MSO000012';
    public const LSR_CARD_ID = '10051';
    public const LSR_LOY_POINTS = 2;
    public const STORE_PICKUP = 'S0001';
    public const VALID_COUPON_CODE = 'COUP0119';
    public const INVALID_COUPON_CODE = 'COUPON_CODE';
    public const LICENSE = '25.0.0.0 (25.0.0.0 [18056] CL:True EL:True)';

    public function testExecute()
    {
        $this->assertEquals(1, 1);
    }
}
