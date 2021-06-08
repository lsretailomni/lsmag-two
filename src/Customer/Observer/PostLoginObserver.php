<?php

namespace Ls\Customer\Observer;

use \Ls\Omni\Helper\ContactHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Observer responsible for storing required values in customer session post authentication in case of service down
 */
class PostLoginObserver implements ObserverInterface
{
    /** @var ContactHelper */
    private $contactHelper;

    /**
     * @param ContactHelper $contactHelper
     */
    public function __construct(
        ContactHelper $contactHelper
    ) {
        $this->contactHelper = $contactHelper;
    }

    /**
     * @inheritDoc
     *
     * @param Observer $observer
     * @return $this|void
     * @throws LocalizedException
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
        }

        return $this;
    }
}
