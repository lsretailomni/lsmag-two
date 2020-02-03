<?php

namespace Ls\Customer\Observer;

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

/**
 * Class AccountEditObserver
 * @package Ls\Customer\Observer
 */
class AccountEditObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var ManagerInterface $messageManager */
    private $messageManager;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Proxy $customerSession */
    private $customerSession;

    /** @var ActionFlag */
    private $actionFlag;

    /** @var RedirectInterface */
    private $redirectInterface;

    /** @var LSR @var */
    private $lsr;

    /**
     * AccountEditObserver constructor.
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
     * Customer Update Password through Omni End Point, currently we are only working on
     * changing customer password and is not focusing on changing the customer account information.
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var Interceptor $controller_action */

        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR()) {
            $controller_action  = $observer->getData('controller_action');
            $customer_edit_post = $controller_action->getRequest()->getParams();
            $customer           = $this->customerSession->getCustomer();
            if (isset($customer_edit_post['change_password']) && $customer_edit_post['change_password']) {
                if ($customer_edit_post['password'] == $customer_edit_post['password_confirmation']) {
                    $result = null;
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
