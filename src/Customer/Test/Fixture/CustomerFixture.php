<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Fixture;

use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Helper\ContactHelper;
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

    /** @var ContactHelper $contactHelper */
    public $contactHelper;

    /**
     * @param CustomerFactory $customerFactory
     * @param Customer $customerResourceModel
     * @param StoreManagerInterface $storeManager
     * @param CustomerRegistry $customerRegistry
     * @param ContactHelper $contactHelper
     */
    public function __construct(
        CustomerFactory $customerFactory,
        Customer $customerResourceModel,
        StoreManagerInterface $storeManager,
        CustomerRegistry $customerRegistry,
        ContactHelper $contactHelper
    ) {
        $this->customerFactory       = $customerFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->storeManager          = $storeManager;
        $this->customerRegistry      = $customerRegistry;
        $this->contactHelper         = $contactHelper;
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
        if (isset($data['random_email'])) {
            $append = 'test' . substr(sha1((uniqid((string)rand(), true))), 0, 40);
            $data['email'] = $append . AbstractIntegrationTest::EMAIL;
        }

        if (isset($data['lsr_password'])) {
            $data['lsr_password'] = $this->contactHelper->encryptPassword($data['lsr_password']);
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
