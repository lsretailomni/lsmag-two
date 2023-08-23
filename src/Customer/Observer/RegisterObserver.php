<?php

namespace Ls\Customer\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

/**
 * Observer responsible for customer registration
 */
class RegisterObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var Registry $registry */
    private $registry;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CustomerSession $customerSession */
    private $customerSession;

    /** @var \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel */
    private $customerResourceModel;

    /** @var LSR @var */
    private $lsr;

    /**
     * RegisterObserver constructor.
     * @param ContactHelper $contactHelper
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        Registry $registry,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel,
        LSR $LSR
    ) {
        $this->contactHelper         = $contactHelper;
        $this->registry              = $registry;
        $this->logger                = $logger;
        $this->customerSession       = $customerSession;
        $this->customerResourceModel = $customerResourceModel;
        $this->lsr                   = $LSR;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        try {
            $parameters = $observer->getRequest()->getParams();
            $session    = $this->customerSession;
            do {
                $parameters['lsr_username'] = $this->contactHelper->generateRandomUsername();
            } while ($this->contactHelper->isUsernameExist($parameters['lsr_username']) ||
            $this->lsr->isLSR($this->lsr->getCurrentStoreId()) ?
                $this->contactHelper->isUsernameExistInLsCentral($parameters['lsr_username']) : false
            );
            /** @var Customer $customer */
            $customer = $session->getCustomer();
            if (empty($customer->getId())) {
                $customer = $this->contactHelper->getCustomerByEmail($parameters['email']);
            }
            if ($customer->getId() && !empty($parameters['lsr_username']) && !empty($parameters['password'])) {
                $customer->setData('lsr_username', $parameters['lsr_username']);
                $customer->setData('password', $parameters['password']);
                if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                    /** @var Entity\MemberContact $contact */
                    $contact = $this->contactHelper->contact($customer);
                    if (is_object($contact) && $contact->getId()) {
                        $customer = $this->contactHelper->setCustomerAttributesValues($contact, $customer);
                        $this->customerResourceModel->save($customer);
                        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $contact);
                        $session->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $customer->getData('lsr_token'));
                        $session->setData(LSR::SESSION_CUSTOMER_LSRID, $customer->getData('lsr_id'));
                        $session->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
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
                        $this->contactHelper->updateBasketAndWishlistAfterLogin($loginResult);
                    }
                } else {
                    $customer->setData('lsr_password', $this->contactHelper->encryptPassword($parameters['password']));
                    $this->customerResourceModel->save($customer);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}
