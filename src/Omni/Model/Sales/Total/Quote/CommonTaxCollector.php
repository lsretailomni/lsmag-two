<?php

namespace Ls\Omni\Model\Sales\Total\Quote;

use Magento\Tax\Model\Config;
use Magento\Tax\Model\Sales\Total\Quote\Subtotal;

/**
 * Class CommonTaxCollector
 * @package Ls\Omni\Model\Sales\Total\Quote
 */
class CommonTaxCollector
{
    /** @var Config */
    public $config;

    /**
     * CommonTaxCollector constructor.
     * @param Config $taxConfig
     */

    public function __construct(
        Config $taxConfig
    ) {
        $this->config = $taxConfig;
    }

    /**
     * @param Subtotal $subject
     * @param callable $proceed
     * @param $quoteItem
     * @param $itemTaxDetails
     * @param $baseItemTaxDetails
     * @param $store
     * @return $this
     */

    public function aroundUpdateItemTaxInfo(
        Subtotal $subject,
        callable $proceed,
        $quoteItem,
        $itemTaxDetails,
        $baseItemTaxDetails,
        $store
    ) {
        $quoteItem->setPrice($baseItemTaxDetails->getPrice());
        $quoteItem->setConvertedPrice($itemTaxDetails->getPrice());
        $quoteItem->setPriceInclTax($itemTaxDetails->getPriceInclTax());
        $quoteItem->setRowTotal($itemTaxDetails->getRowTotal());
        $quoteItem->setRowTotalInclTax($itemTaxDetails->getRowTotalInclTax());
        $quoteItem->setTaxAmount($itemTaxDetails->getRowTax());
        $quoteItem->setTaxPercent($itemTaxDetails->getTaxPercent());
        $quoteItem->setDiscountTaxCompensationAmount($itemTaxDetails->getDiscountTaxCompensationAmount());

        $quoteItem->setBasePrice($baseItemTaxDetails->getPrice());
        $quoteItem->setBasePriceInclTax($baseItemTaxDetails->getPriceInclTax());
        $quoteItem->setBaseRowTotal($baseItemTaxDetails->getRowTotal());
        $quoteItem->setBaseRowTotalInclTax($baseItemTaxDetails->getRowTotalInclTax());
        $quoteItem->setBaseTaxAmount($baseItemTaxDetails->getRowTax());
        $quoteItem->setTaxPercent($baseItemTaxDetails->getTaxPercent());
        $quoteItem->setBaseDiscountTaxCompensationAmount($baseItemTaxDetails->getDiscountTaxCompensationAmount());

        //Set discount calculation price, this may be needed by discount collector
        if ($this->config->discountTax($store)) {
            $quoteItem->setDiscountCalculationPrice($itemTaxDetails->getPriceInclTax());
            $quoteItem->setBaseDiscountCalculationPrice($baseItemTaxDetails->getPriceInclTax());
        } else {
            $quoteItem->setDiscountCalculationPrice($itemTaxDetails->getPrice());
            $quoteItem->setBaseDiscountCalculationPrice($baseItemTaxDetails->getPrice());
        }

        return $this;
    }
}
