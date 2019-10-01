<?php

namespace Ls\Omni\Model\Total\Quote;

use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Class PointsDiscount
 * @package Ls\Omni\Model\Total\Quote
 */
class PointsDiscount extends AbstractTotal
{

    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * PointsDiscount constructor.
     * @param LoyaltyHelper $loyaltyHelper
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper
    ) {
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $totals = [];
        $pointDiscount = $quote->getLsPointsSpent() * $this->loyaltyHelper->getPointRate();
        if ($pointDiscount > 0.001) {
            $totals[] = [
                'code' => $this->getCode(),
                'title' => __('Loyalty Points Redeemed'),
                'value' => $pointDiscount,
            ];
        }
        return $totals;
    }
}
