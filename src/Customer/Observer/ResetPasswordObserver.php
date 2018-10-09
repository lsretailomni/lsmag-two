<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Ls\Omni\Helper\ContactHelper;

class ResetPasswordObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;
    /** @var \Magento\Framework\Message\ManagerInterface $messageManager */
    protected $messageManager;
    /** @var \Magento\Framework\Registry $registry */
    protected $registry;
    /** @var \Psr\Log\LoggerInterface $logger */
    protected $logger;
    /** @var \Magento\Customer\Model\Session $customerSession */
    protected $customerSession;
    /** @var \Magento\Framework\App\ActionFlag */
    protected $_actionFlag;
    /** @var \Magento\Framework\App\Response\RedirectInterface */
    protected $_redirectInterface;
    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $_customerFactory;
    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /**
     * ResetPasswordObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Registry $registry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Response\RedirectInterface $redirectInterface
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */

    public function __construct(
        ContactHelper $contactHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Response\RedirectInterface $redirectInterface,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager

    )
    {
        $this->contactHelper = $contactHelper;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->_redirectInterface = $redirectInterface;
        $this->_actionFlag = $actionFlag;
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;

    }

    /**
     * Reset Customer Password on Omni, it supposed to be triggered after magento is done with their validation.
     * All failed case validation and success message will be handled by magento resetPasswordPost.php class.
     * We are only suppose to do a post dispatch event to update the password.
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Controller\Account\LoginPost\Interceptor $controller_action */
        $controller_action = $observer->getData('controller_action');
        $post_param = $controller_action->getRequest()->getParams();

        /**
         * only have to continue if actual event does not throws any error from Magento/Customer/Controller/Account/ResetPasswordPost.php
         * If its failed then getRpToken() must return some response, but if it success, then it will return null
         */

        $isFailed = $this->customerSession->getRpToken();
        if (!$isFailed) {
            $websiteId = $this->_storeManager->getWebsite()->getWebsiteId();
            $customer = $this->_customerFactory->create()
                ->setWebsiteId($websiteId)
                ->load($post_param['id']);

            $result = $this->contactHelper->resetPassword($customer, $post_param);
            if ($result) {
                // Magento have already generated a success message so do nothing

            } else {
                $this->messageManager->addErrorMessage(
                    __('Something went wrong, Please try again later.')
                );
                $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                $observer->getControllerAction()->getResponse()->setRedirect($this->_redirectInterface->getRefererUrl());
            }
        }
        return $this;

    }
}
