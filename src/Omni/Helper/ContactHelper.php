<?php

namespace Ls\Omni\Helper;

use Exception;
use Laminas\Validator\EmailAddress as ValidateEmailAddress;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Authentication;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\ExpiredException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\ResourceModel\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Helper functions for member contact
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
     * @var CustomerCollection
     */
    public $customerCollection;

    /**
     * @var EncryptorInterface
     */
    public $encryptorInterface;

    /**
     * @var ValidateEmailAddress
     */
    public $validateEmailAddress;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var DateTime
     */
    public $date;

    /**
     * @var CustomerRegistry
     */
    public $customerRegistry;

    /**
     * @var Authentication
     */
    public $authentication;

    /**
     * @var AccountConfirmation
     */
    public $accountConfirmation;

    /**
     * @var StockHelper
     */
    public $stockHelper;

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
     * @param CustomerCollection $customerCollection
     * @param EncryptorInterface $encryptorInterface
     * @param ValidateEmailAddress $validateEmailAddress
     * @param LSR $lsr
     * @param DateTime $date
     * @param CustomerRegistry $customerRegistry
     * @param Authentication $authentication
     * @param AccountConfirmation $accountConfirmation
     * @param StockHelper $stockHelper
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
        ProductRepositoryInterface $productRepository,
        CustomerCollection $customerCollection,
        EncryptorInterface $encryptorInterface,
        ValidateEmailAddress $validateEmailAddress,
        LSR $lsr,
        DateTime $date,
        CustomerRegistry $customerRegistry,
        Authentication $authentication,
        AccountConfirmation $accountConfirmation,
        StockHelper $stockHelper
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
        $this->customerCollection    = $customerCollection;
        $this->encryptorInterface    = $encryptorInterface;
        $this->validateEmailAddress  = $validateEmailAddress;
        $this->lsr                   = $lsr;
        $this->date                  = $date;
        $this->customerRegistry      = $customerRegistry;
        $this->authentication        = $authentication;
        $this->accountConfirmation   = $accountConfirmation;
        $this->stockHelper           = $stockHelper;
        parent::__construct(
            $context
        );
    }

    /**
     * @param $email
     * @return MemberContact[]|null
     * @throws InvalidEnumException
     * @throws LocalizedException
     */
    public function search($email)
    {
        $is_email = $this->isValid($email);
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
            $search  = new Entity\ContactSearch();
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
        } elseif ($contact_pos instanceof MemberContact) {
            return $contact_pos;
        } else {
            return null;
        }
    }

    /**
     * @param $param
     * @return Entity\ArrayOfMemberContact|MemberContact[]|null
     * @throws InvalidEnumException
     * @throws LocalizedException
     */
    public function searchWithUsernameOrEmail($param)
    {
        /** @var Operation\ContactGetById $request */
        // @codingStandardsIgnoreStart
        if (!$this->isValid($param)) {
            // LS Central only accept [a-zA-Z0-9-_@.] pattern of UserName
            if (!preg_match("/^[a-zA-Z0-9-_@.]*$/", $param)) {
                return null;
            }
            $request = new Operation\ContactSearch();
            $search  = new Entity\ContactSearch();
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
            } elseif ($contact_pos instanceof MemberContact) {
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
     * @return Entity\LoginWebResponse|MemberContact|ResponseInterface|null
     */
    public function login($user, $pass)
    {
        // LS Central only accept [a-zA-Z0-9-_@.] pattern of UserName
        if (!preg_match("/^[a-zA-Z0-9-_@.]*$/", $user)) {
            return null;
        }
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\Login();
        $login   = new Entity\Login();
        // @codingStandardsIgnoreEnd
        $login->setUserName($user)
            ->setPassword($pass);
        try {
            $response = $request->execute($login);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response ? $response->getLoginResult() : $response;
    }

    /**
     * @param MemberContact $contact
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
            $customer->setPassword($password)
                ->setData('website_id', $websiteId)
                ->setData('email', $contact->getEmail())
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
                    ->setTelephone($addressInfo->getPhoneNumber())
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
     * @return Entity\ContactCreateResponse|MemberContact|ResponseInterface|null
     */
    public function contact(Customer $customer)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $alternate_id  = 'LSM' . str_pad(md5(rand(500, 600) . $customer->getId()), 8, '0', STR_PAD_LEFT);
        $request       = new Operation\ContactCreate();
        $contactCreate = new Entity\ContactCreate();
        $contact       = new MemberContact();
        if (!empty($customer->getData('lsr_password'))) {
            $lsrPassword = $this->encryptorInterface->decrypt($customer->getData('lsr_password'));
        } else {
            $lsrPassword = null;
        }
        $password = (!empty($lsrPassword)) ? $lsrPassword : $customer->getData('password');
        // @codingStandardsIgnoreEnd
        $contact->setAlternateId($alternate_id)
            ->setEmail($customer->getData('email'))
            ->setFirstName($customer->getData('firstname'))
            ->setLastName($customer->getData('lastname'))
            ->setMiddleName($customer->getData('middlename') ? $customer->getData('middlename') : null)
            ->setPassword($password)
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
     * @return bool|Entity\PasswordChangeResponse|ResponseInterface|null
     */
    public function changePassword($customer, $customer_post)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request        = new Operation\PasswordChange();
        $changepassword = new Entity\PasswordChange();
        // @codingStandardsIgnoreEnd

        $request->setToken($customer->getData('lsr_token'));

        $changepassword->setUserName($customer->getData('lsr_username'))
            ->setOldPassword($customer_post['current_password'])
            ->setNewPassword($customer_post['password'])
            ->setToken('');

        try {
            $response = $request->execute($changepassword);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response ? $response->getPasswordChangeResult() : $response;
    }

    /**
     * @param $customer
     * @return Entity\PasswordResetResponse|ResponseInterface|string|null
     */
    public function forgotPassword($customer)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request        = new Operation\PasswordReset();
        $forgotPassword = new Entity\PasswordReset();
        // @codingStandardsIgnoreEnd

        $forgotPassword->setUserName($customer->getData('lsr_username'));
        $forgotPassword->setEmail('');

        try {
            $response = $request->execute($forgotPassword);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getPasswordResetResult() : $response;
    }

    /**
     * @param $customer
     * @param $customer_post
     * @return bool|Entity\PasswordChangeResponse|ResponseInterface|null
     */
    public function resetPassword($customer, $customer_post)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request       = new Operation\PasswordChange();
        $resetpassword = new Entity\PasswordChange();
        // @codingStandardsIgnoreEnd
        $request->setToken($customer->getData('lsr_token'));
        $resetpassword->setUserName($customer->getData('lsr_username'))
            ->setToken($customer->getData('lsr_resetcode'))
            ->setNewPassword($customer_post['password'])
            ->setOldPassword('');

        try {
            $response = $request->execute($resetpassword);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response ? $response->getPasswordChangeResult() : $response;
    }

    /**
     * Syncing updated customer information to central side
     *
     * @param $customer
     * @param null $customerAddress
     * @return Entity\ContactUpdateResponse|MemberContact|ResponseInterface|null
     * @throws InvalidEnumException
     */
    public function updateCustomerAccount($customer, $customerAddress = null)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request       = new Operation\ContactUpdate();
        $entity        = new Entity\ContactUpdate();
        $memberContact = new MemberContact();
        // @codingStandardsIgnoreEnd

        $request->setToken($customer->getData('lsr_token'));

        if (!empty($customer->getData('dob'))) {
            $dob = $this->date->date("Y-m-d\T00:00:00", strtotime($customer->getData('dob')));
            $memberContact->setBirthDay($dob);
        }
        $memberContact->setFirstName($customer->getFirstname())
            ->setGender($this->getGenderStringById($customer->getData('gender')))
            ->setLastName($customer->getLastname())
            ->setUserName($customer->getData('lsr_username'))
            ->setEmail($customer->getEmail())
            ->setMiddleName('  ')
            ->setId($customer->getData('lsr_id'))
            ->setCards($this->setCards($this->setCard($customer->getData('lsr_cardid'))));

        if ($customerAddress instanceof Address) {
            $memberContact->setAddresses($this->setAddresses($this->setAddress($customerAddress)));
        }

        $entity->setContact($memberContact);
        $response = $request->execute($entity);

        return $response ? $response->getResult() : $response;
    }

    /**
     * @param $id
     * @return string
     */
    public function getGenderStringById($id)
    {
        return ($id == 1) ? Entity\Enum\Gender::MALE :
            (($id == 2) ? Entity\Enum\Gender::FEMALE : Entity\Enum\Gender::UNKNOWN);
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
     * @param null $cardId
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
        if ($groupname == null || $groupname == '') {
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
     * Update basket after login, if oneListBasket is null then recreate it
     *
     * @param Entity\OneList $oneListBasket
     * @param $cardId
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateBasketAfterLogin($oneListBasket, $cardId)
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
                    $simProduct                    = $this->productRepository->get($simSku);
                    $optionsData                   = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                    $buyRequest['super_attribute'] = [];
                    foreach ($optionsData as $key => $option) {
                        $code                                = $option['attribute_code'];
                        $value                               = $simProduct->getData($code);
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
     * Process customer login
     * @param MemberContact $result
     * @param $credentials
     * @param $is_email
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */
    public function processCustomerLogin(MemberContact $result, $credentials, $is_email)
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
        $customer       = $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->loadByEmail($customer_email);
        $this->authentication($customer, $websiteId);
        $customer         = $this->setCustomerAttributesValues($result, $customer);
        $customerSecure   = $this->customerRegistry->retrieveSecureData($customer->getId());
        $validatePassword = $this->encryptorInterface->validateHash(
            $credentials['password'],
            $customerSecure->getPasswordHash()
        );
        if (!$validatePassword) {
            $passwordHash = $this->encryptorInterface->getHash($credentials['password'], true);
            $customerSecure->setRpToken(null);
            $customerSecure->setRpTokenCreatedAt(null);
            $customerSecure->setPasswordHash($passwordHash);
            $customer->setPasswordHash($passwordHash);
        }
        $this->customerResourceModel->save($customer);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);
        $this->basketHelper->unSetOneList();
        $this->basketHelper->unSetOneListCalculation();
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $customer->getData('lsr_token'));
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $customer->getData('lsr_id'));
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
        $this->customerSession->setCustomerAsLoggedIn($customer);
    }

    /**
     * @param $isEmail
     * @param $userNameOrEmail
     * @param $request
     * @param false $isAjax
     * @param false $isGraphQl
     * @return string
     * @throws LocalizedException
     */
    public function loginCustomerIfOmniServiceDown(
        $isEmail,
        $userNameOrEmail,
        $request,
        $isAjax = false,
        $isGraphQl = false
    ) {
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
                    $credentials             = json_decode($request->getContent(), true);
                    $credentials['username'] = $email;
                    $request->setContent(json_encode($credentials));
                } elseif ($isGraphQl == true) {
                    return $email;
                } else {
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
     * @param null $websiteId
     * @return DataObject[]
     * @throws LocalizedException
     */
    public function getAllCustomers($websiteId = null)
    {
        $collection = $this->customerCollection->create()
            ->addAttributeToSelect("*")
            ->addAttributeToFilter("lsr_id", ['null' => true])
            ->addAttributeToFilter("website_id", ['eq' => $websiteId])
            ->load();

        return $collection->getItems();
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

    /**
     * To sync customer details and address to LS Central
     * @param Customer $customer
     * @return bool
     */
    public function syncCustomerAndAddress(Customer $customer)
    {
        try {
            $userName = $customer->getData('lsr_username');
            if (empty($userName)) {
                do {
                    $userName = $this->generateRandomUsername();
                } while ($this->isUsernameExist($userName) ?
                    $this->isUsernameExistInLsCentral($userName) : false
                );
                $customer->setData('lsr_username', $userName);
            }
            //Incase if lsr_password not set due to some exception from LS Central/ Migrating the existing customer.
            // Setting username as password.
            if (empty($customer->getData('lsr_password'))) {
                $customer->setData('lsr_password', $this->encryptorInterface->encrypt($userName));
            }
            $contactUserName = $this->getCustomerByUsernameOrEmailFromLsCentral(
                $customer->getData('lsr_username'),
                Entity\Enum\ContactSearchType::USER_NAME
            );
            $contactEmail    = $this->getCustomerByUsernameOrEmailFromLsCentral(
                $customer->getEmail(),
                Entity\Enum\ContactSearchType::EMAIL
            );
            if (!empty($contactUserName) && !empty($contactEmail)) {
                $contact  = $contactUserName;
                $password = $this->encryptorInterface->decrypt($customer->getData('lsr_password'));
                if (!empty($password)) {
                    $customerPost['password'] = $password;
                    $resetCode                = $this->forgotPassword($customer->getEmail());
                    $customer->setData('lsr_resetcode', $resetCode);
                    $this->resetPassword($customer, $customerPost);
                    $customer->setData('lsr_resetcode', null);
                }
            } else {
                $contact = $this->contact($customer);
            }

            if (is_object($contact) && $contact->getId()) {
                if (!empty($contact->getLoggedOnToDevice())) {
                    $token = $contact->getLoggedOnToDevice()->getSecurityToken();
                    $customer->setData('lsr_token', $token);
                }
                $customer->setData('lsr_id', $contact->getId());
                $customer->setData('lsr_cardid', $contact->getCards()->getCard()[0]->getId());
                $customer->setData('lsr_password', null);
                if ($contact->getAccount()->getScheme()->getId()) {
                    $customerGroupId = $this->getCustomerGroupIdByName(
                        $contact->getAccount()->getScheme()->getId()
                    );
                    $customer->setGroupId($customerGroupId);
                }
                $this->customerResourceModel->save($customer);
                if (!empty($customer->getAddresses())) {
                    $customerAddress = [];
                    foreach ($customer->getAddresses() as $address) {
                        if ($this->isBillingAddress($address)) {
                            // We only saving one address for now
                            $this->updateCustomerAccount($customer, $customerAddress);
                        }
                    }
                }
                return true;
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return false;
    }

    /**
     * @param $paramValue
     * @param $type
     * @return MemberContact|null
     * @throws InvalidEnumException
     */
    public function getCustomerByUsernameOrEmailFromLsCentral($paramValue, $type)
    {
        $response = null;
        $contact  = null;

        // @codingStandardsIgnoreStart
        $request       = new Operation\ContactSearch();
        $contactSearch = new Entity\ContactSearch();
        $contactSearch->setSearchType($type);
        $contactSearch->setSearch($paramValue);
        $contactSearch->setMaxNumberOfRowsReturned(1);
        try {
            $response = $request->execute($contactSearch);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if (!empty($response) && !empty($response->getContactSearchResult())) {
            foreach ($response->getContactSearchResult() as $contact) {
                return $contact;
            }
        }

        return $contact;
    }

    /**
     * @param $password
     * @return string
     */
    public function encryptPassword($password)
    {
        return $this->encryptorInterface->encrypt($password);
    }

    /**
     * For validating email address is correct or not
     * @param $email
     * @return bool
     */
    public function isValid($email)
    {
        return $this->validateEmailAddress->isValid($email) && strlen($email) < 80;
    }

    /**
     * @param int $length
     * @return mixed|string
     * @throws LocalizedException
     */
    public function generateRandomUsername($length = 5)
    {
        $randomString = $this->lsr->getWebsiteConfig(
            LSR::SC_LOYALTY_CUSTOMER_USERNAME_PREFIX_PATH,
            $this->storeManager->getWebsite()->getWebsiteId()
        );
        for ($i = 0; $i < $length; $i++) {
            $randomString .= rand(0, 9);
        }
        return $randomString;
    }

    /**
     * @param MemberContact $contact
     * @param Customer $customer
     * @return mixed
     * @throws InputException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCustomerAttributesValues($contact, $customer)
    {
        $customer->setData('lsr_id', $contact->getId());
        if (!empty($contact->getBirthDay()) && $contact->getBirthDay() != '1753-01-01T00:00:00') {
            $customer->setData('dob', $this->date->date("Y-m-d", strtotime($contact->getBirthDay())));
        }
        if (!empty($contact->getGender())) {
            $genderValue = ($contact->getGender() == Entity\Enum\Gender::MALE) ? 1 : (($contact->getGender() == Entity\Enum\Gender::FEMALE) ? 2 : '');
            $customer->setData('gender', $genderValue);
        }
        if (!empty($contact->getUserName())) {
            $customer->setData('lsr_username', $contact->getUserName());
        }
        if (!empty($contact->getLoggedOnToDevice()) &&
            !empty($contact->getLoggedOnToDevice()->getSecurityToken())) {
            $token = $contact->getLoggedOnToDevice()->getSecurityToken();
            $customer->setData('lsr_token', $token);
        }
        if (!empty($contact->getCards()) &&
            !empty($contact->getCards()->getCard()[0]) &&
            !empty($contact->getCards()->getCard()[0]->getId())) {
            $customer->setData('lsr_cardid', $contact->getCards()->getCard()[0]->getId());
        }
        if (!empty($contact->getAccount()) &&
            !empty($contact->getAccount()->getScheme()) &&
            !empty($contact->getAccount()->getScheme()->getId())) {
            $customerGroupId = $this->getCustomerGroupIdByName(
                $contact->getAccount()->getScheme()->getId()
            );
            $customer->setGroupId($customerGroupId);
            $this->customerSession->setCustomerGroupId($customerGroupId);
        }
        return $customer;
    }

    /**
     * Update both basket and wishlist after login given login result
     *
     * @param $result
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function updateBasketAndWishlistAfterLogin($result)
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $this->stockHelper->validateQty($item->getQty(), $item, $quote, true);
        }

        $oneListBasket = $this->getOneListTypeObject(
            $result->getOneLists()->getOneList(),
            Entity\Enum\ListType::BASKET
        );
        /** Update Basket to Omni */
        $this->updateBasketAfterLogin(
            $oneListBasket,
            $result->getCards()->getCard()[0]->getId()
        );
        $oneListWish = $this->getOneListTypeObject(
            $result->getOneLists()->getOneList(),
            Entity\Enum\ListType::WISH
        );
        if ($oneListWish) {
            /** Update Wishlist to Omni */
            $this->updateWishlistAfterLogin(
                $oneListWish
            );
        }

        $this->setBasketUpdateChecking();
    }

    /**
     * Get customer by email
     * @param $email
     * @return mixed
     * @throws LocalizedException
     */
    public function getCustomerByEmail($email)
    {
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        return $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->loadByEmail($email);
    }

    /**
     * To authenticate user login
     * @param $customer
     * @param null $websiteId
     * @throws EmailNotConfirmedException
     * @throws UserLockedException
     */
    public function authentication($customer, $websiteId = null)
    {
        $customerId = $customer->getId();
        if ($this->authentication->isLocked($customerId)) {
            throw new UserLockedException(__('The account is locked.'));
        }
        if ($customer->getConfirmation() && $this->accountConfirmation->isConfirmationRequired(
                $websiteId,
                $customerId,
                $customer->getEmail()
            )) {
            throw new EmailNotConfirmedException(__("This account isn't confirmed. Verify and try again."));
        }
    }

    /**
     * Loading customer with all custom attributes given email and website_id
     *
     * @param $email
     * @param $websiteId
     * @return Customer
     * @throws LocalizedException
     */
    public function loadCustomerByEmailAndWebsiteId($email, $websiteId)
    {
        return $this->customerFactory->create()
            ->setWebsiteId($websiteId)
            ->loadByEmail($email);
    }

    /**
     * Validate if given customer address is a billing_address
     *
     * @param $customerAddress
     * @return bool
     */
    public function isBillingAddress($customerAddress)
    {
        $defaultBillingAddress = $customerAddress->getCustomer()->getDefaultBillingAddress();

        return $customerAddress->getData('is_default_billing') ||
            ($defaultBillingAddress && $defaultBillingAddress->getId() == $customerAddress->getId());
    }

    /**
     * Setting current card_id in customer session
     *
     * @param $cardId
     */
    public function setCardIdInCustomerSession($cardId)
    {
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $cardId);
    }

    /**
     * Setting current lsr_id in customer session
     *
     * @param $lsrId
     */
    public function setLsrIdInCustomerSession($lsrId)
    {
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $lsrId);
    }

    /**
     * Setting current security_token in customer session
     *
     * @param $token
     */
    public function setSecurityTokenInCustomerSession($token)
    {
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token);
    }

    /**
     * Getting current card_id in customer session
     */
    public function getCardIdFromCustomerSession()
    {
        return $this->customerSession->getData(LSR::SESSION_CUSTOMER_CARDID);
    }

    /**
     * Getting current lsr_id in customer session
     */
    public function getLsrIdFromCustomerSession()
    {
        return $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSRID);
    }

    /**
     * Getting current security_token in customer session
     */
    public function getSecurityTokenFromCustomerSession()
    {
        return $this->customerSession->getData(LSR::SESSION_CUSTOMER_SECURITYTOKEN);
    }

    /**
     * Clear current card_id in customer session
     */
    public function unsetCardIdFromCustomerSession()
    {
        return $this->customerSession->unsetData(LSR::SESSION_CUSTOMER_CARDID);
    }

    /**
     * Clear current lsr_id in customer session
     */
    public function unsetLsrIdFromCustomerSession()
    {
        return $this->customerSession->unsetData(LSR::SESSION_CUSTOMER_LSRID);
    }

    /**
     * Clear current security_token in customer session
     */
    public function unsetSecurityTokenFromCustomerSession()
    {
        return $this->customerSession->unsetData(LSR::SESSION_CUSTOMER_SECURITYTOKEN);
    }

    /**
     * Clear current customer_group_id in customer session
     */
    public function unsetCustomerGroupIdFromCustomerSession()
    {
        return $this->customerSession->unsetData(LSR::SESSION_GROUP_ID);
    }

    /**
     * Clear current customer_id in customer session
     */
    public function unsetCustomerIdFromCustomerSession()
    {
        return $this->customerSession->unsetData(LSR::SESSION_CUSTOMER_ID);
    }

    /**
     * Update basket data checking after login
     */
    public function setBasketUpdateChecking()
    {
        return $this->customerSession->setData('isBasketUpdate', 1);
    }

    /**
     * get basket data checking
     */
    public function getBasketUpdateChecking()
    {
        return $this->customerSession->getData('isBasketUpdate');
    }

    /**
     * Update basket data checking after login
     */
    public function unsetBasketUpdateChecking()
    {
        return $this->customerSession->unsetData('isBasketUpdate');
    }

    /**
     * Clear all required values from customer session
     */
    public function unSetRequiredDataFromCustomerSessions()
    {
        $this->unsetCardIdFromCustomerSession();
        $this->unsetLsrIdFromCustomerSession();
        $this->unsetSecurityTokenFromCustomerSession();
        $this->unsetCustomerGroupIdFromCustomerSession();
        $this->unsetCustomerIdFromCustomerSession();
    }
}
