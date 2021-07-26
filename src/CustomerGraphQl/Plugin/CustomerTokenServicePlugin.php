<?php

namespace Ls\CustomerGraphQl\Plugin;

use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use Magento\Integration\Api\CustomerTokenServiceInterface;

/**
 * Interceptor to unset all values from customer and checkout sessions set manually during login
 */
class CustomerTokenServicePlugin
{
    /**
     * @var ContactHelper
     */
    private $contactHelper;

    /**
     * @var BasketHelper
     */
    private $basketHelper;

    /**
     * @param ContactHelper $contactHelper
     * @param BasketHelper $basketHelper
     */
    public function __construct(ContactHelper $contactHelper, BasketHelper $basketHelper)
    {
        $this->contactHelper = $contactHelper;
        $this->basketHelper  = $basketHelper;
    }

    /**
     * After plugin to unset required data from customer and checkout sessions
     *
     * @param CustomerTokenServiceInterface $subject
     * @param $result
     * @return mixed
     */
    public function afterRevokeCustomerAccessToken(CustomerTokenServiceInterface $subject, $result)
    {
        if ($result) {
            $this->basketHelper->unSetRequiredDataFromCustomerAndCheckoutSessions();
            $this->contactHelper->unSetRequiredDataFromCustomerSessions();
        }

        return $result;
    }
}
