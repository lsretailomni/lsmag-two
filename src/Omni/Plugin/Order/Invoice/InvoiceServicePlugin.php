<?php

namespace Ls\Omni\Plugin\Order\Invoice;

/**
 * Class for fixing invoice total and grand total
 */
class InvoiceServicePlugin
{
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
        $invoice->setGrandTotal($order->getGrandTotal());
        $invoice->setBaseGrandTotal($order->getBaseGrandTotal());

        return $invoice;
    }
}
