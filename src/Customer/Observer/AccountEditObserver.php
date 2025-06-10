<?php

namespace Ls\Customer\Observer;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Observer responsible for syncing customer password change
 */
class AccountEditObserver extends AbstractOmniObserver
{
    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws NoSuchEntityException|GuzzleException
     */
    public function execute(Observer $observer)
    {
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getCustomerIntegrationOnFrontend()
        )) {
            $controller_action = $observer->getData('controller_action');
            $customer_edit_post = $controller_action->getRequest()->getParams();
            $customer = $this->customerSession->getCustomer();
            if (isset($customer_edit_post['change_password']) && $customer_edit_post['change_password']) {
                if ($customer_edit_post['password'] == $customer_edit_post['password_confirmation']) {
                    $result = $this->contactHelper->changePassword($customer, $customer_edit_post);
                    if (!empty($result)) {
                        $this->messageManager->addSuccessMessage(
                            __('Your password has been updated.')
                        );
                    } else {
                        $this->messageManager->addErrorMessage(
                            __('You have entered an invalid current password.')
                        );
                        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
                        $observer->getControllerAction()->getResponse()
                            ->setRedirect($this->redirectInterface->getRefererUrl());
                    }
                } else {
                    $this->messageManager->addErrorMessage(
                        __('Confirm password did not match.')
                    );
                    $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
                    $observer->getControllerAction()->getResponse()
                        ->setRedirect($this->redirectInterface->getRefererUrl());
                }
            }
        }
        return $this;
    }
}
