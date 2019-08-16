<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Core\Model\LSR;

/**
 * Class ResetPasswordObserver
 * @package Ls\Customer\Observer
 */
class ResetPasswordObserver implements ObserverInterface
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

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    private $customerRepository;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;

    /** @var \Ls\Core\Model\LSR @var  */
    private $lsr;

    /** @var \Magento\Framework\Registry */
    private $registry;

    /** @var \Magento\Customer\Model\CustomerFactory */
    private $customerFactory;

    /**
     * ResetPasswordObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Framework\App\Response\RedirectInterface $redirectInterface
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param LSR $LSR
     * @param Registry $registry
     */
    public function __construct(
        ContactHelper $contactHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Framework\App\Response\RedirectInterface $redirectInterface,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        LSR $LSR,
        Registry $registry
    ) {
        $this->contactHelper = $contactHelper;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->redirectInterface = $redirectInterface;
        $this->actionFlag = $actionFlag;
        $this->storeManager = $storeManager;
        $this->lsr  =   $LSR;
        $this->registry = $registry;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Reset Customer Password on Omni, it supposed to be triggered after magento is done with their validation.
     * All failed case validation and success message will be handled by magento resetPasswordPost.php class.
     * We are only suppose to do a post dispatch event to update the password.
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            try {
                /** @var \Magento\Customer\Controller\Account\LoginPost\Interceptor $controller_action */
                $controller_action = $observer->getData('controller_action');
                $post_param = $controller_action->getRequest()->getParams();
                /**
                 * only have to continue if actual event does not throws any error
                 * from Magento/Customer/Controller/Account/ResetPasswordPost.php
                 * If its failed then getRpToken() must return some response,
                 * but if it success, then it will return null
                 */
                $isFailed = $this->customerSession->getRpToken();
                if (!$isFailed) {
                    $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
                    $email = $this->registry->registry(LSR::REGISTRY_CURRENT_RESETPASSWORD_EMAIL);
                    if ($email) {
                        $customer = $this->customerFactory->create()
                            ->setWebsiteId($websiteId)
                            ->loadByEmail($email);
                        $result = $this->contactHelper->resetPassword($customer, $post_param);
                    }
                    if (!$result) {
                        $this->messageManager->addErrorMessage(
                            __('Something went wrong, Please try again later.')
                        );
                        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                        $observer->getControllerAction()->getResponse()
                            ->setRedirect($this->redirectInterface->getRefererUrl());
                    }
                }
                return $this;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }
}
