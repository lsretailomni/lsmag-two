<?php
namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\TestFramework\Event\Magento;
use MagentoDevBox\Command\Pool\MagentoReset;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Customer\Model\LSR;

class RegisterObserver implements ObserverInterface
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

        /** @var \Magento\Customer\Controller\Account\LoginPost\Interceptor $controller_action */
        $controller_action = $observer->getData( 'controller_action' );
        $parameters = $controller_action->getRequest()->getParams();
        $session = $this->customerSession;

        /** @var \Magento\Customer\Model\Customer  $customer*/
        $customer = $session->getCustomer();

        # only continue when validation succeeded and customer was created
        if ($customer->getId()) {
            $customer->setData('lsr_username',  $parameters['lsr_username']);
            $customer->save();
            $customer->setData('password', $parameters['password']);

            # create OMNI contact
            $contact = $this->contactHelper->contact($customer);

            if (is_object($contact) && $contact->getId()) {
                $token = $contact->getDevice()
                    ->getSecurityToken();

                $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $contact);

                $session->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token);
                $session->setData(LSR::SESSION_CUSTOMER_LSRID, $contact->getId());
                /** @var Entity\Card $card */
                $card = $contact->getCard();
                if (!is_null($card)) {
                    $session->setData(LSR::SESSION_CUSTOMER_CARDID, $card->getId());
                }
            }

            $loginResult = $this->contactHelper->login($customer->getData('lsr_username'), $parameters['password']);

            if ($loginResult == FALSE) {
                //$session->addError( 'Invalid Omni login or Omni password' );
                $this->logger->error('Invalid Omni login or Omni password');

                return $this;
            } else {
                $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
                $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $loginResult);
            }
        }

        return $this;

    }
}
