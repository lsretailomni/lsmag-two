<?php

namespace Ls\OmniGraphQl\Model\Resolver\GiftCard;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Class ApplyGiftCard for applying gift card
 */
class ApplyGiftCard extends AbstractGiftCard
{
    /**
     * @param array $args
     * @param $context
     * @return mixed
     * @throws GraphQlInputException
     */
    protected function handleArgs(array $args, $context)
    {
        try {
            $maskedCartId   = $args['input']['cart_id'];
            $storeId        = (int)$context->getExtensionAttributes()->getStore()->getId();
            $cart           = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
            $cartId = $cart->getId();
            $result = $this->giftCardManagement->apply($cartId, $args['input']['code'], $args['input']['amount']);
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
