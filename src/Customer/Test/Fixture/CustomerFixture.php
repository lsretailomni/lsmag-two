<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Customer\Test\Fixture;

use \Ls\Customer\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class CustomerFixture implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'id'               => AbstractIntegrationTest::CUSTOMER_ID,
        'email'            => AbstractIntegrationTest::EMAIL,
        'password'         => AbstractIntegrationTest::PASSWORD,
        'group_id'         => 1,
        'is_active'        => 1,
        'prefix'           => 'Mr.',
        'firstname'        => 'John',
        'middlename'       => 'A',
        'lastname'         => 'Smith',
        'suffix'           => 'Esq.',
        'taxvat'           => '12',
        'gender'           => 1,
    ];

    /** @var CustomerFactory */
    public $customerFactory;

    /** @var Customer $customerResourceModel */
    public $customerResourceModel;

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var CustomerRegistry */
    public $customerRegistry;

    /**
     * @param CustomerFactory $customerFactory
     * @param Customer $customerResourceModel
     * @param StoreManagerInterface $storeManager
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        CustomerFactory $customerFactory,
        Customer $customerResourceModel,
        StoreManagerInterface $storeManager,
        CustomerRegistry $customerRegistry
    ) {
        $this->customerFactory       = $customerFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->storeManager          = $storeManager;
        $this->customerRegistry      = $customerRegistry;
    }

    /**
     * Apply fixture data
     *
     * @param array $data
     * @return DataObject|null
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function apply(array $data = []): ?DataObject
    {
        $data['website_id'] = $this->storeManager->getWebsite()->getWebsiteId();
        $data['store_id']   = $this->storeManager->getStore()->getId();
        $data               = array_merge(self::DEFAULT_DATA, $data);

        $customer = $this->customerFactory->create();
        $customer->addData($data);
        $this->customerResourceModel->save($customer);

        $this->customerRegistry->remove($customer->getId());

        return $customer;
    }
}
