<?php
declare(strict_types=1);

namespace Ls\Customer\Plugin\Customer;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\CentralEcommerce\Entity\RootMemberLogon;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Customer\Model\AccountManagement as AccountManagementModel;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Login and customer account creation plugins
 */
class AccountManagement
{
    /**
     * @param ContactHelper $contactHelper
     * @param LSR $lsr
     * @param CustomerFactory $customerFactory
     * @param Customer $customerResourceModel
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        public ContactHelper $contactHelper,
        public LSR $lsr,
        public CustomerFactory $customerFactory,
        public Customer $customerResourceModel,
        public StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Before plugin to save ls_password as an extension attribute
     *
     * @param $subject
     * @param CustomerInterface $customer
     * @param $password
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function beforeCreateAccount($subject, CustomerInterface $customer, $password = null): ?array
    {
        if (!empty($password)) {
            $extensionAttributes = $customer->getExtensionAttributes();
            $extensionAttributes->setData('ls_password', $this->contactHelper->encryptPassword($password));
            $extensionAttributes->setData('lsr_password', $this->contactHelper->encryptPassword($password));
            $customer->setExtensionAttributes($extensionAttributes);
            if (empty($customer->getStoreId())) {
                $customer->setStoreId($this->lsr->getCurrentStoreId());
            }
        }
        return [$customer, $password];
    }

    /**
     * For checking login authentication
     *
     * @param AccountManagementModel $subject
     * @param callable $proceed
     * @param string $username
     * @param string $password
     * @return mixed
     * @throws AlreadyExistsException
     * @throws AuthenticationException
     * @throws InputException
     * @throws InvalidEnumException
     * @throws InvalidTransitionException
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
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
            $search = null;

            if ($this->lsr->isLSR(
                $this->lsr->getCurrentStoreId(),
                false,
                $this->lsr->getCustomerIntegrationOnFrontend()
            )) {
                if ($isEmail) {
                    $search = $this->contactHelper->search($username);
                    $found = $search !== null
                        && !empty($search->getLscMemberContact())
                        && !empty($search->getLscMemberContact()->getEmail());

                    if (!$found) {
                        throw new NoSuchEntityException(
                            __('Sorry! No account found with the provided email address.')
                        );
                    }
                    $username = $search->getLscMemberLoginCard()->getLoginId();
                }
                $result = $this->contactHelper->login($username, $password);

                if (!$result) {
                    throw new AuthenticationException(
                        __('Invalid LS Central login or password.')
                    );
                }
                if ($result instanceof RootMemberLogon) {
                    if ($isEmail === false && !$search) {
                        $search = $this->contactHelper->search(
                            current((array) $result->getMembercontact())->getEMail()
                        );
                    }

                    $login['username'] = $username;
                    $login['password'] = $password;
                    $this->contactHelper->processCustomerLogin($search, $login, $isEmail);
                    $email = current((array) $result->getMembercontact())->getEMail();
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
     * For resetting password
     *
     * @param AccountManagementModel $subject
     * @param callable $proceed
     * @param string $email
     * @param string $resetToken
     * @param string $newPassword
     * @return mixed
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException|GuzzleException
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
            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
            $customer = $this->customerFactory->create()
                ->setWebsiteId($websiteId)
                ->loadByEmail($email);
            $result = $this->contactHelper->resetPassword($customer, $postParam);
            if (!$result) {
                throw new InputException(__('Cannot set the customer\'s password'));
            }
        }

        return $proceed($email, $resetToken, $newPassword);
    }

    /**
     * Initiate password reset
     *
     * @param AccountManagementModel $subject
     * @param callable $proceed
     * @param string $email
     * @param string $template
     * @return mixed
     * @throws AlreadyExistsException
     * @throws GuzzleException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundInitiatePasswordReset(
        AccountManagementModel $subject,
        callable $proceed,
        $email,
        $template
    ) {
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();

        if ($this->lsr->isLSR($websiteId, 'website')) {
            if ($email) {
                $search = $this->contactHelper->searchWithUsernameOrEmail($email);

                if ($search &&
                    !empty($search->getLscMemberContact())
                    && !empty($search->getLscMemberContact()->getEmail())
                ) {
                    /** @var Customer $customer */
                    $customer = $this->customerFactory->create()
                        ->setWebsiteId($websiteId)
                        ->loadByEmail($search->getLscMemberContact()->getEmail());
                    $userName = ($customer->getData('lsr_username')) ?
                        : $search->getLscMemberLoginCard()->getLoginId();
                    $result = $this->contactHelper->forgotPassword($userName);

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
                    throw new NoSuchEntityException(
                        __('There is no account found with the provided email/username.')
                    );

                }
            }
        }

        return $proceed($email, $template, $websiteId);
    }

    /**
     * Change password
     *
     * @param AccountManagementModel $subject
     * @param callable $proceed
     * @param $customerId
     * @param $currentPassword
     * @param $newPassword
     * @return mixed
     * @throws AuthenticationException
     */
    public function aroundChangePasswordById(
        AccountManagementModel $subject,
        callable $proceed,
        $customerId,
        $currentPassword,
        $newPassword
    ) {
        $customerEditPost['current_password'] = $currentPassword;
        $customerEditPost['password'] = $newPassword;
        $customer = $this->customerFactory->create()->load($customerId);
        $result = $this->contactHelper->changePassword($customer, $customerEditPost);
        if (empty($result)) {
            throw new AuthenticationException(
                __('You have entered an invalid current password.')
            );
        }
        return $proceed($customerId, $currentPassword, $newPassword);
    }
}
