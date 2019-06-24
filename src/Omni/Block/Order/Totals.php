<?php

namespace Ls\Omni\Block\Order;

use \Ls\Omni\Helper\LoyaltyHelper;

/**
 * Class Totals
 * @package Ls\Omni\Block\Order
 */
class Totals extends \Magento\Framework\View\Element\AbstractBlock
{

    /**
     * Totals constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        LoyaltyHelper $loyaltyHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        $orderTotalsBlock = $this->getParentBlock();
        $order = $orderTotalsBlock->getOrder();
        if ($order->getLsPointsSpent() > 0) {
            $loyaltyAmount = $order->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            // @codingStandardsIgnoreLine
            $loyaltyPoints = new \Magento\Framework\DataObject(
                [
                    'code' => 'ls_points_spent',
                    'value' => -$loyaltyAmount,
                    'label' => __('Loyalty Points Redeemed'),
                ]
            );
            $this->getParentBlock()->addTotalBefore($loyaltyPoints, 'discount');
        }
        if ($order->getLsGiftCardAmountUsed() > 0) {
            // @codingStandardsIgnoreLine
            $giftCardAmount = new \Magento\Framework\DataObject(
                [
                    'code' => 'ls_gift_card_amount_used',
                    'value' => -$order->getLsGiftCardAmountUsed(),
                    'label' => __('Gift Card Redeemed ') . '(' . $order->getLsGiftCardNo() . ')',
                ]
            );
            $this->getParentBlock()->addTotalBefore($giftCardAmount, 'discount');
        }

        return $this;
    }
}