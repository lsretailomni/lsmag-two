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
use Magento\Checkout\Model\Session as CheckoutSession;
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
use Magento\Customer\Model\Session as CustomerSession;
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
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
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

    /** @var CustomerSession */
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

    /** @var CheckoutSession */
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
     * @var SessionManagerInterface
     */
    public $session;

    /**
     * @var ManagerInterface
     */
    public $messageManager;

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
     * @param CustomerSession $customerSession
     * @param CountryFactory $countryFactory
     * @param CollectionFactory $customerGroupColl
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupInterfaceFactory $groupInterfaceFactory
     * @param BasketHelper $basketHelper
     * @param ItemHelper $itemHelper
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param CheckoutSession $checkoutSession
     * @param SessionManagerInterface $session
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
     * @param ManagerInterface $messageManager
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
        CustomerSession $customerSession,
        CountryFactory $countryFactory,
        CollectionFactory $customerGroupColl,
        GroupRepositoryInterface $groupRepository,
        GroupInterfaceFactory $groupInterfaceFactory,
        BasketHelper $basketHelper,
        ItemHelper $itemHelper,
        \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel,
        CheckoutSession $checkoutSession,
        SessionManagerInterface $session,
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
        StockHelper $stockHelper,
        ManagerInterface $messageManager
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
        $this->session               = $session;
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
        $this->messageManager        = $messageManager;
        parent::__construct(
            $context
        );
    }

    /**
     * Search in central with username or email
     *
     * @param array $param
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
            if (version_compare($this->lsr->getOmniVersion(), '2022.6.0', '>=')) {
                $request = new Operation\ContactGet();
                $search  = new Entity\ContactGet();
            } else {
                $request = new Operation\ContactSearch();
                $search  = new Entity\ContactSearch();
                $search->setMaxNumberOfRowsReturned(1);
            }
            // @codingStandardsIgnoreEnd
            $search->setSearch($param);
            // enabling this causes the segfault if ContactSearchType is in the classMap of the SoapClient
            $search->setSearchType(Entity\Enum\ContactSearchType::USER_NAME);
            try {
                $response = $request->execute($search);
                if ($response) {
                    $contact_pos = $response->getContactGetResult();
                }
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
     * For validating email address is correct or not
     *
     * @param string $email
     * @return bool
     */
    public function isValid($email)
    {
        return $this->validateEmailAddress->isValid($email) && strlen($email) < 80;
    }

    /**
     * Search by email
     *
     * @param string $email
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
            if (version_compare($this->lsr->getOmniVersion(), '2022.6.0', '>=')) {
                $request = new Operation\ContactGet();
                $search  = new Entity\ContactGet();
            } else {
                $request = new Operation\ContactSearch();
                $search  = new Entity\ContactSearch();
                $search->setMaxNumberOfRowsReturned(1);
            }
            // @codingStandardsIgnoreEnd
            $search->setSearch($email);

            // enabling this causes the segfault if ContactSearchType is in the classMap of the SoapClient
            $search->setSearchType(Entity\Enum\ContactSearchType::EMAIL);

            try {
                $response = $request->execute($search);
                if ($response) {
                    if (version_compare($this->lsr->getOmniVersion(), '2022.6.0', '>=')) {
                        $contact_pos = $response->getContactGetResult();
                    } else {
                        $contact_pos = $response->getContactSearchResult();
                    }
                }
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
     * Set lsr params in session
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->session->start();
        $this->session->setLsrParams($value);
    }

    /**
     * Customer login
     *
     * @param string $user
     * @param string $pass
     * @return Entity\LoginResponse|MemberContact|ResponseInterface|null
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
     * Check email exist in LS Central or not
     *
     * @param string $email
     * @return bool
     * @throws InvalidEnumException
     */
    public function isEmailExistInLsCentral($email)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        if (version_compare($this->lsr->getOmniVersion(), '2022.6.0', '>=')) {
            $request       = new Operation\ContactGet();
            $contactSearch = new Entity\ContactGet();
        } else {
            $request       = new Operation\ContactSearch();
            $contactSearch = new Entity\ContactSearch();
        }
        $contactSearch->setSearchType(Entity\Enum\ContactSearchType::EMAIL);
        $contactSearch->setSearch($email);
        try {
            $response = $request->execute($contactSearch);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if (version_compare($this->lsr->getOmniVersion(), '2022.6.0', '>=')) {
            if (!empty($response) && !empty($response->getContactGetResult())) {
                if ($response->getContactGetResult()->getEmail() === $email) {
                    return true;
                }
            }
        } else {
            if (!empty($response) && !empty($response->getContactSearchResult())) {
                foreach ($response->getContactSearchResult() as $contact) {
                    if ($contact->getEmail() === $email) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Function to change password
     *
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
     * Sync customer to Central after successful registration in magento.
     * If error response (other than timeout error) from Central it will block registration in magento.
     *
     * @param object $observer
     * @param object $session
     * @return $this
     * @throws \Zend_Log_Exception
     */
    public function syncCustomerToCentral($observer, $session)
    {
        $parameters = $observer->getRequest()->getParams();
        try {
            do {
                $parameters['lsr_username'] = $this->generateRandomUsername();
            } while ($this->isUsernameExist($parameters['lsr_username']) ||
            $this->lsr->isLSR($this->lsr->getCurrentStoreId()) ?
                $this->isUsernameExistInLsCentral($parameters['lsr_username']) : false
            );
            /** @var Customer $customer */
            $customer = $session->getCustomer();
            $request  = $observer->getControllerAction()->getRequest();
            $request->setPostValue('lsr_username', $parameters['lsr_username']);
            if (!empty($parameters['email']) && !empty($parameters['lsr_username'])
                && !empty($parameters['password'])
            ) {
                $customer->setData('lsr_username', $parameters['lsr_username']);
                $customer->setData('email', $parameters['email']);
                $customer->setData('password', $parameters['password']);
                $customer->setData('firstname', $parameters['firstname']);
                $customer->setData('lastname', $parameters['lastname']);
                $customer->setData('middlename', (array_key_exists(
                    'middlename',
                    $parameters
                ) && $parameters['middlename']) ? $parameters['middlename'] : null);
                $customer->setData(
                    'gender',
                    (array_key_exists('gender', $parameters) && $parameters['gender']) ? $parameters['gender'] : null
                );
                $customer->setData(
                    'dob',
                    (array_key_exists('dob', $parameters) && $parameters['dob']) ? $parameters['dob'] : null
                );
                if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                    /** @var Entity\MemberContact $contact */
                    $contact = $this->contact($customer);
                    if (is_object($contact) && $contact->getId()) {
                        $customer                   = $this->setCustomerAttributesValues($contact, $customer);
                        $parameters['lsr_id']       = $customer->getLsrId();
                        $parameters['lsr_username'] = $customer->getLsrUsername();
                        $parameters['lsr_token']    = $customer->getLsrToken();
                        $parameters['lsr_cardid']   = $customer->getLsrCardid();
                        $parameters['group_id']     = $customer->getGroupId();
                        $parameters['contact']      = $contact;
                    } else {
                        $this->_logger->info("Timeout error.");
                        $this->messageManager->addErrorMessage(
                            "Something went wrong during customer registration. Please try after sometime."
                        );

                    }
                }
            }
            $this->setValue($parameters);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $this;
    }

    /**
     * Function to generate random username
     *
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
     * Function to check if username exist
     *
     * @param string $username
     * @return bool
     * @throws LocalizedException
     */
    public function isUsernameExist($username)
    {
        // Creating search filter to apply for.
        $filters = [
            $this->filterBuilder
                ->setField('lsr_username')
                ->setConditionType('eq')
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
     *
     * @param string $username
     * @return bool
     * @throws InvalidEnumException
     */
    public function isUsernameExistInLsCentral($username)
    {
        if ($this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_USERNAME_API_CALL,
            $this->lsr->getCurrentStoreId()
        )) {
            $response = null;
            // @codingStandardsIgnoreStart
            if (version_compare($this->lsr->getOmniVersion(), '2022.6.0', '>=')) {
                $request       = new Operation\ContactGet();
                $contactSearch = new Entity\ContactGet();
            } else {
                $request       = new Operation\ContactSearch();
                $contactSearch = new Entity\ContactSearch();
            }
            $contactSearch->setSearchType(Entity\Enum\ContactSearchType::USER_NAME);
            $contactSearch->setSearch($username);
            try {
                $response = $request->execute($contactSearch);
            } catch (Exception $e) {
                $this->_logger->error($e->getMessage());
            }
            if (version_compare($this->lsr->getOmniVersion(), '2022.6.0', '>=')) {
                if (!empty($response) && !empty($response->getContactGetResult())) {
                    if ($response->getContactGetResult()->getUserName() === $username) {
                        return true;
                    }
                }
            } else {
                if (!empty($response) && !empty($response->getContactSearchResult())) {
                    foreach ($response->getContactSearchResult() as $contact) {
                        if ($contact->getUserName() === $username) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Sync customer to central
     *
     * @param Customer $customer
     * @return Entity\ContactCreateResponse|MemberContact|ResponseInterface|null
     * @throws InvalidEnumException
     */
    public function contact(Customer $customer)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $alternate_id  = 'LSM' . str_pad(sha1(rand(500, 600) . $customer->getId()), 8, '0', STR_PAD_LEFT);
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

        if (!empty($customer->getData('gender'))) {
            $genderValue = '';
            if ($customer->getData('gender') == 1) {
                $genderValue = Entity\Enum\Gender::MALE;
            } else {
                if ($customer->getData('gender') == 2) {
                    $genderValue = Entity\Enum\Gender::FEMALE;
                } else {
                    if ($customer->getData('gender') == 3) {
                        $genderValue = Entity\Enum\Gender::UNKNOWN;
                    }
                }
            }
            $contact->setGender($genderValue);
        }

        if (!empty($customer->getData('dob'))) {
            $dob = $this->date->date("Y-m-d\T00:00:00", $customer->getData('dob'));
            $contact->setBirthDay($dob);
        }

        $contactCreate->setContact($contact);
        try {
            $response = $request->execute($contactCreate);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getContactCreateResult() : $response;
    }

    /**
     * Set customer addresss
     *
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
     * Set address
     *
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
            $region = $customerAddress->getRegion() ? substr($customerAddress->getRegion(), 0, 30) : null;
            $address->setCity($customerAddress->getCity())
                ->setCountry($customerAddress->getCountryId())
                ->setPostCode($customerAddress->getPostcode())
                ->setPhoneNumber($customerAddress->getTelephone())
                ->setType(Entity\Enum\AddressType::RESIDENTIAL);
            $region ? $address->setCounty($region)
                : $address->setCounty('');
            return $address;
        } else {
            return null;
        }
    }

    /**
     * Return the Country name by Country Id
     *
     * Default Country Id = US
     *
     * @param string $countryName
     * @return mixed
     */
    public function getCountryId($countryName)
    {
        if ($countryName && strlen($countryName) == 2) {
            return $countryName;
        }

        $countryName       = $countryName ?? '';
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
     * Set customer attribute values
     *
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
        if (!is_array($contact)) {
            $customer->setData('lsr_id', $contact->getId());
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
        } else {
            $customer->setData('lsr_id', $contact['lsr_id']);
            if (!empty($contact['lsr_username'])) {
                $customer->setData('lsr_username', $contact['lsr_username']);
            }
            if (!empty($contact['lsr_token'])) {
                $customer->setData('lsr_token', $contact['lsr_token']);
            }
            if (!empty($contact['lsr_cardid'])) {
                $customer->setData('lsr_cardid', $contact['lsr_cardid']);
            }
            if (!empty($contact['group_id'])) {
                $customerGroupId = $contact['group_id'];
                $customer->setGroupId($customerGroupId);
                $this->customerSession->setCustomerGroupId($customerGroupId);
            }
        }

        return $customer;
    }

    /**
     * Get customer group id by name
     *
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
     * Get all schemes
     *
     * @return array
     */
    public function getSchemes()
    {
        $schemes = [];
        $schemesGetAll = new Entity\SchemesGetAll();
        $request = new Operation\SchemesGetAll();
        try {
            $response = $request->execute($schemesGetAll);
            foreach($response->getSchemesGetAllResult() as $scheme) {
                /** @var Entity\Scheme $scheme */
               $schemes[$scheme->getId()] = $scheme->getClub()->getId();
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $schemes;
    }

    /**
     *  Create new Customer group based on customer name.
     *
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
     * Get lsr session value
     *
     * @return mixed
     */
    public function getValue()
    {
        $this->session->start();
        return $this->session->getLsrParams();
    }

    /**
     * Unset lsr session value
     *
     * @return mixed
     */
    public function unSetValue()
    {
        $this->session->start();
        return $this->session->unsLsrParams();
    }

    /**
     * Get all customer group ids
     *
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
     * This function is overriding in OmniGraphQl module
     *
     * Process customer login
     *
     * @param MemberContact $result
     * @param array $credentials
     * @param string $is_email
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
        $customer         = $this->setCustomerAdditionalValues($result, $customer);
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
     * Crete customer in Magento
     *
     * @param MemberContact $contact
     * @param string $password
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
                ->setData('lastname', $contact->getLastName())
                ->setData('lsr_username', $contact->getUserName())
                ->setData('lsr_id', $contact->getId())
                ->setData('lsr_cardid', current($contact->getCards()->getCard())->getId());
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
                $regionName = $addressInfo->getCounty();
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
     * To authenticate user login
     *
     * @param object $customer
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
     * Saving additional customer values
     *
     * @param MemberContact $contact
     * @param Customer $customer
     * @return mixed
     * @throws InputException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCustomerAdditionalValues($contact, $customer)
    {
        if (!empty($contact->getBirthDay()) && $contact->getBirthDay() != '1753-01-01T00:00:00'
            && $contact->getBirthDay() != '1900-01-01T00:00:00') {
            $customer->setData('dob', $this->date->date("Y-m-d", strtotime($contact->getBirthDay())));
        }
        if (!empty($contact->getGender())) {
            $genderValue = '';
            if ($contact->getGender() == Entity\Enum\Gender::MALE) {
                $genderValue = 1;
            } else {
                if ($contact->getGender() == Entity\Enum\Gender::FEMALE) {
                    $genderValue = 2;
                } else {
                    if ($contact->getGender() == Entity\Enum\Gender::UNKNOWN) {
                        $genderValue = 3;
                    }
                }
            }
            $customer->setData('gender', $genderValue);
        }

        return $customer;
    }

    /**
     * Function to login customer if omni service down
     *
     * @param string $isEmail
     * @param string $userNameOrEmail
     * @param object $request
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
     * Search customer by email
     *
     * @param string $email
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
     *
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
     * To sync customer details and address to LS Central
     *
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
                    $resetCode                = $this->forgotPassword($userName);
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
     * Get customer by username or email from ls central
     *
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
        if (version_compare($this->lsr->getOmniVersion(), '2022.6.0', '>=')) {
            $request       = new Operation\ContactGet();
            $contactSearch = new Entity\ContactGet();
        } else {
            $request       = new Operation\ContactSearch();
            $contactSearch = new Entity\ContactSearch();
            $contactSearch->setMaxNumberOfRowsReturned(1);
        }
        $contactSearch->setSearchType($type);
        $contactSearch->setSearch($paramValue);
        try {
            $response = $request->execute($contactSearch);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        if (version_compare($this->lsr->getOmniVersion(), '2022.6.0', '>=')) {
            if (!empty($response) && !empty($response->getContactGetResult())) {
                return $response->getContactGetResult();
            }
        } else {
            if (!empty($response) && !empty($response->getContactSearchResult())) {
                foreach ($response->getContactSearchResult() as $contact) {
                    return $contact;
                }
            }
        }

        return $contact;
    }

    /**
     * Forgot password
     *
     * @param object $customer
     * @return Entity\PasswordResetResponse|ResponseInterface|string|null
     */
    public function forgotPassword($userName)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request        = new Operation\PasswordReset();
        $forgotPassword = new Entity\PasswordReset();
        // @codingStandardsIgnoreEnd

        $forgotPassword->setUserName($userName);
        $forgotPassword->setEmail('');

        try {
            $response = $request->execute($forgotPassword);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response ? $response->getPasswordResetResult() : $response;
    }

    /**
     * Reset password
     *
     * @param object $customer
     * @param object $customer_post
     * @return bool|Entity\PasswordChangeResponse|ResponseInterface|null
     */
    public function resetPassword($customer, $customer_post)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request       = new Operation\PasswordChange();
        $resetpassword = new Entity\PasswordChange();
        // @codingStandardsIgnoreEnd
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
     * Validate if given customer address is a billing_address
     *
     * @param object $customerAddress
     * @return bool
     */
    public function isBillingAddress($customerAddress)
    {
        $defaultBillingAddress = $customerAddress->getCustomer()->getDefaultBillingAddress();

        return $customerAddress->getData('is_default_billing') ||
            ($defaultBillingAddress && $defaultBillingAddress->getId() == $customerAddress->getId());
    }

    /**
     * Syncing updated customer information to central side
     *
     * @param object $customer
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
     * @param $id
     * @return string
     */
    public function getGenderStringById($id)
    {
        return ($id == 1) ? Entity\Enum\Gender::MALE :
            (($id == 2) ? Entity\Enum\Gender::FEMALE : Entity\Enum\Gender::UNKNOWN);
    }

    /**
     * Function to encrypt password
     *
     * @param string $password
     * @return string
     */
    public function encryptPassword($password)
    {
        return $this->encryptorInterface->encrypt($password);
    }

    /**
     * Update both basket and wishlist after login given login result
     *
     * @param object $result
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
     * Get one list type
     *
     * @param object $arrayOneLists
     * @param string $type
     * @return Entity\OneList|null
     * @throws NoSuchEntityException
     */
    public function getOneListTypeObject($arrayOneLists, $type)
    {
        if (is_array($arrayOneLists)) {
            /** @var Entity\OneList $oneList */
            foreach ($arrayOneLists as $oneList) {
                if ($oneList->getListType() == $type &&
                    $oneList->getStoreId() == $this->basketHelper->getDefaultWebStore()
                ) {
                    return $oneList;
                }
            }
        }
        return null;
    }

    /**
     * Update basket after login, if oneListBasket is null then recreate it
     *
     * @param Entity\OneList $oneListBasket
     * @param string $cardId
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
     * Update wishlist after login
     *
     * @param Entity\OneList $oneListWishlist
     * @return void
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
                $buyRequest = [];
                $sku        = $item->getItemId();
                $product    = $this->itemHelper->getProductByIdentificationAttributes($sku);

                if ($product) {
                    $qty               = $item->getQuantity();
                    $buyRequest['qty'] = $qty;
                    if ($item->getVariantId()) {
                        $simProduct = $this->itemHelper->getProductByIdentificationAttributes(
                            $item->getItemId(),
                            $item->getVariantId()
                        );

                        if ($simProduct) {
                            $optionsData                   = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                            $buyRequest['super_attribute'] = [];
                            foreach ($optionsData as $key => $option) {
                                $code                                = $option['attribute_code'];
                                $value                               = $simProduct->getData($code);
                                $buyRequest['super_attribute'][$key] = $value;
                            }
                        }
                    }
                    $result = $wishlist->addNewItem($product, $buyRequest);
                    $this->wishlistResourceModel->save($wishlist);
                    $this->_eventManager->dispatch(
                        'wishlist_add_product',
                        ['wishlist' => $wishlist, 'product' => $product, 'item' => $result]
                    );
                }
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
     * Function to remove wishlist
     *
     * @param object $wishlist
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
     * Update basket data checking after login
     */
    public function setBasketUpdateChecking()
    {
        return $this->customerSession->setData('isBasketUpdate', 1);
    }

    /**
     * Get customer by email
     *
     * @param string $email
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
     * Loading customer with all custom attributes given email and website_id
     *
     * @param string $email
     * @param int $websiteId
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
     * Setting current card_id in customer session
     *
     * @param string $cardId
     */
    public function setCardIdInCustomerSession($cardId)
    {
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $cardId);
    }

    /**
     * Setting current lsr_id in customer session
     *
     * @param int $lsrId
     */
    public function setLsrIdInCustomerSession($lsrId)
    {
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_LSRID, $lsrId);
    }

    /**
     * Setting current security_token in customer session
     *
     * @param string $token
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
     * Get basket data checking
     */
    public function getBasketUpdateChecking()
    {
        return $this->customerSession->getData('isBasketUpdate');
    }

    /**
     * Unset basket data checking
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
}
