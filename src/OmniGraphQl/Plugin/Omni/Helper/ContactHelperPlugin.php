<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Plugin\Omni\Helper;

use \Ls\Omni\Client\CentralEcommerce\Entity\GetMemberContactInfo_GetMemberContactInfo;
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
     * @param GetMemberContactInfo_GetMemberContactInfo $result
     * @param array $credentials
     * @param string $is_email
     * @return array
     */
    public function beforeProcessCustomerLogin(
        ContactHelper $subject,
        GetMemberContactInfo_GetMemberContactInfo $result,
        $credentials,
        $is_email
    ) {
        $subject->checkoutSession->clearQuote();

        return [$result, $credentials, $is_email];
    }
}
