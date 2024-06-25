<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Integration;

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
    public const INVALID_EMAIL = 'pipeline_retail_pipeline_retail_pipeline_retail_pipeline_retail_pipeline_retail_pipeline_retail@lsretail.com';
    public const USERNAME = 'mc_57745';
    public const CUSTOMER_ID = '1';
    public const CS_URL = 'http://20.6.33.78/commerceservice';
    public const CS_VERSION = '2024.4.1';
    public const CS_STORE = 'S0013';
    public const LS_MAG_ENABLE = '1';
    public static function createCustomerWithCustomAttributesFixture()
    {
        $objectManager = Bootstrap::getObjectManager();
        $customer = $objectManager->create(Customer::class);
        /** @var CustomerRegistry $customerRegistry */
        $customerRegistry = $objectManager->get(CustomerRegistry::class);
        /** @var Customer $customer */
        $customer->setWebsiteId(1)
            ->setId(self::CUSTOMER_ID)
            ->setEmail(self::EMAIL)
            ->setPassword(self::PASSWORD)
            ->setGroupId(1)
            ->setStoreId(1)
            ->setIsActive(1)
            ->setPrefix('Mr.')
            ->setFirstname('John')
            ->setMiddlename('A')
            ->setLastname('Smith')
            ->setSuffix('Esq.')
            ->setDefaultBilling(1)
            ->setDefaultShipping(1)
            ->setTaxvat('12')
            ->setGender(0)
            ->setData('lsr_username', self::USERNAME);

        $customer->isObjectNew(true);
        $customer->save();
        $customerRegistry->remove($customer->getId());
        /** @var RevokedRepositoryInterface $revokedRepo */
        $revokedRepo = $objectManager->get(RevokedRepositoryInterface::class);
        $revokedRepo->saveRevoked(
            new Revoked(
                UserContextInterface::USER_TYPE_CUSTOMER,
                (int) $customer->getId(),
                time() - 3600 * 24
            )
        );
    }

    public function testExecute()
    {
        $this->assertEquals(1, 1);
    }
}
