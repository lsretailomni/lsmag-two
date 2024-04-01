<?php
namespace Ls\Omni\Plugin\Block\Adminhtml\Order\Create\Totals;

use Magento\Directory\Model\Currency;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Block\Adminhtml\Order\Create\Totals\DefaultTotals;

class DefaultTotalsPlugin
{
    /**
     * @var Currency
     */
    public $currency;

    /**
     * @param Currency $currency
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * @param DefaultTotals $subject
     * @param $result
     * @param $value
     * @return string
     */
    public function afterFormatPrice(DefaultTotals $subject, $result, $value)
    {
        if ($subject->getTotal()->getCode() != 'ls_points_earn') {
            return $result;
        }

        return $this->currency->format(
            $value,
            ['precision'=> PriceCurrencyInterface::DEFAULT_PRECISION,
             'display'=> \Magento\Framework\Currency::NO_SYMBOL],
            0
        ). ' '. __('points');
    }
}
