<?php

namespace Ls\Omni\Model\Total\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Class PointsSpent
 * @package Ls\Omni\Model\Total\Quote
 */
class PointsSpent extends AbstractTotal
{
    /**
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $totals = [];
        $spent = $quote->getLsPointsSpent();
        if ($spent > 0.001) {
            $totals[] = [
                'code' => $this->getCode(),
                'title' => __('You are using'),
                'value' => $spent,
            ];
        }
        return $totals;
    }
}
