<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ls\Omni\Model;
use Ls\Omni\Helper\BasketHelper;

class Subtotal extends \Magento\Quote\Model\Quote\Address\Total\Subtotal
{
    protected $basketHelper;
    public function __construct(\Magento\Quote\Model\QuoteValidator $quoteValidator, BasketHelper $basketHelper)
    {
        parent::__construct($quoteValidator);
        $this->basketHelper = $basketHelper;
    }

    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        // parent::collect($quote, $shippingAssignment, $total);
        $total->setTotalQty(0);

        $baseVirtualAmount = $virtualAmount = 0;

        /**
         * Process address items
         */
        $basketCalc = $this->basketHelper->getOneListCalculation();
        $lines = $basketCalc->getBasketLineCalcResponses();
        foreach ($lines as $line) {
            // TODO: maybe implement base currency with $line->getCurrencyFactor()
            // (base)GrandTotal is after tax, (base)SubTotal is before tax
            // (sub/grand)Total is in customer currency, base(Sub/Grand)total in shop currency
            // subtotal: customer currency, before tax
            // baseSubtotal: shop currency, before tax

            // TODO: also check against stored price. Maybe send a message to customer when Magento price doesn't match
            // the Omni price?

            $baseVirtualAmount += $line->getNetPrice();
            $virtualAmount = $baseVirtualAmount;
        }

        // in the end, we should come out to the same value
        $totalAmount = $basketCalc->getTotalNetAmount();
        if ($totalAmount != $baseVirtualAmount or $totalAmount != $virtualAmount) {
            // something went wrong. Discounts?
            throw new Exception("Error during Quote Calculation.");
        }

        $total->setBaseVirtualAmount($baseVirtualAmount);
        $total->setVirtualAmount($virtualAmount);

        /**
         * Initialize grand totals
         */
        $this->quoteValidator->validateQuoteAmount($quote, $total->getSubtotal());
        $this->quoteValidator->validateQuoteAmount($quote, $total->getBaseSubtotal());
        $address = $shippingAssignment->getShipping()->getAddress();
        $address->setSubtotal($total->getSubtotal());
        $address->setBaseSubtotal($total->getBaseSubtotal());
        return $this;
    }
}