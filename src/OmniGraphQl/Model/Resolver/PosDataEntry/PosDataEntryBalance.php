<?php
declare(strict_types=1);

namespace Ls\OmniGraphQl\Model\Resolver\PosDataEntry;

use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\VoucherHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * To return the balance of a gift card or voucher POS data entry in graphql
 */
class PosDataEntryBalance implements ResolverInterface
{
    /**
     * @param GiftCardHelper $giftCardHelper
     * @param VoucherHelper $voucherHelper
     */
    public function __construct(
        public GiftCardHelper $giftCardHelper,
        public VoucherHelper $voucherHelper
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $code      = $args['code'] ?? null;
        $pin       = $args['pin'] ?? null;
        $entryType = $args['entry_type'] ?? null;

        if (empty($code)) {
            throw new GraphQlInputException(__('Required parameter "code" is missing'));
        }

        $code = (string) $code;
        $pin  = $pin === null ? null : (string) $pin;

        if (empty($entryType)) {
            // Try each configured entry type in turn; use the first one that returns a balance.
            $resolved  = $this->voucherHelper->resolveCode($code, $pin);
            $response  = $resolved['response'] ?? null;
            $entryType = $resolved['entry_type'] ?? null;
        } else {
            $entryType = (string) $entryType;
            $response  = $this->giftCardHelper->getGiftCardBalance($code, $pin, $entryType);
        }

        if (empty($response)) {
            return [
                'error' => __('The gift card / voucher code is not valid.')
            ];
        }

        if (!is_object($response)) {
            return [
                'value'      => $this->giftCardHelper->formatValue($response, true),
                'entry_type' => $entryType
            ];
        }

        $convertedBalance = $this->giftCardHelper->getConvertedGiftCardBalance($response);

        return [
            'currency'    => $convertedBalance['gift_card_currency'],
            'value'       => $this->giftCardHelper->formatValue(
                $convertedBalance['gift_card_balance_amount'],
                true
            ),
            'entry_type'  => $entryType,
            'expiry_date' => $this->giftCardHelper->formatExpireDate($response->getExpirydate())
        ];
    }
}
