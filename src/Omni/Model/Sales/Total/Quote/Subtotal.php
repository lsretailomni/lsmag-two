<?php
declare(strict_types=1);

namespace Ls\Omni\Model\Sales\Total\Quote;

use GuzzleHttp\Exception\GuzzleException;
use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;

/**
 * Checking if price including tax or not
 */
class Subtotal
{
    /**
     * @param LSR $lsr
     */
    public function __construct(
        public LSR $lsr
    ) {
    }

    /**
     * Around plugin to check if price is including tax then no need for any further tax calculation
     *
     * @param $subject
     * @param $proceed
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     * @throws NoSuchEntityException|GuzzleException
     */
    public function aroundCollect(
        $subject,
        $proceed,
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        if (!$this->lsr->isLSR(
            $this->lsr->getCurrentStoreId(),
            false,
            $this->lsr->getBasketIntegrationOnFrontend()
        )) {
            return $proceed($quote, $shippingAssignment, $total);
        }

        return $this;
    }
}
