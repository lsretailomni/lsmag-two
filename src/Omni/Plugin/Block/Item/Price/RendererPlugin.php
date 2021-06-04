<?php

namespace Ls\Omni\Plugin\Block\Item\Price;

use Magento\Sales\Model\Order\Creditmemo\Item as CreditMemoItem;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Weee\Block\Item\Price\Renderer;

/**
 * After plugin to fix value of row totals
 */
class RendererPlugin
{
    /**
     * Correcting the row total of items on admin panel
     *
     * @param Renderer $subject
     * @param mixed $result
     * @param OrderItem|InvoiceItem|CreditMemoItem $item $item
     * @return mixed
     */
    public function afterGetTotalAmount(Renderer $subject, $result, $item)
    {
        return $item->getRowTotal();
    }
}
