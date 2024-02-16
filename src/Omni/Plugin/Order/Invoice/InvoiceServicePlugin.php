<?php

namespace Ls\Omni\Plugin\Order\Invoice;

use \Ls\Core\Model\LSR;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Class for fixing invoice total and grand total
 */
class InvoiceServicePlugin
{
    /**
     * @var LSR
     */
    private $lsr;

    /**
     * @param LSR $lsr
     */
    public function __construct(
        LSR $lsr
    ) {
        $this->lsr = $lsr;
    }

    /**
     * After plugin to fix invoice total and grand total
     *
     * @param $subject
     * @param $order
     * @param $invoice
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterPrepareInvoice($subject, $invoice, $order)
    {
        if ($this->lsr->isLSR($invoice->getStoreId())) {
            $grandTotal = $order->getGrandTotal();
            $baseGrandTotal = $order->getBaseGrandTotal();
            $invoice->setGrandTotal($grandTotal);
            $invoice->setBaseGrandTotal($baseGrandTotal);
        }

        return $invoice;
    }
}
