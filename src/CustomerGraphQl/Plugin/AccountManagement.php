<?php

namespace Ls\CustomerGraphQl\Plugin;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Customer\Model\AccountManagement as AccountManagementModel;

/**
 * Login and customer account creation plugins
 */
class AccountManagement
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var LSR @var */
    private $lsr;

    /**
     * AccountManagement constructor.
     * @param ContactHelper $contactHelper
     * @param LSR $LSR
     */
    public function __construct(
        ContactHelper $contactHelper,
        LSR $LSR
    ) {
        $this->contactHelper = $contactHelper;
        $this->lsr           = $LSR;
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
            $isEmail           = $this->contactHelper->isValid($username);
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
                $emailValue = $this->contactHelper->loginCustomerIfOmniServiceDown($isEmail, $email, null, false, true);
                if (!empty($emailValue)) {
                    $email = $emailValue;
                }
            }

        }

        return $proceed($email, $password);
    }
}
