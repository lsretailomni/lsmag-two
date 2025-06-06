<?php

namespace Ls\Customer\Observer;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Observer responsible for syncing customer password in case if it is reset
 * Resets Customer Password on Omni, it supposed to be triggered after magento is done with their validation.
 * All failed case validation and success message will be handled by magento resetPasswordPost.php class.
 * We are only supposed to do a post dispatch event to update the password.
 */
class ResetPasswordObserver extends AbstractOmniObserver
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
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getCustomerIntegrationOnFrontend()
        )) {
            try {
                $controller_action = $observer->getData('controller_action');
                $post_param = $controller_action->getRequest()->getParams();
                $result = null;
                /**
                 * only have to continue if actual event does not throws any error
                 * from Magento/Customer/Controller/Account/ResetPasswordPost.php
                 * If its failed then getRpToken() must return some response,
                 * but if it success, then it will return null
                 */
                $isFailed = $this->customerSession->getRpToken();
                if (!$isFailed) {
                    $customerId = $observer->getRequest()->getQuery('id');
                    $customer = $this->customerFactory->create()->load($customerId);

                    if ($customer) {
                        $result = $this->contactHelper->resetPassword($customer, $post_param);
                    }
                    if (!$result) {
                        $this->messageManager->addErrorMessage(
                            __('Something went wrong, Please try again later.')
                        );
                        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
                        $observer->getControllerAction()->getResponse()
                            ->setRedirect($this->redirectInterface->getRefererUrl());
                    }
                }
                return $this;
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }
}
