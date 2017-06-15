<?php
namespace Ls\Omni\Helper;

use Ls\Omni\Service\Soap\Client;
use Sabre\Xml\Service;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;

class ContactHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $logger;
    protected $filterBuilder;
    protected $searchCriteriaBuilder;
    protected $storeManager;
    protected $customerRepository;
    protected $customerFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory

    ) {
        $this->logger = $logger;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        parent::__construct(
            $context
        );
    }


    /**
     * @param string $email
     *
     * @return \Ls\Omni\Client\Ecommerce\Entity\ContactPOS|null
     */
    public function search ( $email ) {

        $is_email = \Zend_Validate::is( $email, \Zend_Validate_EmailAddress::class );

        $this->logger->debug( "$email is $is_email" );

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

        if ( $is_email ) {
            /** @var Operation\ContactGetById $request */
            $request = new Operation\ContactSearch();
            /** @var Entity\ContactSearch $search */
            $search = new Entity\ContactSearch();
            $search->setSearch($email);
            $search->setMaxNumberOfRowsReturned( 1 );

            // enabling this causes the segfault if ContactSearchType is in the classMap of the SoapClient
            $search->setSearchType(Entity\Enum\ContactSearchType::EMAIL);

            try {
                $response = $request->execute($search);

                $contact_pos = $response->getContactSearchResult();
            } catch ( Exception $e ) {
                $this->logger->error( $e->getMessage() );
            }

        } else {
            // we cannot search by username in Omni as the API does not offer this information. So we quit.
            return NULL;
        }

        if ($contact_pos instanceof Entity\ArrayOfContactPOS && count( $contact_pos->getContactPOS() ) > 0) {
            return  $contact_pos->getContactPOS();
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
     * @return bool| \Ls\Omni\Client\Ecommerce\Entity\Contact | null
     */
    public function login ( $user, $pass ) {

        $response = NULL;
        $request = new Operation\LoginWeb();
        $login = new Entity\LoginWeb();
        $login->setUserName($user)
            ->setPassword($pass);

        try {
            $response = $request->execute($login);
        } catch ( Exception $e ) {
            $this->logger->error( $e->getMessage() );
        }

        return $response ? $response->getLoginWebResult() : $response;
    }

    /**
     * @param \Ls\Omni\Client\Ecommerce\Entity\Contact|\Ls\Omni\Client\Ecommerce\Entity\ContactPOS $contact
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function customer ( $contact , $password) {

        $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();

        $customer   = $this->customerFactory->create();
        $customer->setPassword( $password )
            ->setData( 'website_id', $websiteId )
            ->setData( 'email', $contact->getEmail() )
            ->setData( 'lsr_id', $contact->getId() )
            ->setData( 'lsr_username', $contact->getUserName() )
            ->setData( 'firstname', $contact->getFirstName() )
            ->setData( 'lastname', $contact->getLastName() );

        $customer->save();
        //$customer->sendNewAccountEmail();

        return $customer;
    }
}
