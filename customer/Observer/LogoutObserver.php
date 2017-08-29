<?php
namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\TestFramework\Event\Magento;
use MagentoDevBox\Command\Pool\MagentoReset;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Customer\Model\LSR;

class LogoutObserver implements ObserverInterface
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

        $this->logger->debug('LOGOUT');
        $result = $this->contactHelper->logout();
        $this->logger->debug(var_export($result, true));
        $this->customerSession->destroy();

        return $this;

    }
}
