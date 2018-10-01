<?php

namespace Ls\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Api\CustomerMetadataInterface;

class ForgotPasswordObserver implements ObserverInterface
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;
    /** @var \Magento\Framework\Message\ManagerInterface $messageManager */
    protected $messageManager;
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
     * ForgotPasswordObserver constructor.
     * @param ContactHelper $contactHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
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
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->_redirectInterface = $redirectInterface;
        $this->_actionFlag = $actionFlag;
        $this->_customerFactory = $customerFactory;
        $this->_storeManager = $storeManager;

    }

    /**
     * Check if email is belongs to any account on Omni, if yes then generate the resetpasswordcode and store it in customer account.
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        /** @var \Magento\Customer\Controller\Account\LoginPost\Interceptor $controller_action */
        $controller_action = $observer->getData('controller_action');
        $post_param = $controller_action->getRequest()->getParams();
        $email = false;
        if (isset($post_param['email']) and $post_param['email'] != '') {
            $email = $post_param['email'];
        }
        if ($email) {
            if (!Zend_Validate::is($email, Zend_Validate_EmailAddress::class)) {
                $this->customerSession->setForgottenEmail($email);
                $this->messageManager->addErrorMessage(
                    __('Please correct the email address.')
                );
                $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                $observer->getControllerAction()
                    ->getResponse()
                    ->setRedirect($this->_redirectInterface->getRefererUrl());
            }
            $result = $this->contactHelper->forgotPassword($email);
            if ($result) {
                $websiteId = $this->_storeManager->getWebsite()->getWebsiteId();
                $customer = $this->_customerFactory->create()
                    ->setWebsiteId($websiteId)
                    ->loadByEmail($email);
                $customer->setData('attribute_set_id', CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
                $customer->setData('lsr_resetcode', $result);
                $customer->save();

            } else {
                $this->messageManager->addErrorMessage(
                    __('There is no account found with the provided email address. ')
                );
                $this->customerSession->setForgottenEmail($email);
                $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
                $observer->getControllerAction()
                    ->getResponse()
                    ->setRedirect($this->_redirectInterface->getRefererUrl());

            }
        }
        return $this;
    }
}
