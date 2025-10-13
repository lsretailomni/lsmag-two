<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Order;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context;

class Totals extends AbstractBlock
{
    /**
     * @param Context $context
     * @param LoyaltyHelper $loyaltyHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        public LoyaltyHelper $loyaltyHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Initialize order totals array for email
     *
     * @return $this
     * @throws NoSuchEntityException|GuzzleException
     */
    public function initTotals()
    {
        $orderTotalsBlock = $this->getParentBlock();
        $order            = $orderTotalsBlock->getOrder();

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

        if ($order->getLsPointsSpent() > 0) {
            $loyaltyAmount = $this->loyaltyHelper->getLsPointsDiscount($order->getLsPointsSpent());
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

        return $this;
    }
}
