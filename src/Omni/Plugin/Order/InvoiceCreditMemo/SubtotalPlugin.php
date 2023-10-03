<?php

namespace Ls\Omni\Plugin\Order\InvoiceCreditMemo;

/**
 * Class for subtotal for fixing subtotal in invoice and credit memo
 */
class SubtotalPlugin
{
    /**
     * After plugin to fix subtotal in invoice and credit memo
     *
     * @param $subject
     * @param $subtotalClass
     * @param $invoiceCreditMemo
     * @return mixed
     */
    public function afterCollect($subject, $subtotalClass, $invoiceCreditMemo)
    {
        $subtotal            = 0;
        $baseSubTotal        = 0;

        foreach ($invoiceCreditMemo->getAllItems() as $item) {
            $orderItem      = $invoiceCreditMemo->getOrder()->getItemById($item->getOrderItemId());
            $discountAmount = ($orderItem->getDiscountAmount() / $orderItem->getQtyOrdered()) * $item->getQty();
            $taxAmount           = ($orderItem->getTaxAmount() / $orderItem->getQtyOrdered()) * $item->getQty();
            $subtotal            += ($item->getRowTotal() + $discountAmount) - $taxAmount;
            $baseSubTotal        += ($item->getBaseRowTotal()) - $taxAmount;
        }

        $invoiceCreditMemo->setSubtotal($subtotal);
        $invoiceCreditMemo->setBaseSubtotal($baseSubTotal);
        $invoiceCreditMemo->setGrandTotal($subtotal);
        $invoiceCreditMemo->setBaseGrandTotal($baseSubTotal);

        return $invoiceCreditMemo;
    }
}
