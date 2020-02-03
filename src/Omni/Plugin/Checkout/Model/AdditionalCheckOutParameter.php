<?php

namespace Ls_Omni\Plugin\Checkout\Model;

use Ls\Omni\Helper\GiftCardHelper;
use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class AdditionalCheckOutParameter
 * @package Ls_Omni\Plugin\Checkout\Model
 */
class AdditionalCheckOutParameter implements ConfigProviderInterface
{

    /** @var GiftCardHelper; */
    public $giftCardHelper;

    /**
     * AdditionalCheckOutParameter constructor.
     * @param GiftCardHelper $giftCardHelper
     */
    public function __construct(
        GiftCardHelper $giftCardHelper
    ) {
        $this->giftCardHelper = $giftCardHelper;
    }

    /**
     * @return array|mixed
     */
    public function getConfig()
    {
        $output['gift_card_enable'] = $this->giftCardHelper->isGiftCardEnableOnCheckOut();
        return $output;
    }
}
