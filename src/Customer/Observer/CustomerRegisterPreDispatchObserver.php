<?php

namespace Ls\Customer\Observer;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Zend_Log_Exception;

/**
 * Class CustomerRegisterPreDispatchObserver
 * We need to check if email is already exist or not,
 * If exist redirect back to registration with error message that email already exist.
 */
class CustomerRegisterPreDispatchObserver extends AbstractOmniObserver
{
    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Log_Exception|GuzzleException
     */
    public function execute(Observer $observer)
    {
        $parameters = $observer->getRequest()->getParams();
        $isNotValid = false;

        if (!empty($parameters['email']) && $this->contactHelper->isValid($parameters['email'])) {
            if ($this->lsr->isLSR(
                $this->lsr->getCurrentStoreId(),
                false,
                $this->lsr->getCustomerIntegrationOnFrontend()
            ) &&
                $this->lsr->getStoreConfig(
                    LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL,
                    $this->lsr->getCurrentStoreId()
                )) {
                try {
                    if ($this->contactHelper->isEmailExistInLsCentral($parameters['email'])) {
                        $this->messageManager->addErrorMessage(
                            __('There is already an account with this email address. If you are sure that it is
                            your email address, please proceed to login or use different email address.')
                        );
                        $isNotValid = true;
                    } else {
                        $session = $this->customerSession;
                        $this->contactHelper->syncCustomerToCentral($observer, $session);
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            } else {
                $session = $this->customerSession;
                $this->contactHelper->syncCustomerToCentral($observer, $session);
            }
        } else {
            $this->messageManager->addErrorMessage(__('Your email address is invalid.'));
            $isNotValid = true;
        }
        if ($isNotValid) {
            $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
            $observer->getControllerAction()
                ->getResponse()->setRedirect($this->redirectInterface->getRefererUrl());
            $this->customerSession->setCustomerFormData($parameters);
        }
        return $this;
    }
}
