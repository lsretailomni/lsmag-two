<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Ls\Omni\Helper\ContactHelper;

/**
 * Class AccountEditObserver
 * @package Ls\Customer\Observer
 */
class AccountEditObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var \Magento\Framework\Message\ManagerInterface $messageManager */
    private $messageManager;

    /** @var \Psr\Log\LoggerInterface $logger */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy $customerSession */
    private $customerSession;

    /** @var \Magento\Framework\App\ActionFlag */
    private $actionFlag;

    /** @var \Magento\Framework\App\Response\RedirectInterface */
    private $redirectInterface;

    /** @var \Ls\Core\Model\LSR @var  */
    private $lsr;

    /**
     * AccountEditObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Framework\App\Response\RedirectInterface $redirectInterface
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     */

    public function __construct(
        ContactHelper $contactHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Framework\App\Response\RedirectInterface $redirectInterface,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Ls\Core\Model\LSR $LSR
    ) {
        $this->contactHelper = $contactHelper;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->redirectInterface = $redirectInterface;
        $this->actionFlag = $actionFlag;
        $this->lsr  =   $LSR;
    }

    /**
     * Customer Update Password through Omni End Point, currently we are only working on
     * changing customer password and is not focusing on changing the customer account information.
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Controller\Account\LoginPost\Interceptor $controller_action */

        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $controller_action = $observer->getData('controller_action');
            $customer_edit_post = $controller_action->getRequest()->getParams();
            $customer = $this->customerSession->getCustomer();
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
                        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                        $observer->getControllerAction()->getResponse()
                            ->setRedirect($this->redirectInterface->getRefererUrl());
                    }
                } else {
                    $this->messageManager->addErrorMessage(
                        __('Confirm password did not match.')
                    );
                    $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                    $observer->getControllerAction()->getResponse()
                        ->setRedirect($this->redirectInterface->getRefererUrl());
                }
            }
        }
        return $this;
    }
}
