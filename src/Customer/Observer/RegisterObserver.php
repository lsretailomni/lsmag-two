<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Ls\Omni\Helper\ContactHelper;
use Ls\Omni\Client\Ecommerce\Entity;
use Ls\Core\Model\LSR;

/**
 * Class RegisterObserver
 * @package Ls\Customer\Observer
 */
class RegisterObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $customerRepository;

    /** @var \Magento\Framework\Registry $registry */
    private $registry;

    /** @var \Psr\Log\LoggerInterface $logger */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy $customerSession */
    private $customerSession;

    /**
     * RegisterObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     */
    public function __construct(
        ContactHelper $contactHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession
    ) {
        $this->contactHelper = $contactHelper;
        $this->customerRepository = $customerRepository;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $controller_action = $observer->getData('controller_action');
            $parameters = $controller_action->getRequest()->getParams();
            $session = $this->customerSession;

            /** @var \Magento\Customer\Model\Customer $customer */
            $customer = $session->getCustomer();
            if ($customer->getId()) {
                $customer->setData('lsr_username', $parameters['lsr_username']);
                $customer->setData('password', $parameters['password']);
                /** @var Entity\MemberContact $contact */
                $contact = $this->contactHelper->contact($customer);
                if (is_object($contact) && $contact->getId()) {
                    $token = $contact->getLoggedOnToDevice()->getSecurityToken();
                    /** @var Entity\Card $card */
                    $card = $contact->getCard();
                    $customer->setData('lsr_id', $contact->getId());
                    $customer->setData('lsr_token', $token);
                    $customer->setData('lsr_cardid', $card->getId());

                    if ($contact->getAccount()->getScheme()->getId()) {
                        $customerGroupId = $this->contactHelper->getCustomerGroupIdByName(
                            $contact->getAccount()->getScheme()->getId()
                        );
                        $customer->setGroupId($customerGroupId);
                    }
                    $this->customerRepository->save($customer->getDataModel());
                    $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $contact);
                    $session->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token);
                    $session->setData(LSR::SESSION_CUSTOMER_LSRID, $contact->getId());
                    if ($card !== null) {
                        $session->setData(LSR::SESSION_CUSTOMER_CARDID, $card->getId());
                    }
                }

                $loginResult = $this->contactHelper->login($customer->getData('lsr_username'), $parameters['password']);
                if ($loginResult == false) {
                    $this->logger->error('Invalid Omni login or Omni password');
                    return $this;
                } else {
                    $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
                    $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $loginResult);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $this;
    }
}
