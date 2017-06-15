<?php
namespace Ls\Omni\Helper;

use Ls\Omni\Service\Soap\Client;
use Sabre\Xml\Service;
use Sabre\Xml\Serializer;
use Ls\Omni\Service\Service as OmniService;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client as OmniClient;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Omni\Model\LSR;

class ContactHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $logger;
    protected $filterBuilder;
    protected $searchCriteriaBuilder;
    protected $storeManager;
    protected $customerRepository;
    protected $customerFactory;
    protected $customerSession;
    protected $_ns = NULL;
    const SERVICE_TYPE = 'ecommerce';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->logger = $logger;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        parent::__construct(
            $context
        );
    }


    /**
     * @param string $email
     *
     * @return \Ls\Omni\Client\Ecommerce\Entity\ContactPOS|null
     */
    public function search($email)
    {

        $is_email = \Zend_Validate::is($email, \Zend_Validate_EmailAddress::class);

        $this->logger->debug("$email is $is_email");

        // load lsr_username from magento customer database if we didn't get an email
        if (!$is_email) {
            // https://magento.stackexchange.com/questions/165647/how-to-load-customer-by-attribute-in-magento2
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
                    $is_email = TRUE;
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
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }

        } else {
            // we cannot search by username in Omni as the API does not offer this information. So we quit.
            return NULL;
        }

        if ($contact_pos instanceof Entity\ArrayOfContactPOS && count($contact_pos->getContactPOS()) > 0) {
            return $contact_pos->getContactPOS();
        } elseif ($contact_pos instanceof Entity\ContactPOS) {
            return $contact_pos;
        } else {
            return NULL;
        }
    }

    /**
     * @param string $user
     * @param string $pass
     *
     * @return bool| Entity\Contact | null
     */
    public function login($user, $pass)
    {

        $response = NULL;
        $request = new Operation\LoginWeb();
        $login = new Entity\LoginWeb();
        $login->setUserName($user)
            ->setPassword($pass);

        try {
            $response = $request->execute($login);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $response ? $response->getLoginWebResult() : $response;
    }

    public function logout()
    {

        $customer = $this->customerSession->getCustomer();
        $response = NULL;
        $request = new Operation\Logout();
        $logout = new Entity\Logout();
        $logout->setUserName($customer->getData('lsr_username'))
            ->setDeviceId(NULL);

        try {
            $response = $request->execute($logout);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $response ? $response->getLogoutResult() : $response;
    }

    /**
     * @param Entity\Contact|Entity\ContactPOS $contact
     *
     * @return \Magento\Customer\Model\Customer
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
        //$customer->sendNewAccountEmail();

        return $customer;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return false|Entity\Contact|Entity\ContactCreateResponse
     */
    public function contact(\Magento\Customer\Model\Customer $customer)
    {

        $this->logger->debug(var_export($customer->getData(), true));

        $response = NULL;
        $alternate_id = 'LSM' . str_pad(md5(rand(500, 600) . $customer->getId()), 8, '0', STR_PAD_LEFT);
        $request = new Operation\ContactCreate();
        $contactCreate = new Entity\ContactCreate();
        $contact = new Entity\Contact();
        $card = new Entity\Card();
        $card->setId('dumb-move');
        $contact->setAlternateId($alternate_id)
            ->setCard($card)
            ->setEmail($customer->getData('email'))
            ->setFirstName($customer->getData('firstname'))
            ->setLastName($customer->getData('lastname'))
            ->setMiddleName($customer->getData('middlename') ? $customer->getData('middlename') : NULL)
            ->setPassword($customer->getData('password'))
            ->setUserName($customer->getData('lsr_username'));

        $contactCreate->setContact($contact);

        $this->logger->debug(var_export($contactCreate, true));

        try {
            $response = $request->execute($contactCreate);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $response ? $response->getContactCreateResult() : $response;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return false|Entity\Contact|Entity\ContactCreateResponse
     */
    public function contactOld(\Magento\Customer\Model\Customer $customer)
    {

        /*
         * xmlns:ecom="http://lsretail.com/LSOmniService/2016/04/eCommerceService"
         * xmlns:lsom="http://schemas.datacontract.org/2004/07/LSOmni.Contracts.Data.Loyalty"
         * xmlns:ns="http://lsretail.com/LSOmniService/2014/10"
         */
        $ecomm = 'http://lsretail.com/LSOmniService/2016/04/eCommerceService';
        $soapenv = 'http://schemas.xmlsoap.org/soap/envelope/';
        $ns = $this->_ns = 'http://lsretail.com/LSOmniService/2014/10';
        $xsi = "http://www.w3.org/2001/XMLSchema-instance";
        $lsom = "http://schemas.datacontract.org/2004/07/LSOmni.Contracts.Data.Loyalty";

        $service = new Service();
        $service->namespaceMap = array(
            $soapenv => 'soapenv',
            $ecomm => 'ecommerce',
            $ns => 'omni',
            $xsi => 'xsi',
            $lsom => 'loyalty',
        );

        $parse_profile = array();
        $profiles_combined = array();

        //$getProfiles = $this->profile( $customer->getData( 'lsr_profile' ) );
        $getProfiles = NULL;

        if (is_array($getProfiles)) {

            $profiles_combined = array_reduce($getProfiles, function ($carry, $profile) use ($parse_profile) {

                $profiles = array();
                !is_array($profile->getProfile()) or $profiles = $profile->getProfile();
                $profile = reset($profiles);

                if (is_object($profile)) {
                    $parse_profile = array(
                        '{' . $this->_ns . '}' . 'Profile' => array(
                            '{' . $this->_ns . '}' . 'ContactValue' => $profile->ContactValue,
                            '{' . $this->_ns . '}' . 'DataType' => $profile->DataType,
                            '{' . $this->_ns . '}' . 'DefaultValue' => $profile->DefaultValue,
                            '{' . $this->_ns . '}' . 'Description' => $profile->Description,
                            '{' . $this->_ns . '}' . 'Id' => $profile->Id,
                            '{' . $this->_ns . '}' . 'Mandatory' => $profile->Mandatory,
                        )
                    );
                    array_push($carry, $parse_profile);
                }

                return $carry;
            }, array());
        }

        /** @noinspection PhpIncludeInspection */
        //include_once LSR::path( Mage::getBaseDir( 'lib' ), 'Sabre', 'Xml', 'Serializer', 'functions.php' );
        $alternate_id = 'LSM' . str_pad(md5(rand(500, 600) . $customer->getId()), 8, '0', STR_PAD_LEFT);
        $xml = $service->write('{' . $soapenv . '}' . 'Envelope',
            array('{' . $soapenv . '}' . 'Header' => NULL,
                '{' . $soapenv . '}' . 'Body' => array(
                    '{' . $ecomm . '}' . 'ContactCreate' => array(
                        '{' . $ecomm . '}' . 'contact' => array(
                            '{' . $ns . '}' . 'Account' => NULL,
                            '{' . $ns . '}' . 'Addresses' => NULL,
                            '{' . $ns . '}' . 'AlternateId' => $alternate_id,
                            '{' . $ns . '}' . 'Card' => array(
                                '{' . $ns . '}' . 'Id' => 'dumb-move'
                            ),
                            '{' . $ns . '}' . 'Coupons' => NULL,
                            '{' . $ns . '}' . 'Device' => NULL,
                            '{' . $ns . '}' . 'Email' => $customer->getData('email'),
                            '{' . $ns . '}' . 'Environment' => NULL,
                            '{' . $ns . '}'
                            . 'FirstName' => $customer->getData('firstname'),
                            '{' . $ns . '}' . 'LastName' => $customer->getData('lastname'),
                            '{' . $ns . '}'
                            . 'MiddleName' => !empty($customer->getData('middlename')) ?
                                $customer->getData('middlename') : NULL,
                            '{' . $ns . '}' . 'Offers' => NULL,
                            '{' . $ns . '}' . 'Password' => $customer->getData('password'),
                            '{' . $ns . '}' . 'Profiles' => $profiles_combined,
                            '{' . $ns . '}'
                            . 'UserName' => $customer->getData('lsr_username'),
                        )
                    )
                )
            ));
        $this->logger->debug($xml);
        $soap_request = new \SoapVar($xml, XSD_ANYXML);

        $service_type = new ServiceType(self::SERVICE_TYPE);
        $url = OmniService::getUrl($service_type);
        /** @var \Ls\Omni\Service\Service $omni_service_client */
        $omni_service_client = new OmniClient($url, $service_type);
        $response = $omni_service_client->getSoapClient()
            ->ContactCreate($soap_request);

        $this->logger->debug(var_export($response, true));

        return $response ? $response->getContactCreateResult() : $response;
    }
}
