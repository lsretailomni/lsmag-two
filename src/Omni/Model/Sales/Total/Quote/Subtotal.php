<?php

namespace Ls\Omni\Model\Sales\Total\Quote;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Tax\Model\Config;

/**
 * Checking if price including tax or not
 */
class Subtotal
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $taxConfig
     */
    public function __construct(
        Config $taxConfig
    ) {
        $this->config = $taxConfig;
    }

    /**
     * Around plugin to check if price is including tax then no need for any further tax calculation
     * @param \Magento\Tax\Model\Sales\Total\Quote\Subtotal $subject
     * @param $proceed
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function aroundCollect(
        \Magento\Tax\Model\Sales\Total\Quote\Subtotal $subject,
        $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        $store = $quote->getStore();
        $priceIncludesTax = $this->config->priceIncludesTax($store);
        if (!$priceIncludesTax) {
            return $proceed($quote, $shippingAssignment, $total);
        }
        return $this;
    }
}
