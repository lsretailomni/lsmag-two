<?php
namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\TestFramework\Event\Magento;
use MagentoDevBox\Command\Pool\MagentoReset;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Client\Ecommerce\Entity;

class LoginObserver implements ObserverInterface
{
    private $contactHelper;
    protected $filterBuilder;
    protected $searchCriteriaBuilder;
    protected $customerRepository;
    protected $messageManager;
    protected $registry;
    protected $logger;
    protected $customerSession;

    public function __construct(
        ContactHelper $contactHelper,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        //Observer initialization code...
        //You can use dependency injection to get any class this observer may need.
        $this->contactHelper = $contactHelper;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        #if ( LSR::isLSR() ) { // TODO: implement a isLSR function?

        /** @var Magento\Customer\Controller\Account\LoginPost\Interceptor $controller_action */
        $controller_action = $observer->getData( 'controller_action' );

        $login = $controller_action->getRequest()->getPost( 'login' );
        $email = $username = $login[ 'username' ];

        $is_email = Zend_Validate::is( $username, Zend_Validate_EmailAddress::class );

        // CASE FOR EMAIL LOGIN := TRANSLATION TO USERNAME
        if ( $is_email ) {
            // we trigger an api call only if the supplied username is an email
            $search = $this->contactHelper->search( $username );
            $found = !is_null( $search )
                && ( $search instanceof Entity\ContactPOS )
                && !empty( $search->getEmail() );

            if ( !$found ) {
                // TODO: message not shown yet?
                $this->messageManager->addErrorMessage( 'There are no members with that email' );

                return $this;
            }
            $email = $search->getEmail();
        }

        if ( $is_email ) {
            $filters = [$this->filterBuilder
                    ->setField('lsr_username')
                    ->setConditionType('like')
                    ->setValue($email)
                    ->create()];
            $this->searchCriteriaBuilder->addFilters($filters);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->customerRepository->getList($searchCriteria);

            if ( $searchResults->getTotalCount() == 0 ) {
                // TODO: message not shown yet?
                $this->messageManager->addErrorMessage('Unfortunately email login is only available for members registered in Magento' );

                return $this;
            }
//            else {
//                // TODO: get the username from the data
//                $username = $customer->getData( 'lsr_username' );
//            }
        }

        // MORE OR LESS WORKING UNTIL HERE
        // below this, you need to adapt the old mag1 code to mag2 code once we are able to actually create users in Magento2

        // TRY TO LOGIN
        $this->logger->debug('LOGIN');
        $result = $this->contactHelper->login( $username, $login[ 'password' ] );
        $this->logger->debug('LOGIN AFTER');

        if ( $result == FALSE ) {
            //$this->customerSession->addError( 'Invalid Omni login or Omni password' );
            $this->logger->debug('Invalid Omni login or Omni password');
            return $this;
        }

        if ( $result instanceof Entity\Contact ) {

            $obj_manager = \Magento\Framework\App\ObjectManager::getInstance();

            $filters = [$this->filterBuilder
                ->setField('email')
                ->setConditionType('eq')
                ->setValue($result->getEmail())
                ->create()];
            $this->searchCriteriaBuilder->addFilters($filters);
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->customerRepository->getList($searchCriteria);

            $customer = NULL;

            if ( $searchResults->getTotalCount() == 0 ) {
                $customer = $this->contactHelper->customer( $result , $login[ 'password' ] );
            }else{
                foreach($searchResults->getItems() as $match){
                    $customer = $this->customerRepository->getById($match->getId());
                    break;
                }
            }

            $customer_email = $customer->getEmail();
            $this->logger->debug($customer->getId());
            $this->logger->debug($customer->getEmail());

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $url = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $url->get('\Magento\Store\Model\StoreManagerInterface');
            $websiteId = $storeManager->getWebsite()->getWebsiteId();
            // Get Store ID
            $store = $storeManager->getStore();
            $storeId = $store->getStoreId();
            $customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory');
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer=$customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($customer_email);// load customer by email address
            if ( is_null( $customer->getData( 'lsr_id' ) ) ) {
                $customer->setData( 'lsr_id', $result->getId() );
            }
            if ( !$is_email && empty( $customer->getData( 'lsr_username' ) ) ) {
                $customer->setData( 'lsr_username', $username );
            }
            $token = $result->getDevice()
                ->getSecurityToken();

            // save the OneList as the cart
            // TODO: merge with maybe existing cart

            // load OneLists from $result
            /** @var Entity\ArrayOfOneList $oneListArray */
            $oneListArray = $result->getOneList()->getOneList();
            // filter for basket OneLists
            $basketOneLists = array_filter($oneListArray, function($oneList) { return $oneList->getListType() == 'Basket';});
            if (count($basketOneLists) > 1) {
                $this->logger->debug("Multiple OneLists with type basket for customer.");
            } else {
                // TODO: OMNI-3410 Synchronize OneList with Apps
            }

            // THIS IS FOR LATER IF THE CONFIG DATA FOR ORDER CREATION IS NOT INLINE
            $customer->setData( 'lsr_token', $token );
            $customer->save();

            $this->registry->register(  \Ls\Customer\Model\LSR::REGISTRY_LOYALTY_LOGINRESULT, $result );
            $this->customerSession->setData(   \Ls\Customer\Model\LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token );
            $this->customerSession->setData(   \Ls\Customer\Model\LSR::SESSION_CUSTOMER_LSRID, $result->getId() );

            /** @var LSR_Omni_Model_Omni_Domain_Card $card */
            $card = $result->getCard();
            if ( $card instanceof Entity\Card && !is_null( $card->getId() ) ) {
                $this->customerSession->setData(  \Ls\Customer\Model\LSR::SESSION_CUSTOMER_CARDID, $card->getId() );
            }

            $this->customerSession->setCustomerAsLoggedIn( $customer );
        } else {
            $this->customerSession->addError( 'The service is currently unavailable. Please try again later.' );
        }
        #}

        return $this;

    }
}
