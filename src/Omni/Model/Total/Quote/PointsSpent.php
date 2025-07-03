<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Total\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class PointsSpent extends AbstractTotal
{
    /**
     * For fetching point spent from quote
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $totals = [];
        $spent  = $quote->getLsPointsSpent();
        if ($spent > 0) {
            $totals[] = [
                'code'  => $this->getCode(),
                'title' => __('You are using'),
                'value' => $spent,
            ];
        }
        return $totals;
    }
}
