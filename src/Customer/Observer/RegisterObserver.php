<?php

namespace Ls\Customer\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

/**
 * Class RegisterObserver
 * @package Ls\Customer\Observer
 */
class RegisterObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var Registry $registry */
    private $registry;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Proxy $customerSession */
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
     * @param Proxy $customerSession
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        Registry $registry,
        LoggerInterface $logger,
        Proxy $customerSession,
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
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR()) {
            try {
                $controller_action = $observer->getData('controller_action');
                $parameters        = $controller_action->getRequest()->getParams();
                $session           = $this->customerSession;

                /** @var Customer $customer */
                $customer = $session->getCustomer();
                if ($customer->getId()) {
                    $customer->setData('lsr_username', $parameters['lsr_username']);
                    $customer->setData('password', $parameters['password']);
                    /** @var Entity\MemberContact $contact */
                    $contact = $this->contactHelper->contact($customer);
                    if (is_object($contact) && $contact->getId()) {
                        $token = $contact->getLoggedOnToDevice()->getSecurityToken();
                        $customer->setData('lsr_id', $contact->getId());
                        $customer->setData('lsr_token', $token);
                        $customer->setData('lsr_cardid', $contact->getCards()->getCard()[0]->getId());
                        if ($contact->getAccount()->getScheme()->getId()) {
                            $customerGroupId = $this->contactHelper->getCustomerGroupIdByName(
                                $contact->getAccount()->getScheme()->getId()
                            );
                            $customer->setGroupId($customerGroupId);
                        }
                        $this->customerResourceModel->save($customer);
                        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $contact);
                        $session->setData(LSR::SESSION_CUSTOMER_SECURITYTOKEN, $token);
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
                        $oneListBasket = $this->contactHelper->getOneListTypeObject(
                            $loginResult->getOneLists()->getOneList(),
                            Entity\Enum\ListType::BASKET
                        );
                        $this->contactHelper->updateBasketAfterLogin(
                            $oneListBasket,
                            $customer->getData('lsr_id'),
                            $customer->getData('lsr_cardid')
                        );
                        $oneListWish = $this->contactHelper->getOneListTypeObject(
                            $loginResult->getOneLists()->getOneList(),
                            Entity\Enum\ListType::WISH
                        );
                        if ($oneListWish) {
                            $this->contactHelper->updateWishlistAfterLogin(
                                $oneListWish
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }
}
