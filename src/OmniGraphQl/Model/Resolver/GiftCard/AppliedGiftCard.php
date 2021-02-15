<?php

namespace Ls\OmniGraphQl\Model\Resolver\GiftCard;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use \Ls\Omni\Model\GiftCard\GiftCardManagement;

/**
 * Applied gift card data return in cart
 */
class AppliedGiftCard implements ResolverInterface
{
    /**
     * @var GiftCardManagement
     */
    private $giftCardManagement;

    /**
     * @param GiftCardManagement $giftCardManagement
     */
    public function __construct(
        GiftCardManagement $giftCardManagement
    ) {
        $this->giftCardManagement = $giftCardManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        $cart     = $value['model'];
        $cartId   = $cart->getId();
        $giftCard = $this->giftCardManagement->get($cartId);
        return !empty($giftCard) ? $giftCard : null;
    }
}
