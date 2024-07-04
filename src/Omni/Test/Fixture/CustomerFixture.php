<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Ls\Omni\Test\Fixture;

use Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\Api\DataMerger;
use Magento\TestFramework\Fixture\Api\ServiceFactory;
use Magento\TestFramework\Fixture\Data\ProcessorInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;

/**
 * Data fixture for customer
 */
class CustomerFixture implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'password'                                   => AbstractIntegrationTest::PASSWORD,
        CustomerInterface::ID                        => null,
        CustomerInterface::CONFIRMATION              => null,
        CustomerInterface::CREATED_AT                => null,
        CustomerInterface::UPDATED_AT                => null,
        CustomerInterface::CREATED_IN                => null,
        CustomerInterface::DOB                       => null,
        CustomerInterface::EMAIL                     => AbstractIntegrationTest::EMAIL,
        CustomerInterface::FIRSTNAME                 => AbstractIntegrationTest::FIRST_NAME,
        CustomerInterface::GENDER                    => null,
        CustomerInterface::GROUP_ID                  => null,
        CustomerInterface::LASTNAME                  => AbstractIntegrationTest::LAST_NAME,
        CustomerInterface::MIDDLENAME                => null,
        CustomerInterface::PREFIX                    => null,
        CustomerInterface::STORE_ID                  => null,
        CustomerInterface::SUFFIX                    => null,
        CustomerInterface::TAXVAT                    => null,
        CustomerInterface::WEBSITE_ID                => null,
        CustomerInterface::DEFAULT_BILLING           => null,
        CustomerInterface::DEFAULT_SHIPPING          => null,
        CustomerInterface::KEY_ADDRESSES             => [],
        CustomerInterface::DISABLE_AUTO_GROUP_CHANGE => null,
        CustomerInterface::CUSTOM_ATTRIBUTES         => [],
        CustomerInterface::EXTENSION_ATTRIBUTES_KEY  => [],
    ];

    private const DEFAULT_DATA_ADDRESS = [
        AddressInterface::ID                       => null,
        AddressInterface::CUSTOMER_ID              => null,
        AddressInterface::REGION                   => 'Massachusetts',
        AddressInterface::REGION_ID                => '32',
        AddressInterface::COUNTRY_ID               => 'US',
        AddressInterface::STREET                   => ['%street_number% Test Street%uniqid%'],
        AddressInterface::COMPANY                  => null,
        AddressInterface::TELEPHONE                => '1234567890',
        AddressInterface::FAX                      => null,
        AddressInterface::POSTCODE                 => '02108',
        AddressInterface::CITY                     => 'Boston',
        AddressInterface::FIRSTNAME                => AbstractIntegrationTest::FIRST_NAME,
        AddressInterface::LASTNAME                 => AbstractIntegrationTest::LAST_NAME,
        AddressInterface::MIDDLENAME               => null,
        AddressInterface::PREFIX                   => null,
        AddressInterface::SUFFIX                   => null,
        AddressInterface::VAT_ID                   => null,
        AddressInterface::DEFAULT_BILLING          => true,
        AddressInterface::DEFAULT_SHIPPING         => true,
        AddressInterface::CUSTOM_ATTRIBUTES        => [],
        AddressInterface::EXTENSION_ATTRIBUTES_KEY => [],
    ];

    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @var CustomerRegistry
     */
    private CustomerRegistry $customerRegistry;

    /**
     * @var ProcessorInterface
     */
    private ProcessorInterface $dataProcessor;

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var CustomerFactory */
    public $customerFactory;

    /** @var Customer $customerResourceModel */
    public $customerResourceModel;

    /**
     * @param ServiceFactory $serviceFactory
     * @param AccountManagementInterface $accountManagement
     * @param CustomerRegistry $customerRegistry
     * @param ProcessorInterface $dataProcessor
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        Customer $customerResourceModel,
        ProcessorInterface $dataProcessor,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory
    ) {
        $this->serviceFactory        = $serviceFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->dataProcessor         = $dataProcessor;
        $this->storeManager          = $storeManager;
        $this->customerFactory       = $customerFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as Customer::DEFAULT_DATA.
     * @return DataObject|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function apply(array $data = []): ?DataObject
    {
        if (isset($data['random_email'])) {
            $append        = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);
            $data['email'] = $append . \Ls\Customer\Test\Integration\AbstractIntegrationTest::EMAIL;
        }
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
