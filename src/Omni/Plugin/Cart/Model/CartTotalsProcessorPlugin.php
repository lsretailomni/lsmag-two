<?php

namespace Ls\Omni\Plugin\Cart\Model;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Block\Cart\CartTotalsProcessor;

/**
 * Class CartTotalsProcessorPlugin
 * @package Ls\Omni\Plugin\Cart\Model
 */
class CartTotalsProcessorPlugin
{
    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * CartTotalsProcessorPlugin constructor.
     * @param LoyaltyHelper $loyaltyHelper
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper
    ) {
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * @param CartTotalsProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(
        CartTotalsProcessor $subject,
        array $jsLayout
    ) {
        if ($this->loyaltyHelper->isLoyaltyPointsEnabled('cart') == '0') {
            unset($jsLayout['components']['block-totals']['children']['loyalty_points_label']);
        }
        return $jsLayout;
    }
}