<?php

namespace Ls\Customer\Controller\Account;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session\Proxy;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Store\Model\StoreManagerInterface;

class ForgotPasswordPost extends \Magento\Customer\Controller\Account\ForgotPasswordPost
{

    /** @var LSR */
    public $lsr;

    /** @var ContactHelper $contactHelper */
    public $contactHelper;

    /** @var CustomerFactory */
    public $customerFactory;

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var Customer */
    public $customerResourceModel;

    /**
     * ForgotPasswordPost constructor.
     * @param Context $context
     * @param Proxy $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper $escaper
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param LSR $LSR
     * @param ContactHelper $contactHelper
     * @param Customer $customerResourceModel
     */
    public function __construct(
        Context $context,
        Proxy $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        LSR $LSR,
        ContactHelper $contactHelper,
        Customer $customerResourceModel
    ) {
        $this->lsr                   = $LSR;
        $this->contactHelper         = $contactHelper;
        $this->customerFactory       = $customerFactory;
        $this->storeManager          = $storeManager;
        $this->customerResourceModel = $customerResourceModel;
        parent::__construct($context, $customerSession, $customerAccountManagement, $escaper);
    }

    /**
     * Have to completely override the core function because we are allowing resetting the password
     * with and without email address.
     * @return Redirect
     */
    public function execute()
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            $email          = (string)$this->getRequest()->getPost('email');
            if ($email) {
                /** @var Entity\ForgotPasswordResponse | null $result */
                try {
                    $search = $this->contactHelper->searchWithUsernameOrEmail($email);
                    if ($search) {
                        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
                        /** @var Customer $customer */
                        $customer = $this->customerFactory->create()
                            ->setWebsiteId($websiteId)
                            ->loadByEmail($search->getEmail());
                        $result   = $this->contactHelper->forgotPassword($customer);
                        if ($result) {
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
                        }
                    } else {
                        $this->session->setForgottenEmail($email);
                        $this->messageManager->addErrorMessage(
                            __('There is no account found with the provided email/username.')
                        );
                        return $resultRedirect->setPath('*/*/forgotpassword');
                    }
                } catch (SecurityViolationException $exception) {
                    $this->messageManager->addErrorMessage($exception->getMessage());
                    return $resultRedirect->setPath('*/*/forgotpassword');
                } catch (Exception $exception) {
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
