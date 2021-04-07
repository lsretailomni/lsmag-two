<?php

namespace Ls\Customer\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AccountAddressObserver for adding and updating address
 */
class AccountAddressObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var ManagerInterface $messageManager */
    private $messageManager;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Proxy $customerSession */
    private $customerSession;

    /** @var LSR @var */
    private $lsr;

    /**
     * AccountAddressObserver constructor.
     * @param ContactHelper $contactHelper
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param Proxy $customerSession
     * @param LSR $lsr
     */
    public function __construct(
        ContactHelper $contactHelper,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        Proxy $customerSession,
        LSR $lsr
    ) {
        $this->contactHelper   = $contactHelper;
        $this->messageManager  = $messageManager;
        $this->logger          = $logger;
        $this->customerSession = $customerSession;
        $this->lsr             = $lsr;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var $customerAddress Address */

        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $customerAddress = $observer->getCustomerAddress();
            // only process if the customer has any valid lsr_username
            if ($customerAddress->getCustomer()->getData('lsr_username')
                && $customerAddress->getCustomer()->getData('lsr_token')
            ) {
                $defaultBillingAddress = $customerAddress->getCustomer()->getDefaultBillingAddress();
                if ($customerAddress->getData('is_default_billing')) {
                    $result = $this->contactHelper->updateAccount($customerAddress);
                    if (empty($result)) {
                        //Generate Message only when Variable is either empty, null, 0 or undefined.
                        $this->messageManager->addErrorMessage(
                            __('Something went wrong, Please try again later.')
                        );
                    }
                } elseif ($defaultBillingAddress) {
                    if ($defaultBillingAddress->getId() == $customerAddress->getId()) {
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
