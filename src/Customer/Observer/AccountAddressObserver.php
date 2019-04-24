<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Core\Model\LSR;

/**
 * Class AccountAddressObserver
 * @package Ls\Customer\Observer
 */
class AccountAddressObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var \Magento\Framework\Message\ManagerInterface $messageManager */
    private $messageManager;

    /** @var \Psr\Log\LoggerInterface $logger */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy $customerSession */
    private $customerSession;

    /** @var LSR @var  */
    private $lsr;

    /**
     * AccountAddressObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param LSR $lsr
     */
    public function __construct(
        ContactHelper $contactHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        LSR $lsr
    ) {
        $this->contactHelper = $contactHelper;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->lsr = $lsr;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $customerAddress \Magento\Customer\Model\Address */

        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR()) {
            $customerAddress = $observer->getCustomerAddress();
            // only process if the customer has any valid lsr_username
            if ($customerAddress->getCustomer()->getData('lsr_username')
                && $customerAddress->getCustomer()->getData('lsr_token')
            ) {
                $defaultShipping = $customerAddress->getCustomer()->getDefaultShippingAddress();
                if ($customerAddress->getData('is_default_shipping')) {
                    $result = $this->contactHelper->updateAccount($customerAddress);
                    if (empty($result)) {
                        //Generate Message only when Variable is either empty, null, 0 or undefined.
                        $this->messageManager->addErrorMessage(
                            __('Something went wrong, Please try again later.')
                        );
                    }
                } elseif ($defaultShipping) {
                    if ($defaultShipping->getId() == $customerAddress->getId()) {
                        $result = $this->contactHelper->updateAccount($customerAddress);
                        if (empty($result)) {
                            $this->messageManager->addErrorMessage(
                                __('Something went wrong, Please try again later.')
                            );
                        }
                    }
                }
            }
        }
        return $this;
    }
}
