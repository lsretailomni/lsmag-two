<?php

namespace Ls\Customer\Observer;

use \Ls\Omni\Client\Ecommerce\Entity\Enum\ContactSearchType;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Core\Model\LSR;
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
     * @var LSR
     */
    private $lsr;

    /**
     * @param ContactHelper $contactHelper
     */
    public function __construct(
        ContactHelper $contactHelper,
        LSR $lsr
    ) {
        $this->contactHelper = $contactHelper;
        $this->lsr           = $lsr;
    }

    /**
     * @inheritDoc
     *
     * @param Observer $observer
     * @return $this|void
     * @throws LocalizedException
     * @throws InvalidEnumException
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getData('customer');

        if ($customer && !$this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
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
                $this->contactHelper->lsr->isLSR($this->contactHelper->lsr->getCurrentStoreId())
            ) {
                $contact = $this->contactHelper->getCustomerByUsernameOrEmailFromLsCentral(
                    $customer->getEmail(),
                    ContactSearchType::EMAIL
                );
                if (!empty($contact)) {
                    $this->contactHelper->updateBasketAndWishlistAfterLogin($contact);
                }
            } else {
                $this->contactHelper->unsetBasketUpdateChecking();
            }
        }

        return $this;
    }
}
