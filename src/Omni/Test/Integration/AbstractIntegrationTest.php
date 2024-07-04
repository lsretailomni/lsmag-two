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
    public const PASSWORD = 'Signout369';
    public const EMAIL = 'deepak.ret@lsretail.com';
    public const FIRST_NAME = 'Deepak';
    public const LAST_NAME = 'Ret';
    public const CUSTOMER_ID = '1';
    public const CS_URL = 'http://20.6.33.78/commerceservice';
    public const CS_VERSION = '2024.4.1';
    public const CS_STORE = 'S0013';
    public const ENABLED = '1';
    public const USERNAME = 'mc_61394';
    public const LSR_ID = 'MSO000030';
    public const LSR_CARD_ID = '10069';

//    public static function createCustomerWithCustomAttributesFixture()
//    {
//        $objectManager = Bootstrap::getObjectManager();
//        $customer      = $objectManager->create(Customer::class);
//        /** @var CustomerRegistry $customerRegistry */
//        $customerRegistry = $objectManager->get(CustomerRegistry::class);
//        /** @var Customer $customer */
//        $customer->setWebsiteId(1)
//            ->setId(self::CUSTOMER_ID)
//            ->setEmail(self::EMAIL)
//            ->setPassword(self::PASSWORD)
//            ->setGroupId(1)
//            ->setStoreId(1)
//            ->setIsActive(1)
//            ->setPrefix('Mr.')
//            ->setFirstname('John')
//            ->setMiddlename('A')
//            ->setLastname('Smith')
//            ->setSuffix('Esq.')
//            ->setDefaultBilling(1)
//            ->setDefaultShipping(1)
//            ->setTaxvat('12')
//            ->setGender(0)
//            ->setData('lsr_username', self::USERNAME);
//
//        $customer->isObjectNew(true);
//        $customer->save();
//        $customerRegistry->remove($customer->getId());
//        /** @var RevokedRepositoryInterface $revokedRepo */
//        $revokedRepo = $objectManager->get(RevokedRepositoryInterface::class);
//        $revokedRepo->saveRevoked(
//            new Revoked(
//                UserContextInterface::USER_TYPE_CUSTOMER,
//                (int)$customer->getId(),
//                time() - 3600 * 24
//            )
//        );
//    }

//    public static function createSimpleProductFixture()
//    {
//        $objectManager = Bootstrap::getObjectManager();
//
//        /** @var Product $product */
//        $product = $objectManager->create(Product::class);
//        $product->setTypeId('simple')
//            ->setAttributeSetId(63)
//            ->setName('Leather backpack')
//            ->setSku('40180')
//            ->setPrice(95)
//            ->setQty(100)
//            ->setVisibility(Visibility::VISIBILITY_BOTH)
//            ->setStatus(Status::STATUS_ENABLED);
//
//        /** @var StockItemInterface $stockItem */
//        $stockItem = $objectManager->create(StockItemInterface::class);
//        $stockItem->setQty(100)
//            ->setIsInStock(true);
//        $extensionAttributes = $product->getExtensionAttributes();
//        $extensionAttributes->setStockItem($stockItem);
//
//        /** @var ProductRepositoryInterface $productRepository */
//        $productRepository = $objectManager->get(ProductRepositoryInterface::class);
//        $product           = $productRepository->save($product);
//    }

    public static function setCustomerQuoteFixture()
    {
        $objectManager = Bootstrap::getObjectManager();

        $customer    = $objectManager->get(CustomerRepositoryInterface::class)->get(self::EMAIL);
        $addressData = [
            [
                'telephone'  => 3468676,
                'postcode'   => 90230,
                'country_id' => 'US',
                'city'       => 'Culver City',
                'street'     => 'Green str, 67',
                'lastname'   => 'Smith',
                'firstname'  => 'John',
                'region_id'  => 12,
            ],
            [
                'telephone'  => 845454465,
                'postcode'   => 10178,
                'country_id' => 'DE',
                'city'       => 'Berlin',
                'street'     => ['Tunnel Alexanderpl'],
                'lastname'   => 'Smith',
                'firstname'  => 'John',
            ]
        ];
        /** @var Address $shippingAddress */
        $shippingAddress = $objectManager->create(Address::class, ['data' => $addressData[0]]);
        $shippingAddress->setAddressType('shipping');

        $billingAddress = clone $shippingAddress;
        $billingAddress->setId(null)
            ->setAddressType('billing');

        /** @var Quote $quote */
        $quote = $objectManager->create(
            Quote::class,
            [
                'data' => [
                    'customer_id'       => $customer->getId(),
                    'store_id'          => 1,
                    'reserved_order_id' => 'tsg-123456789',
                    'is_active'         => true,
                    'is_multishipping'  => false
                ],
            ]
        );
        $quote->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress);

        /** @var CartRepositoryInterface $repository */
        $repository = $objectManager->get(CartRepositoryInterface::class);
        $repository->save($quote);
    }

    public function testExecute()
    {
        $this->assertEquals(1, 1);
    }
}
