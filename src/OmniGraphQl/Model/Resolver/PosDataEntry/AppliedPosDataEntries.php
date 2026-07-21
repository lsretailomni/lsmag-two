<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Model\Resolver\PosDataEntry;

use \Ls\Omni\Model\GiftCard\GiftCardManagement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolve the list of applied gift card / voucher POS data entries for a cart.
 */
class AppliedPosDataEntries implements ResolverInterface
{
    /**
     * @param GiftCardManagement $giftCardManagement
     */
    public function __construct(
        public GiftCardManagement $giftCardManagement
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $cart    = $value['model'];
        $entries = $this->giftCardManagement->getEntries((int)$cart->getId());

        return !empty($entries) ? $entries : null;
    }
}
