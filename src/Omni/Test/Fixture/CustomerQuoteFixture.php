<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Fixture;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\TestFramework\Fixture\DataFixtureInterface;
use Magento\TestFramework\Helper\Bootstrap;
use \Ls\Customer\Test\Integration\AbstractIntegrationTest;

class CustomerQuoteFixture implements DataFixtureInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    public $customerRepository;

    /**
     * @var Address
     */
    public $address;

    /**
     * @var CartRepositoryInterface
     */
    public $cartRepository;

    /**
     * @var Quote
     */
    public $quote;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Address $address,
        CartRepositoryInterface $cartRepository,
        Quote $quote
    ) {
        $this->customerRepository = $customerRepository;
        $this->address            = $address;
        $this->cartRepository     = $cartRepository;
        $this->quote              = $quote;
    }

    public function apply(array $data = []): ?DataObject
    {
        $objectManager = Bootstrap::getObjectManager();

        $customer    = $objectManager->get(CustomerRepositoryInterface::class)->get(AbstractIntegrationTest::EMAIL);
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

        return $quote;
    }
}
