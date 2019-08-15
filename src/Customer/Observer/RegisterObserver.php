<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Core\Model\LSR;

/**
 * Class RegisterObserver
 * @package Ls\Customer\Observer
 */
class RegisterObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var \Magento\Framework\Registry $registry */
    private $registry;

    /** @var \Psr\Log\LoggerInterface $logger */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy $customerSession */
    private $customerSession;

    /** @var \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel */
    private $customerResourceModel;

    /** @var \Ls\Core\Model\LSR @var  */
    private $lsr;

    /**
     * RegisterObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel,
        LSR $LSR
    ) {
        $this->contactHelper = $contactHelper;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->customerResourceModel = $customerResourceModel;
        $this->lsr  =   $LSR;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
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
                        $this->customerResourceModel->save($customer);
                        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $contact);
                        $session->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token);
                        $session->setData(LSR::SESSION_CUSTOMER_LSRID, $contact->getId());
                        if ($card !== null) {
                            $session->setData(LSR::SESSION_CUSTOMER_CARDID, $card->getId());
                        }
                    }

                    $loginResult = $this->contactHelper->login(
                        $customer->getData('lsr_username'),
                        $parameters['password']
                    );
                    if ($loginResult == false) {
                        $this->logger->error('Invalid Omni login or Omni password');
                        return $this;
                    } else {
                        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
                        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $loginResult);
                        $this->contactHelper->updateWishlistAfterLogin(
                            $loginResult->getWishList()
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }
}
