<?php

namespace Ls\Omni\Model\Total\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Class PointsSpent
 * @package Ls\Omni\Model\Total\Quote
 */
class GiftCardAmountUsed extends AbstractTotal
{
    /**
     * For fetching git card amount from quote
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $totals = [];
        $amount = $quote->getLsGiftCardAmountUsed();
        if ($amount > 0) {
            $totals[] = [
                'code'  => $this->getCode(),
                'title' => __('Gift Card Redeemed'),
                'value' => $amount,
            ];
        }
        return $totals;
    }
}
