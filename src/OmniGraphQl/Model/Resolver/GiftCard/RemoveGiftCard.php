<?php

namespace Ls\OmniGraphQl\Model\Resolver\GiftCard;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Class RemoveGiftCard for removing gift card
 */
class RemoveGiftCard extends AbstractGiftCard
{
    /**
     * @inheritdoc
     */
    protected function handleArgs(array $args, $context)
    {
        try {
            $maskedCartId = $args['input']['cart_id'];
            $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
            $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
            $cartId = $cart->getId();
            $result = $this->giftCardManagement->remove($cartId);
            if ($result == true) {
                $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
                $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
            }
        } catch (\Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
        return [
            'cart' => [
                'model' => $cart,
            ],
        ];
    }
}
