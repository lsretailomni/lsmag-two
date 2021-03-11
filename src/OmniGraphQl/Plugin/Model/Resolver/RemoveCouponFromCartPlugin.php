<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use \Ls\Omni\Exception\InvalidEnumException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * For removing coupon from cart
 */
class RemoveCouponFromCartPlugin
{

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * RemoveCouponFromCartPlugin constructor.
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        GetCartForUser $getCartForUser
    ) {
        $this->getCartForUser = $getCartForUser;
    }

    /**
     * @param $subject
     * @param $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     * @throws InvalidEnumException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterResolve(
        $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (isset($result['cart']) && isset($result['cart']['model'])) {
            $maskedCartId             = $args['input']['cart_id'];
            $currentUserId            = $context->getUserId();
            $storeId                  = (int)$context->getExtensionAttributes()->getStore()->getId();
            $cart                     = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);
            $result['cart'] ['model'] = $cart;
        }
        return $result;
    }
}
