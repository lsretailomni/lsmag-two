<?php

namespace Ls\Omni\Plugin\Cart\Model;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Checkout\Block\Cart\CartTotalsProcessor;
use Magento\Framework\Exception\NoSuchEntityException;

class CartTotalsProcessorPlugin
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @param LoyaltyHelper $loyaltyHelper
     * @param LSR $lsr
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper,
        LSR $lsr
    ) {
        $this->loyaltyHelper = $loyaltyHelper;
        $this->lsr           = $lsr;
    }

    /**
     * After plugin to update the layout processor of cart page
     *
     * @param CartTotalsProcessor $subject
     * @param array $jsLayout
     * @return array
     * @throws NoSuchEntityException
     */
    public function afterProcess(
        CartTotalsProcessor $subject,
        array $jsLayout
    ) {
        if (!$this->lsr->isEnabled()) {
            $totalsBlock = &$jsLayout['components']['block-totals'];
            unset($totalsBlock['children']['loyalty_points_label']);
            unset($totalsBlock['children']['before_grandtotal']['children']['ls_points_discount']);
            unset($totalsBlock['children']['before_grandtotal']['children']['ls_gift_card_amount_used']);
        }

        if ($this->loyaltyHelper->isLoyaltyPointsEnabled('cart') == '0') {
            unset($jsLayout['components']['block-totals']['children']['loyalty_points_label']);
        }

        return $jsLayout;
    }
}
