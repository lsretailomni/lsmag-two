<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Total\Quote;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Omni\Helper\LoyaltyHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class PointsDiscount extends AbstractTotal
{
    /**
     * @var LoyaltyHelper
     */
    public $loyaltyHelper;

    /**
     * @param LoyaltyHelper $loyaltyHelper
     */
    public function __construct(
        LoyaltyHelper $loyaltyHelper
    ) {
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * For fetching ls point discount
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     * @throws NoSuchEntityException|GuzzleException
     */
    public function fetch(Quote $quote, Total $total)
    {
        $totals      = [];
        $pointsSpent = $quote->getLsPointsSpent();
        if ($pointsSpent > 0) {
            $pointDiscount = $this->loyaltyHelper->getLsPointsDiscount($pointsSpent);
            if ($pointDiscount > 0.001) {
                $totals[] = [
                    'code'  => $this->getCode(),
                    'title' => __('Loyalty Points Redeemed'),
                    'value' => $pointDiscount,
                ];
            }
        }
        return $totals;
    }
}
