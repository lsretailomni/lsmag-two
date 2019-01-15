<?php

namespace Ls\Omni\Helper;

use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Client\Ecommerce\Operation;
use Ls\Core\Model\LSR;

class ContactHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SERVICE_TYPE = 'ecommerce';

    /** @var \Magento\Framework\Api\FilterBuilder */
    protected $filterBuilder;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    protected $searchCriteriaBuilder;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    protected $customerSession;

    /** @var null */
    protected $_ns = null;

    /** @var \Magento\Directory\Model\CountryFactory */
    protected $_countryFactory;

    /** @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory */
    protected $_customerGroupColl;

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    protected $_groupRepository;

    /** @var \Magento\Customer\Api\Data\GroupInterfaceFactory */
    protected $_groupInterfaceFactory;

    /**
     * ContactHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupColl
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Customer\Api\Data\GroupInterfaceFactory $groupInterfaceFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupColl,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\Data\GroupInterfaceFactory $groupInterfaceFactory
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->_countryFactory = $countryFactory;
        $this->_customerGroupColl = $customerGroupColl;
        $this->_groupRepository = $groupRepository;
        $this->_groupInterfaceFactory = $groupInterfaceFactory;
        parent::__construct(
            $context
        );
    }

    /**
     * @param $email
     * @return Entity\MemberContact[]|null
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function search($email)
    {
        $is_email = \Zend_Validate::is($email, \Zend_Validate_EmailAddress::class);

        // load customer data from magento customer database based on lsr_username if we didn't get an email
        if (!$is_email) {
            $filters = [$this->filterBuilder
                ->setField('lsr_username')
                ->setConditionType('like')
                ->setValue($email)
                ->create()];
            $this->searchCriteriaBuilder->addFilters($filters);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->customerRepository->getList($searchCriteria);
            if ($searchResults->getTotalCount() == 1) {
                /** @var \Magento\Customer\Model\Customer $customer */
                $customer = $searchResults->getItems()[0];
                if ($customer->getId()) {
                    $is_email = true;
                    $email = $customer->getData('email');
                }
            }
        }

        if ($is_email) {
            /** @var Operation\ContactGetById $request */
            $request = new Operation\ContactSearch();
            /** @var Entity\ContactSearch $search */
            $search = new Entity\ContactSearch();
            $search->setSearch($email);
            $search->setMaxNumberOfRowsReturned(1);

            // enabling this causes the segfault if ContactSearchType is in the classMap of the SoapClient
            $search->setSearchType(Entity\Enum\ContactSearchType::EMAIL);

            try {
                $response = $request->execute($search);
                $contact_pos = $response->getContactSearchResult();
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        } else {
            // we cannot search by username in Omni as the API does not offer this information. So we quit.
            return null;
        }

        if ($contact_pos instanceof Entity\ArrayOfMemberContact && count($contact_pos->getMemberContact()) > 0) {
            return $contact_pos->getMemberContact();
        } elseif ($contact_pos instanceof Entity\MemberContact) {
            return $contact_pos;
        } else {
            return null;
        }
    }

    /**
     * @param $user
     * @param $pass
     * @return Entity\LoginWebResponse|Entity\MemberContact|\Ls\Omni\Client\ResponseInterface|null
     */
    public function login($user, $pass)
    {
        $response = null;
        $request = new Operation\LoginWeb();
        $login = new Entity\LoginWeb();
        $login->setUserName($user)
            ->setPassword($pass);
        try {
            $response = $request->execute($login);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getLoginWebResult() : $response;
    }

    /**
     * @return bool|Entity\LogoutResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function logout()
    {
        $customer = $this->customerSession->getCustomer();
        $response = null;
        $request = new Operation\Logout();
        $request->setToken($customer->getData('lsr_token'));
        $logout = new Entity\Logout();
        $logout->setUserName($customer->getData('lsr_username'))
            ->setDeviceId(null);
        try {
            $response = $request->execute($logout);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getLogoutResult() : $response;
    }


    /**
     * @param $contact
     * @param $password
     * @return \Magento\Customer\Model\Customer
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function customer($contact, $password)
    {
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $customer = $this->customerFactory->create();
        $customer->setPassword($password)
            ->setData('website_id', $websiteId)
            ->setData('email', $contact->getEmail())
            ->setData('lsr_id', $contact->getId())
            ->setData('lsr_username', $contact->getUserName())
            ->setData('firstname', $contact->getFirstName())
            ->setData('lastname', $contact->getLastName());
        $customer->save();
        return $customer;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return Entity\ContactCreateResponse|Entity\MemberContact|\Ls\Omni\Client\ResponseInterface|null
     */
    public function contact(\Magento\Customer\Model\Customer $customer)
    {

        $response = null;
        $alternate_id = 'LSM' . str_pad(md5(rand(500, 600) . $customer->getId()), 8, '0', STR_PAD_LEFT);
        $request = new Operation\ContactCreate();
        $contactCreate = new Entity\ContactCreate();
        $contact = new Entity\MemberContact();
        $contact->setAlternateId($alternate_id)
            ->setEmail($customer->getData('email'))
            ->setFirstName($customer->getData('firstname'))
            ->setLastName($customer->getData('lastname'))
            ->setMiddleName($customer->getData('middlename') ? $customer->getData('middlename') : null)
            ->setPassword($customer->getData('password'))
            ->setUserName($customer->getData('lsr_username'))
            ->setAddresses([]);
        $contactCreate->setContact($contact);
        try {
            $response = $request->execute($contactCreate);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getContactCreateResult() : $response;
    }

    /**
     * @param $username
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isUsernameExist($username)
    {
        // Creating search filter to apply for.
        $filters = [$this->filterBuilder
            ->setField('lsr_username')
            ->setConditionType('like')
            ->setValue($username)
            ->create()];
        // generating where statement to apply for.
        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)->create();

        // applying the where statement clause to the customer repository.
        $searchResults = $this->customerRepository->getList($searchCriteria);

        if ($searchResults->getTotalCount() > 0) {
            // if username already exist
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $customer
     * @param $customer_post
     * @return bool|Entity\ChangePasswordResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function changePassword($customer, $customer_post)
    {

        $response = null;
        $request = new Operation\ChangePassword();
        $changepassword = new Entity\ChangePassword();

        $request->setToken($customer->getData('lsr_token'));

        $changepassword->setUserName($customer->getData('lsr_username'))
            ->setOldPassword($customer_post['current_password'])
            ->setNewPassword($customer_post['password']);

        try {
            $response = $request->execute($changepassword);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response ? $response->getChangePasswordResult() : $response;
    }


    /**
     * @param string $email
     * @return bool| Entity\ForgotPasswordResponse | null
     */
    public function forgotPassword($email)
    {

        $response = null;
        $request = new Operation\ForgotPassword();
        $forgotpassword = new Entity\ForgotPassword();
        $forgotpassword->setUserNameOrEmail($email);

        try {
            $response = $request->execute($forgotpassword);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getForgotPasswordResult() : $response;
    }


    /**
     * @param $customer
     * @param $customer_post
     * @return bool|Entity\ResetPasswordResponse|\Ls\Omni\Client\ResponseInterface|null
     */
    public function resetPassword(\Magento\Customer\Api\Data\CustomerInterface $customer, $customer_post)
    {
        $response = null;
        $request = new Operation\ResetPassword();
        $request->setToken($customer->getCustomAttribute('lsr_token')->getValue());
        $resetpassword = new Entity\ResetPassword();

        $resetpassword->setUserName($customer->getCustomAttribute('lsr_username')->getValue())
            ->setResetCode($customer->getCustomAttribute('lsr_resetcode')->getValue())
            ->setNewPassword($customer_post['password']);

        try {
            $response = $request->execute($resetpassword);
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response ? $response->getResetPasswordResult() : $response;
    }


    /**
     * @param null $customerAddress
     * @return Entity\ContactUpdateResponse|Entity\MemberContact|\Ls\Omni\Client\ResponseInterface|null
     */
    public function UpdateAccount($customerAddress = null)
    {
        $response = null;
        $request = new Operation\ContactUpdate();
        $entity = new Entity\ContactUpdate();

        // only process if the pass object is the instance of Customer Address
        if ($customerAddress instanceof \Magento\Customer\Model\Address) {
            $customer = $customerAddress->getCustomer();
            $request->setToken($customer->getData('lsr_token'));
            $memberContact = new Entity\MemberContact();
            $memberContact->setFirstName($customerAddress->getFirstname())
                ->setLastName($customerAddress->getLastname())
                ->setPhone($customerAddress->getTelephone())
                ->setUserName($customer->getData('lsr_username'))
                ->setEmail($customer->getEmail())
                ->setMobilePhone($customerAddress->getTelephone())
                ->setMiddleName('  ')
                ->setId($customer->getData('lsr_id'))
                ->setAddresses($this->setAddresses($this->setAddress($customerAddress)));
            $entity->setContact($memberContact);
            try {
                $response = $request->execute($entity);
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            return $response ? $response->getResult() : $response;
        }
    }

    /**
     * @param null $address
     * @return Entity\ArrayOfAddress|null
     */
    public function setAddresses($address = null)
    {

        $addresses = new Entity\ArrayOfAddress();
        // only process if the pass object in the instance of customer address
        if ($address instanceof Entity\Address) {
            $addresses->setAddress($address);
            return $addresses;
        } else {
            return null;
        }
    }

    /**
     * @param null $customerAddress
     * @return Entity\Address|null
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    private function setAddress($customerAddress = null)
    {

        $address = new Entity\Address();
        // only process if the pass object in the instance of customer address
        if ($customerAddress instanceof \Magento\Customer\Model\Address) {
            $street = $customerAddress->getStreet();
            // check if street is in the form of array or string
            if (is_array($street)) {
                // set address 1
                $address->setAddress1($street[0]);
                // check if the pass data are more than 1 then set address 2 as well
                if (count($street) > 1) {
                    $address->setAddress2($street[1]);
                } else {
                    $address->setAddress2('');
                }
            } else {
                $address->setAddress1($street);
                $address->setAddress2('');
            }
            $countryname = $this->getCountryname($customerAddress->getCountryId());
            $address->setCity($customerAddress->getCity())
                ->setCountry($countryname)
                ->setPostCode($customerAddress->getPostcode())
                ->setType(Entity\Enum\AddressType::RESIDENTIAL);
            $customerAddress->getRegion() ? $address->setStateProvinceRegion($customerAddress->getRegion()) : $address->setStateProvinceRegion('');
            return $address;
        } else {
            return null;
        }
    }

    /**
     * @param $countryCode
     * @return string
     */
    private function getCountryname($countryCode)
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }


    /**
     * @return array
     */
    public function getAllCustomerGroupIds()
    {
        $customerGroupsIds = [];
        $customerGroups = $this->_customerGroupColl->create()
            ->toOptionArray();
        foreach ($customerGroups as $group) {
            $customerGroupsIds[] = $group['value'];
        }
        return $customerGroupsIds;
    }

    /**
     * @param string $groupname
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */

    public function getCustomerGroupIdByName($groupname = '')
    {

        if (is_null($groupname) or $groupname == '') {
            return false;
        }

        /** @var \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroups */
        $customerGroups = $this->_customerGroupColl->create()
            ->addFieldToFilter('customer_group_code', $groupname);

        if ($customerGroups->getSize() > 0) {

            /** @var \Magento\Customer\Model\Group $customerGroup */
            foreach ($customerGroups as $customerGroup) {
                return $customerGroup->getId();
                // Stop processing further.
                break;
            }
        } else {
            // If customer group does not exist in Magento, then create new one.
            $this->createCustomerGroupByName($groupname);

            return $this->getCustomerGroupIdByName($groupname);
        }
    }

    /**
     *  Create new Customer group based on customer name.
     * @param string $groupname
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */

    private function createCustomerGroupByName($groupname = '')
    {
        /** @var \Magento\Customer\Model\Group $group */
        $group = $this->_groupInterfaceFactory->create()
            ->setCode($groupname)
            ->setTaxClassId(3);// Default Tax Class ID for retail customers, please check tax_class table of magento2 database.
        $this->_groupRepository->save($group);
    }
}
