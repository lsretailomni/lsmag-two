<?php
declare(strict_types=1);

namespace Ls\Omni\Helper;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity;
use \Ls\Omni\Client\CentralEcommerce\Entity\ContactCreateParameters;
use \Ls\Omni\Client\Ecommerce\Entity\Enum\ListType;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetMemberCard;
use \Ls\Omni\Client\CentralEcommerce\Entity\GetMemberCardResult;
use \Ls\Omni\Client\CentralEcommerce\Entity\MemberContactCreateResult as MemberContactCreateResponse;
use \Ls\Omni\Client\CentralEcommerce\Entity\MemberPasswordChange;
use \Ls\Omni\Client\CentralEcommerce\Entity\MemberPasswordChangeResult as MemberPasswordChangeResponse;
use \Ls\Omni\Client\CentralEcommerce\Entity\MemberPasswordReset;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootMemberContactCreate;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootMemberLogon;
use \Ls\Omni\Client\CentralEcommerce\Operation;
use \Ls\Omni\Client\CentralEcommerce\Operation\GetMemberContactInfo_GetMemberContactInfo;
use \Ls\Omni\Client\CentralEcommerce\Operation\MemberContactCreate;
use \Ls\Omni\Client\CentralEcommerce\Operation\MemberContactUpdate;
use \Ls\Omni\Client\CentralEcommerce\Operation\MemberLogon;
use \Ls\Omni\Client\ResponseInterface;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Exception\NavException;
use \Ls\Omni\Exception\NavObjectReferenceNotAnInstanceException;
use Magento\Catalog\Model\AbstractModel;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\ResourceModel\Group\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\ExpiredException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Phrase;
use Zend_Log_Exception;

/**
 * Helper functions for member contact
 */
class ContactHelper extends AbstractHelperOmni
{
    /**
     * Search in central with username or email
     *
     * @param string $param
     * @return false|Entity\GetMemberContactInfo_GetMemberContactInfo|mixed|void|null
     */
    public function searchWithUsernameOrEmail(string $param)
    {
        // @codingStandardsIgnoreStart
        if (!$this->isValid($param)) {
            // LS Central only accept [a-zA-Z0-9-_@.] pattern of UserName
            if (!preg_match("/^[a-zA-Z0-9-_@.]*$/", $param)) {
                return null;
            }

            return $this->getCentralCustomerByUsername($param);
        } else {
            return $this->getCentralCustomerByEmail($param);
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
     * @return Entity\GetMemberContactInfo_GetMemberContactInfo
     * @throws LocalizedException
     */
    public function search($email)
    {
        $response = null;
        $isEmail = $this->isValid($email);
        // load customer data from magento customer database based on lsr_username if we didn't get an email
        if (!$isEmail) {
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
                    $isEmail = true;
                    $email    = $customer->getData('email');
                }
            }
        }

        if ($isEmail) {
            $response = $this->getCentralCustomerByEmail($email);
        }

        return $response;
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
     * RootMemberLogon
     *
     * @param string $user
     * @param string $pass
     * @return RootMemberLogon|NavException|NavObjectReferenceNotAnInstanceException|AbstractModel|DataObject|string|null
     */
    public function login(string $user, string $pass)
    {
        // LS Central only accept [a-zA-Z0-9-_@.] pattern of UserName
        if (!preg_match("/^[a-zA-Z0-9-_@.]*$/", $user)) {
            return null;
        }

        $response = null;
        $memberLogon = $this->createInstance(MemberLogon::class);
        $memberLogon->setOperationInput(
            [
                'loginID' => $user,
                'password' => $pass,
                'totalRemainingPoints' => 0
            ]
        );

        try {
            $response = $memberLogon->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response && $response->getResponsecode() == "0000" ? $response->getMemberlogonxml() : null;
    }

    /**
     * Get customer from central based on given search type
     *
     * @param $searchStr
     * @param int $searchType
     * @return false|Entity\GetMemberContactInfo_GetMemberContactInfo|mixed|null
     */
    public function getCentralCustomerBasedOnSearchType($searchStr, int $searchType)
    {
        $response = null;
        $contactSearchOperation = $this->createInstance(GetMemberContactInfo_GetMemberContactInfo::class);
        $contactSearchOperation->setOperationInput([
            'contactSearchType' => $searchType,
            'searchText' => $searchStr,
            'searchMethod' => 0,
            'maxResultContacts' => 0,
        ]);
        try {
            $response = $contactSearchOperation->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response &&
        $response->getResponseCode() == "0000" &&
        !empty(current($response->getRecords())->getLscMemberContact()) ?
            current($response->getRecords()) : null;
    }

    /**
     * Get given member card
     *
     * @param string $cardId
     * @return GetMemberCardResult
     */
    public function getGivenMemberCard(string $cardId)
    {
        $operation = $this->createInstance(Operation\GetMemberCard::class);
        $operation->setOperationInput(
            [
                GetMemberCard::CARD_NO => $cardId,
                GetMemberCard::TOTAL_REMAINING_POINTS => 0
            ]
        );
        $response = null;

        try {
            $response = $operation->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response && $response->getResponseCode() == "0000" ? $response : null;
    }

    /**
     * Check username exist in LS Central or not
     *
     * @param string $username
     * @return bool
     * @throws InvalidEnumException|NoSuchEntityException
     */
    public function isUsernameExistInLsCentral($username)
    {
        if ($this->lsr->getStoreConfig(
            LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_USERNAME_API_CALL,
            $this->lsr->getCurrentStoreId()
        )) {
            $response = $this->getCentralCustomerByUsername($username);

            return $response &&
                $response->getLscMemberLoginCard() &&
                $response->getLscMemberLoginCard()->getLoginId() == $username;
        }

        return false;
    }

    /**
     * Check email exist in LS Central or not
     *
     * @param string $email
     * @return bool
     * @throws InvalidEnumException
     */
    public function isEmailExistInLsCentral(string $email)
    {
        $response = $this->getCentralCustomerByEmail($email);

        return $response &&
            $response->getLscMemberContact() &&
            $response->getLscMemberContact()->getEmail() == $email;
    }

    /**
     * Get customer from central based on email
     *
     * @param string $email
     * @return false|Entity\GetMemberContactInfo_GetMemberContactInfo|mixed|null
     */
    public function getCentralCustomerByEmail(string $email)
    {
        return $this->getCentralCustomerBasedOnSearchType($email, 3);
    }

    /**
     * Get customer from central based on username
     *
     * @param string $username
     * @return false|Entity\GetMemberContactInfo_GetMemberContactInfo|mixed|null
     */
    public function getCentralCustomerByUsername(string $username)
    {
        return $this->getCentralCustomerBasedOnSearchType($username, 5);
    }

    /**
     * Get customer from central based on cardId
     *
     * @param $cardId
     * @return false|Entity\GetMemberContactInfo_GetMemberContactInfo|mixed|null
     */
    public function getCentralCustomerByCardId($cardId)
    {
        return $this->getCentralCustomerBasedOnSearchType($cardId, 0);
    }

    /**
     * Function to change password
     *
     * @param Customer $customer
     * @param array $customerPost
     * @return MemberPasswordChangeResponse|NavException|NavObjectReferenceNotAnInstanceException|null
     */
    public function changePassword(Customer $customer, array $customerPost)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $memberPasswordChangeOperation = $this->createInstance(Operation\MemberPasswordChange::class);
        $memberPasswordChangeOperation->setOperationInput([
            MemberPasswordChange::LOGIN_ID => $customer->getLsrUsername(),
            MemberPasswordChange::OLD_PASSWORD => $customerPost['current_password'],
            MemberPasswordChange::NEW_PASSWORD => $customerPost['password']
        ]);
        // @codingStandardsIgnoreEnd

        try {
            $response = $memberPasswordChangeOperation->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response && $response->getResponsecode() == "0000" ? $response : null;
    }

    /**
     * Sync customer to Central after successful registration in magento.
     *
     * If error response (other than timeout error) from Central it will block registration in magento.
     *
     * @param object $observer
     * @param object $session
     * @return $this
     * @throws Zend_Log_Exception|GuzzleException
     */
    public function syncCustomerToCentral($observer, $session)
    {
        $parameters = $observer->getRequest()->getParams();
        try {
            do {
                $parameters['lsr_username'] = $this->generateRandomUsername();
            } while ($this->isUsernameExist($parameters['lsr_username']) ||
                ($this->lsr->isLSR(
                    $this->lsr->getCurrentStoreId(),
                    false,
                    $this->lsr->getCustomerIntegrationOnFrontend()
                ) &&
                $this->isUsernameExistInLsCentral($parameters['lsr_username'])
            ));
            /** @var Customer $customer */
            $customer = $session->getCustomer();
            $request = $observer->getControllerAction()->getRequest();
            $request->setPostValue('lsr_username', $parameters['lsr_username']);
            if (!empty($parameters['email']) && !empty($parameters['lsr_username'])
                && !empty($parameters['password'])
            ) {
                $customer->setData('lsr_username', $parameters['lsr_username']);
                $customer->setData('email', $parameters['email']);
                $customer->setData('password', $parameters['password']);
                $customer->setData('firstname', $parameters['firstname']);
                $customer->setData('lastname', $parameters['lastname']);
                $customer->setData(
                    'middlename',
                    (array_key_exists('middlename', $parameters)
                        && $parameters['middlename']) ?
                        $parameters['middlename'] : null
                );
                $customer->setData(
                    'gender',
                    (array_key_exists('gender', $parameters) && $parameters['gender']) ? $parameters['gender'] : null
                );
                $customer->setData(
                    'dob',
                    (array_key_exists('dob', $parameters) && $parameters['dob']) ? $parameters['dob'] : null
                );
                if ($this->lsr->isLSR(
                    $this->lsr->getCurrentStoreId(),
                    false,
                    $this->lsr->getCustomerIntegrationOnFrontend()
                )) {
                    /** @var Entity\MemberContact $contact */
                    $contact = $this->contact($customer);
                    if ($contact && is_object($contact) && $contact->getContactid()) {
                        $customer = $this->setCustomerAttributesValues($contact, $customer);
                        $parameters['lsr_id'] = $customer->getLsrId();
                        $parameters['lsr_account_id'] = $customer->getLsrAccountId();
                        $parameters['lsr_username'] = $customer->getLsrUsername();
                        $parameters['lsr_token'] = $customer->getLsrToken();
                        $parameters['lsr_cardid'] = $customer->getLsrCardid();
                        $parameters['group_id'] = $customer->getGroupId();
                        $parameters['contact'] = $this->flattenModel($contact);
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
     * Sync customer to central
     *
     * @param Customer $customer
     * @return MemberContactCreateResponse|NavException|NavObjectReferenceNotAnInstanceException|AbstractModel|DataObject|string
     * @throws NoSuchEntityException
     */
    public function contact(Customer $customer)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $alternate_id = 'LSM' . str_pad(sha1(rand(500, 600) . $customer->getId()), 8, '0', STR_PAD_LEFT);

        if (!empty($customer->getData('lsr_password'))) {
            $lsrPassword = $this->encryptorInterface->decrypt($customer->getData('lsr_password'));
        } else {
            $lsrPassword = null;
        }
        $password = (!empty($lsrPassword)) ? $lsrPassword : $customer->getData('password');

        if (empty($password)) {
            $masterPassword = $this->lsr->getStoreConfig(LSR::SC_MASTER_PASSWORD, $this->lsr->getCurrentStoreId());
            $password = $masterPassword;
        }
        $contactCreateParameters = [
            ContactCreateParameters::EMAIL => $customer->getData('email'),
            ContactCreateParameters::FIRST_NAME => $customer->getData('firstname'),
            ContactCreateParameters::LAST_NAME => $customer->getData('lastname'),
            ContactCreateParameters::EXTERNAL_ID => $alternate_id,
            ContactCreateParameters::LOGIN_ID => $customer->getData('lsr_username'),
            ContactCreateParameters::PASSWORD => $password,
            ContactCreateParameters::GENDER => 0,
            ContactCreateParameters::DEVICE_ID => 'WEB-' . $customer->getData('lsr_username'),
        ];

        if (!empty($customer->getData('gender'))) {
            $genderValue = $customer->getData('gender');
            $contactCreateParameters[ContactCreateParameters::GENDER] = $genderValue;
        }

        if (!empty($customer->getData('dob'))) {
            $dob = $this->dateTime->date("Y-m-d\T00:00:00", $customer->getData('dob'));
            $contactCreateParameters[ContactCreateParameters::DATE_OF_BIRTH] = $dob;
        }

        $memberContactUpdateOperation = $this->createInstance(MemberContactCreate::class);
        $rootMemberContactCreate = $memberContactUpdateOperation->createInstance(
            RootMemberContactCreate::class,
            ['data' => [
                'ContactCreateParameters' =>
                    $memberContactUpdateOperation->createInstance(
                        ContactCreateParameters::class,
                        ['data' => $contactCreateParameters]
                    ),
            ]]
        );
        $memberContactCreateXml = $memberContactUpdateOperation->createInstance(
            Entity\MemberContactCreate::class,
            ['data' => [
                'memberContactCreateXML' => $rootMemberContactCreate,
                'totalRemainingPoints' => 0
            ]]
        );
        $memberContactUpdateOperation->setRequest($memberContactCreateXml);

        try {
            $response = $memberContactUpdateOperation->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response && !empty($response->getResponsecode() == "0000") ? $response : null;
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
     * @param mixed $contact
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
            if ($contact instanceof Entity\GetMemberContactInfo_GetMemberContactInfo) {
                $customerGroupId = $this->getCustomerGroupIdByName(
                    $contact->getLscMemberAccount()->getSchemeCode()
                );
                $customer->setGroupId($customerGroupId);
                $this->customerSession->setCustomerGroupId($customerGroupId);
                $customer->setData('lsr_id', $contact->getLscMemberContact()->getContactNo());
                $customer->setData('lsr_account_id', $contact->getLscMemberContact()->getAccountNo());
                $customer->setData('lsr_cardid', $contact->getLscMemberLoginCard()->getCardNo());
            } else {
                $customerGroupId = $this->getCustomerGroupIdByName(
                    $contact->getSchemeid()
                );
                $customer->setGroupId($customerGroupId);
                $this->customerSession->setCustomerGroupId($customerGroupId);
                $customer->setData('lsr_id', $contact->getContactid());
                $customer->setData('lsr_account_id', $contact->getAccountid());
                $customer->setData('lsr_cardid', $contact->getCardid());
            }
        } else {
            if (!empty($contact['lsr_id'])) {
                $customer->setData('lsr_id', $contact['lsr_id']);
            }
            if (!empty($contact['lsr_account_id'])) {
                $customer->setData('lsr_account_id', $contact['lsr_account_id']);
            }
            if (!empty($contact['lsr_username'])) {
                $customer->setData('lsr_username', $contact['lsr_username']);
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
     * @throws NoSuchEntityException
     */
    public function getSchemes()
    {
        return $this->loyaltyHelper->getSchemes();
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
     * @param Entity\GetMemberContactInfo_GetMemberContactInfo $result
     * @param array $credentials
     * @param string $is_email
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException
     */
    public function processCustomerLogin($result, $credentials, $is_email)
    {
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);
        $filters = [
            $this->filterBuilder
                ->setField('email')
                ->setConditionType('eq')
                ->setValue($result->getLscMemberContact()->getEmail())
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

        if (isset($credentials['password'])) {
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
        }
        $this->customerResourceModel->save($customer);
        $this->customerRegistry->remove($customer->getId());
        $this->basketHelper->unSetOneList();
        $this->basketHelper->unSetOneListCalculation();
        $this->setLsrAccountIdInCustomerSession($customer->getData('lsr_account_id'));
        $this->setLsrIdInCustomerSession($customer->getData('lsr_id'));
        $this->setCardIdInCustomerSession($customer->getData('lsr_cardid'));
        $this->customerSession->setCustomerAsLoggedIn($customer);
    }

    /**
     * Crete customer in Magento
     *
     * @param Entity\GetMemberContactInfo_GetMemberContactInfo $contact
     * @param string $password
     * @return Customer
     * @throws Exception
     * @throws LocalizedException
     */
    public function createNewCustomerAgainstProvidedInformation($contact, $password)
    {
        $memberContact = $contact->getLscMemberContact();
        // Create Customer to Magento
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $customer = $this->customerFactory->create();
        try {
            $customer->setPassword($password)
                ->setData('website_id', $websiteId)
                ->setData('email', $memberContact->getEmail())
                ->setData('firstname', $memberContact->getFirstName())
                ->setData('lastname', $memberContact->getSurname())
                ->setData('lsr_username', $contact->getLscMemberLoginCard()->getLoginId())
                ->setData('lsr_id', $memberContact->getContactNo())
                ->setData('lsr_cardid', $contact->getLscMemberLoginCard()->getCardNo())
                ->setData('lsr_account_id', $memberContact->getAccountNo());
            $this->customerResourceModel->save($customer);
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        // Save Address
        if (!empty($memberContact->getCountryRegionCode())) {
            $address = $this->addressFactory->create();
            $address->setCustomerId($customer->getId())
                ->setFirstname($memberContact->getFirstName())
                ->setLastname($memberContact->getSurname())
                ->setCountryId($this->getCountryId($memberContact->getCountryRegionCode()))
                ->setPostcode($memberContact->getPostCode())
                ->setCity($memberContact->getCity())
                ->setTelephone($memberContact->getPhoneno())
                ->setStreet([$memberContact->getAddress(), $memberContact->getAddress2()])
                ->setIsDefaultBilling('1')
                ->setIsDefaultShipping('1');
            $regionName = $memberContact->getCounty();
            if (!empty($regionName)) {
                $regionDataFactory = $this->regionFactory->create();
                $address->setRegion($regionDataFactory->setRegion($regionName));
                $regionFactory = $this->region->create();
                $regionId = $regionFactory->loadByName($regionName, $memberContact->getCountry());
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
     * @param mixed $contact
     * @param Customer $customer
     * @return mixed
     * @throws InputException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCustomerAdditionalValues($contact, $customer)
    {
        if ($contact instanceof Entity\GetMemberContactInfo_GetMemberContactInfo) {
            $dob = $contact->getLscMemberContact()->getDateOfBirth();

            if (!empty($dob) && $dob != '0001-01-01') {
                $customer->setData('dob', $this->dateTime->date("Y-m-d", strtotime($dob)));
            }

            $gender = $contact->getLscMemberContact()->getContactGender();

            if (!empty($gender)) {
                $customer->setData('gender', $gender);
            }
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
                } while ($this->isUsernameExist($userName) && $this->isUsernameExistInLsCentral($userName));
                $customer->setData('lsr_username', $userName);
            }
            $contactEmail = $this->searchWithUsernameOrEmail($customer->getEmail());
            if ($contactEmail && !empty($contactEmail->getLscMemberLoginCard())) {
                $card = !is_array($contactEmail->getLscMembershipCard()) ?
                    $contactEmail->getLscMembershipCard() : current($contactEmail->getLscMembershipCard());
                $contact = [
                    'lsr_username' => $contactEmail->getLscMemberLoginCard()->getLoginId(),
                    'lsr_cardid' => $card->getCardNo(),
                    'lsr_id' => $card->getContactNo(),
                    'lsr_account_id' => $contactEmail->getLscMemberContact()->getAccountNo(),
                    'group_id' => $card->getClubCode()
                ];
                $password = $this->encryptorInterface->decrypt($customer->getData('lsr_password'));
                if (!empty($password)) {
                    $customerPost['password'] = $password;
                    $resetCode = $this->forgotPassword($contactEmail->getLscMemberLoginCard()->getLoginId());
                    $customer->setData('lsr_resetcode', $resetCode);
                    $customer->setData('lsr_username', $contactEmail->getLscMemberLoginCard()->getLoginId());
                    $this->resetPassword($customer, $customerPost);
                    $customer->setData('lsr_resetcode', null);
                }
            } else {
                $contact = $this->contact($customer);
            }

            if ($contact) {
                $customer = $this->setCustomerAttributesValues($contact, $customer);
                $this->customerResourceModel->save($customer);
                return true;
            }
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return false;
    }

    /**
     * Forgot password
     *
     * @param string $userName
     * @return string|null
     */
    public function forgotPassword($userName)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $operation = $this->createInstance(Operation\MemberPasswordReset::class);
        $operation->setOperationInput([
            MemberPasswordReset::LOGIN_ID => $userName
        ]);
        // @codingStandardsIgnoreEnd

        try {
            $response = $operation->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $response && $response->getResponsecode() == "0000" ? $response->getToken() : null;
    }

    /**
     * Reset password
     *
     * @param Customer $customer
     * @param array $customerPost
     * @return MemberPasswordChangeResponse|NavException|NavObjectReferenceNotAnInstanceException|string
     */
    public function resetPassword($customer, $customerPost)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $memberPasswordChangeOperation = $this->createInstance(Operation\MemberPasswordChange::class);
        $memberPasswordChangeOperation->setOperationInput([
            MemberPasswordChange::LOGIN_ID => $customer->getLsrUsername(),
            MemberPasswordChange::OLD_PASSWORD => '',
            MemberPasswordChange::NEW_PASSWORD => $customerPost['password'],
            MemberPasswordChange::TOKEN => $customer->getData('lsr_resetcode')
        ]);
        // @codingStandardsIgnoreEnd

        try {
            $response = $memberPasswordChangeOperation->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response && $response->getResponsecode() == "0000" ? $response : null;
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
     * @param Customer $customer
     * @param $customerAddress
     * @return NavException|NavObjectReferenceNotAnInstanceException|AbstractModel|DataObject|string|null
     */
    public function updateCustomerAccount($customer, $customerAddress = null)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $memberContactUpdateOperation = $this->createInstance(MemberContactUpdate::class);
        $contactCreateParameters = [
            ContactCreateParameters::CONTACT_ID => $customer->getData('lsr_id'),
            ContactCreateParameters::ACCOUNT_ID => $customer->getData('lsr_account_id'),
            ContactCreateParameters::EMAIL => $customer->getData('email'),
            ContactCreateParameters::FIRST_NAME => $customer->getData('firstname'),
            ContactCreateParameters::LAST_NAME => $customer->getData('lastname'),
            ContactCreateParameters::LOGIN_ID => $customer->getData('lsr_username'),
            ContactCreateParameters::COUNTRY => $customerAddress->getData('country_id'),
            ContactCreateParameters::CITY => $customerAddress->getData('city'),
            ContactCreateParameters::PHONE => $customerAddress->getData('telephone'),
            ContactCreateParameters::POST_CODE => $customerAddress->getData('postcode'),
            ContactCreateParameters::ADDRESS1 => $customerAddress->getStreetLine(1),
            ContactCreateParameters::ADDRESS2 => $customerAddress->getStreetLine(2),
        ];

        if (!empty($customer->getData('gender'))) {
            $contactCreateParameters[ContactCreateParameters::GENDER] = $customer->getData('gender');
        }


        if (!empty($customer->getData('dob'))) {
            $dob = $this->dateTime->date("Y-m-d", $customer->getData('dob'));
            $contactCreateParameters[ContactCreateParameters::DATE_OF_BIRTH] = $dob;
        }

        $rootMemberContactCreate = $memberContactUpdateOperation->createInstance(
            RootMemberContactCreate::class,
            ['data' => [
                'ContactCreateParameters' =>
                    $memberContactUpdateOperation->createInstance(
                        ContactCreateParameters::class,
                        ['data' => $contactCreateParameters]
                    ),
            ]]
        );
        $memberContactCreateXml = $memberContactUpdateOperation->createInstance(
            Entity\MemberContactUpdate::class,
            ['data' => [
                'memberContactUpdateXML' => $rootMemberContactCreate
            ]]
        );
        $memberContactUpdateOperation->setRequest($memberContactCreateXml);

        try {
            $response = $memberContactUpdateOperation->execute();
        } catch (Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        // @codingStandardsIgnoreEnd

        return $response && $response->getResponsecode() == "0000" ? $response : null;
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
     * @throws GuzzleException
     */
    public function updateBasketAndWishlistAfterLogin($result)
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();
        foreach ($items as $item) {
            $this->stockHelper->validateQty($item->getQty(), $item, $quote, true);
        }

        $cardNo = $result->getLscMembershipCard()->getCardNo();
        /** Update Basket to Omni */
        $this->updateBasketAfterLogin(
            null,
            $cardNo
        );
        /** Update Wishlist to Omni */
        $this->eventManager->dispatch('controller_action_postdispatch_wishlist_index_update');
        $this->setBasketUpdateChecking();
    }

    /**
     * Get one list type
     *
     * @param object $arrayOneLists
     * @param string $type
     * @return \Ls\Omni\Client\Ecommerce\Entity\OneList|null
     * @throws NoSuchEntityException
     */
    public function getOneListTypeObject($arrayOneLists, $type)
    {
        if (is_array($arrayOneLists)) {
            /** @var \Ls\Omni\Client\Ecommerce\Entity\OneList $oneList */
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
     * @param mixed $oneListBasket
     * @param string $cardId
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function updateBasketAfterLogin($oneListBasket, $cardId)
    {
        $quote = $this->checkoutSession->getQuote();

        if (!empty($quote->getAllItems())) {
            // get the onelist or if not exist then create new one with empty data of customer.
            $oneList = $this->basketHelper->get();
            $oneList = $this->basketHelper->setOneListQuote($quote, $oneList);
            $this->basketHelper->update($oneList);
            $quote = $this->basketHelper->getCurrentQuote();
            $this->itemHelper->setDiscountedPricesForItems(
                $quote,
                $this->basketHelper->getBasketSessionValue()
            );
        }
    }

    /**
     * Update wishlist after login
     *
     * @param \Ls\Omni\Client\Ecommerce\Entity\OneList $oneListWishlist
     * @return void
     */
    public function updateWishlistAfterLogin(\Ls\Omni\Client\Ecommerce\Entity\OneList $oneListWishlist)
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
                $oneListWishlist instanceof \Ls\Omni\Client\Ecommerce\Entity\OneList) {
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
     * Setting current account_id in customer session
     *
     * @param string $accountId
     */
    public function setLsrAccountIdInCustomerSession($accountId)
    {
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_LSR_ACCOUNT_ID, $accountId);
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
     * Getting current account_id in customer session
     */
    public function getLsrAccountIdFromCustomerSession()
    {
        return $this->customerSession->getData(LSR::SESSION_CUSTOMER_LSR_ACCOUNT_ID);
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

    /**
     * Execute the OneListGetByCardId request
     *
     * @param $cardId
     * @return \Ls\Omni\Client\Ecommerce\Entity\OneListGetByCardIdResponse|ResponseInterface
     * @throws InvalidEnumException
     */
    public function getOneListGetByCardId($cardId)
    {
        $request = new \Ls\Omni\Client\Ecommerce\Operation\OneListGetByCardId();
        // @codingStandardsIgnoreLine
        $entity = new \Ls\Omni\Client\Ecommerce\Entity\OneListGetByCardId();
        $entity->setCardId($cardId);
        $entity->setListType(ListType::WISH);
        $entity->setIncludeLines(true);
        try {
            $response = $request->execute($entity);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_logger->error($e->getMessage());
        }

        return $response;
    }
}
