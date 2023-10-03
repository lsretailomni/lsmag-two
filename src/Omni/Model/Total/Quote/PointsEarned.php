<?php

namespace Ls\Omni\Model\Total\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Class PointsEarned
 * @package Ls\Omni\Model\Total\Quote
 */
class PointsEarned extends AbstractTotal
{
    /**
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        if ($quote->getLsPointsEarn() > 0) {
            $title = $quote->getCustomerId() ? __('You will earn') : __('Login to earn');

            return [
                'code'  => $this->getCode(),
                'title' => $title,
                'value' => $quote->getLsPointsEarn()
            ];
        }
        return [];
    }
}
