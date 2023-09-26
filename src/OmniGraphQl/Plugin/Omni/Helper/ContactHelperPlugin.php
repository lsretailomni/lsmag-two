<?php

namespace Ls\OmniGraphQl\Plugin\Omni\Helper;

use \Ls\Omni\Client\Ecommerce\Entity\MemberContact;
use \Ls\Omni\Helper\ContactHelper;

/**
 * Interceptor to intercept methods in ContactHelper
 */
class ContactHelperPlugin
{
    /**
     * Before plugin to clear existing quote in the checkout session
     *
     * To fix issue with merging quotes causing crash on checkout
     *
     * @param ContactHelper $subject
     * @param MemberContact $result
     * @param array $credentials
     * @param string $is_email
     * @return array
     */
    public function beforeProcessCustomerLogin(
        ContactHelper $subject,
        MemberContact $result,
        $credentials,
        $is_email
    ) {
        $subject->checkoutSession->clearQuote();

        return [$result, $credentials, $is_email];
    }
}
