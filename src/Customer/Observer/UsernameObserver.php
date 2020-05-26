<?php

namespace Ls\Customer\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Controller\Account\LoginPost\Interceptor;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Zend_Validate;
use Zend_Validate_EmailAddress;

/**
 * Class UsernameObserver
 * @package Ls\Customer\Observer
 */
class UsernameObserver implements ObserverInterface
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
     * UsernameObserver constructor.
     * @param ContactHelper $contactHelper
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param Proxy $customerSession
     * @param RedirectInterface $redirectInterface
     * @param ActionFlag $actionFlag
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
     * We need to check if username is already exist or not,
     * Magento does not care about the lsr_username field of whatever it is,
     * but since NAV rely on it, and it does not allow creation of duplicate lsr_username
     * so we need to check if the username field which is coming with the form is already exist or not.
     * If exist redirect back to registration with error message that username already exist.
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            try {
                $isNotValid = false;
                /** @var Interceptor $controller_action */
                $controller_action = $observer->getData('controller_action');
                $parameters        = $controller_action->getRequest()->getParams();
                $this->customerSession->setLsrUsername($parameters['lsr_username']);
                // LS Central only accept [a-zA-Z0-9-_@.] pattern of UserName
                if (!preg_match("/^[a-zA-Z0-9-_@.]*$/", $parameters['lsr_username'])) {
                    $this->messageManager->addErrorMessage(
                        __('Enter a valid username. Valid characters are A-Z a-z 0-9 . _ - @.')
                    );
                    $isNotValid = true;
                } elseif ($this->contactHelper->isUsernameExist($parameters['lsr_username']) || $this->contactHelper->isUsernameExistInLsCentral($parameters['lsr_username'])) {
                    $this->messageManager->addErrorMessage(
                        __('Username already exist, please try another one.')
                    );
                    $isNotValid = true;
                } else {
                    $isEmailValid = Zend_Validate::is($parameters['email'], Zend_Validate_EmailAddress::class);
                    if (!$isEmailValid) {
                        $this->messageManager->addErrorMessage(
                            __('Your email address is invalid.')
                        );
                        $isNotValid = true;
                    } elseif ($this->contactHelper->isEmailExistInLsCentral($parameters['email'])) {
                        $this->messageManager->addErrorMessage(
                            __('There is already an account with this email address. If you are sure that it is your email address, please proceed to login or use different email address.')
                        );
                        $isNotValid = true;
                    }
                }
                if ($isNotValid) {
                    $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
                    $observer->getControllerAction()
                        ->getResponse()->setRedirect($this->redirectInterface->getRefererUrl());
                    $this->customerSession->setCustomerFormData($parameters);
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }
}
