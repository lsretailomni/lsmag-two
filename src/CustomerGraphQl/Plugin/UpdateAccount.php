<?php

namespace Ls\CustomerGraphQl\Plugin;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\ContactHelper;
use Magento\CustomerGraphQl\Model\Customer\SaveCustomer;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Update Customer Account
 */
class UpdateAccount
{
    /** @var ContactHelper */
    private $contactHelper;

    /** @var LSR @var */
    private $lsr;

    /**
     * UpdateAccount constructor.
     * @param ContactHelper $contactHelper
     * @param LSR $lsr
     */
    public function __construct(
        ContactHelper $contactHelper,
        LSR $lsr
    ) {
        $this->contactHelper = $contactHelper;
        $this->lsr           = $lsr;
    }

    /**
     * @param SaveCustomer $subject
     * @param callable $proceed
     * @param CustomerInterface $customer
     * @return mixed
     * @throws GraphQlInputException
     * @throws \Ls\Omni\Exception\InvalidEnumException
     */
    public function aroundExecute(
        SaveCustomer $subject,
        callable $proceed,
        CustomerInterface $customer
    ) {
        $result = $proceed($customer);
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $response = $this->contactHelper->updateCustomerAccount($customer);
            if (empty($response)) {
                throw new GraphQlInputException(
                    __('Cannot update account')
                );
            }
        }

        return $result;
    }
}
