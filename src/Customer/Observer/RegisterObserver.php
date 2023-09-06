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
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('im here in postdispatch');
        try {
            $parameters = $observer->getRequest()->getParams();
            $logger->info(print_r($this->contactHelper->getValue(),1));
            $additionalParams = $this->contactHelper->getValue();
            $session    = $this->customerSession;
//            do {
//                $additionalParams['lsr_username'] = $this->contactHelper->generateRandomUsername();
//            } while ($this->contactHelper->isUsernameExist($additionalParams['lsr_username']) ||
//            $this->lsr->isLSR($this->lsr->getCurrentStoreId()) ?
//                $this->contactHelper->isUsernameExistInLsCentral($additionalParams['lsr_username']) : false
//            );
            /** @var Customer $customer */
            $customer = $session->getCustomer();
            if (empty($customer->getId())) {
                $logger->info('im here in customer get Id '.$customer->getId());
                $logger->info('im here in customer email '.$additionalParams['email']);
                $customer = $this->contactHelper->getCustomerByEmail($additionalParams['email']);
            }
            $logger->info('im here in customer get Id 2'.$customer->getId());
            if ($customer->getId() && !empty($additionalParams['lsr_username']) && !empty($additionalParams['password'])) {
                $customer->setData('lsr_username', $additionalParams['lsr_username']);
                $logger->info('im here in lsr_username '.$additionalParams['lsr_username']);
                $customer->setData('password', $additionalParams['password']);
                if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                    /** @var Entity\MemberContact $contact */
                    //$contact = $this->contactHelper->contact($customer);
                    if (is_array($additionalParams) && $additionalParams['lsr_id']) {
//                        $customer = $this->contactHelper->setCustomerAttributesValues($contact, $customer);
                        $customer = $this->contactHelper->setCustomerAttributesValues($additionalParams, $customer);
                        $this->customerResourceModel->save($customer);
                        $logger->info('im here in customer save');
                        $contact = $additionalParams['contact'];
                        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $contact);
                        $session->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $customer->getData('lsr_token'));
                        $session->setData(LSR::SESSION_CUSTOMER_LSRID, $customer->getData('lsr_id'));
                        $session->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getData('lsr_cardid'));
                    }
                    $logger->info('im here in customer lsr_username '.$customer->getData('lsr_username'));
                    $loginResult = $this->contactHelper->login(
                        $customer->getData('lsr_username'),
                        $additionalParams['password']
                    );
                    if ($loginResult == false) {
                        $logger->info('im here in login failed');
                        $this->logger->error('Invalid Omni login or Omni password');
                        return $this;
                    } else {
                        $logger->info('im here in logged in');
                        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
                        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $loginResult);
                        $this->contactHelper->updateBasketAndWishlistAfterLogin($loginResult);
                    }
                } else {
                    $customer->setData('lsr_password', $this->contactHelper->encryptPassword($additionalParams['password']));
                    $this->customerResourceModel->save($customer);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}
