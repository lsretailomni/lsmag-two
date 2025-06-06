<?php

namespace Ls\Customer\Observer;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;

/**
 * Observer responsible for storing required values in customer session post authentication in case of service down
 */
class PostLoginObserver extends AbstractOmniObserver
{
    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws LocalizedException
     * @throws InvalidEnumException|GuzzleException
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');

        if ($customer) {
            $customer = $this->contactHelper->loadCustomerByEmailAndWebsiteId(
                $customer->getEmail(),
                $customer->getWebsiteId()
            );

            if (!empty($customer->getData('lsr_cardid'))) {
                $this->contactHelper->setCardIdInCustomerSession(
                    $customer->getData('lsr_cardid')
                );
            }

            if (!empty($customer->getData('lsr_id'))) {
                $this->contactHelper->setLsrIdInCustomerSession(
                    $customer->getData('lsr_id')
                );
            }

            if (!empty($customer->getData('lsr_token'))) {
                $this->contactHelper->setSecurityTokenInCustomerSession(
                    $customer->getData('lsr_token')
                );
            }
            if (empty($this->contactHelper->getBasketUpdateChecking()) &&
                $this->contactHelper->lsr->isLSR(
                    $this->contactHelper->lsr->getCurrentStoreId(),
                    false,
                    $this->lsr->getCustomerIntegrationOnFrontend()
                )
            ) {
//                $contact = $this->contactHelper->getCentralCustomerByEmail($customer->getEmail());
//                if (!empty($contact)) {
//                    $this->contactHelper->updateBasketAndWishlistAfterLogin($contact);
//                }
            } else {
                $this->contactHelper->unsetBasketUpdateChecking();
            }
        }

        return $this;
    }
}
