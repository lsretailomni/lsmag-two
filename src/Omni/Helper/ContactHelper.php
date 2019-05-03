<?php

namespace Ls\Omni\Helper;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\ExpiredException;
use Zend_Validate;
use Zend_Validate_EmailAddress;

/**
 * Class ContactHelper
 * @package Ls\Omni\Helper
 */
class ContactHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    const SERVICE_TYPE = 'ecommerce';

    /** @var \Magento\Framework\Api\FilterBuilder */
    public $filterBuilder;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    public $storeManager;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    public $customerRepository;

    /** @var \Magento\Customer\Api\Data\AddressInterfaceFactory */
    public $addressFactory;

    /** @var \Magento\Customer\Api\Data\RegionInterfaceFactory */
    public $regionFactory;

    /** @var \Magento\Customer\Model\CustomerFactory */
    public $customerFactory;

    /** @var \Magento\Customer\Api\AddressRepositoryInterface */
    public $addressRepository;

    /** @var \Magento\Customer\Model\Session\Proxy */
    public $customerSession;

    /** @var null */
    public $ns = null;

    /** @var \Magento\Directory\Model\CountryFactory */
    public $countryFactory;

    /** @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory */
    public $customerGroupColl;

    /** @var \Magento\Customer\Api\GroupRepositoryInterface */
    public $groupRepository;

    /** @var \Magento\Customer\Api\Data\GroupInterfaceFactory */
    public $groupInterfaceFactory;

    /** @var  \Ls\Omni\Helper\BasketHelper */
    public $basketHelper;

    /** @var \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel */
    public $customerResourceModel;

    /** @var \Magento\Framework\Registry */
    public $registry;

    /** @var \Magento\Checkout\Model\Session\Proxy */
    public $checkoutSession;

    /** @var \Magento\Directory\Model\Country */
    public $country;

    /** @var \Magento\Directory\Model\RegionFactory */
    public $region;

    /** @var ItemHelper * */
    public $itemHelper;

    /**
     * @var \Magento\Wishlist\Model\Wishlist
     */
    public $wishlist;

    /**
     * @var \Magento\Wishlist\Model\Wishlist
     */
    public $wishlistFactory;
    /**
     * @var \Magento\Wishlist\Model\Wishlist
     */
    public $wishlistResourceModel;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * ContactHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory
     * @param \Magento\Customer\Api\Data\RegionInterfaceFactory $regionFactory
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupColl
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Customer\Api\Data\GroupInterfaceFactory $groupInterfaceFactory
     * @param BasketHelper $basketHelper
     * @param \Ls\Omni\Helper\ItemHelper $itemHelper
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Directory\Model\Country $country
     * @param \Magento\Directory\Model\RegionFactory $region
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist $wishlistResourceModel
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $customerGroupColl,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\Data\GroupInterfaceFactory $groupInterfaceFactory,
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Framework\Registry $registry,
        \Magento\Directory\Model\Country $country,
        \Magento\Directory\Model\RegionFactory $region,
        \Magento\Wishlist\Model\Wishlist $wishlist,
        \Magento\Wishlist\Model\ResourceModel\Wishlist $wishlistResourceModel,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->regionFactory = $regionFactory;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->countryFactory = $countryFactory;
        $this->customerGroupColl = $customerGroupColl;
        $this->groupRepository = $groupRepository;
        $this->groupInterfaceFactory = $groupInterfaceFactory;
        $this->basketHelper = $basketHelper;
        $this->itemHelper = $itemHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->registry = $registry;
        $this->checkoutSession = $checkoutSession;
        $this->country = $country;
        $this->region = $region;
        $this->wishlist = $wishlist;
        $this->wishlistResourceModel = $wishlistResourceModel;
        $this->wishlistFactory = $wishlistFactory;
        $this->productRepository = $productRepository;
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
            $filters = [
                $this->filterBuilder
                    ->setField('lsr_username')
                    ->setConditionType('like')
                    ->setValue($email)
                    ->create()
            ];
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
            // @codingStandardsIgnoreStart
            $request = new Operation\ContactSearch();
            /** @var Entity\ContactSearch $search */
            $search = new Entity\ContactSearch();
            // @codingStandardsIgnoreEnd
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

        if ($contact_pos instanceof Entity\ArrayOfMemberContact && !empty($contact_pos->getMemberContact())) {
            return $contact_pos->getMemberContact();
        } elseif ($contact_pos instanceof Entity\MemberContact) {
            return $contact_pos;
        } else {
            return null;
        }
    }

    /**
     * @param $param
     * @return Entity\ArrayOfMemberContact|Entity\MemberContact[]|null
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function searchWithUsernameOrEmail($param)
    {
        /** @var Operation\ContactGetById $request */
        // @codingStandardsIgnoreStart
        if (!Zend_Validate::is($param, Zend_Validate_EmailAddress::class)) {
            $request = new Operation\ContactSearch();
            /** @var Entity\ContactSearch $search */
            $search = new Entity\ContactSearch();
            // @codingStandardsIgnoreEnd
            $search->setSearch($param);
            $search->setMaxNumberOfRowsReturned(1);
            // enabling this causes the segfault if ContactSearchType is in the classMap of the SoapClient
            $search->setSearchType(Entity\Enum\ContactSearchType::USER_NAME);
            try {
                $response = $request->execute($search);
                $contact_pos = $response->getContactSearchResult();
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            if ($contact_pos instanceof Entity\ArrayOfMemberContact && !empty($contact_pos->getMemberContact())) {
                return $contact_pos->getMemberContact();
            } elseif ($contact_pos instanceof Entity\MemberContact) {
                return $contact_pos;
            } else {
                return null;
            }
        } else {
            return $this->search($param);
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
        // @codingStandardsIgnoreStart
        $request = new Operation\LoginWeb();
        $login = new Entity\LoginWeb();
        // @codingStandardsIgnoreEnd
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
        // @codingStandardsIgnoreStart
        $request = new Operation\Logout();
        $request->setToken($customer->getData('lsr_token'));
        $logout = new Entity\Logout();
        // @codingStandardsIgnoreEnd
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
     * @param Entity\MemberContact $contact
     * @param $password
     * @return \Magento\Customer\Model\Customer
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createNewCustomerAgainstProvidedInformation($contact, $password)
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
        $this->customerResourceModel->save($customer);
        // Save Address
        $addressArray = $contact->getAddresses();
        if (!empty($addressArray)) {
            $addressInfo = $addressArray->getAddress();
            if ($addressInfo instanceof Entity\Address) {
                $address = $this->addressFactory->create();
                $address->setCustomerId($customer->getId())
                    ->setFirstname($contact->getFirstName())
                    ->setLastname($contact->getLastName())
                    ->setCountryId($this->getCountryId($addressInfo->getCountry()))
                    ->setPostcode($addressInfo->getPostCode())
                    ->setCity($addressInfo->getCity())
                    ->setTelephone($contact->getMobilePhone())
                    ->setStreet([$addressInfo->getAddress1(), $addressInfo->getAddress2()])
                    ->setIsDefaultBilling('1')
                    ->setIsDefaultShipping('1');
                $regionName = $addressInfo->getStateProvinceRegion();
                if (isset($regionName)) {
                    $regionDataFactory = $this->regionFactory->create();
                    $address->setRegion($regionDataFactory->setRegion($regionName));
                    $regionFactory = $this->region->create();
                    $regionId = $regionFactory->loadByName($regionName, $addressInfo->getCountry());
                    if (!empty($regionId->getId())) {
                        $address->setRegionId($regionId->getId());
                    }
                }
                try {
                    $this->addressRepository->save($address);
                } catch (\Exception $e) {
                    $this->_logger->error($e->getMessage());
                }
            }
        }
        return $customer;
    }

    /**
     * Return the Country name by Country Id
     * default Country Id = US
     * @param $countryName
     * @return mixed
     */
    public function getCountryId($countryName)
    {
        if (strlen($countryName) == 2) {
            return $countryName;
        }
        $countryName = ucwords(strtolower($countryName));
        $countryId = 'US';
        $countryCollection = $this->country->getCollection();
        foreach ($countryCollection as $country) {
            if ($countryName == $country->getName()) {
                $countryId = $country->getCountryId();
                break;
            }
        }
        return $countryId;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return Entity\ContactCreateResponse|Entity\MemberContact|\Ls\Omni\Client\ResponseInterface|null
     */
    public function contact(\Magento\Customer\Model\Customer $customer)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $alternate_id = 'LSM' . str_pad(md5(rand(500, 600) . $customer->getId()), 8, '0', STR_PAD_LEFT);
        $request = new Operation\ContactCreate();
        $contactCreate = new Entity\ContactCreate();
        $contact = new Entity\MemberContact();
        // @codingStandardsIgnoreEnd
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
        $filters = [
            $this->filterBuilder
                ->setField('lsr_username')
                ->setConditionType('like')
                ->setValue($username)
                ->create()
        ];
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
        // @codingStandardsIgnoreStart
        $request = new Operation\ChangePassword();
        $changepassword = new Entity\ChangePassword();
        // @codingStandardsIgnoreEnd

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
        // @codingStandardsIgnoreStart
        $request = new Operation\ForgotPassword();
        $forgotpassword = new Entity\ForgotPassword();
        // @codingStandardsIgnoreEnd
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
    public function resetPassword($customer, $customer_post)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\ResetPassword();
        $resetpassword = new Entity\ResetPassword();
        // @codingStandardsIgnoreEnd
        $request->setToken($customer->getData('lsr_token'));
        $resetpassword->setUserName($customer->getData('lsr_username'))
            ->setResetCode($customer->getData('lsr_resetcode'))
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
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function updateAccount($customerAddress = null)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\ContactUpdate();
        $entity = new Entity\ContactUpdate();
        // @codingStandardsIgnoreEnd

        // only process if the pass object is the instance of Customer Address
        if ($customerAddress instanceof \Magento\Customer\Model\Address) {
            $customer = $customerAddress->getCustomer();
            $request->setToken($customer->getData('lsr_token'));
            // @codingStandardsIgnoreLine
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
        // @codingStandardsIgnoreLine
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
        // @codingStandardsIgnoreLine
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
            $region = substr($customerAddress->getRegion(), 0, 30);
            $address->setCity($customerAddress->getCity())
                ->setCountry($customerAddress->getCountryId())
                ->setPostCode($customerAddress->getPostcode())
                ->setPhoneNumber($customerAddress->getTelephone())
                ->setType(Entity\Enum\AddressType::RESIDENTIAL);
            $region ? $address->setStateProvinceRegion($region)
                : $address->setStateProvinceRegion('');
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
        $country = $this->countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     * @return array
     */
    public function getAllCustomerGroupIds()
    {
        $customerGroupsIds = [];
        $customerGroups = $this->customerGroupColl->create()
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

        if ($groupname == null or $groupname == '') {
            return false;
        }

        /** @var \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroups */
        $customerGroups = $this->customerGroupColl->create()
            ->addFieldToFilter('customer_group_code', $groupname);

        if ($customerGroups->getSize() > 0) {

            /** @var \Magento\Customer\Model\Group $customerGroup */
            foreach ($customerGroups as $customerGroup) {
                return $customerGroup->getId();
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
        $group = $this->groupInterfaceFactory->create()
            ->setCode($groupname)
            // Default Tax Class ID for retail customers, please check tax_class table of magento2 database.
            ->setTaxClassId(3);
        $this->groupRepository->save($group);
    }

    /**
     * @param Entity\OneList $oneListBasket
     * @param $contactId
     * @param $cardId
     * @throws \Ls\Omni\Exception\InvalidEnumException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateBasketAfterLogin(Entity\OneList $oneListBasket, $contactId, $cardId)
    {
        $quote = $this->checkoutSession->getQuote();
        if (!is_array($oneListBasket) &&
            $oneListBasket instanceof Entity\OneList && $oneListBasket->getId() != '') {
            // If customer has previously one list created then get
            // that and sync the current information with that
            // store the onelist returned from Omni into Magento session.
            $this->customerSession->setData(LSR::SESSION_CART_ONELIST, $oneListBasket);

            // update items from quote to basket.
            $oneList = $this->basketHelper->setOneListQuote($quote, $oneListBasket);

            // update the onelist to Omni.
            $this->basketHelper->update($oneList);
            $this->itemHelper->setDiscountedPricesForItems(
                $quote,
                $this->basketHelper->getBasketSessionValue()
            );
        } elseif ($this->customerSession->getData(LSR::SESSION_CART_ONELIST)) {
            // if customer already has onelist created then update
            // the list to get the information with user.
            $oneListBasket = $this->customerSession->getData(LSR::SESSION_CART_ONELIST);

            //Update onelist in Omni with user data.
            $oneListBasket->setCardId($cardId)
                ->setContactId($contactId)
                ->setDescription('OneList Magento')
                ->setIsDefaultList(true)
                ->setListType(Entity\Enum\ListType::BASKET);
            // update items from quote to basket.
            $oneList = $this->basketHelper->setOneListQuote($quote, $oneListBasket);
            // update the onelist to Omni.
            $this->basketHelper->update($oneList);
            $this->itemHelper->setDiscountedPricesForItems(
                $quote,
                $this->basketHelper->getBasketSessionValue()
            );
        } elseif (!empty($quote->getAllItems())) {
            // get the onelist or if not exist then create new one with empty data of customer.
            $oneList = $this->basketHelper->get();
            $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
            $this->basketHelper->update($oneList);
            $this->itemHelper->setDiscountedPricesForItems(
                $quote,
                $this->basketHelper->getBasketSessionValue()
            );
        }
    }

    /**
     * @param Entity\OneList $oneListWishlist
     * @throws \Exception
     */
    public function updateWishlistAfterLogin(Entity\OneList $oneListWishlist)
    {
        // @codingStandardsIgnoreStart
        $customerId = $this->customerSession->getCustomer()->getId();
        $wishlist = $this->wishlist->loadByCustomerId($customerId);
        $this->removeWishlist($wishlist);
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId($customerId, true);
        $itemsCollection = $oneListWishlist->getItems()->getOneListItem();
        if (!is_array($itemsCollection)) {
            $itemsCollection = [$itemsCollection];
        }
        try {
            foreach ($itemsCollection as $item) {
                $buyRequest = [];
                $sku = $item->getItem()->getId();
                $product = $this->productRepository->get($sku);
                $qty = $item->getQuantity();
                $buyRequest['qty'] = $qty;
                if ($item->getVariantReg()) {
                    $simSku = $sku . '-' . $item->getVariantReg()->getId();
                    $simProuduct = $this->productRepository->get($simSku);
                    $optionsData = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                    $buyRequest['super_attribute'] = [];
                    foreach ($optionsData as $key => $option) {
                        $code = $option['attribute_code'];
                        $value = $simProuduct->getData($code);
                        $buyRequest['super_attribute'][$key] = $value;
                    }
                }
                $wishlist->addNewItem($product, $buyRequest);
                $this->wishlistResourceModel->save($wishlist);
            }

            if (!is_array($oneListWishlist) &&
                $oneListWishlist instanceof Entity\OneList) {
                $this->customerSession->setData(LSR::SESSION_CART_WISHLIST, $oneListWishlist);
            }
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param $wishlist
     */
    public function removeWishlist(&$wishlist)
    {
        // @codingStandardsIgnoreStart
        try {
            $wishlist->delete();
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        // @codingStandardsIgnoreEnd
    }
    /**
     * @param Entity\MemberContact $result
     * @param $credentials
     * @param $is_email
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function processCustomerLogin(Entity\MemberContact $result, $credentials, $is_email)
    {
        $filters = [
            $this->filterBuilder
                ->setField('email')
                ->setConditionType('eq')
                ->setValue($result->getEmail())
                ->create()
        ];
        $this->searchCriteriaBuilder->addFilters($filters);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->customerRepository->getList($searchCriteria);
        $customer = null;
        if ($searchResults->getTotalCount() == 0) {
            $customer = $this->createNewCustomerAgainstProvidedInformation($result, $credentials['password']);
        } else {
            foreach ($searchResults->getItems() as $match) {
                $customer = $this->customerRepository->getById($match->getId());
                break;
            }
        }
        $customer_email = $customer->getEmail();
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->loadByEmail($customer_email);
        $card = $result->getCard();
        if ($customer->getData('lsr_id') === null) {
            $customer->setData('lsr_id', $result->getId());
        }
        if (!$is_email && empty($customer->getData('lsr_username'))) {
            $customer->setData('lsr_username', $credentials['username']);
        }
        if ($customer->getData('lsr_cardid') === null) {
            $customer->setData('lsr_cardid', $card->getId());
        }
        $token = $result->getLoggedOnToDevice()->getSecurityToken();

        $customer->setData('lsr_token', $token);
        $customer->setData(
            'attribute_set_id',
            \Magento\Customer\Api\CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER
        );

        if ($result->getAccount()->getScheme()->getId()) {
            $customerGroupId = $this->getCustomerGroupIdByName(
                $result->getAccount()->getScheme()->getId()
            );
            $customer->setGroupId($customerGroupId);
        }
        $this->customerResourceModel->save($customer);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token);
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $result->getId());

        $card = $result->getCard();
        if ($card instanceof Entity\Card && $card->getId() !== null) {
            $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $card->getId());
        }

        $this->customerSession->setCustomerAsLoggedIn($customer);
    }

    /**
     * @param $email
     * @return \Magento\Customer\Api\Data\CustomerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function searchCustomerByEmail($email)
    {
        $filters = [
            $this->filterBuilder
                ->setField('email')
                ->setConditionType('eq')
                ->setValue($email)
                ->create()
        ];
        $this->searchCriteriaBuilder->addFilters($filters);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->customerRepository->getList($searchCriteria);
    }

    /**
     * Returns customer against the provided rptoken
     * @param string $rpToken
     * @return CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function matchCustomerByRpToken(string $rpToken): CustomerInterface
    {

        $this->searchCriteriaBuilder->addFilter(
            'rp_token',
            $rpToken
        );
        $this->searchCriteriaBuilder->setPageSize(1);
        $found = $this->customerRepository->getList(
            $this->searchCriteriaBuilder->create()
        );

        if ($found->getTotalCount() > 1) {
            // @codingStandardsIgnoreStart
            //Failed to generated unique RP token
            throw new ExpiredException(
                new \Magento\Framework\Phrase('Reset password token expired.')
            );
            // @codingStandardsIgnoreEnd
        }
        if ($found->getTotalCount() === 0) {
            //Customer with such token not found.
            throw NoSuchEntityException::singleField(
                'rp_token',
                $rpToken
            );
        }

        //Unique customer found.
        return $found->getItems()[0];
    }
}
