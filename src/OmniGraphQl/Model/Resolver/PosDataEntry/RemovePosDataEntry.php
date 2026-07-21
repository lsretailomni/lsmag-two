<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Model\Resolver\PosDataEntry;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Remove a specific gift card or voucher POS data entry from the cart.
 */
class RemovePosDataEntry extends AbstractPosDataEntry
{
    /**
     * @inheritdoc
     */
    protected function handleArgs(array $args, $context): array
    {
        try {
            $maskedCartId = $args['input']['cart_id'];
            $storeId      = (int)$context->getExtensionAttributes()->getStore()->getId();
            $cart         = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);

            $result = $this->giftCardManagement->removeEntry(
                (int)$cart->getId(),
                (string)$args['input']['entry_type'],
                (string)$args['input']['code']
            );

            if ($result) {
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
