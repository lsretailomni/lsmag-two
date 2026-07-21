<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Model\Resolver\PosDataEntry;

use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\VoucherHelper;
use \Ls\Omni\Model\GiftCard\GiftCardManagement;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * Base resolver for gift card / voucher (POS data entry) mutations.
 *
 * Enablement is satisfied when either gift card or voucher redemption is enabled, since both
 * gift cards and vouchers travel through the same unified ls_pos_data_entries contract.
 */
abstract class AbstractPosDataEntry implements ResolverInterface
{
    /**
     * @param GiftCardHelper $giftCardHelper
     * @param VoucherHelper $voucherHelper
     * @param GiftCardManagement $giftCardManagement
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        public GiftCardHelper $giftCardHelper,
        public VoucherHelper $voucherHelper,
        public GiftCardManagement $giftCardManagement,
        public GetCartForUser $getCartForUser
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (!$this->giftCardHelper->isGiftCardEnabled('cart') && !$this->voucherHelper->isVoucherEnabled('cart')) {
            throw new GraphQlInputException(__('The module is not enabled'));
        }

        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        return $this->handleArgs($args, $context);
    }

    /**
     * Handle the mutation for a POS data entry.
     *
     * @param array $args
     * @param mixed $context
     * @return array
     * @throws GraphQlInputException
     */
    abstract protected function handleArgs(array $args, $context): array;
}
