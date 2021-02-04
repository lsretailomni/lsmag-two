<?php

namespace Ls\CustomerGraphQl\Plugin;

use \Ls\Core\Model\LSR;
use Ls\Omni\Client\Ecommerce\Entity\Enum\ListType;
use \Ls\Omni\Client\Ecommerce\Entity\ForgotPasswordResponse;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Customer\Model\AccountManagement as AccountManagementModel;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Login and customer account creation plugins
 */
class AccountManagement
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var LSR @var */
    private $lsr;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CustomerFactory */
    public $customerFactory;

    /** @var Customer $customerResourceModel */
    private $customerResourceModel;

    /**
     * AccountManagement constructor.
     * @param ContactHelper $contactHelper
     * @param LSR $LSR
     * @param CustomerFactory $customerFactory
     * @param Customer $customerResourceModel
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ContactHelper $contactHelper,
        LSR $LSR,
        CustomerFactory $customerFactory,
        Customer $customerResourceModel,
        StoreManagerInterface $storeManager
    ) {
        $this->contactHelper         = $contactHelper;
        $this->lsr                   = $LSR;
        $this->customerFactory       = $customerFactory;
        $this->customerResourceModel = $customerResourceModel;
        $this->storeManager          = $storeManager;
    }

    /**
     * @param $subject
     * @param CustomerInterface $customer
     * @param $password
     * @return array|null
     */
    public function beforeCreateAccount($subject, CustomerInterface $customer, $password): ?array
    {
        if (!empty($password)) {
            $extensionAttributes = $customer->getExtensionAttributes();
            $extensionAttributes->setData('ls_password', $this->contactHelper->encryptPassword($password));
            $customer->setExtensionAttributes($extensionAttributes);
        }
        return [$customer, $password];
    }

    /**
     * @param AccountManagementModel $subject
     * @param callable $proceed
     * @param $username
     * @param $password
     * @return mixed
     * @throws AlreadyExistsException
     * @throws GraphQlAuthenticationException
     * @throws GraphQlNoSuchEntityException
     * @throws InputException
     * @throws InvalidEnumException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundAuthenticate(
        AccountManagementModel $subject,
        callable $proceed,
        $username,
        $password
    ) {
        $email = $username;
        if (!empty($username) && !empty($password)) {
            $isEmail = $this->contactHelper->isValid($username);
            if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
                if ($isEmail) {
                    $search = $this->contactHelper->search($username);
                    $found  = $search !== null
                        && ($search instanceof MemberContact)
                        && !empty($search->getEmail());
                    if (!$found) {
                        throw new GraphQlNoSuchEntityException(
                            __('Sorry! No account found with the provided email address.')
                        );
                    }
                    $username = $search->getUserName();
                }
                /** @var  MemberContact $result */
                $result = $this->contactHelper->login($username, $password);
                if ($result == false) {
                    throw new GraphQlAuthenticationException(
                        __('Invalid LS Central login or password.')
                    );
                }
                if ($result instanceof MemberContact) {
                    $login['username'] = $username;
                    $login['password'] = $password;
                    $this->contactHelper->processCustomerLogin($result, $login, $isEmail);
                    $email = $result->getEmail();
                }
            } else {
                $emailValue = $this->contactHelper->loginCustomerIfOmniServiceDown(
                    $isEmail,
                    $email,
                    null,
                    false,
                    true
                );
                if (!empty($emailValue)) {
                    $email = $emailValue;
                }
            }

        }

        return $proceed($email, $password);
    }

    /**
     * @param AccountManagementModel $subject
     * @param callable $proceed
     * @param $email
     * @param $resetToken
     * @param $newPassword
     * @throws GraphQlInputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundResetPassword(
        AccountManagementModel $subject,
        callable $proceed,
        $email,
        $resetToken,
        $newPassword
    ) {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $postParam ['password'] = $newPassword;
            $websiteId              = $this->storeManager->getWebsite()->getWebsiteId();
            $customer               = $this->customerFactory->create()
                ->setWebsiteId($websiteId)
                ->loadByEmail($email);
            $result                 = $this->contactHelper->resetPassword($customer, $postParam);
            if (!$result) {
                throw new GraphQlInputException(__('Cannot set the customer\'s password'));
            }
        }

        return $proceed($email, $resetToken, $newPassword);
    }

    /**
     * @param AccountManagementModel $subject
     * @param callable $proceed
     * @param $email
     * @param $template
     * @param $websiteId
     * @return mixed
     * @throws AlreadyExistsException
     * @throws GraphQlNoSuchEntityException
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundInitiatePasswordReset(
        AccountManagementModel $subject,
        callable $proceed,
        $email,
        $template,
        $websiteId
    ) {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            if ($email) {
                /** @var ForgotPasswordResponse | null $result */
                $search = $this->contactHelper->searchWithUsernameOrEmail($email);
                if ($search) {
                    if ($websiteId === null) {
                        $websiteId = $this->storeManager->getStore()->getWebsiteId();
                    }
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
                    }
                } else {
                    throw new GraphQlNoSuchEntityException(
                        __('There is no account found with the provided email/username.')
                    );

                }
            }
        }

        return $proceed($email, $template, $websiteId);
    }

    /**
     * @param AccountManagementModel $subject
     * @param callable $proceed
     * @param $customerId
     * @param $currentPassword
     * @param $newPassword
     * @return mixed
     * @throws GraphQlAuthenticationException
     */
    public function aroundChangePasswordById(
        AccountManagementModel $subject,
        callable $proceed,
        $customerId,
        $currentPassword,
        $newPassword
    ) {
        $customerEditPost['current_password'] = $currentPassword;
        $customerEditPost['password']         = $newPassword;
        $customer                             = $this->customerFactory->create()->load($customerId);
        $result                               = $this->contactHelper->changePassword($customer, $customerEditPost);
        if (empty($result)) {
            throw new GraphQlAuthenticationException(
                __('You have entered an invalid current password.')
            );
        }
        return $proceed($customerId, $currentPassword, $newPassword);
    }
}
