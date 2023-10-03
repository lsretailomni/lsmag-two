<?php

namespace Ls\Customer\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Address;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class AccountAddressObserver for adding and updating address
 */
class AccountAddressObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var ManagerInterface $messageManager */
    private $messageManager;

    /** @var LSR @var */
    private $lsr;

    /**
     * @param ContactHelper $contactHelper
     * @param ManagerInterface $messageManager
     * @param LSR $lsr
     */
    public function __construct(
        ContactHelper $contactHelper,
        ManagerInterface $messageManager,
        LSR $lsr
    ) {
        $this->contactHelper  = $contactHelper;
        $this->messageManager = $messageManager;
        $this->lsr            = $lsr;
    }

    /**
     * @inheritDoc
     * @throws NoSuchEntityException
     * @throws InvalidEnumException
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            /** @var $customerAddress Address */
            $customerAddress = $observer->getCustomerAddress();
            if (empty($customerAddress->getCustomer()->getData('lsr_cardid'))) {
                $customer = $this->contactHelper->getCustomerByEmail($customerAddress->getCustomer()->getEmail());
                $customerAddress->getCustomer()->setData('lsr_username', $customer->getData('lsr_username'));
                $customerAddress->getCustomer()->setData('lsr_token', $customer->getData('lsr_token'));
                $customerAddress->getCustomer()->setData('lsr_id', $customer->getData('lsr_id'));
                $customerAddress->getCustomer()->setData('lsr_cardid', $customer->getData('lsr_cardid'));
            }
            // only process if the customer has any valid lsr_username
            if ($customerAddress->getCustomer()->getData('lsr_username')
                && $customerAddress->getCustomer()->getData('lsr_token')
            ) {
                if ($this->contactHelper->isBillingAddress($customerAddress)) {
                    $result = $this->contactHelper->updateCustomerAccount(
                        $customerAddress->getCustomer(),
                        $customerAddress
                    );

                    if (empty($result)) {
                        //Generate Message only when Variable is either empty, null, 0 or undefined.
                        $this->messageManager->addErrorMessage(
                            __('Something went wrong, Please try again later.')
                        );
                    }
                }
            }
        }

        return $this;
    }
}
