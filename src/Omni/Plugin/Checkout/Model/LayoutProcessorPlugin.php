<?php

namespace Ls\Omni\Plugin\Checkout\Model;

use \Ls\Omni\Helper\Data;
use \Ls\Omni\Helper\GiftCardHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Block\Checkout\LayoutProcessor;

/**
 * Class LayoutProcessorPlugin
 * @package Ls\Omni\Plugin\Checkout\Model
 */
class LayoutProcessorPlugin
{
    /**
     * @var Data
     */
    public $data;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /** @var GiftCardHelper */
    public $giftCardHelper;

    /**
     * LayoutProcessor constructor.
     * @param Data $data
     * @param LoyaltyHelper $loyaltyHelper
     * @param GiftCardHelper $giftCardHelper
     */
    public function __construct(
        Data $data,
        LoyaltyHelper $loyaltyHelper,
        GiftCardHelper $giftCardHelper
    )
    {
        $this->data = $data;
        $this->loyaltyHelper          = $loyaltyHelper;
        $this->giftCardHelper         = $giftCardHelper;
    }
    /**
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        LayoutProcessor $subject,
        array $jsLayout
    ) {
        if ($this->data->isCouponsEnabled('checkout') == '0') {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['discount']);
        }
        if ($this->loyaltyHelper->isLoyaltyPointsEnabled('checkout') == '0') {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['loyalty-points']);
            unset($jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['totals']['children']['loyalty_points_label']);
        }
        if ($this->giftCardHelper->isGiftCardEnabled('checkout') == '0' ) {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['afterMethods']['children']['gift-card']);
        }
        return $jsLayout;
    }
}