<?php

namespace Ls\CustomerGraphQl\Plugin;

use Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Plugin on CreateCustomerAccount to send password
 */
class CreateCustomerAccount
{
    /** @var ContactHelper $contactHelper */
    private $contactHelper;

    /**
     * CreateCustomerAccount constructor.
     * @param ContactHelper $contactHelper
     */
    public function __construct(
        ContactHelper $contactHelper
    ) {
        $this->contactHelper = $contactHelper;
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
}
