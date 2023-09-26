<?php

namespace Ls\Customer\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ResetPasswordObserver
 * @package Ls\Customer\Observer
 */
class ResetPasswordObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /** @var ManagerInterface $messageManager */
    private $messageManager;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var CustomerSession $customerSession */
    private $customerSession;

    /** @var ActionFlag */
    private $actionFlag;

    /** @var RedirectInterface */
    private $redirectInterface;

    /** @var LSR @var */
    private $lsr;

    /** @var CustomerFactory */
    private $customerFactory;

    /**
     * @param ContactHelper $contactHelper
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param RedirectInterface $redirectInterface
     * @param ActionFlag $actionFlag
     * @param CustomerFactory $customerFactory
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        RedirectInterface $redirectInterface,
        ActionFlag $actionFlag,
        CustomerFactory $customerFactory,
        LSR $LSR
    ) {
        $this->contactHelper      = $contactHelper;
        $this->messageManager     = $messageManager;
        $this->logger             = $logger;
        $this->customerSession    = $customerSession;
        $this->redirectInterface  = $redirectInterface;
        $this->actionFlag         = $actionFlag;
        $this->lsr                = $LSR;
        $this->customerFactory    = $customerFactory;
    }

    /**
     * Reset Customer Password on Omni, it supposed to be triggered after magento is done with their validation.
     * All failed case validation and success message will be handled by magento resetPasswordPost.php class.
     * We are only suppose to do a post dispatch event to update the password.
     * @param Observer $observer
     * @return $this
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            try {
                $controller_action = $observer->getData('controller_action');
                $post_param        = $controller_action->getRequest()->getParams();
                $result            = null;
                /**
                 * only have to continue if actual event does not throws any error
                 * from Magento/Customer/Controller/Account/ResetPasswordPost.php
                 * If its failed then getRpToken() must return some response,
                 * but if it success, then it will return null
                 */
                $isFailed = $this->customerSession->getRpToken();
                if (!$isFailed) {
                    $customerId = $observer->getRequest()->getQuery('id');
                    $customer   =  $this->customerFactory->create()->load($customerId);

                    if ($customer) {
                        $result   = $this->contactHelper->resetPassword($customer, $post_param);
                    }
                    if (!$result) {
                        $this->messageManager->addErrorMessage(
                            __('Something went wrong, Please try again later.')
                        );
                        $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
                        $observer->getControllerAction()->getResponse()
                            ->setRedirect($this->redirectInterface->getRefererUrl());
                    }
                }
                return $this;
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $this;
    }
}
