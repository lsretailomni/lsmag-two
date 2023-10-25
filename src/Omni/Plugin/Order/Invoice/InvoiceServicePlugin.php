<?php

namespace Ls\Omni\Plugin\Order\Invoice;

use \Ls\Core\Model\LSR;

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
    public function __construct(LSR $lsr)
    {
        $this->lsr = $lsr;
    }


    /**
     * After plugin to fix invoice total and grand total
     *
     * @param $subject
     * @param $order
     * @param $invoice
     * @return mixed
     */
    public function afterPrepareInvoice($subject, $invoice, $order)
    {
        if ($this->lsr->isLSR($invoice->getStoreId())) {
            $invoice->setGrandTotal($invoice->getGrandTotal() - $invoice->getTaxAmount());
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $invoice->getBaseTaxAmount());
        }

        return $invoice;
    }
}
