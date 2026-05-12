<?php
declare(strict_types=1);

namespace Ls\Omni\Plugin\Email\Sender;

use Magento\Sales\Model\Order\Invoice;

class InvoiceSender
{
    /**
     * @param $subject
     * @param $proceed
     * @param Invoice $invoice
     * @param $forceSyncMode
     * @return mixed
     */
    public function aroundSend($subject, $proceed, Invoice $invoice, $forceSyncMode = false)
    {
        $incrementId = $invoice->getOrder()->getIncrementId();
        if (!empty($invoice->getOrder()->getDocumentId())) {
            $invoice->getOrder()->setIncrementId($invoice->getOrder()->getDocumentId());
        }
        $result = $proceed($invoice, $forceSyncMode);
        $invoice->getOrder()->setIncrementId($incrementId);
        return $result;
    }
}
