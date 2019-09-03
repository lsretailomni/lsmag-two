<?php

namespace Ls\Customer\Controller\Account;

use \Ls\Omni\Helper\ContactHelper;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\AccountManagement;

class ForgotPasswordPost extends \Magento\Customer\Controller\Account\ForgotPasswordPost
{

    /** @var \Ls\Core\Model\LSR @var */
    public $lsr;

    /** @var ContactHelper $contactHelper */
    public $contactHelper;

    /** @var \Magento\Customer\Model\CustomerFactory */
    public $customerFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    public $storeManager;

    /** @var \Magento\Customer\Model\ResourceModel\Customer */
    public $customerResourceModel;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Customer\Api\AccountManagementInterface $customerAccountManagement,
        \Magento\Framework\Escaper $escaper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        LSR $LSR,
        ContactHelper $contactHelper,
        \Magento\Customer\Model\ResourceModel\Customer $customerResourceModel
    ) {
        $this->lsr = $LSR;
        $this->contactHelper = $contactHelper;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        parent::__construct($context, $customerSession, $customerAccountManagement, $escaper);
    }

    /**
     * Have to completely override the core funciton because we are allowing resetting the password
     * with and without email address.
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /*
         * Adding condition to only process if LSR is enabled.
         */
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $email = (string)$this->getRequest()->getPost('email');
            if ($email) {
                // handling the LS Central Reset Password functionality.
                /** @var Entity\ForgotPasswordResponse | null $result */
                try {
                    // check if omni return the success reponse of reset token
                    $result = $this->contactHelper->forgotPassword($email);
                    // check if omni also returned some response for the contact search
                    $search = $this->contactHelper->searchWithUsernameOrEmail($email);
                    if ($result && $search) {
                        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
                        /** @var \Magento\Customer\Model\Customer $customer */
                        $customer = $this->customerFactory->create()
                            ->setWebsiteId($websiteId)
                            ->loadByEmail($search->getEmail());
                        if (!$customer->getId()) {
                            // Check if customer is already created in magento or not.
                            $customer = $this->contactHelper->createNewCustomerAgainstProvidedInformation(
                                $search,
                                LSR::LS_RESETPASSWORD_DEFAULT
                            );
                        }
                        $customer->setData(
                            'attribute_set_id',
                            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER
                        );
                        $customer->setData('lsr_resetcode', $result);
                        $this->customerResourceModel->save($customer);
                        $this->customerAccountManagement->initiatePasswordReset(
                            $search->getEmail(),
                            AccountManagement::EMAIL_RESET
                        );
                    } else {
                        $this->session->setForgottenEmail($email);
                        $this->messageManager->addErrorMessage(
                            __('There is no account found with the provided email/username.')
                        );
                        return $resultRedirect->setPath('*/*/forgotpassword');
                    }
                } catch (\Magento\Framework\Exception\SecurityViolationException $exception) {
                    $this->messageManager->addErrorMessage($exception->getMessage());
                    return $resultRedirect->setPath('*/*/forgotpassword');
                } catch (\Exception $exception) {
                    $this->messageManager->addExceptionMessage(
                        $exception,
                        __('We\'re unable to send the password reset email.')
                    );
                    return $resultRedirect->setPath('*/*/forgotpassword');
                }
                $this->messageManager->addSuccessMessage($this->getSuccessMessage($email));
                return $resultRedirect->setPath('*/*/');
            } else {
                $this->messageManager->addErrorMessage(__('Please enter your email.'));
                return $resultRedirect->setPath('*/*/forgotpassword');
            }
        } else {
            return parent::execute();
        }
    }

}