<?php

namespace Ls\CustomerGraphQl\Plugin;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Exception\InvalidEnumException;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\CustomerGraphQl\Model\Customer\SaveCustomer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * Around plugin to intercepting SaveCustomer Execute method
     *
     * @param SaveCustomer $subject
     * @param callable $proceed
     * @param CustomerInterface $customer
     * @return mixed
     * @throws GraphQlInputException
     * @throws InvalidEnumException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function aroundExecute(
        SaveCustomer $subject,
        callable $proceed,
        CustomerInterface $customer
    ) {
        $result = $proceed($customer);

        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            $customer = $this->contactHelper->loadCustomerByEmailAndWebsiteId(
                $customer->getEmail(),
                $customer->getWebsiteId()
            );

            if (!$customer->getDefaultBillingAddress()) {
                $response = $this->contactHelper->updateCustomerAccount($customer);

                if (empty($response)) {
                    throw new GraphQlInputException(
                        __('Cannot update account')
                    );
                }
            }
        }

        return $result;
    }
}
