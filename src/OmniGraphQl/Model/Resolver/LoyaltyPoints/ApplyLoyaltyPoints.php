<?php

namespace Ls\OmniGraphQl\Model\Resolver\LoyaltyPoints;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Class ApplyLoyaltyPoints for applying loyalty points
 */
class ApplyLoyaltyPoints extends AbstractLoyaltyPoints
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
            $result       = $this->loyaltyPointsManagement->apply(
                $cartId,
                $args['input']['loyalty_points']
            );
            if ($result == true) {
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
