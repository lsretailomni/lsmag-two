<?php

namespace Ls\OmniGraphQl\Model\Resolver\LoyaltyPoints;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Class RemoveLoyaltyPoints for removing Loyalty Points
 */
class RemoveLoyaltyPoints extends AbstractLoyaltyPoints
{
    /**
     * @inheritdoc
     */
    protected function handleArgs(array $args, $context)
    {
        try {
            $maskedCartId = $args['input']['cart_id'];
            $storeId      = (int)$context->getExtensionAttributes()->getStore()->getId();
            $cart         = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
            $cartId       = $cart->getId();
            $result       = $this->loyaltyPointsManagement->remove($cartId);
            if ($result == true) {
                $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
                $cart    = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
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
