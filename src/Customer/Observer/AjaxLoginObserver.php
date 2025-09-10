<?php
declare(strict_types=1);

namespace Ls\Customer\Observer;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootMemberLogon;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Observer responsible for customer ajax login from checkout
 */
class AjaxLoginObserver extends AbstractOmniObserver
{
    /**
     * Entry point for the observer
     *
     * @param Observer $observer
     * @return $this|Json
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
     */
    public function execute(Observer $observer)
    {
        /** @var $request RequestInterface */
        $request = $observer->getEvent()->getRequest();
        $resultJson = $this->resultJsonFactory->create();
        // check if we have a data in request and request is Ajax.
        if ($request && $request->isXmlHttpRequest()) {
            $credentials = $this->jsonHelper->jsonDecode($request->getContent());

            if (!empty($credentials['username']) && !empty($credentials['password'])) {
                $email = $username = $credentials['username'];
                $isEmail = $this->contactHelper->isValid($username);
                $search = null;
                if ($this->lsr->isLSR(
                    $this->lsr->getCurrentStoreId(),
                    false,
                    $this->lsr->getCustomerIntegrationOnFrontend()
                )) {
                    try {
                        // CASE FOR EMAIL LOGIN := TRANSLATION TO USERNAME
                        if ($isEmail) {
                            $search = $this->contactHelper->search($username);
                            $found = $search !== null
                                && !empty($search->getLscMemberContact())
                                && !empty($search->getLscMemberContact()->getEmail());
                            if (!$found) {
                                $message = __('Sorry. No account found with the provided email address');
                                return $this->generateMessage($observer, $message, true);
                            }
                            $username = $search->getLscMemberLoginCard()->getLoginId();
                        }
                        $result = $this->contactHelper->login($username, $credentials['password']);
                        if (!$result) {
                            $message = __('Invalid LS Central login or password.');
                            return $this->generateMessage($observer, $message, true);
                        }
                        $response = [
                            'errors' => false,
                            'message' => __('LS Central login successful.')
                        ];
                        if ($result instanceof RootMemberLogon) {
                            if ($isEmail === false && !$search) {
                                $search = $this->contactHelper->search(
                                    current((array)$result->getMembercontact())->getEMail()
                                );
                            }
                            $this->contactHelper->processCustomerLogin($search, $credentials, $isEmail);
                            /**
                             * Fetch customer related info from omni and create user in magento
                             */
//                            $this->contactHelper->updateBasketAndWishlistAfterLogin($result);
                            $this->customerSession->regenerateId();
                            $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
                            return $resultJson->setData($response);
                        } else {
                            $message = __('The service is currently unavailable. Please try again later.');
                            return $this->generateMessage($observer, $message, true);
                        }
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                } else {
                    $isAjax = true;
                    $this->contactHelper->loginCustomerIfOmniServiceDown($isEmail, $email, $request, $isAjax);
                }
            }
        }
        return $this;
    }

    /**
     * Generate message
     *
     * @param Observer $observer
     * @param $message
     * @param bool $isError
     * @return $this
     */
    private function generateMessage(Observer $observer, $message, $isError = true)
    {
        $response = [
            'errors' => $isError,
            'message' => __($message)
        ];
        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
        $observer->getControllerAction()
            ->getResponse()
            ->representJson($this->jsonHelper->jsonEncode($response));
        return $this;
    }
}
