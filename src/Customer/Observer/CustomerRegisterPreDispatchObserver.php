<?php

namespace Ls\Customer\Observer;

use Exception;
use Ls\Core\Model\LSR;
use Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class CustomerRegisterPreDispatchObserver
 * We need to check if email is already exist or not,
 * If exist redirect back to registration with error message that email already exist.
 */
class CustomerRegisterPreDispatchObserver implements ObserverInterface
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var ManagerInterface */
    private $messageManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var CustomerSession */
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
     * @param CustomerSession $customerSession
     * @param RedirectInterface $redirectInterface
     * @param ActionFlag $actionFlag
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        ManagerInterface $messageManager,
        LoggerInterface $logger,
        CustomerSession $customerSession,
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
     * @param Observer $observer
     * @return $this
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
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
                        $this->messageManager->addErrorMessage(__('There is already an account with this email address. If you are sure that it is your email address, please proceed to login or use different email address.'));
                        $isNotValid = true;
                    } else {
                        //do nothing
//                        $session    = $this->customerSession;
//                        $this->contactHelper->syncCustomerToCentral($observer,$session);

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
            $this->actionFlag->set('', Action::FLAG_NO_DISPATCH, true);
            $observer->getControllerAction()
                ->getResponse()->setRedirect($this->redirectInterface->getRefererUrl());
            $this->customerSession->setCustomerFormData($parameters);
        }
        return $this;
    }
}
