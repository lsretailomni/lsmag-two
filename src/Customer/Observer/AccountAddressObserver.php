<?php

namespace Ls\Customer\Observer;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Customer\Model\Address;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class AccountAddressObserver for adding and updating address
 */
class AccountAddressObserver extends AbstractOmniObserver
{
    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws GuzzleException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getCustomerIntegrationOnFrontend()
        )) {
            /** @var $customerAddress Address */
            $customerAddress = $observer->getCustomerAddress();
            if (empty($customerAddress->getCustomer()->getData('lsr_cardid'))) {
                $customer = $this->contactHelper->getCustomerByEmail($customerAddress->getCustomer()->getEmail());
                $customerAddress->getCustomer()->setData('lsr_username', $customer->getData('lsr_username'));
                $customerAddress->getCustomer()->setData('lsr_token', $customer->getData('lsr_token'));
                $customerAddress->getCustomer()->setData('lsr_id', $customer->getData('lsr_id'));
                $customerAddress->getCustomer()->setData('lsr_account_id', $customer->getData('lsr_account_id'));
                $customerAddress->getCustomer()->setData('lsr_cardid', $customer->getData('lsr_cardid'));
            }
            // only process if the customer has any valid lsr_username
            if ($customerAddress->getCustomer()->getData('lsr_username')
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
