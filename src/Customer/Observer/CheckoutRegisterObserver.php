<?php

namespace Ls\Customer\Observer;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;

/**
 * Observer responsible for customer register from checkout
 */
class CheckoutRegisterObserver extends AbstractOmniObserver
{
    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws LocalizedException
     * @throws InvalidEnumException
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws InvalidTransitionException|GuzzleException
     */
    // @codingStandardsIgnoreStart
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
            $orderId = $this->checkoutSession->getLastOrderId();
            $order   = $this->orderRepository->get($orderId);
            if ($order->getCustomerId()) {
                // only performed when a customer id is created
                $customer = $this->customerFactory->create()
                    ->setWebsiteId($this->storeManager->getWebsite()->getWebsiteId())
                    ->loadByEmail($order->getCustomerEmail());
                // setting the lsr_username field
                $customer->setData('lsr_username', $customer->getEmail());
                // manually set the password for now and generate the reset password link
                $customer->setData('password', 'admin@123');
                /* var $contact = Ls/Omni/Client/Ecommerce/Entity/MemberContact */
                $contact = $this->contactHelper->contact($customer);
                if (is_object($contact) && $contact->getId()) {
                    $token = $contact->getLoggedOnToDevice()->getSecurityToken();
                    $card  = $contact->getCard();
                    $customer->setData('lsr_id', $contact->getId());
                    $customer->setData('lsr_token', $token);
                    $customer->setData('lsr_cardid', $card->getId());

                    if ($contact->getAccount()->getScheme()->getId()) {
                        $customerGroupId = $this->contactHelper->getCustomerGroupIdByName(
                            $contact->getAccount()->getScheme()->getId()
                        );
                        $customer->setGroupId($customerGroupId);
                    }
                    $result = $this->contactHelper->forgotPassword($customer->getEmail());
                    if ($result) {
                        $customer->setData('lsr_resetcode', $result);
                    }
                    $this->customerResourceModel->save($customer);
                }
            }
        }
        return $this;
    }
    // @codingStandardsIgnoreEnd
}
