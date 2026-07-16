<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Model\Resolver\PosDataEntry;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Apply (append) a gift card or voucher POS data entry to the cart.
 */
class ApplyPosDataEntry extends AbstractPosDataEntry
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
            $entryType    = $args['input']['entry_type'] ?? null;

            $result = $this->giftCardManagement->applyEntry(
                (int)$cart->getId(),
                $entryType !== null ? (string)$entryType : null,
                (string)$args['input']['code'],
                isset($args['input']['pin']) ? (string)$args['input']['pin'] : null,
                (float)$args['input']['amount']
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
