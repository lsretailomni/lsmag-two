<?php

namespace Ls\Customer\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use Zend_Validate_Exception;

/**
 * Class LoginObserver
 * @package Ls\Customer\Observer
 */
class LoginObserver implements ObserverInterface
{

    /** @var ContactHelper */
    private $contactHelper;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var Proxy */
    private $customerSession;

    /** @var RedirectInterface */
    private $redirectInterface;

    /** @var ActionFlag */
    private $actionFlag;

    /** @var LSR @var */
    private $lsr;

    /**
     * LoginObserver constructor.
     * @param ContactHelper $contactHelper
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param Proxy $customerSession
     * @param RedirectInterface $redirectInterface
     * @param ActionFlag $actionFlag
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        Proxy $customerSession,
        RedirectInterface $redirectInterface,
        ActionFlag $actionFlag,
        LSR $LSR
    ) {
        $this->contactHelper     = $contactHelper;
        $this->messageManager    = $messageManager;
        $this->logger            = $logger;
        $this->customerSession   = $customerSession;
        $this->redirectInterface = $redirectInterface;
        $this->actionFlag        = $actionFlag;
        $this->lsr               = $LSR;
    }

    /**
     * NAV/Omni only accept data for the authentication in the form of
     * username and password so whatever the input user provide,
     * we need to convert it into the form of Username and Password.
     * Exceptions:
     * If user exist in NAV but does not in Magento, then after login,
     * we need to create user in magento based on the data we received from NAV.
     * If input is email but the account does not exist in Magento then
     * we need to throw an error that "Email login is only available for users registered in Magento".
     * @param Observer $observer
     * @return $this|void
     * @throws LocalizedException
     * @throws Zend_Validate_Exception
     */
    public function execute(Observer $observer)
    {
        $login = $observer->getRequest()->getPost('login');
        if (!empty($login['username']) && !empty($login['password'])) {
            $email    = $username = $login['username'];
            $is_email = Zend_Validate::is($username, Zend_Validate_EmailAddress::class);
            if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                try {
                    if ($is_email) {
                        $search = $this->contactHelper->search($username);
                        $found  = $search !== null
                            && ($search instanceof Entity\MemberContact)
                            && !empty($search->getEmail());
                        if (!$found) {
                            $errorMessage = __('Sorry! No account found with the provided email address.');
                            return $this->handleErrorMessage($observer, $errorMessage);
                        }
                        $username = $search->getUserName();
                    }
                    /** @var  Entity\MemberContact $result */
                    $result = $this->contactHelper->login($username, $login['password']);
                    if ($result == false) {
                        $errorMessage = __('Invalid LS Central login or password.');
                        return $this->handleErrorMessage($observer, $errorMessage);
                    }
                    if ($result instanceof Entity\MemberContact) {
                        $this->contactHelper->processCustomerLogin($result, $login, $is_email);
                        $oneListBasket = $this->contactHelper->getOneListTypeObject(
                            $result->getOneLists()->getOneList(),
                            Entity\Enum\ListType::BASKET
                        );
                        if ($oneListBasket) {
                            /** Update Basket to Omni */
                            $this->contactHelper->updateBasketAfterLogin(
                                $oneListBasket,
                                $result->getId(),
                                $result->getCards()->getCard()[0]->getId()
                            );
                        }
                        $oneListWish = $this->contactHelper->getOneListTypeObject(
                            $result->getOneLists()->getOneList(),
                            Entity\Enum\ListType::WISH
                        );
                        if ($oneListWish) {
                            $this->contactHelper->updateWishlistAfterLogin(
                                $oneListWish
                            );
                        }
                    } else {
                        $this->customerSession->addError(
                            __('The service is currently unavailable. Please try again later.')
                        );
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            } else {
                $this->contactHelper->loginCustomerIfOmniServiceDown($is_email, $email, $observer->getRequest());
            }
        }

        return $this;
    }

    /**
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
