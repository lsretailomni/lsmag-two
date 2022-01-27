<?php

namespace Ls\OmniGraphQl\Plugin\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\TotalsCollector;

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
     * @param TotalsCollector $totalsCollector
     */
    public function __construct(
        TotalsCollector $totalsCollector
    ) {
        $this->totalsCollector = $totalsCollector;
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
        $cartTotals    = $this->totalsCollector->collectQuoteTotals($quote);
        $currency      = $quote->getQuoteCurrencyCode();
        $result['vat'] = $this->getVat($cartTotals, $currency);

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
            'amount' => ['value' => $total->getTaxAmount(), 'currency' => $currency]
        ];
    }
}
