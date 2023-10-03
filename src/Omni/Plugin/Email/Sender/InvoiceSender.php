<?php

namespace Ls\Omni\Plugin\Email\Sender;

use Magento\Sales\Model\Order\Invoice;

/**
 * Class InvoiceSender
 * @package Ls\Omni\Plugin\Email\Sender
 */
class InvoiceSender
{

    /**
     * @param $subject
     * @param Invoice $invoice
     * @param false $forceSyncMode
     * @return array
     */
    public function beforeSend($subject, Invoice $invoice, $forceSyncMode = false)
    {
        if (!empty($invoice->getOrder()->getDocumentId())) {
            $invoice->getOrder()->setIncrementId($invoice->getOrder()->getDocumentId());
        }
        return [$invoice, $forceSyncMode];
    }
}
