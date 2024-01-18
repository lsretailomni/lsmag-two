<?php

namespace Ls\Customer\Plugin\Customer\Captcha\Observer;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Captcha\Observer\CheckUserCreateObserver;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to sync customer to central after captcha validation
 */
class CheckUserCreate
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var ContactHelper
     */
    private ContactHelper $contactHelper;
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;
    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;
    /**
     * @var LSR
     */
    private LSR $lsr;
    /**
     * @var RedirectInterface
     */
    private RedirectInterface $redirectInterface;
    /**
     * @var ActionFlag
     */
    private ActionFlag $actionFlag;

    /**
     * @param LoggerInterface $logger
     * @param ContactHelper $contactHelper
     * @param ManagerInterface $messageManager
     * @param CustomerSession $customerSession
     * @param RedirectInterface $redirectInterface
     * @param ActionFlag $actionFlag
     * @param LSR $LSR
     */
    public function __construct(
        LoggerInterface $logger,
        ContactHelper $contactHelper,
        ManagerInterface $messageManager,
        CustomerSession $customerSession,
        RedirectInterface $redirectInterface,
        ActionFlag $actionFlag,
        LSR $LSR
    ) {
        $this->logger            = $logger;
        $this->contactHelper     = $contactHelper;
        $this->messageManager    = $messageManager;
        $this->customerSession   = $customerSession;
        $this->redirectInterface = $redirectInterface;
        $this->actionFlag        = $actionFlag;
        $this->lsr               = $LSR;
    }

    /**
     * Around plugin for execute
     *
     * @param CheckUserCreateObserver $subject
     * @param object $result
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterExecute(
        \Magento\Captcha\Observer\CheckUserCreateObserver $subject,
        object $result,
        \Magento\Framework\Event\Observer $observer
    ) {
        if (!$this->actionFlag->get('', ActionInterface::FLAG_NO_DISPATCH)) {
            $this->customerRegisterationOnCentral($observer);
        }
    }

    /**
     * Observer execute
     *
     * @param Observer $observer
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function customerRegisterationOnCentral($observer)
    {
        $parameters = $observer->getRequest()->getParams();
        $isNotValid = false;

        if (!empty($parameters['email']) && $this->contactHelper->isValid($parameters['email'])) {
            if ($this->lsr->isLSR($this->lsr->getCurrentStoreId()) && $this->lsr->getStoreConfig(
                LSR::SC_LOYALTY_CUSTOMER_REGISTRATION_EMAIL_API_CALL,
                $this->lsr->getCurrentStoreId()
            )) {
                try {
                    if ($this->contactHelper->isEmailExistInLsCentral($parameters['email'])) {
                        $this->messageManager->addErrorMessage(
                            __('There is already an account with this email address. If you are sure that it is
                            your email address, please proceed to login or use different email address.')
                        );
                        $isNotValid = true;
                    } else {
                        $session = $this->customerSession;
                        $this->logger->info("pre dispatch observer plugin");
                        $this->contactHelper->syncCustomerToCentral($observer, $session);
                    }
                } catch (Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        } else {
            $this->messageManager->addErrorMessage(__('Your email address is invalid.'));
            $isNotValid = true;
        }
        if ($isNotValid) {
            $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
            $observer->getControllerAction()
                ->getResponse()->setRedirect($this->redirectInterface->getRefererUrl());
            $this->customerSession->setCustomerFormData($parameters);
        }
        return $this;
    }
}
