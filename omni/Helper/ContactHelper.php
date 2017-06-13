<?php
namespace Ls\Omni\Helper;

use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Omni\Client\Ecommerce\Operation;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class ContactHelper extends AbstractHelper
{
    protected $logger;
    protected $filterBuilder;
    protected $searchCriteriaBuilder;
    protected $customerRepository;

    public function __construct (
        Context $context,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->logger = $context->getLogger();
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
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
        if ( !$is_email ) {
            // https://magento.stackexchange.com/questions/165647/how-to-load-customer-by-attribute-in-magento2
            $filters = [ $this->filterBuilder
                             ->setField( 'lsr_username' )
                             ->setConditionType( 'like' )
                             ->setValue( $email )
                             ->create() ];
            $this->searchCriteriaBuilder->addFilters( $filters );
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchResults = $this->customerRepository->getList( $searchCriteria );

            if ( $searchResults->getTotalCount() == 1 ) {
                /** @var \Magento\Customer\Model\Customer $customer */
                $customer = $searchResults->getItems()[ 0 ];
                if ( $customer->getId() ) {
                    $is_email = TRUE;
                    $email = $customer->getData( 'email' );
                }
            }
        }

        if ( $is_email ) {
            /** @var Operation\ContactGetById $request */
            $request = new Operation\ContactSearch();
            /** @var Entity\ContactSearch $search */
            $search = new Entity\ContactSearch();
            $search->setSearch( $email );
            $search->setMaxNumberOfRowsReturned( 1 );

            // enabling this causes the segfault if ContactSearchType is in the classMap of the SoapClient
            $search->setSearchType( Entity\Enum\ContactSearchType::EMAIL );

            try {
                $response = $request->execute( $search );

                $contact_pos = $response->getContactSearchResult();
            } catch ( Exception $e ) {
                $this->logger->error( $e->getMessage() );
            }

        } else {
            // we cannot search by username in Omni as the API does not offer this information. So we quit.
            return NULL;
        }

        if ( $contact_pos instanceof Entity\ArrayOfContactPOS && count( $contact_pos->getContactPOS() ) > 0 ) {
            return $contact_pos->getContactPOS();
        } elseif ( $contact_pos instanceof Entity\ContactPOS ) {
            return $contact_pos;
        } else {
            return NULL;
        }
    }
}
