<?php

namespace Ls\Omni\Model\Sales\Total\Quote;

use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var LSR
     */
    private $lsr;

    /**
     * @param Config $taxConfig
     * @param LSR $lsr
     */
    public function __construct(
        Config $taxConfig,
        LSR $lsr
    ) {
        $this->config = $taxConfig;
        $this->lsr    = $lsr;
    }

    /**
     * Around plugin to check if price is including tax then no need for any further tax calculation
     * @param $subject
     * @param $proceed
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     * @throws NoSuchEntityException
     */
    public function aroundCollect(
        $subject,
        $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        if (!$this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            return $proceed($quote, $shippingAssignment, $total);
        }

        return $this;
    }
}
