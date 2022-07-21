<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\OmniGraphQl\Helper\DataHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\CheckCartCheckoutAllowance;

/**
 * Interceptor to intercept SetPaymentMethodOnCart methods
 */
class SetPaymentMethodOnCartPlugin
{
    /**
     * @var CheckCartCheckoutAllowance
     */
    private $checkCartCheckoutAllowance;

    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @param CheckCartCheckoutAllowance $checkCartCheckoutAllowance
     * @param DataHelper $dataHelper
     */
    public function __construct(
        CheckCartCheckoutAllowance $checkCartCheckoutAllowance,
        DataHelper $dataHelper
    ) {
        $this->checkCartCheckoutAllowance = $checkCartCheckoutAllowance;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Before plugin to set dummy address for anonymous ordering
     *
     * @param mixed $subject
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws NoSuchEntityException
     */
    public function beforeResolve(
        $subject,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['input']['anonymous']) || $args['input']['anonymous'] === false) {
            return [$field, $context, $info, $value, $args];
        }

        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing.'));
        }
        $maskedCartId = $args['input']['cart_id'];

        if (empty($args['input']['payment_method']['code'])) {
            throw new GraphQlInputException(__('Required parameter "code" for "payment_method" is missing.'));
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->dataHelper->getCartGivenRequiredData($maskedCartId, $context->getUserId(), $storeId);
        $this->checkCartCheckoutAllowance->execute($cart);

        if (!$cart->getCustomerEmail()) {
            $cart->setCustomerEmail($this->dataHelper->getAnonymousOrderCustomerEmail());
        }
        $anonymousAddress = $this->dataHelper->getAnonymousAddress();
        $cart->setShippingAddress($anonymousAddress);
        $cart->setBillingAddress($anonymousAddress);

        return [$field, $context, $info, $value, $args];
    }
}
