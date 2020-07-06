<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\ExpiredException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\ResourceModel\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use Zend_Validate_Exception;

/**
 * Class ContactHelper
 * @package Ls\Omni\Helper
 */
class ContactHelper extends AbstractHelper
{
    const SERVICE_TYPE = 'ecommerce';

    /** @var FilterBuilder */
    public $filterBuilder;

    /** @var SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var CustomerRepositoryInterface */
    public $customerRepository;

    /** @var AddressInterfaceFactory */
    public $addressFactory;

    /** @var RegionInterfaceFactory */
    public $regionFactory;

    /** @var CustomerFactory */
    public $customerFactory;

    /** @var AddressRepositoryInterface */
    public $addressRepository;

    /** @var \Magento\Customer\Model\Session\Proxy */
    public $customerSession;

    /** @var null */
    public $ns = null;

    /** @var CountryFactory */
    public $countryFactory;

    /** @var CollectionFactory */
    public $customerGroupColl;

    /** @var GroupRepositoryInterface */
    public $groupRepository;

    /** @var GroupInterfaceFactory */
    public $groupInterfaceFactory;

    /** @var  BasketHelper */
    public $basketHelper;

    /** @var \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel */
    public $customerResourceModel;

    /** @var Registry */
    public $registry;

    /** @var Proxy */
    public $checkoutSession;

    /** @var Country */
    public $country;

    /** @var RegionFactory */
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
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * ContactHelper constructor.
     * @param Context $context
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param AddressInterfaceFactory $addressFactory
     * @param RegionInterfaceFactory $regionFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param CountryFactory $countryFactory
     * @param CollectionFactory $customerGroupColl
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupInterfaceFactory $groupInterfaceFactory
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param Proxy $checkoutSession
     * @param Registry $registry
     * @param Country $country
     * @param RegionFactory $region
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param Wishlist $wishlistResourceModel
     * @param WishlistFactory $wishlistFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        AddressInterfaceFactory $addressFactory,
        RegionInterfaceFactory $regionFactory,
        AddressRepositoryInterface $addressRepository,
        CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        CountryFactory $countryFactory,
        CollectionFactory $customerGroupColl,
        GroupRepositoryInterface $groupRepository,
        GroupInterfaceFactory $groupInterfaceFactory,
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel,
        Proxy $checkoutSession,
        Registry $registry,
        Country $country,
        RegionFactory $region,
        \Magento\Wishlist\Model\Wishlist $wishlist,
        Wishlist $wishlistResourceModel,
        WishlistFactory $wishlistFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->filterBuilder         = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager          = $storeManager;
        $this->customerRepository    = $customerRepository;
        $this->addressFactory        = $addressFactory;
        $this->addressRepository     = $addressRepository;
        $this->regionFactory         = $regionFactory;
        $this->customerFactory       = $customerFactory;
        $this->customerSession       = $customerSession;
        $this->countryFactory        = $countryFactory;
        $this->customerGroupColl     = $customerGroupColl;
        $this->groupRepository       = $groupRepository;
        $this->groupInterfaceFactory = $groupInterfaceFactory;
        $this->basketHelper          = $basketHelper;
        $this->itemHelper            = $itemHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->registry              = $registry;
        $this->checkoutSession       = $checkoutSession;
        $this->country               = $country;
        $this->region                = $region;
        $this->wishlist              = $wishlist;
        $this->wishlistResourceModel = $wishlistResourceModel;
        $this->wishlistFactory       = $wishlistFactory;
        $this->productRepository     = $productRepository;
        parent::__construct(
            $context
        );
    }

    /**
     * @param $email
     * @return Entity\MemberContact[]|null
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function search($email)
    {
        $is_email = Zend_Validate::is($email, Zend_Validate_EmailAddress::class);
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
            $searchResults  = $this->customerRepository->getList($searchCriteria);
            if ($searchResults->getTotalCount() == 1) {
                /** @var Customer $customer */
                $customer = $searchResults->getItems()[0];
                if ($customer->getId()) {
                    $is_email = true;
                    $email    = $customer->getData('email');
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
                $response    = $request->execute($search);
                $contact_pos = $response->getContactSearchResult();
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
        } else {
            // we cannot search by username in Omni as the API does not offer this information. So we quit.
            return null;
        }

        if ($contact_pos instanceof Entity\ArrayOfMemberContact && !empty($contact_pos->getMemberContact())) {
            if (is_array($contact_pos->getMemberContact())) {
                return $contact_pos->getMemberContact()[0];
            } else {
                return $contact_pos->getMemberContact();
            }
        } elseif ($contact_pos instanceof Entity\MemberContact) {
            return $contact_pos;
        } else {
            return null;
        }
    }

    /**
     * @param $param
     * @return Entity\ArrayOfMemberContact|Entity\MemberContact[]|null
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function searchWithUsernameOrEmail($param)
    {
        /** @var Operation\ContactGetById $request */
        // @codingStandardsIgnoreStart
        if (!Zend_Validate::is($param, Zend_Validate_EmailAddress::class)) {
            // LS Central only accept [a-zA-Z0-9-_@.] pattern of UserName
            if (!preg_match("/^[a-zA-Z0-9-_@.]*$/", $param)) {
                return null;
            }
            $request = new Operation\ContactSearch();
            /** @var Entity\ContactSearch $search */
            $search = new Entity\ContactSearch();
            // @codingStandardsIgnoreEnd
            $search->setSearch($param);
            $search->setMaxNumberOfRowsReturned(1);
            // enabling this causes the segfault if ContactSearchType is in the classMap of the SoapClient
            $search->setSearchType(Entity\Enum\ContactSearchType::USER_NAME);
            try {
                $response    = $request->execute($search);
                $contact_pos = $response->getContactSearchResult();
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            if ($contact_pos instanceof Entity\ArrayOfMemberContact && !empty($contact_pos->getMemberContact())) {
                if (is_array($contact_pos->getMemberContact())) {
                    return $contact_pos->getMemberContact()[0];
                } else {
                    return $contact_pos->getMemberContact();
                }
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
     * @return Entity\LoginWebResponse|Entity\MemberContact|ResponseInterface|null
     */
    public function login($user, $pass)
    {
        // LS Central only accept [a-zA-Z0-9-_@.] pattern of UserName
        if (!preg_match("/^[a-zA-Z0-9-_@.]*$/", $user)) {
            return null;
        }
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\LoginWeb();
        $login   = new Entity\LoginWeb();
        // @codingStandardsIgnoreEnd
        $login->setUserName($user)
            ->setPassword($pass);
        try {
            $response = $request->execute($login);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response ? $response->getLoginWebResult() : $response;
    }

    /**
     * @param Entity\MemberContact $contact
     * @param $password
     * @return Customer
     * @throws Exception
     * @throws LocalizedException
     */
    public function createNewCustomerAgainstProvidedInformation($contact, $password)
    {
        // Create Customer to Magento
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $customer  = $this->customerFactory->create();
        try {
            $cards  = $contact->getCards()->getCard();
            $cardId = $cards[0]->getId();
            $customer->setPassword($password)
                ->setData('website_id', $websiteId)
                ->setData('email', $contact->getEmail())
                ->setData('lsr_id', $contact->getId())
                ->setData('lsr_username', $contact->getUserName())
                ->setData('lsr_cardid', $cardId)
                ->setData('firstname', $contact->getFirstName())
                ->setData('lastname', $contact->getLastName());
            $this->customerResourceModel->save($customer);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        // Save Address
        $addressesArray = $contact->getAddresses();
        if (!empty($addressesArray)) {
            $addressArray = $addressesArray->getAddress();
            $addressInfo  = reset($addressArray);
            if ($addressInfo instanceof Entity\Address && !empty($addressInfo->getCountry())) {
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
                if (!empty($regionName)) {
                    $regionDataFactory = $this->regionFactory->create();
                    $address->setRegion($regionDataFactory->setRegion($regionName));
                    $regionFactory = $this->region->create();
                    $regionId      = $regionFactory->loadByName($regionName, $addressInfo->getCountry());
                    if (!empty($regionId->getId())) {
                        $address->setRegionId($regionId->getId());
                    }
                }
                try {
                    $this->addressRepository->save($address);
                } catch (Exception $e) {
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
        $countryName       = ucwords(strtolower($countryName));
        $countryId         = 'US';
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
     * @param Customer $customer
     * @return Entity\ContactCreateResponse|Entity\MemberContact|ResponseInterface|null
     */
    public function contact(Customer $customer)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $alternate_id  = 'LSM' . str_pad(md5(rand(500, 600) . $customer->getId()), 8, '0', STR_PAD_LEFT);
        $request       = new Operation\ContactCreate();
        $contactCreate = new Entity\ContactCreate();
        $contact       = new Entity\MemberContact();
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
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getContactCreateResult() : $response;
    }

    /**
     * @param $username
     * @return bool
     * @throws LocalizedException
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
     * Check username exist in LS Central or not
     * @param $username
     * @return bool
     * @throws InvalidEnumException
     */
    public function isUsernameExistInLsCentral($username)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request       = new Operation\ContactSearch();
        $contactSearch = new Entity\ContactSearch();
        $contactSearch->setSearchType(Entity\Enum\ContactSearchType::USER_NAME);
        $contactSearch->setSearch($username);
        try {
            $response = $request->execute($contactSearch);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if (!empty($response) && !empty($response->getContactSearchResult())) {
            foreach ($response->getContactSearchResult() as $contact) {
                if ($contact->getUserName() === $username) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Check email exist in LS Central or not
     * @param $email
     * @return bool
     * @throws InvalidEnumException
     */
    public function isEmailExistInLsCentral($email)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request       = new Operation\ContactSearch();
        $contactSearch = new Entity\ContactSearch();
        $contactSearch->setSearchType(Entity\Enum\ContactSearchType::EMAIL);
        $contactSearch->setSearch($email);
        try {
            $response = $request->execute($contactSearch);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if (!empty($response) && !empty($response->getContactSearchResult())) {
            foreach ($response->getContactSearchResult() as $contact) {
                if ($contact->getEmail() === $email) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $customer
     * @param $customer_post
     * @return bool|Entity\ChangePasswordResponse|ResponseInterface|null
     */
    public function changePassword($customer, $customer_post)
    {

        $response = null;
        // @codingStandardsIgnoreStart
        $request        = new Operation\ChangePassword();
        $changepassword = new Entity\ChangePassword();
        // @codingStandardsIgnoreEnd

        $request->setToken($customer->getData('lsr_token'));

        $changepassword->setUserName($customer->getData('lsr_username'))
            ->setOldPassword($customer_post['current_password'])
            ->setNewPassword($customer_post['password']);

        try {
            $response = $request->execute($changepassword);
        } catch (Exception $e) {
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
        $request        = new Operation\ForgotPassword();
        $forgotpassword = new Entity\ForgotPassword();
        // @codingStandardsIgnoreEnd
        $forgotpassword->setUserNameOrEmail($email);

        try {
            $response = $request->execute($forgotpassword);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getForgotPasswordResult() : $response;
    }

    /**
     * @param $customer
     * @param $customer_post
     * @return bool|Entity\ResetPasswordResponse|ResponseInterface|null
     */
    public function resetPassword($customer, $customer_post)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request       = new Operation\ResetPassword();
        $resetpassword = new Entity\ResetPassword();
        // @codingStandardsIgnoreEnd
        $request->setToken($customer->getData('lsr_token'));
        $resetpassword->setUserName($customer->getData('lsr_username'))
            ->setResetCode($customer->getData('lsr_resetcode'))
            ->setNewPassword($customer_post['password']);

        try {
            $response = $request->execute($resetpassword);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response ? $response->getResetPasswordResult() : $response;
    }

    /**
     * @param null $customerAddress
     * @return Entity\ContactUpdateResponse|Entity\MemberContact|ResponseInterface|null
     * @throws InvalidEnumException
     */
    public function updateAccount($customerAddress = null)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\ContactUpdate();
        $entity  = new Entity\ContactUpdate();
        // @codingStandardsIgnoreEnd

        // only process if the pass object is the instance of Customer Address
        if ($customerAddress instanceof Address) {
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
                ->setCards($this->setCards($this->setCard($customer->getData('lsr_cardid'))))
                ->setAddresses($this->setAddresses($this->setAddress($customerAddress)));
            $entity->setContact($memberContact);
            try {
                $response = $request->execute($entity);
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            return $response ? $response->getResult() : $response;
        }
    }

    /**
     * @param null $card
     * @return Entity\ArrayOfCard|null
     */
    public function setCards($card = null)
    {
        // @codingStandardsIgnoreLine
        $cards = new Entity\ArrayOfCard();
        if ($card instanceof Entity\Card) {
            $cards->setCard($card);
            return $cards;
        } else {
            return null;
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
     * @param null $card
     * @return Entity\Card|null
     */
    private function setCard($cardId = null)
    {
        // @codingStandardsIgnoreLine
        $card = new Entity\Card();
        $card->setId($cardId);
        return $card;
    }

    /**
     * @param null $customerAddress
     * @return Entity\Address|null
     * @throws InvalidEnumException
     */
    private function setAddress($customerAddress = null)
    {
        // @codingStandardsIgnoreLine
        $address = new Entity\Address();
        // only process if the pass object in the instance of customer address
        if ($customerAddress instanceof Address) {
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
        $customerGroups    = $this->customerGroupColl->create()
            ->toOptionArray();
        foreach ($customerGroups as $group) {
            $customerGroupsIds[] = $group['value'];
        }
        return $customerGroupsIds;
    }

    /**
     * @param string $groupname
     * @return bool|mixed
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */

    public function getCustomerGroupIdByName($groupname = '')
    {

        if ($groupname == null or $groupname == '') {
            return false;
        }

        /** @var Collection $customerGroups */
        $customerGroups = $this->customerGroupColl->create()
            ->addFieldToFilter('customer_group_code', $groupname);

        if ($customerGroups->getSize() > 0) {

            /** @var Group $customerGroup */
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
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */

    private function createCustomerGroupByName($groupname = '')
    {
        /** @var Group $group */
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
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     */
    public function updateBasketAfterLogin($oneListBasket, $contactId, $cardId)
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
                ->setDescription('OneList Magento')
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
     * @throws Exception
     */
    public function updateWishlistAfterLogin(Entity\OneList $oneListWishlist)
    {
        // @codingStandardsIgnoreStart
        $customerId = $this->customerSession->getCustomer()->getId();
        $wishlist   = $this->wishlist->loadByCustomerId($customerId);
        $this->removeWishlist($wishlist);
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId($customerId, true);
        $itemsCollection = $oneListWishlist->getItems()->getOneListItem();
        if (!is_array($itemsCollection)) {
            $itemsCollection = [$itemsCollection];
        }
        try {
            foreach ($itemsCollection as $item) {
                $buyRequest        = [];
                $sku               = $item->getItemId();
                $product           = $this->productRepository->get($sku);
                $qty               = $item->getQuantity();
                $buyRequest['qty'] = $qty;
                if ($item->getVariantId()) {
                    $simSku                        = $sku . '-' . $item->getVariantId();
                    $simProuduct                   = $this->productRepository->get($simSku);
                    $optionsData                   = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                    $buyRequest['super_attribute'] = [];
                    foreach ($optionsData as $key => $option) {
                        $code                                = $option['attribute_code'];
                        $value                               = $simProuduct->getData($code);
                        $buyRequest['super_attribute'][$key] = $value;
                    }
                }
                $result = $wishlist->addNewItem($product, $buyRequest);
                $this->wishlistResourceModel->save($wishlist);
                $this->_eventManager->dispatch(
                    'wishlist_add_product',
                    ['wishlist' => $wishlist, 'product' => $product, 'item' => $result]
                );
            }

            if (!is_array($oneListWishlist) &&
                $oneListWishlist instanceof Entity\OneList) {
                $this->customerSession->setData(LSR::SESSION_CART_WISHLIST, $oneListWishlist);
            }
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        // @codingStandardsIgnoreEnd
    }

    /**
     * @param Entity\MemberContact $result
     * @param $credentials
     * @param $is_email
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
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
        $searchResults  = $this->customerRepository->getList($searchCriteria);
        $customer       = null;
        if ($searchResults->getTotalCount() == 0) {
            $customer = $this->createNewCustomerAgainstProvidedInformation($result, $credentials['password']);
        } else {
            foreach ($searchResults->getItems() as $match) {
                $customer = $this->customerRepository->getById($match->getId());
                break;
            }
        }
        $customer_email = $customer->getEmail();
        $websiteId      = $this->storeManager->getWebsite()->getWebsiteId();
        /** @var Customer $customer */
        $customer = $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->loadByEmail($customer_email);
        $cards    = $result->getCards()->getCard();
        $cardId   = $cards[0]->getId();
        if ($customer->getData('lsr_id') === null) {
            $customer->setData('lsr_id', $result->getId());
        }
        if (!$is_email && empty($customer->getData('lsr_username'))) {
            $customer->setData('lsr_username', $credentials['username']);
        }
        if ($customer->getData('lsr_cardid') === null) {
            $customer->setData('lsr_cardid', $cardId);
        }
        $token = $result->getLoggedOnToDevice()->getSecurityToken();

        $customer->setData('lsr_token', $token);
        $customer->setData(
            'attribute_set_id',
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER
        );

        if (!empty($result) &&
            !empty($result->getAccount()) &&
            !empty($result->getAccount()->getScheme())) {
            $customerGroupId = $this->getCustomerGroupIdByName(
                $result->getAccount()->getScheme()->getId()
            );
            $customer->setGroupId($customerGroupId);
        }
        $this->customerResourceModel->save($customer);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token);
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $result->getId());

        if ($cardId !== null) {
            $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $cardId);
        }

        $this->customerSession->setCustomerAsLoggedIn($customer);
    }

    /**
     * @param $isEmail
     * @param $userNameOrEmail
     * @param RequestInterface $request
     * @param bool $isAjax
     * @throws LocalizedException
     */
    public function loginCustomerIfOmniServiceDown($isEmail, $userNameOrEmail, $request, $isAjax = false)
    {
        if (!$isEmail) {
            $filters = [
                $this->filterBuilder
                    ->setField('lsr_username')
                    ->setConditionType('like')
                    ->setValue($userNameOrEmail)
                    ->create()
            ];
            $this->searchCriteriaBuilder->addFilters($filters);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults  = $this->customerRepository->getList($searchCriteria);
            if ($searchResults->getTotalCount() == 1) {
                $customerRepository = $searchResults->getItems()[0];
                $email              = $customerRepository->getEmail();
                if ($isAjax == true) {
                    /** @var RequestInterface $request */
                    $credentials             = json_decode($request->getContent(), true);
                    $credentials['username'] = $email;
                    $request->setContent(json_encode($credentials));
                } else {
                    /** @var RequestInterface $request */
                    $login             = $request->getPost("login");
                    $login['username'] = $email;
                    $request->setPostValue("login", $login);
                }
            }
        }
    }

    /**
     * @param $email
     * @return CustomerSearchResultsInterface
     * @throws LocalizedException
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
     * @throws LocalizedException
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
                new Phrase('Reset password token expired.')
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
        return $found->getItems()[0];
    }

    /**
     * @param $arrayOneLists
     * @param $type
     * @return Entity\OneList|null
     */
    public function getOneListTypeObject($arrayOneLists, $type)
    {
        if (is_array($arrayOneLists)) {
            /** @var Entity\OneList $oneList */
            foreach ($arrayOneLists as $oneList) {
                if ($oneList->getListType() == $type && $oneList->getStoreId() == $this->basketHelper->getDefaultWebStore()) {
                    return $oneList;
                }
            }
        }
        return null;
    }
}
