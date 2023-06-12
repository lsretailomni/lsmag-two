<?php

namespace Ls\Customer\Plugin\Controller\Account;

use Ls\Core\Model\LSR;
use Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Controller\Account\ForgotPasswordPost;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class ForgotPasswordPostPlugin
{
    /**
     * @var CustomerFactory
     */
    public $customerFactory;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var ContactHelper
     */
    public $contactHelper;

    /**
     * @var Customer
     */
    public $customerResourceModel;

    /**
     * @var RedirectFactory
     */
    public $resultRedirectFactory;

    /**
     * @var MessageManagerInterface
     */
    public $messageManager;

    /**
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param LSR $lsr
     * @param ContactHelper $contactHelper
     * @param Customer $customerResourceModel
     * @param RedirectFactory $resultRedirectFactory
     * @param MessageManagerInterface $messageManager
     */
    public function __construct(
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        LSR $lsr,
        ContactHelper $contactHelper,
        Customer $customerResourceModel,
        RedirectFactory $resultRedirectFactory,
        MessageManagerInterface $messageManager
    ) {
        $this->customerFactory       = $customerFactory;
        $this->storeManager          = $storeManager;
        $this->lsr                   = $lsr;
        $this->contactHelper         = $contactHelper;
        $this->customerResourceModel = $customerResourceModel;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager        = $messageManager;
    }

    /**
     * @param ForgotPasswordPost $subject
     * @param $proceed
     * @return Redirect|mixed
     * @throws NoSuchEntityException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function aroundExecute(ForgotPasswordPost $subject, $proceed)
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $email = (string)$subject->getRequest()->getPost('email');

            if ($email) {
                try {
                    $search = $this->contactHelper->searchWithUsernameOrEmail($email);

                    if ($search) {
                        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
                        /** @var Customer $customer */
                        $customer = $this->customerFactory->create()
                            ->setWebsiteId($websiteId)
                            ->loadByEmail($search->getEmail());
                        $subject->getRequest()->setPostValue('email', $search->getEmail());
                        $userName = ($customer->getData('lsr_username')) ?: $search->getUserName();
                        $result   = $this->contactHelper->forgotPassword($userName);

                        if ($result) {
                            if (!$customer->getId()) {
                                // If customer doesn't exist in magento then creating a new one
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
                        }
                    }
                } catch (\Exception $exception) {
                    $this->messageManager->addExceptionMessage(
                        $exception,
                        __('Unable to reset password.')
                    );
                    $resultRedirect = $this->resultRedirectFactory->create();
                    return $resultRedirect->setPath('*/*/forgotpassword');
                }
            }
        }

        return $proceed();
    }
}
