<?php

namespace Ls\Omni\Model\Total\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Class PointsSpent
 * @package Ls\Omni\Model\Total\Quote
 */
class GiftCardNo extends AbstractTotal
{
    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $totals         = [];
        $giftCardNumber = $quote->getLsGiftCardNo();
        if (!empty($giftCardNumber)) {
            $totals[] = [
                'code'  => $this->getCode(),
                'title' => __('Gift Card No'),
                'value' => $giftCardNumber,
            ];
        }
        return $totals;
    }
}
