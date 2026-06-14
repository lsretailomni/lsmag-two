<?php

namespace Ls\Omni\Block\Order\Custom;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template\Context;

class Totals extends AbstractBlock
{

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
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

        $allEntries = json_decode((string)$order->getLsPosDataEntries(), true) ?? [];

        $gcEntries = array_values(array_filter($allEntries, fn($e) => strtoupper($e['entry_type'] ?? '') === 'GIFTCARDNO'));
        $gcTotal   = (float)array_sum(array_column($gcEntries, 'amount'));
        if ($gcTotal > 0) {
            $gcCount = count($gcEntries);
            $gcTitle = $gcCount === 1
                ? __('%1 - %2 Redeemed', $gcEntries[0]['entry_type'] ?? 'Gift Card', $gcEntries[0]['entry_no'] ?? '')
                : __('Gift Cards Redeemed (%1)', $gcCount);
            // @codingStandardsIgnoreLine
            $giftCardAmount = new DataObject(
                [
                    'code'  => 'ls_gift_card_amount_used',
                    'value' => -$gcTotal,
                    'label' => $gcTitle,
                ]
            );
            $this->getParentBlock()->addTotalBefore($giftCardAmount, 'discount');
        }

        $voucherEntries     = array_values(array_filter($allEntries, fn($e) => strtoupper($e['entry_type'] ?? '') !== 'GIFTCARDNO'));
        $voucherAmountTotal = (float)array_sum(array_column($voucherEntries, 'amount'));
        if ($voucherAmountTotal > 0) {
            $vCount = count($voucherEntries);
            $vTitle = $vCount === 1
                ? __('%1 - %2 Redeemed', $voucherEntries[0]['entry_type'] ?? 'Voucher', $voucherEntries[0]['entry_no'] ?? '')
                : __('Vouchers Redeemed (%1)', $vCount);
            // @codingStandardsIgnoreLine
            $voucherAmount = new DataObject(
                [
                    'code'  => 'ls_entry_amount',
                    'value' => -$voucherAmountTotal,
                    'label' => $vTitle,
                ]
            );
            $this->getParentBlock()->addTotalBefore($voucherAmount, 'discount');
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
