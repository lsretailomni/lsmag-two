<?php

namespace Ls\OmniGraphQl\Model\Resolver;

use \Ls\Omni\Helper\GiftCardHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

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
     * @param GiftCardHelper $giftCardHelper
     */
    public function __construct(
        GiftCardHelper $giftCardHelper
    ) {
        $this->giftCardHelper = $giftCardHelper;
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

        $response = $this->giftCardHelper->getGiftCardBalance($args['gift_card_no'], $giftCardPin);
        if ($response) {
            $giftCardBalance = $response->getBalance();
            $currency        = $response->getCurrencyCode();
        }

        if (empty($response)) {
            return [
                'error' => __('The gift card is not valid.')
            ];
        }

        return [
            'currency' => $currency,
            'value'    => $giftCardBalance,
        ];
    }
}
