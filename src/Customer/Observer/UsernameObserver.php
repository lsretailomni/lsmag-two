<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Ls\Omni\Helper\ContactHelper;

/**
 * Class UsernameObserver
 * @package Ls\Customer\Observer
 */
class UsernameObserver implements ObserverInterface
{
    /** @var ContactHelper  */
    private $contactHelper;

    /** @var \Magento\Framework\Message\ManagerInterface  */
    private $messageManager;

    /** @var \Psr\Log\LoggerInterface  */
    private $logger;

    /** @var \Magento\Customer\Model\Session\Proxy  */
    private $customerSession;

    /** @var \Magento\Framework\App\Response\RedirectInterface  */
    private $redirectInterface;

    /** @var \Magento\Framework\App\ActionFlag  */
    private $actionFlag;

    /**
     * UsernameObserver constructor.
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
        \Magento\Framework\App\ActionFlag $actionFlag
    ) {
        $this->contactHelper = $contactHelper;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->redirectInterface = $redirectInterface;
        $this->actionFlag = $actionFlag;
    }

    /**
     * We need to check if username is already exist or not,
     * Magento does not care about the lsr_username field of whatever it is,
     * but since NAV rely on it, and it does not allow creation of duplicate lsr_username
     * so we need to check if the username field which is coming with the form is already exist or not.
     * If exist redirect back to registration with error message that username already exist.
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            /** @var \Magento\Customer\Controller\Account\LoginPost\Interceptor $controller_action */
            $controller_action = $observer->getData('controller_action');
            $parameters = $controller_action->getRequest()->getParams();
            $this->customerSession->setLsrUsername($parameters['lsr_username']);
            if ($this->contactHelper->isUsernameExist($parameters['lsr_username'])) {
                $this->messageManager->addErrorMessage(
                    __('Username already exist, please try another one.')
                );
                $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                $observer->getControllerAction()
                    ->getResponse()->setRedirect($this->redirectInterface->getRefererUrl());
                $this->customerSession->setCustomerFormData($parameters);
            }
            return $this;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
