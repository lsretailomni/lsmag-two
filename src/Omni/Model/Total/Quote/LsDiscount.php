<?php

namespace Ls\Omni\Model\Total\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class LsDiscount extends AbstractTotal
{
    /**
     * For fetching discount after basket calculation
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $totals = [];
        $amount = $quote->getLsDiscountAmount();

        if (!empty($amount)) {
            $totals[] = [
                'code'  => $this->getCode(),
                'title' => __('Discount'),
                'value' => $amount,
            ];
        }

        return $totals;
    }
}
