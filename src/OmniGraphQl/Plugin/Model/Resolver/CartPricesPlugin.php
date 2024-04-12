<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use Ls\Omni\Helper\BasketHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Tests\NamingConvention\true\object;

/**
 * Interceptor to return vat value of the whole cart
 */
class CartPricesPlugin
{

    /**
     * @var TotalsCollector
     */
    private $totalsCollector;
    /**
     * @var BasketHelper
     */
    private BasketHelper $basketHelper;

    /**
     * @param TotalsCollector $totalsCollector
     * @param BasketHelper $basketHelper
     */
    public function __construct(
        TotalsCollector $totalsCollector,
        BasketHelper $basketHelper,
    ) {
        $this->totalsCollector = $totalsCollector;
        $this->basketHelper    = $basketHelper;
    }

    /**
     * After plugin to add vat amount in the result
     *
     * @param $subject
     * @param $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     */
    public function afterResolve(
        $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var Quote $quote */
        $quote = $value['model'];
        $quote->setCartFixedRules([]);
        $cartTotals           = $this->totalsCollector->collectQuoteTotals($quote);
        $currency             = $quote->getQuoteCurrencyCode();
        $result['lstax']      = $this->getVat($cartTotals, $currency);
        $result['lsdiscount'] = $this->getDiscounts($quote, $cartTotals, $currency);

        return $result;
    }

    /**
     * Returns information related to tax from total
     *
     * @param Total $total
     * @param string $currency
     * @return array|null
     */
    private function getVat(Total $total, string $currency)
    {
        if ($total->getTaxAmount() === 0) {
            return null;
        }
        return [
            'amount' => ['value' => $total->getTaxAmount(), 'currency' => $currency],
            'label'  => __('Tax')
        ];
    }

    /**
     *  Returns information related to discounts from total
     *
     * @param object $quote
     * @param Total $total
     * @param string $currency
     * @return array|null
     */
    private function getDiscounts($quote, Total $total, string $currency)
    {
        $totalDiscounts = $this->getTotalDiscount($quote);
        if ($totalDiscounts === 0) {
            return null;
        }
        return [
            'amount' => ['value' => $totalDiscounts, 'currency' => $currency],
            'label'  => __('Discount')
        ];
    }

    /**
     * Calculate total discounts in quote based on basket calculation response
     *
     * @param object $quote
     * @return float|int
     */
    public function getTotalDiscount($quote)
    {
        $amount     = 0;
        $basketData = $this->basketHelper->getBasketSessionValue();
        if (!empty($basketData)) {
            $amount = -$basketData->getTotalDiscount();
        }
        return $amount;
    }
}
