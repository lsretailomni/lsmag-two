<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\Helper\Data;

/**
 * To return gift card balance in graphql
 */
class GiftCardBalanceOutput implements ResolverInterface
{
    /**
     * @var GiftCardHelper
     */
    public $giftCardHelper;

    /**
     * @var Data
     */
    public $priceHelper;

    /**
     * Giftcard balance output constructor.
     *
     * @param GiftCardHelper $giftCardHelper
     * @param Data $priceHelper
     */

    public function __construct(
        GiftCardHelper $giftCardHelper,
        Data $priceHelper,
    ) {
        $this->giftCardHelper = $giftCardHelper;
        $this->priceHelper    = $priceHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['gift_card_no'])) {
            throw new GraphQlInputException(__('Required parameter "gift_card_no" is missing'));
        }

        $giftCardPin     = $args['gift_card_pin'];
        $giftCardBalance = '';
        $currency        = '';
        $pointRate = $storeCurrencyPointRate = $giftCardPointRate = 1;

        $response = $this->giftCardHelper->getGiftCardBalance($args['gift_card_no'], $giftCardPin);

        if (!empty($response)) {
            $convertedGiftCardBalanceArr = $this->giftCardHelper->getConvertedGiftCardBalance($response);
            return [
                'currency' => $convertedGiftCardBalanceArr['gift_card_currency'],
                'value'    => $this->priceHelper->currency($convertedGiftCardBalanceArr['gift_card_balance_amount'],true, false)
            ];
        } else {
            return [
                'error' => __('The gift card is not valid.')
            ];
        }
    }
}
