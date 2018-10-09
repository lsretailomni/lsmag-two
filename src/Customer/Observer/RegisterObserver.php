<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Core\Model\LSR;

class RegisterObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;
    /** @var \Magento\Framework\Api\FilterBuilder $filterBuilder */
    protected $filterBuilder;
    /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
    protected $searchCriteriaBuilder;
    /** @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository */
    protected $customerRepository;
    /** @var \Magento\Framework\Message\ManagerInterface $messageManager */
    protected $messageManager;
    /** @var \Magento\Framework\Registry $registry */
    protected $registry;
    /** @var \Psr\Log\LoggerInterface $logger */
    protected $logger;
    /** @var \Magento\Customer\Model\Session $customerSession */
    protected $customerSession;

    /**
     * RegisterObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session $customerSession
     */

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
        $this->contactHelper = $contactHelper;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        /** @var \Magento\Customer\Controller\Account\LoginPost\Interceptor $controller_action */
        $controller_action = $observer->getData('controller_action');
        $parameters = $controller_action->getRequest()->getParams();
        $session = $this->customerSession;

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $session->getCustomer();
        if ($customer->getId()) {
            $customer->setData('lsr_username', $parameters['lsr_username']);
            $customer->setData('password', $parameters['password']);

            /** @var Entity\MemberContact  $contact */
            $contact = $this->contactHelper->contact($customer);
            if (is_object($contact) && $contact->getId()) {
                $token = $contact->getLoggedOnToDevice()->getSecurityToken();
                /** @var Entity\Card $card */
                $card = $contact->getCard();
                $customer->setData('lsr_id', $contact->getId());
                $customer->setData('lsr_token', $token);
                $customer->setData('lsr_cardid', $card->getId());

                if($contact->getAccount()->getScheme()->getId()){
                    $customerGroupId      =   $this->contactHelper->getCustomerGroupIdByName($contact->getAccount()->getScheme()->getId());
                    $customer->setGroupId($customerGroupId);
                }

                // TODO use Repository instead of Model.
                $customer->save();
                $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $contact);
                $session->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token);
                $session->setData(LSR::SESSION_CUSTOMER_LSRID, $contact->getId());
                if (!is_null($card)) {
                    $session->setData(LSR::SESSION_CUSTOMER_CARDID, $card->getId());
                }
            }

            $loginResult = $this->contactHelper->login($customer->getData('lsr_username'), $parameters['password']);
            if ($loginResult == FALSE) {
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
