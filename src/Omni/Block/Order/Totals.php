<?php

namespace Ls\Omni\Block\Order;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Totals
 * @package Ls\Omni\Block\Order
 */
class Totals extends AbstractBlock
{

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * Totals constructor.
     * @param Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        LoyaltyHelper $loyaltyHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * Initialize order totals array for email
     *
     * @return $this
     * @throws NoSuchEntityException
     */
    public function initTotals()
    {
        $orderTotalsBlock = $this->getParentBlock();
        $order            = $orderTotalsBlock->getOrder();

        if ($order->getLsDiscountAmount() > 0) {
            $lsDiscountAmount = $order->getLsDiscountAmount();
            // @codingStandardsIgnoreLine
            $lsDiscounts = new DataObject(
                [
                    'code'  => 'ls_discount_amount',
                    'value' => -$lsDiscountAmount,
                    'label' => __('Discount'),
                ]
            );
            $this->getParentBlock()->addTotalBefore($lsDiscounts, 'discount');
        }

        if ($order->getLsPointsSpent() > 0) {
            $loyaltyAmount = $order->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
            // @codingStandardsIgnoreLine
            $loyaltyPoints = new DataObject(
                [
                    'code'  => 'ls_points_spent',
                    'value' => -$loyaltyAmount,
                    'label' => __('Loyalty Points Redeemed'),
                ]
            );
            $this->getParentBlock()->addTotalBefore($loyaltyPoints, 'discount');
        }
        if ($order->getLsGiftCardAmountUsed() > 0) {
            // @codingStandardsIgnoreLine
            $giftCardAmount = new DataObject(
                [
                    'code'  => 'ls_gift_card_amount_used',
                    'value' => -$order->getLsGiftCardAmountUsed(),
                    'label' => __('Gift Card Redeemed ') . '(' . $order->getLsGiftCardNo() . ')',
                ]
            );
            $this->getParentBlock()->addTotalBefore($giftCardAmount, 'discount');
        }

        return $this;
    }
}
