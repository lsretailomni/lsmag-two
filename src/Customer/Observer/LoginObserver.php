<?php

namespace Ls\Customer\Observer;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Entity\RootMemberLogon;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;

/**
 * Observer responsible for customer login
 */
class LoginObserver extends AbstractOmniObserver
{
    /**
     * NAV/Omni only accept data for the authentication in the form of
     * username and password so whatever the input user provide,
     * we need to convert it into the form of Username and Password.
     * Exceptions:
     * If user exist in NAV but does not in Magento, then after login,
     * we need to create user in magento based on the data we received from NAV.
     * If input is email but the account does not exist in Magento then
     * we need to throw an error that "Email login is only available for users registered in Magento".
     *
     * @param Observer $observer
     * @return $this
     * @throws LocalizedException|GuzzleException
     */
    public function execute(Observer $observer)
    {
        $login = $observer->getRequest()->getPost('login');
        if (!empty($login['username']) && !empty($login['password'])) {
            $email = $username = $login['username'];
            $isEmail = $this->contactHelper->isValid($username);
            $search = null;
            if ($this->lsr->isLSR(
                $this->lsr->getCurrentStoreId(),
                false,
                $this->lsr->getCustomerIntegrationOnFrontend()
            )) {
                try {
                    if ($isEmail) {
                        $search = $this->contactHelper->search($username);
                        $found = $search !== null
                            && !empty($search->getLscMemberContact())
                            && !empty($search->getLscMemberContact()->getEmail());
                        if (!$found) {
                            $errorMessage = __('Sorry! No account found with the provided email address.');
                            return $this->handleErrorMessage($observer, $errorMessage);
                        }
                        $username = $search->getLscMemberLoginCard()->getLoginId();
                    }
                    /** @var  Entity\MemberContact $result */
                    $result = $this->contactHelper->login($username, $login['password']);
                    if (!$result) {
                        $errorMessage = __('Invalid LS Central login or password.');
                        return $this->handleErrorMessage($observer, $errorMessage);
                    }
                    if ($result instanceof RootMemberLogon) {
                        if ($isEmail === false && !$search) {
                            $search = $this->contactHelper->search(current($result->getMembercontact())->getEMail());
                        }
                        $this->contactHelper->processCustomerLogin($search, $login, $isEmail);
                    } else {
                        $this->customerSession->addError(
                            __('The service is currently unavailable. Please try again later.')
                        );
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            } else {
                $this->contactHelper->loginCustomerIfOmniServiceDown($isEmail, $email, $observer->getRequest());
            }
        }

        return $this;
    }

    /**
     * Handle error message
     *
     * @param Observer $observer
     * @param string $errorMessage
     * @return $this
     */
    private function handleErrorMessage(Observer $observer, $errorMessage = '')
    {
        $this->messageManager->addErrorMessage(
            __($errorMessage)
        );
        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
        $observer->getControllerAction()->getResponse()->setRedirect(
            $this->redirectInterface->getRefererUrl()
        );
        return $this;
    }
}
